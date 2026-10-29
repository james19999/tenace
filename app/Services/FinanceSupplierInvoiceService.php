<?php

namespace App\Services;

use App\Models\FinanceSupplierInvoice;
use App\Models\FinanceSupplierPayment;
use App\Models\StockReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceSupplierInvoiceService
{
    public function __construct(
        private readonly FinanceCashLedgerService $ledger,
        private readonly FinanceAuditService $audit,
    ) {
    }

    public function register(array $data, User $actor): FinanceSupplierInvoice
    {
        $supplier = trim($data['supplier_name']);
        $supplierInvoiceNumber = trim((string) ($data['supplier_invoice_number'] ?? ''));
        $receipt = ! empty($data['stock_receipt_id']) ? StockReceipt::query()->with('lines')->findOrFail($data['stock_receipt_id']) : null;
        if ($receipt && ! in_array($receipt->status, ['partial', 'validated'], true)) {
            throw ValidationException::withMessages(['stock_receipt_id' => 'La facture peut être liée à une réception ayant déjà au moins une livraison approuvée.']);
        }
        if ($receipt && $receipt->origin_name && mb_strtolower(trim($receipt->origin_name)) !== mb_strtolower($supplier)) {
            throw ValidationException::withMessages(['supplier_name' => 'Le fournisseur ne correspond pas à l’origine inscrite sur la réception sélectionnée.']);
        }

        return DB::transaction(function () use ($data, $actor, $supplier, $supplierInvoiceNumber, $receipt): FinanceSupplierInvoice {
            if ($supplierInvoiceNumber !== '' && FinanceSupplierInvoice::query()
                ->where('supplier_name', $supplier)->where('supplier_invoice_number', $supplierInvoiceNumber)->exists()) {
                throw ValidationException::withMessages(['supplier_invoice_number' => 'Cette référence existe déjà pour ce fournisseur. Vérifie la facture avant de continuer.']);
            }
            if ($receipt) {
                StockReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
                $alreadyInvoiced = FinanceSupplierInvoice::query()->where('stock_receipt_id', $receipt->id)
                    ->whereIn('status', ['awaiting_review', 'approved'])->exists();
                if ($alreadyInvoiced) {
                    throw ValidationException::withMessages(['stock_receipt_id' => 'Cette réception est déjà liée à une facture en cours de contrôle ou validée.']);
                }
            }

            $invoice = FinanceSupplierInvoice::query()->create([
                'supplier_name' => $supplier,
                'invoice_number' => 'TMP-'.\Illuminate\Support\Str::uuid(),
                'supplier_invoice_number' => $supplierInvoiceNumber !== '' ? $supplierInvoiceNumber : null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'amount' => (int) $data['amount'],
                'stock_receipt_id' => $receipt?->id,
                'status' => 'awaiting_review',
                'payment_status' => 'unpaid',
                'invoice_path' => $data['invoice_path'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);
            $invoice->invoice_number = sprintf('TEN-FAC-%s-%06d', now()->format('Y'), $invoice->id);
            $invoice->save();
            $this->audit->record($invoice, 'supplier_invoice_registered', null, $invoice->toArray());

            return $invoice->fresh();
        });
    }

    public function review(FinanceSupplierInvoice $invoice, User $reviewer, string $decision, ?string $comment): FinanceSupplierInvoice
    {
        abort_unless($reviewer->isAdmin() || $reviewer->isAccountant(), 403);
        abort_if((int) $invoice->created_by === (int) $reviewer->id, 403, 'La personne qui a enregistré la facture ne peut pas la contrôler.');
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        if ($decision === 'rejected' && ! trim((string) $comment)) {
            throw ValidationException::withMessages(['comment' => 'Indique le motif du rejet.']);
        }

        return DB::transaction(function () use ($invoice, $reviewer, $decision, $comment): FinanceSupplierInvoice {
            $locked = FinanceSupplierInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            abort_unless($locked->status === 'awaiting_review', 409);
            $locked->loadMissing('receipt.lines');
            if ($decision === 'approved' && $locked->receipt) {
                $receivedValue = $locked->receipt->lines->sum(fn ($line) => (float) $line->quantity_received * (float) $line->unit_cost);
                if (abs($receivedValue - (int) $locked->amount) >= 1 && ! trim((string) $comment)) {
                    throw ValidationException::withMessages(['comment' => 'L’écart entre la facture et la valeur des marchandises reçues doit être expliqué avant validation.']);
                }
            }
            $before = $locked->only(['status', 'reviewed_by', 'reviewed_at', 'review_comment']);
            $locked->status = $decision;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_comment = $comment;
            $locked->save();
            $this->audit->record($locked, 'supplier_invoice_'.$decision, $before, $locked->only(['status', 'reviewed_by', 'reviewed_at', 'review_comment']), $comment);

            return $locked->fresh();
        });
    }

    public function pay(FinanceSupplierInvoice $invoice, User $payer, int $amount, ?string $reference, string $proofPath): FinanceSupplierPayment
    {
        abort_unless($payer->isAdmin() || $payer->isAccountant() || $payer->hasRole(User::ROLE_CASHIER), 403);

        return DB::transaction(function () use ($invoice, $payer, $amount, $reference, $proofPath): FinanceSupplierPayment {
            $locked = FinanceSupplierInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            abort_unless($locked->status === 'approved' && in_array($locked->payment_status, ['unpaid', 'partial'], true), 409);
            if ($amount <= 0 || (int) $locked->paid_amount + $amount > (int) $locked->amount) {
                throw ValidationException::withMessages(['amount' => 'Le règlement dépasse le solde de la facture.']);
            }
            $payment = FinanceSupplierPayment::query()->create([
                'supplier_invoice_id' => $locked->id, 'amount' => $amount,
                'payment_reference' => $reference, 'proof_path' => $proofPath,
                'paid_by' => $payer->id, 'paid_at' => now(),
            ]);
            $this->ledger->record([
                'movement_type' => 'supplier_payment', 'from_custody' => 'general_cash', 'to_custody' => 'external',
                'amount' => $amount, 'source_type' => 'supplier_payment', 'source_id' => $payment->id,
                'source_key' => 'supplier-payment:'.$payment->id,
                'source_reference' => $locked->supplier_name.' · '.$locked->invoice_number,
                'occurred_at' => now(), 'description' => 'Règlement fournisseur de la facture '.$locked->invoice_number.'.',
                'created_by' => $payer->id,
            ]);
            $locked->paid_amount = (int) $locked->paid_amount + $amount;
            $locked->payment_status = $locked->paid_amount === (int) $locked->amount ? 'paid' : 'partial';
            $locked->save();
            $this->audit->record($locked, 'supplier_invoice_payment_recorded', null, [
                'payment_id' => $payment->id, 'amount' => $amount, 'paid_amount' => $locked->paid_amount,
            ]);

            return $payment;
        });
    }
}
