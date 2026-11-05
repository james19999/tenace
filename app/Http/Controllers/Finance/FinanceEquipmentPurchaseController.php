<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceEquipmentPurchaseRequest as PurchaseRequest;
use App\Models\FinanceEquipmentPurchaseSetting;
use App\Models\FinanceGeneralCashSetting;
use App\Models\User;
use App\Notifications\FinanceWorkflowNotification;
use App\Services\FinanceAuditService;
use App\Services\FinanceCashLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceEquipmentPurchaseController extends Controller
{
    public function setResponsible(Request $request, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('active', true)->whereNotIn('user_type', [User::ROLE_ADMIN, User::ROLE_PARTNER]))],
        ]);
        $setting = FinanceEquipmentPurchaseSetting::query()->firstOrCreate(['id' => 1]);
        $before = ['assignee_user_id' => $setting->assignee_user_id];
        $setting->update(['assignee_user_id' => $data['assigned_to'], 'updated_by' => $request->user()->id]);
        $audit->record($setting, 'equipment_purchase_responsible_set', $before, ['assignee_user_id' => $setting->assignee_user_id]);
        $setting->assignee?->notify(new FinanceWorkflowNotification('Responsable des achats de matériel', 'Tu es désigné(e) pour préparer les demandes d’achat de matériel. Tu peux les créer dans le menu Achats de matériel de ton espace personnel.', route('employees.self-service.purchases.index'), 'shopping_bag'));
        return back()->with('success', 'Le responsable des achats de matériel est enregistré. Il pourra créer les demandes sans nouvelle affectation du DG.');
    }

    public function requestPurchase(Request $request, FinanceAuditService $audit): RedirectResponse
    {
        $responsible = FinanceEquipmentPurchaseSetting::query()->find(1);
        abort_unless($responsible && (int) $responsible->assignee_user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'needed_items' => ['required', 'string', 'max:5000'],
            'requested_amount' => ['required', 'integer', 'min:1'],
        ]);
        $purchase = PurchaseRequest::query()->create([
            'reference' => 'TMP-'.Str::uuid(), 'title' => $data['title'], 'needed_items' => $data['needed_items'],
            'requested_amount' => $data['requested_amount'], 'assigned_to' => $request->user()->id, 'created_by' => $request->user()->id,
            'status' => 'awaiting_director', 'submitted_at' => now(),
        ]);
        $purchase->update(['reference' => sprintf('MAT-%s-%06d', now()->format('Y'), $purchase->id)]);
        $audit->record($purchase, 'equipment_purchase_requested', null, $purchase->only(['reference', 'title', 'needed_items', 'requested_amount', 'status']));
        Notification::send(User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(), new FinanceWorkflowNotification(
            'Achat de matériel à valider', $request->user()->name.' demande '.number_format((int) $purchase->requested_amount, 0, ',', ' ').' F pour '.$purchase->title.'.',
            route('finance.management.index', ['tab' => 'purchases']), 'shopping_bag',
        ));
        return redirect()->route('employees.self-service.purchases.index')->with('success', 'La demande a été transmise au DG pour validation.');
    }

    public function submitEstimate(Request $request, PurchaseRequest $purchase, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless((int) $purchase->assigned_to === (int) $request->user()->id, 403);
        abort_unless($purchase->status === 'assigned', 409);
        $data = $request->validate(['needed_items' => ['required', 'string', 'max:5000'], 'requested_amount' => ['required', 'integer', 'min:1']]);
        $before = $purchase->only(['needed_items', 'requested_amount', 'status']);
        $purchase->fill($data)->forceFill(['status' => 'awaiting_director', 'submitted_at' => now()])->save();
        $audit->record($purchase, 'equipment_purchase_submitted', $before, $purchase->only(['needed_items', 'requested_amount', 'status']));
        Notification::send(User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(), new FinanceWorkflowNotification('Achat de matériel à valider', $purchase->assignee?->name.' demande '.number_format((int) $purchase->requested_amount, 0, ',', ' ').' F pour '.$purchase->title.'.', route('finance.management.index', ['tab' => 'purchases']), 'shopping_bag'));
        return back()->with('success', 'Ta liste de matériel et le montant demandé ont été transmis au DG.');
    }

    public function authorizeFunds(Request $request, PurchaseRequest $purchase, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['approved_amount' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($purchase, $request, $data, $audit): void {
            $locked = PurchaseRequest::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($locked->status === 'awaiting_director', 409);
            if ((int) $data['approved_amount'] > (int) $locked->requested_amount) {
                throw ValidationException::withMessages(['approved_amount' => 'Le montant autorisé ne peut pas dépasser le montant demandé.']);
            }
            $before = $locked->only(['status', 'approved_amount']);
            $locked->fill([
                'approved_amount' => $data['approved_amount'],
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'status' => 'authorized_to_pay',
            ])->save();
            $audit->record($locked, 'equipment_purchase_funds_authorized', $before, $locked->only(['status', 'approved_amount', 'approved_by', 'approved_at']));

            $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
            $cashier = $cashierId
                ? User::query()->where('active', true)->find($cashierId)
                : null;
            $cashier?->notify(new FinanceWorkflowNotification('Argent d’achat autorisé à remettre', 'Le DG a autorisé '.number_format((int) $locked->approved_amount, 0, ',', ' ').' F pour '.$locked->reference.'. Remets les fonds depuis la caisse générale.', route('finance.expenses.index'), 'payments'));
            $locked->assignee?->notify(new FinanceWorkflowNotification('Achat de matériel autorisé', 'Le DG a validé le montant demandé. La remise des fonds sera effectuée par le responsable de la caisse générale.', route('employees.self-service.purchases.index'), 'shopping_bag'));
        });

        return back()->with('success', 'Montant autorisé par le DG. Le responsable de la caisse générale a été notifié pour la remise.');
    }

    public function issueFunds(Request $request, PurchaseRequest $purchase, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        abort_unless($cashierId && (int) $cashierId === (int) $request->user()->id, 403,
            'Seul le responsable désigné de la caisse générale peut remettre ces fonds.');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$purchase->approved_amount],
            'issued_at' => ['required', 'date', 'before_or_equal:now'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'issue_note' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($purchase, $request, $data, $ledger, $audit): void {
            $locked = PurchaseRequest::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($locked->status === 'authorized_to_pay' && (int) $locked->approved_amount > 0, 409);
            abort_if((int) $locked->assigned_to === (int) $request->user()->id, 403, 'Le caissier ne peut pas remettre les fonds pour son propre achat.');
            if ((int) $data['amount'] > (int) $locked->approved_amount) {
                throw ValidationException::withMessages(['amount' => 'Le montant remis ne peut pas dépasser le montant autorisé par le DG.']);
            }
            $issuedAt = \Illuminate\Support\Carbon::parse($data['issued_at']);
            $before = $locked->only(['status', 'cash_issued', 'issued_by', 'issued_at']);
            $movement = $ledger->record([
                'movement_type' => 'equipment_purchase_advance', 'from_custody' => 'general_cash', 'to_custody' => 'external',
                'amount' => (int) $data['amount'], 'source_type' => 'finance_equipment_purchase', 'source_id' => $locked->id,
                'source_key' => 'equipment-purchase:'.$locked->id.':issue', 'source_reference' => $locked->reference,
                'occurred_at' => $issuedAt, 'description' => 'Avance achat matériel confiée à '.$locked->assignee?->name,
                'metadata' => ['assigned_to' => $locked->assigned_to, 'title' => $locked->title, 'payment_reference' => $data['payment_reference'] ?? null, 'issue_note' => $data['issue_note'] ?? null], 'created_by' => $request->user()->id,
            ]);
            $locked->fill(['cash_issued' => (int) $data['amount'], 'issued_by' => $request->user()->id, 'issued_at' => $issuedAt,
                'cash_movement_id' => $movement->id, 'status' => 'funds_issued'])->save();
            $audit->record($locked, 'equipment_purchase_funds_issued', $before, $locked->only(['status', 'cash_issued', 'issued_by', 'issued_at']), $data['issue_note'] ?? null);
            $locked->assignee?->notify(new FinanceWorkflowNotification('Argent remis pour ton achat', 'Le responsable de la caisse générale t’a remis '.number_format((int) $locked->cash_issued, 0, ',', ' ').' F le '.$issuedAt->format('d/m/Y à H:i').'. Effectue l’achat puis ajoute les reçus dans ton espace personnel.', route('employees.self-service.purchases.index'), 'payments'));
        });
        return back()->with('success', 'Remise enregistrée par le responsable de la caisse générale.');
    }

    public function submitReport(Request $request, PurchaseRequest $purchase, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless((int) $purchase->assigned_to === (int) $request->user()->id, 403);
        abort_unless($purchase->status === 'funds_issued', 409);
        $data = $request->validate([
            'spent_amount' => ['required', 'integer', 'min:0'], 'report_note' => ['nullable', 'string', 'max:3000'],
            'receipt_photos' => ['required', 'array', 'min:1', 'max:8'],
            'receipt_photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);
        if ((int) $data['spent_amount'] > (int) $purchase->cash_issued) {
            throw ValidationException::withMessages(['spent_amount' => 'Le montant dépensé dépasse l’avance remise.']);
        }
        if ($purchase->returned_received_at && (int) $data['spent_amount'] !== (int) $purchase->spent_amount) {
            throw ValidationException::withMessages(['spent_amount' => 'Le caissier a déjà confirmé le reliquat reçu. Le montant dépensé ne peut plus être modifié pour ce dossier.']);
        }
        $returned = (int) $purchase->cash_issued - (int) $data['spent_amount'];
        $paths = array_map(fn ($file) => $file->store('finance/equipment-purchases/'.$purchase->id, 'local'), $request->file('receipt_photos'));
        DB::transaction(function () use ($purchase, $request, $data, $returned, $paths, $audit): void {
            $locked = PurchaseRequest::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($locked->status === 'funds_issued' && (int) $locked->assigned_to === (int) $request->user()->id, 409);
            $before = $locked->only(['status', 'spent_amount', 'returned_amount']);
            $locked->fill(['spent_amount' => $data['spent_amount'], 'returned_amount' => $returned, 'report_note' => $data['report_note'] ?? null,
                'receipt_paths' => array_merge($locked->receipt_paths ?? [], $paths), 'report_submitted_at' => now(),
                'return_movement_id' => $locked->returned_received_at ? $locked->return_movement_id : null,
                'receipt_received_at' => null, 'receipt_received_by' => null, 'status' => 'submitted_for_review'])->save();
            $audit->record($locked, 'equipment_purchase_report_submitted', $before, $locked->only(['status', 'spent_amount', 'returned_amount']));
            Notification::send(User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(), new FinanceWorkflowNotification('Reçus transmis · réception caissier requise', $locked->assignee?->name.' a transmis les reçus pour '.$locked->title.'. Le caissier général doit confirmer leur réception avant ton contrôle final.', route('finance.management.index', ['tab' => 'purchases']), 'receipt_long'));
            $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
            User::query()->where('active', true)->find($cashierId)?->notify(new FinanceWorkflowNotification(
                $returned > 0 ? 'Reçus et reliquat d’achat à réceptionner' : 'Reçu d’achat à confirmer',
                $locked->assignee?->name.' a transmis les justificatifs de l’achat '.$locked->reference.($returned > 0 ? ' et déclare te remettre un reliquat de '.number_format($returned, 0, ',', ' ').' F. Confirme le reliquat seulement après l’avoir reçu physiquement.' : '. Confirme leur réception après avoir reçu les justificatifs.'),
                route('finance.expenses.index', ['cashier_tab' => 'expenses']),
                $returned > 0 ? 'payments' : 'receipt_long',
            ));
        });
        return back()->with('success', 'Ton compte rendu et les reçus ont été transmis au caissier général. Le reliquat sera ajouté à la caisse uniquement après sa confirmation de réception.');
    }

    public function confirmReturnedCash(Request $request, PurchaseRequest $purchase, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        abort_unless($cashierId && (int) $cashierId === (int) $request->user()->id, 403,
            'Seul le responsable désigné de la caisse générale peut confirmer la réception du reliquat.');

        DB::transaction(function () use ($purchase, $request, $ledger, $audit): void {
            $locked = PurchaseRequest::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($locked->status === 'submitted_for_review' && (int) $locked->returned_amount > 0, 409);
            abort_unless((int) $locked->assigned_to !== (int) $request->user()->id, 403,
                'Tu ne peux pas confirmer le reliquat de ton propre achat.');
            abort_if($locked->returned_received_at, 409, 'La réception du reliquat est déjà confirmée.');

            $before = $locked->only(['returned_received_at', 'returned_received_by', 'return_movement_id']);
            $movement = $ledger->record([
                'movement_type' => 'equipment_purchase_cash_return', 'from_custody' => 'external', 'to_custody' => 'general_cash',
                'amount' => (int) $locked->returned_amount, 'source_type' => 'finance_equipment_purchase', 'source_id' => $locked->id,
                'source_key' => 'equipment-purchase:'.$locked->id.':return', 'source_reference' => $locked->reference,
                'occurred_at' => now(), 'description' => 'Reliquat effectivement reçu par le caissier pour l’achat '.$locked->reference,
                'metadata' => ['assigned_to' => $locked->assigned_to, 'reported_spent_amount' => $locked->spent_amount],
                'created_by' => $request->user()->id,
            ]);
            $locked->forceFill([
                'returned_received_at' => now(), 'returned_received_by' => $request->user()->id,
                'return_movement_id' => $movement->id,
            ])->save();
            $audit->record($locked, 'equipment_purchase_return_received', $before, $locked->only(['returned_received_at', 'returned_received_by', 'return_movement_id']));
            $locked->assignee?->notify(new FinanceWorkflowNotification(
                'Reliquat reçu par le caissier',
                'Le caissier général a confirmé avoir reçu '.number_format((int) $locked->returned_amount, 0, ',', ' ').' F de reliquat pour '.$locked->reference.'.',
                route('employees.self-service.purchases.index'),
                'payments',
            ));
            Notification::send(User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(), new FinanceWorkflowNotification(
                'Reliquat versé dans la caisse générale',
                'Le caissier a reçu et enregistré '.number_format((int) $locked->returned_amount, 0, ',', ' ').' F pour '.$locked->reference.'. Le dossier attend ton contrôle final.',
                route('finance.management.index', ['tab' => 'purchases']),
                'account_balance_wallet',
            ));
        });

        return back()->with('success', 'Reliquat reçu et ajouté à la caisse générale.');
    }

    public function confirmReceipt(Request $request, PurchaseRequest $purchase, FinanceAuditService $audit): RedirectResponse
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        abort_unless($cashierId && (int) $cashierId === (int) $request->user()->id, 403,
            'Seul le responsable désigné de la caisse générale peut confirmer la réception des reçus.');

        DB::transaction(function () use ($purchase, $request, $audit): void {
            $locked = PurchaseRequest::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($locked->status === 'submitted_for_review' && ! empty($locked->receipt_paths), 409);
            abort_unless((int) $locked->assigned_to !== (int) $request->user()->id, 403,
                'Tu ne peux pas confirmer la réception du reçu de ton propre achat.');
            abort_if($locked->receipt_received_at, 409, 'La réception des reçus est déjà confirmée.');

            $before = $locked->only(['receipt_received_at', 'receipt_received_by']);
            $locked->forceFill(['receipt_received_at' => now(), 'receipt_received_by' => $request->user()->id])->save();
            $audit->record($locked, 'equipment_purchase_receipts_received', $before, [
                'receipt_received_at' => $locked->receipt_received_at?->toDateTimeString(),
                'receipt_received_by' => $request->user()->id,
            ]);
            $locked->assignee?->notify(new FinanceWorkflowNotification(
                'Réception des reçus confirmée',
                'Le caissier général a confirmé la réception des justificatifs pour '.$locked->reference.'. Le DG peut terminer son contrôle.',
                route('employees.self-service.purchases.index'),
                'task_alt',
            ));
            Notification::send(User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(), new FinanceWorkflowNotification(
                'Reçus reçus par le caissier',
                'Le caissier général a confirmé la réception des justificatifs pour '.$locked->reference.'. Le dossier peut être contrôlé.',
                route('finance.management.index', ['tab' => 'purchases']),
                'receipt_long',
            ));
        });

        return back()->with('success', 'Réception des reçus d’achat confirmée. Le DG peut maintenant contrôler le dossier.');
    }

    public function review(Request $request, PurchaseRequest $purchase, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['accepted', 'correction'])], 'review_note' => ['nullable', 'string', 'max:2000']]);
        abort_unless($purchase->status === 'submitted_for_review', 409);
        abort_unless($purchase->receipt_received_at, 409, 'Le caissier général doit d’abord confirmer la réception des reçus.');
        DB::transaction(function () use ($purchase, $data, $request, $audit): void {
            $locked = PurchaseRequest::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($locked->status === 'submitted_for_review', 409);
            abort_unless($locked->receipt_received_at, 409, 'Le caissier général doit d’abord confirmer la réception des reçus.');
            if ($data['decision'] === 'accepted' && (int) $locked->returned_amount > 0) {
                abort_unless($locked->returned_received_at, 409, 'Le caissier général doit confirmer la réception du reliquat avant la clôture.');
            }
            $before = $locked->only(['status', 'review_note', 'return_movement_id']);
            $locked->fill(['status' => $data['decision'] === 'accepted' ? 'closed' : 'funds_issued', 'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => $request->user()->id, 'reviewed_at' => now()])->save();
            $audit->record($locked, $data['decision'] === 'accepted' ? 'equipment_purchase_closed' : 'equipment_purchase_correction_requested', $before, $locked->only(['status', 'review_note', 'return_movement_id']));
            $locked->assignee?->notify(new FinanceWorkflowNotification(
                $data['decision'] === 'accepted' ? 'Achat contrôlé et clôturé' : 'Complément demandé sur ton achat',
                $data['decision'] === 'accepted'
                    ? 'Le DG a terminé le contrôle du dossier '.$locked->reference.'.'
                    : 'Le DG demande un complément pour le dossier '.$locked->reference.': '.trim((string) ($data['review_note'] ?? '')),
                route('employees.self-service.purchases.index'),
                $data['decision'] === 'accepted' ? 'task_alt' : 'info',
            ));
        });
        return back()->with('success', $data['decision'] === 'accepted' ? 'L’achat est contrôlé et clôturé.' : 'Un complément a été demandé ; l’employé peut renvoyer ses reçus.');
    }

    public function receipt(Request $request, PurchaseRequest $purchase, int $index): BinaryFileResponse
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        abort_unless($request->user()->isAdmin() || $request->user()->isAccountant()
            || (int) $request->user()->id === (int) $purchase->assigned_to
            || ($cashierId && (int) $request->user()->id === (int) $cashierId), 403);
        $path = $purchase->receipt_paths[$index] ?? abort(404);
        abort_unless(Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path));
    }
}
