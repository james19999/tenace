<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSupplierInvoice;
use App\Models\FinanceGeneralCashSetting;
use App\Models\StockReceipt;
use App\Models\User;
use App\Services\FinanceSupplierInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceSupplierInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAssignedCashier = $this->isAssignedCashier($user);
        abort_unless($user->isAdmin() || $isAssignedCashier, 403);
        $query = FinanceSupplierInvoice::query()->with(['creator', 'reviewer'])
            ->when($isAssignedCashier, fn ($query) => $query->where('status', 'approved'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($scope) => $scope
                ->where('supplier_name', 'like', '%'.$request->string('search').'%')
                ->orWhere('invoice_number', 'like', '%'.$request->string('search').'%')
                ->orWhere('supplier_invoice_number', 'like', '%'.$request->string('search').'%')))
            ->latest('invoice_date')->latest('id');

        return view('finance.suppliers.index', ['invoices' => $query->paginate(15)->withQueryString(), 'isAssignedCashier' => $isAssignedCashier]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isAccountant() || $request->user()->isManager(), 403);
        $receipts = StockReceipt::query()->with(['location:id,name', 'lines:id,receipt_id,quantity_received,unit_cost'])
            ->whereIn('status', ['partial', 'validated'])
            ->whereNotIn('id', FinanceSupplierInvoice::query()->select('stock_receipt_id')->whereNotNull('stock_receipt_id')->whereIn('status', ['awaiting_review', 'approved']))
            ->latest('occurred_at')->limit(200)
            ->get(['id', 'receipt_number', 'origin_name', 'location_id', 'status', 'occurred_at']);
        $receipts->each(function (StockReceipt $receipt): void {
            $receivedLines = $receipt->lines->filter(fn ($line) => (float) $line->quantity_received > 0);
            $hasAllCosts = $receivedLines->isNotEmpty() && $receivedLines->every(fn ($line) => $line->unit_cost !== null);
            $receipt->setAttribute('calculated_invoice_amount', $hasAllCosts
                ? (int) round($receivedLines->sum(fn ($line) => (float) $line->quantity_received * (float) $line->unit_cost))
                : null);
        });

        $selectedReceipt = $receipts->firstWhere('id', (int) $request->query('stock_receipt_id'));
        $selectedReceiptId = $selectedReceipt?->id;
        $supplierName = old('supplier_name', $selectedReceipt?->origin_name ?? '');
        $invoiceAmount = old('amount', $selectedReceipt?->calculated_invoice_amount ?? '');

        return view('finance.suppliers.create', compact('receipts', 'selectedReceipt', 'selectedReceiptId', 'supplierName', 'invoiceAmount'));
    }

    public function store(Request $request, FinanceSupplierInvoiceService $service): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isAccountant() || $request->user()->isManager(), 403);
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:160'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:120'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'amount' => ['required', 'integer', 'min:1'],
            'stock_receipt_id' => ['nullable', 'integer', 'exists:stock_receipts,id'],
            'invoice' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['invoice_path'] = $request->file('invoice')->store('finance/suppliers/invoices', 'local');
        $invoice = $service->register($data, $request->user());

        return redirect()->route('finance.suppliers.show', $invoice)->with('success', 'Facture fournisseur enregistrée. Elle attend le contrôle financier avant tout paiement.');
    }

    public function show(Request $request, FinanceSupplierInvoice $invoice): View
    {
        $this->authorizeInvoice($request->user(), $invoice);
        $invoice->load(['receipt.location', 'receipt.lines.product', 'creator', 'reviewer', 'payments.payer']);
        $receivedValue = $invoice->receipt
            ? (int) round($invoice->receipt->lines->sum(fn ($line) => (float) $line->quantity_received * (float) $line->unit_cost))
            : null;
        $receiptDifference = $receivedValue === null ? null : (int) $invoice->amount - $receivedValue;
        $isAssignedCashier = $this->isAssignedCashier($request->user());

        return view('finance.suppliers.show', compact('invoice', 'receivedValue', 'receiptDifference', 'isAssignedCashier'));
    }

    public function review(Request $request, FinanceSupplierInvoice $invoice, FinanceSupplierInvoiceService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $request->input('decision') === 'rejected')],
        ]);
        $service->review($invoice, $request->user(), $data['decision'], $data['comment'] ?? null);

        return back()->with('success', $data['decision'] === 'approved' ? 'Facture fournisseur validée comme dette à régler.' : 'Facture fournisseur rejetée avec motif.');
    }

    public function pay(Request $request, FinanceSupplierInvoice $invoice, FinanceSupplierInvoiceService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);
        $path = 'generated://supplier-payment-voucher';
        $service->pay($invoice, $request->user(), (int) $data['amount'], $data['payment_reference'] ?? null, $path);

        return back()->with('success', 'Règlement enregistré. Imprime le bon et fais-le signer par le fournisseur au moment de la remise des espèces.');
    }

    public function attachment(Request $request, string $kind, int $id)
    {
        if ($kind === 'supplier-invoice') {
            $invoice = FinanceSupplierInvoice::query()->findOrFail($id);
            $this->authorizeInvoice($request->user(), $invoice);
            $path = $invoice->invoice_path;
        } elseif ($kind === 'supplier-payment') {
            $payment = \App\Models\FinanceSupplierPayment::query()->with('invoice')->findOrFail($id);
            $this->authorizeInvoice($request->user(), $payment->invoice);
            $path = $payment->proof_path;
            if ($path === 'generated://supplier-payment-voucher') {
                return view('finance.suppliers.payment-voucher', compact('payment'));
            }
        } else {
            abort(404);
        }
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    private function authorizeInvoice(User $user, FinanceSupplierInvoice $invoice): void
    {
        abort_unless(
            $user->isAdmin() || ($this->isAssignedCashier($user) && $invoice->status === 'approved'),
            403,
        );
    }

    private function isAssignedCashier(User $user): bool
    {
        return (int) FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id') === (int) $user->id;
    }
}
