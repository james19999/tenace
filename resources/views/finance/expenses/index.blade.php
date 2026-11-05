@extends('layouts.admin')
@section('content')
<div class="container-fluid finance-page"><header class="finance-page-heading"><div><a href="{{ auth()->user()->isWholesaleSalesRole() ? route('product') : route('finance.dashboard') }}">{{ auth()->user()->isWholesaleSalesRole() ? '← Espace grossiste' : '← Trésorerie' }}</a><h1>{{ $isAssignedCashier ? 'Remises de la caisse générale' : 'Demandes et paiements de dépenses' }}</h1><p>{{ $isAssignedCashier ? 'Traite les dépenses déjà autorisées, remets les fonds et enregistre chaque paiement au moment réel de la remise.' : 'Les demandes autorisées sont payées uniquement par le responsable désigné, depuis la caisse générale. Le demandeur transmet ses reçus après l’achat.' }}</p></div><div class="expense-head-actions"><a class="btn btn-primary" href="{{ route('finance.expenses.create') }}">Nouvelle demande</a>@if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isAccountant())<a class="btn btn-outline-primary" href="{{ route('finance.expenses.categories.index') }}">Catégories</a>@endif</div></header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(auth()->user()->isAdmin())
        <section class="cashier-assignment finance-panel">
            <div>
                <span class="material-icons">account_balance</span>
                <div>
                    <h2>Responsable unique de la caisse générale</h2>
                    <p>
                        @if($cashSetting?->cashier)
                            {{ $cashSetting->cashier->name }} remettra toutes les dépenses autorisées.
                        @else
                            Aucun responsable n’est encore désigné. Aucun décaissement de dépense ne sera possible avant ce choix.
                        @endif
                    </p>
                </div>
            </div>
            @if($cashiers->isEmpty())
                <div class="cashier-empty"><strong>Aucun employé disponible</strong><span>Active un compte ayant déjà accès au menu Comptabilité, puis reviens le sélectionner.</span></div>
            @else
                <form method="POST" action="{{ route('finance.expenses.cashier.update') }}">@csrf
                    <label class="sr-only" for="expense-cashier">Choisir le caissier général</label>
                    <select class="form-control" id="expense-cashier" name="cashier_user_id" required>
                        <option value="">Choisir un caissier</option>
                        @foreach($cashiers as $cashier)<option value="{{ $cashier->id }}" @selected((int) $cashSetting?->cashier_user_id === (int) $cashier->id)>{{ $cashier->name }} · {{ match($cashier->user_type) { 'MNG' => 'Manager', 'SCR' => 'Secrétaire', 'VDS' => 'Commercial', 'CSA' => 'Caissier', 'COMPTABLE' => 'Comptable', 'LVS' => 'Livreur', 'CALLCENTER' => 'SAV', 'RCM' => 'Responsable grossiste', 'GRO' => 'Grossiste', default => 'Employé' } }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">Enregistrer le responsable</button>
                </form>
            @endif
        </section>
    @elseif($isAssignedCashier)
        <div class="cashier-notice"><span class="material-icons">verified_user</span><span><strong>Tu es le caissier général désigné.</strong> Ne remets que les fonds des demandes autorisées. Confirme le paiement quand l’argent est réellement remis ; tu ne peux pas payer ta propre demande.</span></div>
        <section class="cashier-workspace" aria-label="Résumé des remises à effectuer">
            <article class="cashier-workspace-balance"><span class="material-icons">account_balance_wallet</span><div><small>Solde actuel caisse générale</small><strong>{{ number_format($cashierGeneralBalance,0,',',' ') }} F</strong></div></article>
            <article><span>Dépenses autorisées à remettre</span><strong>{{ $cashierPayableExpenseCount }}</strong><small>{{ number_format($cashierPayableExpenseAmount,0,',',' ') }} F restants</small></article>
            <article><span>Achats de matériel à remettre</span><strong>{{ $cashierPurchaseCount }}</strong><small>{{ number_format($cashierPurchaseAmount,0,',',' ') }} F autorisés</small></article>
        </section>
        @php($cashierQueueTab = request()->query('cashier_tab') === 'tontines' ? 'tontines' : 'expenses')
        <nav class="cashier-queue-tabs" aria-label="Files de remise">
            <a class="{{ $cashierQueueTab === 'expenses' ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['cashier_tab' => 'expenses']) }}" @if($cashierQueueTab === 'expenses') aria-current="page" @endif>
                Dépenses et avances <span>{{ $cashierPayableExpenseCount }}</span>
            </a>
            <a class="{{ $cashierQueueTab === 'tontines' ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['cashier_tab' => 'tontines']) }}" @if($cashierQueueTab === 'tontines') aria-current="page" @endif>
                Cotisations de tontine <span>{{ $cashierTontineRequests->total() }}</span>
            </a>
        </nav>
        @if($cashierQueueTab === 'expenses')
            <section class="finance-panel cashier-pay-queue">
                <div class="cashier-purchases-head"><div><h2>Demandes autorisées à remettre</h2><p>Les dépenses et avances apparaissent ici après autorisation. Confirme la remise réelle depuis le dossier.</p></div><strong>{{ $cashierPayableExpenseCount }} à traiter</strong></div>
                <div class="table-responsive"><table class="table finance-table"><thead><tr><th>Demandeur / référence</th><th>Bénéficiaire / motif</th><th>Montant restant</th><th>Statut</th><th></th></tr></thead><tbody>
                    @forelse($cashierPayableExpenses as $payableExpense)
                        <tr><td><strong>{{ $payableExpense->requester?->name ?? '—' }}</strong><small class="d-block">{{ $payableExpense->reference }} · {{ $payableExpense->created_at?->format('d/m/Y') }}</small></td><td>{{ $payableExpense->beneficiary }}<small class="d-block">{{ $payableExpense->staff_advance_type === 'loan' ? 'Prêt au personnel' : ($payableExpense->staff_advance_type === 'salary_advance' ? 'Avance sur salaire' : $payableExpense->category_name) }} · {{ \Illuminate\Support\Str::limit($payableExpense->description, 72) }}</small></td><td><strong>{{ number_format(max(0,(int)$payableExpense->amount-(int)$payableExpense->paid_amount),0,',',' ') }} F</strong></td><td><span class="badge badge-warning">{{ \App\Support\FinanceDisplayLabels::status($payableExpense->status) }}</span></td><td><a class="btn btn-sm btn-primary" href="{{ route('finance.expenses.show',$payableExpense) }}">Ouvrir la remise</a></td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">Aucune dépense autorisée n’attend de remise.</td></tr>@endforelse
                </tbody></table></div>
                @if($cashierPayableExpenses->hasPages()){{ $cashierPayableExpenses->links() }}@endif
            </section>
        @else
            <section class="finance-panel cashier-tontine-queue">
                <div class="cashier-purchases-head"><div><h2>Cotisations de tontine à remettre</h2><p>Les demandes arrivent directement ici. Confirme la remise réelle depuis la caisse générale.</p></div><strong>{{ $cashierTontineRequests->total() }} à remettre</strong></div>
                <div class="table-responsive"><table class="table finance-table"><thead><tr><th>Tontine / référence</th><th>Demandeur</th><th>Montant demandé</th><th>Date de demande</th><th>Note</th><th>Action</th></tr></thead><tbody>
                    @forelse($cashierTontineRequests as $tontineRequest)
                        <tr><td><strong>{{ $tontineRequest->tontine?->name ?? 'Tontine supprimée' }}</strong><small class="d-block">{{ $tontineRequest->reference }}</small></td><td>{{ $tontineRequest->requester?->name ?? '—' }}</td><td><strong>{{ number_format($tontineRequest->amount, 0, ',', ' ') }} F</strong></td><td>{{ $tontineRequest->requested_at?->format('d/m/Y à H:i') ?? '—' }}</td><td>{{ $tontineRequest->note ?: 'Aucune note' }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('finance.tontines.contribution-requests.remittance', $tontineRequest) }}">Ouvrir la remise</a></td></tr>
                    @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune cotisation de tontine n’attend une remise.</td></tr>@endforelse
                </tbody></table></div>
                @if($cashierTontineRequests->hasPages()){{ $cashierTontineRequests->links() }}@endif
            </section>
        @endif
    @endif
    @if($isAssignedCashier)
        <section class="finance-panel direct-dg-expense">
            <div class="direct-dg-heading"><span class="material-icons">request_quote</span><div><h2>Demande de dépense directe du DG</h2><p>Enregistre le montant et le motif demandés par le DG. Aucun argent ne sort de la caisse avant que le DG ait vérifié et validé la demande.</p></div></div>
            @if($directExpenseDirectors->isEmpty())<div class="alert alert-warning mb-0">Aucun compte DG actif n’est disponible pour attribuer cette dépense.</div>
            @elseif($directExpenseCategories->isEmpty())<div class="alert alert-warning mb-0">Crée d’abord une catégorie de dépense pour pouvoir enregistrer cette sortie.</div>
            @else
                <form method="POST" action="{{ route('finance.expenses.director-direct-payout') }}">@csrf
                    <div class="direct-dg-form-grid">
                        <label>DG demandeur<select class="form-control" name="director_user_id" required><option value="">Choisir le DG</option>@foreach($directExpenseDirectors as $director)<option value="{{ $director->id }}" @selected(old('director_user_id')==$director->id)>{{ $director->name }}</option>@endforeach</select></label>
                        <label>Catégorie<select class="form-control" name="category_id" required><option value="">Choisir une catégorie</option>@foreach($directExpenseCategories as $category)<option value="{{ $category->id }}" @selected(old('category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
                        <label>Montant demandé par le DG (F CFA)<input class="form-control" type="number" name="amount" min="1" value="{{ old('amount') }}" required></label>
                        <label>Bénéficiaire<input class="form-control" name="beneficiary" maxlength="160" value="{{ old('beneficiary') }}" placeholder="Personne ou fournisseur payé" required></label>
                        <label class="direct-dg-reason">Motif / détail de l’achat<textarea class="form-control" name="description" rows="3" maxlength="5000" placeholder="Indique pourquoi le DG demande cette dépense." required>{{ old('description') }}</textarea></label>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">Transmettre au DG pour validation</button>
                </form>
            @endif
        </section>
    @endif
    @if($isAssignedCashier && $cashierPurchaseRequests)
        <section class="finance-panel cashier-purchases">
            <div class="cashier-purchases-head"><div><h2>Achats de matériel autorisés</h2><p>Le DG a fixé un plafond. Saisis les détails et confirme la remise uniquement quand les fonds sont réellement remis à la personne chargée.</p></div><strong>{{ $cashierPurchaseRequests->total() }} à remettre</strong></div>
            <div class="table-responsive"><table class="table finance-table cashier-purchase-table"><thead><tr><th>Référence / besoin</th><th>Chargé de l’achat</th><th>Montant autorisé</th><th>Remise</th></tr></thead><tbody>
                @forelse($cashierPurchaseRequests as $purchase)
                    <tr>
                        <td><strong class="cashier-purchase-ref">{{ $purchase->reference }}</strong><span class="cashier-purchase-title">{{ $purchase->title }}</span><small class="cashier-purchase-items">{{ \Illuminate\Support\Str::limit($purchase->needed_items, 90) }}</small><small class="cashier-purchase-trace">Demandé par {{ $purchase->creator?->name ?? $purchase->assignee?->name ?? '—' }} · {{ $purchase->created_at?->format('d/m/Y à H:i') ?? '—' }}</small><small class="cashier-purchase-trace">Validé par {{ $purchase->approver?->name ?? 'le DG' }} · {{ $purchase->approved_at?->format('d/m/Y à H:i') ?? '—' }}</small></td>
                        <td class="cashier-purchase-assignee">{{ $purchase->assignee?->name ?? '—' }}</td>
                        <td><strong class="cashier-purchase-amount">{{ number_format((int) $purchase->approved_amount,0,',',' ') }} F</strong></td>
                        <td>
                            @if((int) $purchase->assigned_to === (int) auth()->id())
                                <span class="text-muted">Tu ne peux pas remettre les fonds pour ton propre achat.</span>
                            @else
                                <details class="cashier-purchase-remit"><summary class="btn btn-sm btn-primary">Saisir la remise</summary>
                                    <form method="POST" action="{{ route('finance.management.purchases.issue',$purchase) }}" data-cashier-confirm-form data-confirm-title="Confirmer cette remise ?" data-confirm-message="Confirme uniquement si tu remets {amount} F CFA à {{ $purchase->assignee?->name ?? 'la personne chargée' }} pour l’achat {{ $purchase->reference }}." data-confirm-button="Oui, confirmer la remise">@csrf
                                        <label>Montant réellement remis (F CFA)<input class="form-control" type="number" name="amount" min="1" max="{{ $purchase->approved_amount }}" value="{{ $purchase->approved_amount }}" required><small>Maximum autorisé : {{ number_format((int) $purchase->approved_amount,0,',',' ') }} F.</small></label>
                                        <label>Date et heure de remise<input class="form-control" type="datetime-local" name="issued_at" value="{{ now()->format('Y-m-d\\TH:i') }}" max="{{ now()->format('Y-m-d\\TH:i') }}" required></label>
                                        <label>Référence du reçu ou du paiement (facultatif)<input class="form-control" name="payment_reference" maxlength="120" placeholder="N° de pièce ou référence"></label>
                                        <label>Note (facultatif)<textarea class="form-control" name="issue_note" rows="2" maxlength="1000" placeholder="Précision sur la remise"></textarea></label>
                                        <button class="btn btn-primary" type="submit">Vérifier et confirmer</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-4">Aucun achat autorisé n’attend de remise.</td></tr>@endforelse
            </tbody></table></div>{{ $cashierPurchaseRequests->links() }}
        </section>
        @if($cashierPurchaseReceiptRequests && $cashierPurchaseReceiptRequests->total())
            <section class="finance-panel cashier-purchases">
                <div class="cashier-purchases-head"><div><h2>Reçus et reliquats d’achat à réceptionner</h2><p>Ouvre les justificatifs. Confirme chaque réception uniquement quand les reçus ou l’argent t’ont été remis physiquement.</p></div><strong>{{ $cashierPurchaseReceiptRequests->total() }} à traiter</strong></div>
                <div class="table-responsive"><table class="table finance-table"><thead><tr><th>Référence / achat</th><th>Personne chargée</th><th>Dépensé</th><th>Reliquat déclaré</th><th>Justificatifs</th><th>Réceptions à confirmer</th></tr></thead><tbody>
                    @foreach($cashierPurchaseReceiptRequests as $purchase)
                        <tr>
                            <td><strong class="cashier-purchase-ref">{{ $purchase->reference }}</strong><span class="cashier-purchase-title">{{ $purchase->title }}</span><small class="cashier-purchase-trace">Compte rendu envoyé le {{ $purchase->report_submitted_at?->format('d/m/Y à H:i') ?? '—' }}</small></td>
                            <td class="cashier-purchase-assignee">{{ $purchase->assignee?->name ?? '—' }}</td>
                            <td><strong>{{ number_format((int) $purchase->spent_amount,0,',',' ') }} F</strong></td>
                            <td><strong>{{ number_format((int) $purchase->returned_amount,0,',',' ') }} F</strong>@if($purchase->returned_received_at)<small class="cashier-purchase-trace">Reçu le {{ $purchase->returned_received_at->format('d/m/Y à H:i') }} par {{ $purchase->returnedReceiver?->name ?? '—' }}</small>@endif</td>
                            <td>@foreach($purchase->receipt_paths ?? [] as $i=>$path)<a class="btn btn-sm btn-outline-primary" href="{{ route('finance.management.purchases.receipt',[$purchase,$i]) }}" target="_blank" rel="noopener">Reçu {{ $loop->iteration }}</a>@endforeach</td>
                            <td class="cashier-purchase-confirm-actions">
                                @if(!$purchase->receipt_received_at)
                                    <form method="POST" action="{{ route('finance.management.purchases.receipts.confirm',$purchase) }}" data-cashier-confirm-form data-confirm-title="Confirmer la réception des reçus ?" data-confirm-message="Confirme uniquement si les justificatifs de {{ $purchase->assignee?->name ?? 'la personne chargée' }} pour {{ $purchase->reference }} t’ont bien été remis." data-confirm-button="Oui, confirmer les reçus">@csrf<button class="btn btn-sm btn-primary" type="submit">Confirmer les reçus</button></form>
                                @else
                                    <span class="receipt-state receipt-state-done">Reçus reçus le {{ $purchase->receipt_received_at->format('d/m/Y') }}</span>
                                @endif
                                @if((int) $purchase->returned_amount > 0 && !$purchase->returned_received_at)
                                    <form method="POST" action="{{ route('finance.management.purchases.return.confirm',$purchase) }}" data-cashier-confirm-form data-confirm-title="Confirmer la réception du reliquat ?" data-confirm-message="Confirme uniquement si tu as réellement reçu {{ number_format((int) $purchase->returned_amount,0,',',' ') }} F CFA de {{ $purchase->assignee?->name ?? 'la personne chargée' }} pour {{ $purchase->reference }}." data-confirm-button="Oui, ajouter le reliquat à la caisse">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Confirmer le reliquat</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody></table></div>{{ $cashierPurchaseReceiptRequests->links() }}
            </section>
        @endif
    @endif
    <section class="finance-panel expense-history"><div class="expense-list-heading"><div><h2>{{ $isAssignedCashier ? 'Historique et suivi des demandes' : 'Toutes les demandes de dépense' }}</h2><p>{{ $isAssignedCashier ? 'Retrouve une demande, vérifie son statut et consulte les remises déjà enregistrées.' : 'Recherche une demande par référence, bénéficiaire ou motif.' }}</p></div><span>{{ $expenses->total() }} demande(s)</span></div><form method="GET" class="finance-filter"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Référence, bénéficiaire, motif"><select name="status" class="form-control"><option value="">Tous les statuts</option>@foreach(['awaiting_finance'=>'À contrôler','awaiting_director'=>'À valider par le DG','authorized_to_pay'=>'Autorisée à payer','partially_paid'=>'Partiellement payée','paid'=>'Payée','rejected'=>'Refusée'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-primary">Filtrer</button></form>
    <div class="table-responsive"><table class="table finance-table"><thead><tr><th>Référence / demandeur</th><th>Bénéficiaire / motif</th><th>Lieu</th><th>Montant demandé</th><th>Statut</th><th>Reçus après achat</th><th></th></tr></thead><tbody>
    @forelse($expenses as $expense)<tr><td><strong>{{ $expense->reference }}</strong><small class="d-block text-muted">{{ $expense->created_at?->format('d/m/Y H:i') }}</small>@if($isAssignedCashier)<small class="d-block text-muted">Par {{ $expense->requester?->name ?? '—' }}</small>@endif</td><td>{{ $expense->beneficiary }}<small class="d-block text-muted">{{ $expense->category_name }} · {{ \Illuminate\Support\Str::limit($expense->description, 90) }}</small></td><td>{{ $expense->location?->name ?? 'Centrale' }}</td><td class="font-weight-bold">{{ number_format($expense->amount,0,',',' ') }} F</td><td><span class="badge badge-{{ in_array($expense->status,['paid','authorized_to_pay']) ? 'success' : (in_array($expense->status,['rejected']) ? 'danger' : 'warning') }}">{{ \App\Support\FinanceDisplayLabels::status($expense->status) }}</span></td><td>@if((int) $expense->paid_amount === 0)<span class="text-muted">Après remise des fonds</span>@elseif($expense->receipt_received_at)<span class="receipt-state receipt-state-done">Réception confirmée le {{ $expense->receipt_received_at->format('d/m/Y') }}</span><small class="d-block text-muted">Par {{ $expense->receiptReceiver?->name ?? '—' }}</small>@elseif($expense->receipt_paths)<span class="receipt-state receipt-state-pending">Reçu transmis · à confirmer</span>@else<span class="receipt-state receipt-state-pending">À fournir par {{ $expense->requester?->name ?? 'le demandeur' }}</span>@endif</td><td>@php($canConfirmReceipt = (auth()->user()->isAdmin() || $isAssignedCashier) && (int) $expense->requested_by !== (int) auth()->id() && (int) $expense->paid_amount > 0 && ! $expense->receipt_received_at)@if($canConfirmReceipt)<form method="POST" action="{{ route('finance.expenses.receipts.confirm',$expense) }}" data-receipt-confirm-form data-requester-name="{{ $expense->requester?->name ?? 'le demandeur' }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Confirmer la réception</button></form>@else<a class="btn btn-sm btn-outline-primary" href="{{ route('finance.expenses.show',$expense) }}">Ouvrir</a>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Aucune demande ne correspond aux filtres.</td></tr>@endforelse
    </tbody></table></div>{{ $expenses->links() }}</section>
</div>
<div class="receipt-confirm-modal" id="receipt-confirm-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="receipt-confirm-title">
    <div class="receipt-confirm-backdrop" data-receipt-confirm-close></div>
    <section class="receipt-confirm-panel" tabindex="-1">
        <span class="material-icons receipt-confirm-icon" aria-hidden="true">task_alt</span>
        <h2 id="receipt-confirm-title">Confirmer la réception du reçu ?</h2>
        <p id="receipt-confirm-message">Confirme uniquement lorsque le demandeur t’a bien remis son reçu.</p>
        <div class="receipt-confirm-actions"><button type="button" class="receipt-confirm-cancel" data-receipt-confirm-close>Annuler</button><button type="button" class="receipt-confirm-submit" id="receipt-confirm-submit">Oui, confirmer la réception</button></div>
    </section>
</div>
<style>
.finance-page{max-width:1500px;padding:22px 24px 44px;color:#273142}.finance-page-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}.finance-page-heading>div>a{display:inline-flex;align-items:center;gap:5px;color:#187b2a;font-size:13px;font-weight:750;text-decoration:none}.finance-page-heading h1{font-size:clamp(25px,2.4vw,32px);font-weight:850;letter-spacing:-.025em;margin:8px 0}.finance-page-heading p{color:#778397;margin:0;line-height:1.55}.expense-head-actions{display:flex;gap:8px;flex-wrap:wrap}.expense-head-actions .btn,.cashier-assignment .btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;padding:10px 17px;font-weight:750;white-space:nowrap}.finance-panel{padding:20px 22px;background:#fff;border:1px solid #e1e5eb;box-shadow:0 5px 20px rgba(25,38,55,.035)}.cashier-assignment{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.75fr);gap:22px;align-items:center;margin-bottom:17px}.cashier-assignment>div{display:flex;gap:13px;align-items:flex-start}.cashier-assignment>div>.material-icons{font-size:25px;color:#187b2a}.cashier-assignment h2{font-size:17px;font-weight:850;margin:0 0 5px}.cashier-assignment p{margin:0;color:#758194;line-height:1.45}.cashier-assignment form{display:grid;grid-template-columns:minmax(150px,1fr) auto;gap:9px}.cashier-notice{display:flex;gap:10px;align-items:center;padding:13px 16px;margin-bottom:17px;background:#eef7ef;color:#315f39}.cashier-notice .material-icons{color:#187b2a}.receipt-state{display:inline-flex;padding:5px 8px;border-radius:14px;font-size:11px;font-weight:750}.receipt-state-done{background:#e8f5eb;color:#24733a}.receipt-state-pending{background:#fff4dc;color:#946512}.finance-filter{display:grid;grid-template-columns:minmax(240px,1fr) minmax(190px,240px) auto;gap:10px;margin-bottom:18px;padding:14px;background:#f7f9fb;border:1px solid #e9edf1}.finance-filter .form-control{min-width:0;height:42px;border-color:#dce2e8}.finance-filter .form-control:focus{border-color:#24833a;box-shadow:0 0 0 3px rgba(24,123,42,.1)}.finance-filter .btn{min-width:105px;font-weight:700}.finance-table{min-width:850px;margin:0;border-collapse:separate;border-spacing:0}.finance-table thead th{padding:12px 13px;background:#f4f6f8;border-top:0;border-bottom:1px solid #e2e7eb;color:#687486;font-size:10px;font-weight:850;letter-spacing:.07em;text-transform:uppercase;white-space:nowrap}.finance-table tbody td{padding:14px 13px;border-top:1px solid #edf0f3;color:#3d4654;font-size:13px;vertical-align:middle}.finance-table tbody tr:hover{background:#fbfcfd}.finance-table td strong{color:#293342}.finance-table td small{margin-top:4px;color:#8590a0;font-size:11px;line-height:1.45}.finance-panel .badge{display:inline-flex;align-items:center;min-height:27px;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:800;letter-spacing:.01em;text-transform:none}.finance-panel .badge-warning{background:#fff4dc;color:#946512}.finance-panel .badge-success{background:#e8f5eb;color:#24733a}.finance-panel .badge-danger{background:#fff0ef;color:#a33c35}.finance-panel .btn-outline-primary{border-color:#cbd9ce;color:#187b2a;font-weight:750}.finance-panel .btn-outline-primary:hover{background:#187b2a;border-color:#187b2a;color:#fff}.finance-panel .table-responsive{border:1px solid #e8edf0}.finance-panel .pagination{margin:18px 0 0;justify-content:flex-end}.finance-panel .page-link{color:#187b2a;border-color:#e1e7e3}.finance-panel .page-item.active .page-link{background:#187b2a;border-color:#187b2a;color:#fff}.finance-page .alert{border:0;border-left:4px solid #24833a;border-radius:0;box-shadow:0 3px 14px rgba(25,38,55,.04)}
@media(max-width:760px){.finance-page{padding:14px 12px 30px}.finance-page-heading{align-items:flex-start;flex-direction:column;gap:13px;margin-bottom:16px}.expense-head-actions,.expense-head-actions .btn{width:100%}.cashier-assignment{grid-template-columns:1fr;padding:14px}.cashier-assignment form{grid-template-columns:1fr}.finance-panel{padding:13px}.finance-filter{grid-template-columns:1fr;padding:10px}.finance-filter .btn{width:100%;height:42px}.finance-table{min-width:780px}.finance-panel .pagination{justify-content:center}.finance-panel .page-item:not(:first-child):not(:last-child):not(.active){display:none}}
</style>
<style>.cashier-purchases{margin-bottom:17px}.cashier-purchases-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:12px}.cashier-purchases-head h2{font-size:18px;font-weight:850;margin:0 0 5px}.cashier-purchases-head p{margin:0;color:#758194}.cashier-purchases-head>strong{padding:7px 10px;background:#fff4dc;color:#946512;white-space:nowrap}.cashier-purchases td form{display:grid;gap:4px}.cashier-purchases td form small{color:#946512}@media(max-width:650px){.cashier-purchases-head{flex-direction:column}.cashier-purchases-head>strong{white-space:normal}}</style>
<style>.direct-dg-expense{margin-bottom:17px}.direct-dg-heading{display:flex;align-items:flex-start;gap:12px;margin-bottom:17px}.direct-dg-heading>.material-icons{font-size:24px;color:#187b2a}.direct-dg-heading h2{font-size:18px;font-weight:850;margin:0 0 5px}.direct-dg-heading p{margin:0;color:#758194;line-height:1.5}.direct-dg-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.direct-dg-form-grid label{display:grid;gap:6px;font-weight:700;color:#465164}.direct-dg-reason{grid-column:1/-1}.direct-dg-expense .btn{font-weight:750}@media(max-width:650px){.direct-dg-form-grid{grid-template-columns:1fr}.direct-dg-reason{grid-column:auto}.direct-dg-expense .btn{width:100%}}</style>
<style>
.finance-page{width:100%;max-width:1500px;margin-inline:auto;min-width:0;overflow-x:clip}
.finance-page-heading{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:24px}
.finance-page-heading>div:first-child{min-width:0}
.expense-head-actions{align-items:center;justify-content:flex-end;gap:10px;min-width:max-content}
.finance-page .expense-head-actions .btn,
.finance-page .direct-dg-expense .btn,
.finance-page .cashier-assignment .btn{
    min-height:46px;padding:11px 18px;border-radius:7px!important;
    display:inline-flex;align-items:center;justify-content:center;
    color:#fff!important;background:var(--app-primary,#811515)!important;
    border:1px solid var(--app-primary,#811515)!important;
    font-size:14px;line-height:1.25;font-weight:750;text-align:center;
    white-space:normal;text-decoration:none;opacity:1;visibility:visible;
}
.finance-page .expense-head-actions .btn-outline-primary{color:var(--app-primary,#811515)!important;background:#fff!important}
.finance-page .expense-head-actions .btn-outline-primary:hover{color:#fff!important;background:var(--app-primary,#811515)!important}
.cashier-assignment{grid-template-columns:minmax(0,1fr) minmax(360px,.9fr);gap:24px;padding:22px 24px}
.cashier-assignment>div{min-width:0;align-items:flex-start}
.cashier-assignment>div>div{min-width:0}
.cashier-assignment form{min-width:0;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:10px}
.cashier-assignment .form-control{height:46px;min-width:0}
.finance-page .cashier-assignment .btn{white-space:nowrap}
.cashier-assignment h2{line-height:1.3}
.expense-list-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:14px}
.expense-list-heading h2{margin:0 0 4px;font-size:18px;font-weight:850;color:var(--app-text,#273142)}
.expense-list-heading p{margin:0;color:var(--app-muted,#778397);font-size:13px;line-height:1.45}
.expense-list-heading>span{flex:none;padding:7px 10px;border-radius:7px;background:var(--app-primary-soft,#f7eeee);color:var(--app-primary,#811515);font-size:12px;font-weight:800}
.cashier-workspace{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:0 0 16px}
.cashier-workspace>article{display:flex;flex-direction:column;gap:5px;min-width:0;padding:17px 19px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e1e5eb);border-radius:9px}
.cashier-workspace>article>span:not(.material-icons),.cashier-workspace small{color:var(--app-muted,#778397);font-size:12px;font-weight:700}
.cashier-workspace>article>strong{color:var(--app-text,#273142);font-size:25px;font-weight:850;line-height:1.2}
.cashier-workspace-balance{flex-direction:row!important;align-items:center;gap:13px!important;border-left:4px solid var(--app-primary,#811515)!important}
.cashier-workspace-balance>.material-icons{display:grid;place-items:center;flex:none;width:42px;height:42px;border-radius:8px;background:var(--app-primary-soft,#f7eeee);color:var(--app-primary,#811515);font-size:23px}
.cashier-workspace-balance>div{display:grid;gap:4px;min-width:0}.cashier-workspace-balance strong{color:var(--app-primary,#811515);font-size:22px;font-weight:850;overflow-wrap:anywhere}
.cashier-pay-queue{margin-bottom:16px}.cashier-pay-queue .cashier-purchases-head{align-items:center}
.cashier-pay-queue .table-responsive{border-radius:8px}
.cashier-pay-queue .btn-primary{background:var(--app-primary,#811515);border-color:var(--app-primary,#811515);font-weight:750;white-space:nowrap}
.cashier-queue-tabs{display:flex;gap:8px;margin:0 0 12px;padding:5px;background:#f1f3f5;border:1px solid #e1e5eb;border-radius:9px}
.cashier-queue-tabs a{display:flex;align-items:center;justify-content:center;gap:9px;min-height:43px;padding:8px 14px;border:1px solid transparent;border-radius:6px;color:#647083;font-size:13px;font-weight:750;text-decoration:none}
.cashier-queue-tabs a.is-active{background:#fff;border-color:#e1e5eb;color:var(--app-primary,#811515);box-shadow:0 2px 6px rgba(25,38,55,.06)}
.cashier-queue-tabs a span{display:inline-grid;place-items:center;min-width:23px;height:22px;padding:0 6px;border-radius:12px;background:#fff0d3;color:#91600a;font-size:11px;font-weight:850}
.cashier-tontine-queue{margin-bottom:16px}.cashier-tontine-queue .cashier-purchases-head{align-items:center}
.cashier-tontine-queue .finance-table{min-width:980px}
.cashier-tontine-queue .finance-table td:first-child strong,.cashier-tontine-queue .finance-table td:first-child small{display:block}
.cashier-tontine-queue .finance-table td:first-child small{margin-top:4px}
.cashier-tontine-queue .finance-table td form{margin:0;min-width:145px}
.cashier-tontine-queue .finance-table .btn{white-space:nowrap}
.cashier-notice{align-items:flex-start;border:1px solid #cfe6d3;border-radius:8px;line-height:1.5}
.cashier-notice .material-icons{flex:none;margin-top:1px}
.cashier-notice strong{display:inline}
@media(max-width:980px){.finance-page-heading{grid-template-columns:minmax(0,1fr)}.expense-head-actions{justify-content:flex-start;min-width:0}.cashier-assignment{grid-template-columns:minmax(0,1fr)}.cashier-assignment form{grid-template-columns:minmax(0,1fr) auto}}
@media(max-width:760px){.finance-page{padding:14px 12px 30px}.finance-page-heading{gap:14px}.expense-head-actions{display:grid;grid-template-columns:1fr 1fr;width:100%}.finance-page .expense-head-actions .btn{width:100%;min-width:0;padding-inline:10px}.cashier-assignment{padding:16px}.cashier-assignment form{grid-template-columns:1fr}.finance-page .cashier-assignment .btn{width:100%;white-space:normal}}
@media(max-width:760px){.cashier-workspace{grid-template-columns:1fr 1fr;gap:8px}.cashier-workspace-balance{grid-column:1/-1}.cashier-workspace>article{padding:13px}.cashier-workspace>article>strong{font-size:21px}.expense-list-heading{align-items:flex-start}.cashier-pay-queue .cashier-purchases-head{align-items:flex-start}}
@media(max-width:560px){.cashier-queue-tabs{display:grid;grid-template-columns:1fr}.cashier-queue-tabs a{justify-content:space-between}.cashier-tontine-queue .cashier-purchases-head{align-items:flex-start}}
@media(max-width:420px){.expense-head-actions{grid-template-columns:1fr}.cashier-workspace{grid-template-columns:1fr}.cashier-workspace-balance{grid-column:auto}.expense-list-heading{flex-direction:column}.cashier-notice{padding:12px}}
.receipt-confirm-modal{--rc-ink:var(--app-text,#202938);--rc-muted:var(--app-muted,#748093);--rc-line:var(--app-border,#e3e8ef);position:fixed;z-index:12000;inset:0;display:none;align-items:center;justify-content:center;padding:18px}.receipt-confirm-modal.is-open{display:flex}.receipt-confirm-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.62);backdrop-filter:blur(3px)}.receipt-confirm-panel{position:relative;width:min(100%,440px);padding:28px;background:var(--app-surface,#fff);border:1px solid var(--rc-line);border-radius:12px;box-shadow:0 24px 80px rgba(0,0,0,.25);text-align:center}.receipt-confirm-icon{display:grid;place-items:center;width:50px;height:50px;margin:0 auto 14px;border-radius:50%;background:#e8f5eb;color:#24733a;font-size:26px}.receipt-confirm-panel h2{margin:0;color:var(--rc-ink);font-size:20px;font-weight:800}.receipt-confirm-panel p{margin:8px 0 22px;color:var(--rc-muted);font-size:13px;line-height:1.55}.receipt-confirm-actions{display:flex;justify-content:center;gap:9px;flex-wrap:wrap}.receipt-confirm-actions button{min-height:42px;padding:9px 15px;border:1px solid var(--rc-line);border-radius:5px;font-size:12px;font-weight:750;cursor:pointer}.receipt-confirm-cancel{background:transparent;color:var(--rc-ink)}.receipt-confirm-submit{background:var(--app-primary,#811515);border-color:var(--app-primary,#811515)!important;color:#fff}.receipt-confirm-actions button:focus-visible{outline:3px solid rgba(129,21,21,.24);outline-offset:2px}html[data-app-theme="dark"] .receipt-confirm-panel{background:var(--app-surface)}
.cashier-purchase-table{min-width:990px}.cashier-purchase-table td:first-child{min-width:330px}.cashier-purchase-ref,.cashier-purchase-title,.cashier-purchase-items,.cashier-purchase-trace{display:block}.cashier-purchase-ref{margin-bottom:5px}.cashier-purchase-title{color:var(--app-text,#273142);font-weight:700;line-height:1.4}.cashier-purchase-items{margin-top:4px;color:var(--app-muted,#778397);line-height:1.45}.cashier-purchase-trace{margin-top:6px;font-size:11px;line-height:1.4}.cashier-purchase-assignee{min-width:170px;white-space:normal;line-height:1.45}.cashier-purchase-amount{white-space:nowrap}.cashier-purchase-remit{min-width:250px}.cashier-purchase-remit summary{display:inline-flex;align-items:center;justify-content:center;list-style:none;cursor:pointer}.cashier-purchase-remit summary::-webkit-details-marker{display:none}.cashier-purchase-remit[open] summary{margin-bottom:10px}.cashier-purchase-remit form{display:grid;gap:10px;min-width:260px;padding:14px;background:#faf8f8;border:1px solid #eadede;border-radius:8px}.cashier-purchase-remit label{display:grid;gap:5px;margin:0;color:var(--app-text,#273142);font-size:11px;font-weight:750;line-height:1.4}.cashier-purchase-remit .form-control{min-width:0;min-height:39px}.cashier-purchase-remit textarea.form-control{min-height:60px}.cashier-purchase-remit label small{color:var(--app-muted,#778397);font-weight:500;line-height:1.4}.cashier-purchase-remit form>.btn{min-height:41px;white-space:normal}
.cashier-purchase-confirm-actions{min-width:210px}.cashier-purchase-confirm-actions form{margin:0 0 8px!important;min-width:0!important}.cashier-purchase-confirm-actions form:last-child{margin-bottom:0!important}.cashier-purchase-confirm-actions .btn{width:100%;white-space:normal;line-height:1.35}.cashier-purchase-confirm-actions .receipt-state{white-space:normal;line-height:1.4}
</style>
<script>
(() => {
    const modal = document.getElementById('receipt-confirm-modal');
    if (!modal) return;
    let pendingForm = null;
    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    };
    document.querySelectorAll('form[data-receipt-confirm-form], form[data-cashier-confirm-form]').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault();
        pendingForm = form;
        document.getElementById('receipt-confirm-title').textContent = form.dataset.confirmTitle || 'Confirmer la réception du reçu ?';
        const amountField = form.elements.namedItem('amount');
        const formattedAmount = amountField?.value ? new Intl.NumberFormat('fr-FR').format(Number(amountField.value)) : '';
        const message = form.dataset.confirmMessage || `Confirme uniquement lorsque le reçu de ${form.dataset.requesterName || 'la personne demandeuse'} t’a bien été remis.`;
        document.getElementById('receipt-confirm-message').textContent = message.replace('{amount}', formattedAmount);
        document.getElementById('receipt-confirm-submit').textContent = form.dataset.confirmButton || 'Oui, confirmer la réception';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.querySelector('.receipt-confirm-cancel').focus();
    }));
    modal.querySelectorAll('[data-receipt-confirm-close]').forEach(button => button.addEventListener('click', close));
    document.getElementById('receipt-confirm-submit').addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        pendingForm = null;
        HTMLFormElement.prototype.submit.call(form);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
})();
</script>
@endsection
