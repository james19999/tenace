@extends('layouts.admin')
@section('content')
<main class="container-fluid fm-page">
    <header class="fm-heading"><div><a href="{{ route('finance.dashboard') }}">← Comptabilité</a><h1>Autres suivis financiers</h1><p>Suivi des sommes à recevoir, des dépenses prévues, du personnel et des achats de matériel confiés.</p></div></header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <nav class="fm-tabs" aria-label="Rubriques financières">
        <a class="{{ $tab==='receivables'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'receivables']) }}"><span class="material-icons">receipt_long</span>À encaisser <b>{{ number_format($receivableTotal,0,',',' ') }} F</b></a>
        <a class="{{ $tab==='planning'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'planning']) }}"><span class="material-icons">monitoring</span>Dépenses et argent à prévoir</a>
        @if(auth()->user()->isAdmin())<a class="{{ $tab==='team'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'team']) }}"><span class="material-icons">groups</span>Avances</a>@endif
        <a class="{{ $tab==='purchases'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'purchases']) }}"><span class="material-icons">shopping_bag</span>Achats de matériel</a>
    </nav>

    @if($tab==='receivables')
        <section class="fm-card"><div class="fm-section-head"><div><h2>Commandes pas encore livrées et pas entièrement payées</h2><p>À la livraison, la commande est considérée payée. Cette liste montre donc seulement l’argent qui reste à encaisser avant de terminer la commande. Pour encaisser, ouvre la commande dans le parcours habituel.</p></div><form class="fm-search" method="GET" action="{{ route('finance.management.index') }}"><input type="hidden" name="tab" value="receivables"><label class="sr-only" for="fm-customer-search">Rechercher une commande, un client ou un téléphone</label><input id="fm-customer-search" name="q" value="{{ $receivableSearch }}" class="form-control" placeholder="Numéro, nom ou téléphone"><button class="btn btn-primary" type="submit">Rechercher</button></form></div>
            <div class="table-responsive"><table class="table fm-table"><thead><tr><th>Commande</th><th>Client</th><th>Créée le</th><th>En attente depuis</th><th>Montant total</th><th>Déjà payé</th><th>Reste à encaisser</th></tr></thead><tbody>
                @forelse($receivables as $order)<tr><td><strong>{{ $order->code }}</strong></td><td>{{ $order->costumer?->name ?? 'Client non renseigné' }}<small>{{ $order->costumer?->phone ?? 'Téléphone non renseigné' }}</small></td><td>{{ $order->created_at?->format('d/m/Y') }}</td><td>{{ $order->balance_age_days }} jour(s)</td><td>{{ number_format($order->receivable_total,0,',',' ') }} F</td><td>{{ number_format($order->receivable_paid,0,',',' ') }} F</td><td><strong class="fm-due">{{ number_format($order->balance_due,0,',',' ') }} F</strong></td></tr>
                @empty<tr><td colspan="7" class="fm-empty">Aucune commande en attente de paiement pour cette recherche.</td></tr>@endforelse
            </tbody></table></div>
            <div class="fm-pagination">{{ $receivables->onEachSide(1)->links() }}</div>
            <p class="fm-footnote">Les commandes livrées et les commandes annulées ne figurent pas ici. Cette liste sert au suivi ; elle ne crée pas de paiement et ne remplace pas la validation de livraison.</p>
        </section>
    @elseif($tab==='planning')
        <section class="fm-kpis"><article><span>Total des limites de dépenses</span><strong>{{ number_format($budgetTotal,0,',',' ') }} F</strong></article><article><span>Dépenses déjà payées</span><strong>{{ number_format($budgetActualTotal,0,',',' ') }} F</strong></article><article><span>Argent actuellement dans la caisse générale</span><strong>{{ number_format($forecast['opening_balance'],0,',',' ') }} F</strong></article><article><span>Entrées habituelles estimées par semaine</span><strong>{{ number_format($forecast['average_inflow'],0,',',' ') }} F</strong></article></section>
        <section class="fm-card"><div class="fm-section-head"><div><h2>Fixer une limite de dépense</h2><p>Choisis une catégorie, une boutique et une période. Le tableau montre ensuite la limite, ce qui a été payé et ce qu’il reste.</p></div></div>
            <form class="fm-form-grid" method="POST" action="{{ route('finance.management.budgets.store') }}">@csrf
                <label>Catégorie<select class="form-control" name="category_name" required><option value="">Choisir une catégorie</option>@foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></label>
                <label>Boutique concernée<select class="form-control" name="stock_location_id"><option value="">Toutes / dépense centrale</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></label>
                <label>Début de période<input class="form-control" type="date" name="period_start" value="{{ now()->startOfMonth()->toDateString() }}" required></label><label>Fin de période<input class="form-control" type="date" name="period_end" value="{{ now()->endOfMonth()->toDateString() }}" required></label>
                <label>Montant prévu (F CFA)<input class="form-control" type="number" name="amount" min="1" step="1" required></label><label>Note (facultative)<input class="form-control" name="note" maxlength="500" placeholder="Ex. entretien boutique"></label><div class="fm-form-action"><button class="btn btn-primary">Enregistrer le budget</button></div>
            </form>
            <div class="table-responsive"><table class="table fm-table"><thead><tr><th>Catégorie</th><th>Boutique</th><th>Période</th><th>Budget</th><th>Payé</th><th>Disponible</th></tr></thead><tbody>@forelse($budgets as $budget)<tr><td><strong>{{ $budget->category_name }}</strong>@if($budget->note)<small>{{ $budget->note }}</small>@endif</td><td>{{ $budget->location?->name ?? 'Central / toutes' }}</td><td>{{ $budget->period_start->format('d/m/Y') }} – {{ $budget->period_end->format('d/m/Y') }}</td><td>{{ number_format($budget->amount,0,',',' ') }} F</td><td>{{ number_format($budget->actual_amount,0,',',' ') }} F</td><td><strong class="{{ $budget->actual_amount > $budget->amount ? 'fm-over' : '' }}">{{ number_format($budget->amount-$budget->actual_amount,0,',',' ') }} F</strong></td></tr>@empty<tr><td colspan="6" class="fm-empty">Aucun budget défini pour la période en cours.</td></tr>@endforelse</tbody></table></div>
            <div class="fm-pagination">{{ $budgets->onEachSide(1)->links() }}</div>
        </section>
        <section class="fm-card"><div class="fm-section-head"><div><h2>Estimation de l’argent disponible dans les 13 prochaines semaines</h2><p>Le solde projeté tient compte des dépenses, des prêts à remettre et des factures fournisseurs. Les prêts pèsent sur la trésorerie, mais ne sont pas comptés comme dépenses.</p></div></div>
            <div class="table-responsive"><table class="table fm-table"><thead><tr><th>Semaine</th><th>Entrées estimées</th><th>Factures fournisseurs dues</th><th>Dépenses prévues</th><th>Prêts à remettre</th><th>Solde projeté</th></tr></thead><tbody>@foreach($forecast['weeks'] as $week)<tr><td>{{ $week['start']->format('d/m') }} – {{ $week['end']->format('d/m/Y') }}</td><td>+{{ number_format($week['inflow'],0,',',' ') }} F</td><td>{{ number_format($week['supplier_due'],0,',',' ') }} F</td><td>{{ number_format($week['expense_due'],0,',',' ') }} F</td><td>{{ number_format($week['loan_due'],0,',',' ') }} F</td><td><strong class="{{ $week['projected_balance'] < 0 ? 'fm-over' : '' }}">{{ number_format($week['projected_balance'],0,',',' ') }} F</strong></td></tr>@endforeach</tbody></table></div>
            <p class="fm-footnote">Cette projection sert au pilotage. Elle ne remplace pas un plan de trésorerie validé et ne constitue pas une écriture comptable.</p>
        </section>
    @elseif($tab==='team')
        <nav class="fm-team-tabs" aria-label="Suivi des avances et prêts">
            @if(auth()->user()->isAdmin())
                <a class="{{ $teamView==='requests'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'team','team_view'=>'requests']) }}">Demandes à examiner <b>{{ $pendingEmployeeAdvances->total() }}</b></a>
                <a class="{{ $teamView==='disburse'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'team','team_view'=>'disburse']) }}">À remettre <b>{{ $loansToDisburse->total() }}</b></a>
            @endif
            <a class="{{ $teamView==='loans'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'team','team_view'=>'loans']) }}">Dossiers avances et prêts</a>
            <a class="{{ $teamView==='create'?'is-active':'' }}" href="{{ route('finance.management.index',['tab'=>'team','team_view'=>'create']) }}">{{ auth()->user()->isAdmin() ? 'Enregistrer une avance' : 'Demander une avance' }}</a>
        </nav>
        @if($teamView==='requests' || $teamView==='disburse')
            <section class="fm-card fm-scope-note"><strong>Demandes et remise des fonds</strong><p>Un prêt est suivi séparément des dépenses. Après validation, enregistre sa remise réelle dans le dossier pour commencer son suivi.</p></section>
        @endif
        @if(auth()->user()->isAdmin())
        @if($teamView==='requests')
        <section class="fm-card">
            <div class="fm-section-head"><div><h2>Demandes à examiner</h2><p>Vérifie le montant et le motif. Pour un prêt, définis aussi la retenue mensuelle.</p></div><span class="fm-count">{{ $pendingEmployeeAdvances->total() }} en attente</span></div>
            <div class="fm-advance-list">
                @forelse($pendingEmployeeAdvances as $request)
                    <article class="fm-advance-card">
                        <div class="fm-advance-heading"><div><span class="fm-advance-kind">{{ $request->staff_advance_type === 'loan' ? 'Prêt à rembourser' : 'Avance sur salaire' }}</span><h3>{{ $request->staffMember?->name ?? $request->requester?->name }}</h3></div><strong class="fm-advance-amount">{{ number_format($request->amount,0,',',' ') }} F</strong></div>
                        <div class="fm-advance-meta"><span>Demandée le {{ $request->submitted_at?->format('d/m/Y') ?? '—' }}</span>@if($request->justification_note)<p>{{ $request->justification_note }}</p>@endif</div>
                        <div class="fm-advance-actions">
                            <form class="fm-loan-approval" method="POST" action="{{ route('finance.management.employee-advances.decide',$request) }}">@csrf<input type="hidden" name="decision" value="approved">
                                @if($request->staff_advance_type === 'loan')
                                    <label>Retenue mensuelle<select class="form-control js-loan-method" name="repayment_method" required><option value="fixed_amount" @selected($request->staff_repayment_method === 'fixed_amount')>Montant fixe · F CFA</option><option value="salary_percent" @selected($request->staff_repayment_method === 'salary_percent')>Pourcentage du salaire</option></select></label>
                                    <label><span class="js-loan-value-label">Somme fixe à retenir par mois (F CFA)</span><input class="form-control js-loan-value" type="number" name="repayment_value" min="1" max="1000000000" step="1" value="{{ old('repayment_value', $request->staff_repayment_value) }}" placeholder="Ex. 10000" required><small class="js-loan-value-help">Cette somme sera retenue automatiquement sur le salaire chaque mois.</small></label>
                                @endif
                                <button class="btn btn-primary">Accepter la demande</button>
                            </form>
                            <form class="fm-reject-form" method="POST" action="{{ route('finance.management.employee-advances.decide',$request) }}">@csrf<input type="hidden" name="decision" value="rejected"><label>Motif du refus<input class="form-control" name="comment" placeholder="Ex. montant à préciser" aria-label="Motif du refus" required></label><button class="btn btn-outline-primary">Refuser</button></form>
                        </div>
                    </article>
                @empty
                    <div class="fm-team-empty"><span class="material-icons">task_alt</span><strong>Aucune demande à examiner</strong><p>Les nouvelles demandes des employés apparaîtront ici.</p></div>
                @endforelse
            </div>
            <div class="fm-pagination">{{ $pendingEmployeeAdvances->onEachSide(1)->links() }}</div>
        </section>
        @endif
        @endif
        @if($teamView==='disburse' && auth()->user()->isAdmin())
        <section class="fm-card">
            <div class="fm-section-head"><div><h2>Prêts autorisés à remettre</h2><p>Le prêt ne commence qu’après enregistrement de la remise effective de l’argent.</p></div><span class="fm-count">{{ $loansToDisburse->total() }} à remettre</span></div>
            <div class="fm-advance-list">
                @forelse($loansToDisburse as $loanRequest)
                    <article class="fm-advance-card fm-disbursement-card"><div class="fm-advance-heading"><div><span class="fm-advance-kind">{{ $loanRequest->reference }}</span><h3>{{ $loanRequest->staffMember?->name ?? $loanRequest->beneficiary }}</h3></div><strong class="fm-advance-amount">{{ number_format(max(0,$loanRequest->amount-$loanRequest->paid_amount),0,',',' ') }} F</strong></div><div class="fm-advance-meta"><span>À remettre</span><span>Retenue : {{ $loanRequest->staff_repayment_method === 'salary_percent' ? number_format((float)$loanRequest->staff_repayment_value,2,',',' ').' % du salaire' : ($loanRequest->staff_repayment_value ? number_format((int)$loanRequest->staff_repayment_value,0,',',' ').' F / mois' : 'À définir') }}</span></div><a class="btn btn-outline-primary" href="{{ route('finance.expenses.show',$loanRequest) }}">Ouvrir le dossier et enregistrer la remise</a></article>
                @empty
                    <div class="fm-team-empty"><span class="material-icons">payments</span><strong>Aucun prêt à remettre</strong><p>Les prêts acceptés apparaîtront ici jusqu’à l’enregistrement de leur paiement.</p></div>
                @endforelse
            </div>
            <div class="fm-pagination">{{ $loansToDisburse->onEachSide(1)->links() }}</div>
        </section>
        @endif
        @if($teamView==='create')
        <section class="fm-card"><div class="fm-section-head"><div><h2>{{ auth()->user()->isAdmin() ? 'Enregistrer une avance à un employé' : 'Demander une avance à un employé' }}</h2><p>Un prêt est remboursé chaque mois par un montant fixe ou un pourcentage du salaire. @if(auth()->user()->isAdmin())Le DG l’autorise directement ici ; enregistre le décaissement lors de la remise.@elseLa demande est transmise au DG pour décision.@endif</p></div></div>
            <form class="fm-form-grid" method="POST" action="{{ route('finance.management.advances.store') }}">@csrf
                <label>Employé<select class="form-control" name="user_id" required><option value="">Choisir une personne</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></label>
                <label>Type d’avance<select class="form-control" name="advance_type" id="staff-advance-type" required><option value="loan">Prêt à rembourser séparément</option><option value="salary_advance">Avance à déduire d’un salaire</option></select></label>
                <label>Montant de l’avance (F CFA)<input class="form-control" type="number" name="amount" min="1" required></label>
                <label id="staff-loan-plan-field">Remboursement mensuel<select class="form-control" name="repayment_method" id="staff-repayment-method"><option value="fixed_amount">Montant fixe · F CFA</option><option value="salary_percent">Pourcentage du salaire</option></select></label>
                <label id="staff-loan-value-field"><span id="staff-loan-value-label">Somme fixe à retenir par mois (F CFA)</span><input class="form-control" type="number" name="repayment_value" min="1" max="1000000000" step="1" placeholder="Ex. 10000" required><small id="staff-loan-value-help">Cette somme sera retenue automatiquement sur le salaire chaque mois jusqu’au remboursement complet.</small></label>
                <label id="staff-advance-period-field" hidden><span>Mois de salaire à réduire</span><select class="form-control" name="payroll_period_id" id="staff-advance-period"><option value="">Choisir d’abord un employé</option>@foreach($salaryPeriods as $period)<option value="{{ $period->id }}" data-user-id="{{ $period->user_id }}">{{ $period->period_start->translatedFormat('F Y') }}</option>@endforeach</select><small id="staff-advance-period-help">L’avance sera déduite du salaire de la personne pour ce mois.</small></label>
                <label>Date prévue<input class="form-control" type="date" name="granted_on" value="{{ today()->toDateString() }}" required></label><label>Motif<textarea class="form-control" name="reason" rows="2" required maxlength="2000"></textarea></label><div class="fm-form-action"><button class="btn btn-primary">{{ auth()->user()->isAdmin() ? 'Autoriser l’avance' : 'Soumettre à validation' }}</button></div>
            </form>
            <script>
                (function () {
                    var type = document.getElementById('staff-advance-type');
                    var field = document.getElementById('staff-advance-period-field');
                    var employee = document.querySelector('select[name="user_id"]');
                    var loanPlan = document.getElementById('staff-loan-plan-field');
                    var loanValue = document.getElementById('staff-loan-value-field');
                    if (!type || !field || !employee || !loanPlan || !loanValue) return;
                    var period = document.getElementById('staff-advance-period');
                    var periodHelp = document.getElementById('staff-advance-period-help');
                    var method = document.getElementById('staff-repayment-method');
                    var value = loanValue.querySelector('input');
                    var help = document.getElementById('staff-loan-value-help');
                    var valueLabel = document.getElementById('staff-loan-value-label');
                    var update = function () {
                        field.hidden = type.value !== 'salary_advance';
                        loanPlan.hidden = type.value !== 'loan';
                        loanValue.hidden = type.value !== 'loan';
                        if (period) period.required = !field.hidden;
                        if (value) value.required = type.value === 'loan';
                        if (method && value) {
                            var percent = method.value === 'salary_percent';
                            value.max = percent ? '100' : '1000000000';
                            value.step = percent ? '0.01' : '1';
                            value.min = percent ? '0.01' : '1';
                            value.placeholder = percent ? 'Ex. 10' : 'Ex. 10000';
                            if (valueLabel) valueLabel.textContent = percent ? 'Pourcentage du salaire à retenir chaque mois (%)' : 'Somme fixe à retenir par mois (F CFA)';
                            if (help) help.textContent = percent ? 'Ce pourcentage du salaire sera retenu automatiquement chaque mois jusqu’au remboursement complet.' : 'Cette somme sera retenue automatiquement sur le salaire chaque mois jusqu’au remboursement complet.';
                        }
                    };
                    var updatePeriods = function () {
                        if (!period) return;
                        var employeeId = employee.value;
                        var available = 0;
                        Array.from(period.options).slice(1).forEach(function (option) {
                            var matches = employeeId !== '' && option.dataset.userId === employeeId;
                            option.hidden = !matches;
                            option.disabled = !matches;
                            if (matches) available++;
                        });
                        var selected = period.selectedOptions[0];
                        if (selected && selected.dataset.userId && selected.dataset.userId !== employeeId) period.value = '';
                        period.options[0].textContent = !employeeId ? 'Choisir d’abord un employé' : (available ? 'Choisir le mois de salaire' : 'Aucun mois de salaire disponible');
                        period.disabled = !employeeId || available === 0;
                        if (periodHelp) periodHelp.textContent = available
                            ? 'L’avance sera déduite du salaire de la personne sélectionnée pour ce mois.'
                            : 'Aucun mois de paie n’est encore ouvert pour cette personne. Demande à l’administration de préparer sa paie.';
                    };
                    type.addEventListener('change', update);
                    employee.addEventListener('change', updatePeriods);
                    if (method) method.addEventListener('change', update);
                    updatePeriods();
                    update();
                })();
            </script>
        </section>
        @endif
        @if($teamView==='loans')
        <section class="fm-card">
            <div class="fm-section-head"><div><h2>Employés avec une avance ou un prêt</h2><p>Recherche un nom pour afficher son dossier et consulter les montants ainsi que les remboursements éventuels.</p></div></div>
            <form class="fm-loan-search-form" method="GET" action="{{ route('finance.management.index') }}" autocomplete="off">
                <input type="hidden" name="tab" value="team"><input type="hidden" name="team_view" value="loans">
                <label class="fm-loan-search-label" for="fm-loan-search">Rechercher un employé</label>
                <input id="fm-loan-search" name="advance_search" value="{{ $advanceSearch }}" class="form-control fm-loan-search" type="search" placeholder="Commence à taper un nom…" autocomplete="off">
            </form>
            <div class="fm-employee-loan-list" id="fm-employee-loan-list">
                @forelse($advances->getCollection()->groupBy('user_id') as $employeeAdvances)
                    @php
                        $loanEmployee = $employeeAdvances->first()->employee;
                        $employeeLoanBalance = $employeeAdvances->where('advance_type', 'loan')->sum(fn ($item) => max(0, (int) $item->amount - (int) $item->repaid_amount));
                        $employeeSearchName = mb_strtolower($loanEmployee?->name ?? 'Employé');
                    @endphp
                    <details class="fm-employee-loan" data-employee-name="{{ $employeeSearchName }}">
                        <summary><span><strong>{{ $loanEmployee?->name ?? 'Employé' }}</strong><small>{{ $employeeAdvances->count() }} dossier{{ $employeeAdvances->count() > 1 ? 's' : '' }}</small></span>@if($employeeLoanBalance > 0)<b>{{ number_format($employeeLoanBalance,0,',',' ') }} F à rembourser</b>@else<b>Avance sur salaire</b>@endif</summary>
                        <div class="fm-employee-loan-dossiers">
                            @foreach($employeeAdvances as $advance)
                                @php
                                    $remaining = max(0, (int) $advance->amount - (int) $advance->repaid_amount);
                                    $isLoan = $advance->advance_type === 'loan';
                                    $planLabel = $advance->repayment_method === 'salary_percent'
                                        ? number_format((float) $advance->repayment_value, 2, ',', ' ').' % du salaire'
                                        : ($advance->repayment_value ? number_format((int) $advance->repayment_value, 0, ',', ' ').' F / mois' : 'À définir');
                                @endphp
                                <article class="fm-advance-card fm-recorded-advance">
                                    <div class="fm-advance-heading"><div><span class="fm-advance-kind">{{ $advance->reference }} · {{ $advance->granted_on->format('d/m/Y') }}</span><h3>{{ $isLoan ? 'Prêt' : 'Avance sur salaire' }} · {{ number_format($advance->amount,0,',',' ') }} F</h3></div><span class="fm-tag {{ $isLoan ? ($remaining > 0 ? 'fm-tag-open' : 'fm-tag-closed') : 'fm-tag-closed' }}">{{ $isLoan ? ($remaining > 0 ? 'Remboursement en cours' : 'Soldé') : 'À déduire de la paie' }}</span></div>
                                    @if($isLoan)<div class="fm-advance-facts"><div><span>Déjà remboursé</span><strong>{{ number_format($advance->repaid_amount,0,',',' ') }} F</strong></div><div><span>Reste à rembourser</span><strong>{{ number_format($remaining,0,',',' ') }} F</strong></div><div><span>Retenue mensuelle</span><strong>{{ $planLabel }}</strong></div></div>@else<div class="fm-advance-facts"><div><span>Mois de paie concerné</span><strong>{{ $advance->payrollPeriod?->period_start->translatedFormat('F Y') ?? 'Non défini' }}</strong></div><div><span>Suivi</span><strong>Déduite du salaire du mois indiqué</strong></div></div>@endif
                                    @if($advance->reason)<p class="fm-advance-reason">{{ $advance->reason }}</p>@endif
                                    @if($advance->repayments->isNotEmpty())<details class="fm-repayment-history"><summary>Historique des remboursements ({{ $advance->repayments->count() }})</summary><ul>@foreach($advance->repayments as $repayment)<li><span>{{ $repayment->employee_payroll_payment_id ? 'Retenue sur salaire' : 'Remboursement reçu' }} · {{ $repayment->received_at->format('d/m/Y') }}</span><strong>{{ number_format($repayment->amount,0,',',' ') }} F</strong></li>@endforeach</ul></details>@endif
                                    @if($isLoan && $remaining > 0 && auth()->user()->isAdmin())
                                        <div class="fm-advance-admin-actions">
                                            @if(!$advance->repayment_method)<form class="fm-loan-plan-form" method="POST" action="{{ route('finance.management.advances.plan.update',$advance) }}">@csrf @method('PUT')<strong>Définir la retenue mensuelle</strong><div><select class="form-control js-loan-method" name="repayment_method"><option value="fixed_amount">Montant fixe</option><option value="salary_percent">Pourcentage du salaire</option></select><input class="form-control js-loan-value" type="number" name="repayment_value" min="1" max="1000000000" step="1" placeholder="Montant / %" required><button class="btn btn-outline-primary">Enregistrer</button></div></form>@endif
                                            <details class="fm-early-repayment">
                                                <summary>Enregistrer un remboursement anticipé</summary>
                                                <p>Facultatif : utilise cette option seulement si l’employé verse une somme avant la fin du prêt. Les retenues mensuelles sur salaire sont déjà enregistrées automatiquement.</p>
                                                <form class="fm-inline-form" method="POST" action="{{ route('finance.management.advances.repay',$advance) }}">@csrf<label>Montant réellement reçu (F CFA)<input class="form-control" type="number" name="amount" min="1" max="{{ $remaining }}" placeholder="Montant versé" required></label><input type="hidden" name="received_at" value="{{ now()->format('Y-m-d\TH:i') }}"><button class="btn btn-outline-primary">Enregistrer le versement</button></form>
                                            </details>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <div class="fm-team-empty"><span class="material-icons">account_balance_wallet</span><strong>{{ $advanceSearch ? 'Aucun employé ne correspond à cette recherche' : 'Aucun employé n’a de prêt enregistré' }}</strong><p>{{ $advanceSearch ? 'Essaie avec un autre nom.' : 'Les dossiers apparaîtront après la remise effective d’un prêt.' }}</p></div>
                @endforelse
            </div>
            <p class="fm-loan-no-results" id="fm-loan-no-results" hidden>Aucun employé ne correspond à cette recherche.</p>
            <div class="fm-pagination">{{ $advances->onEachSide(1)->links() }}</div>
            <script>
                (function () {
                    var input = document.getElementById('fm-loan-search');
                    var list = document.getElementById('fm-employee-loan-list');
                    var empty = document.getElementById('fm-loan-no-results');
                    if (!input || !list) return;
                    var timer;
                    var applyFilter = function () {
                        var normalize = function (value) {
                            return value.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        };
                        var query = normalize(input.value.trim());
                        var visible = 0;
                        list.querySelectorAll('.fm-employee-loan').forEach(function (item) {
                            var matches = !query || normalize(item.dataset.employeeName).includes(query);
                            item.hidden = !matches;
                            if (matches) {
                                visible++;
                                item.open = Boolean(query);
                            }
                        });
                        if (empty) empty.hidden = visible > 0;
                    };
                    input.addEventListener('input', function () {
                        applyFilter();
                        window.clearTimeout(timer);
                        var query = input.value.trim();
                        if (query.length === 0 || query.length >= 2) {
                            timer = window.setTimeout(function () { input.form.requestSubmit(); }, 350);
                        }
                    });
                    applyFilter();
                })();
            </script>
        </section>
        @endif
        <script>
            document.querySelectorAll('.js-loan-method').forEach(function (select) {
                var value = select.closest('form')?.querySelector('.js-loan-value');
                if (!value) return;
                var update = function () {
                    var percent = select.value === 'salary_percent';
                    value.min = percent ? '0.01' : '1';
                    value.max = percent ? '100' : '1000000000';
                    value.step = percent ? '0.01' : '1';
                    value.placeholder = percent ? 'Ex. 10' : 'Ex. 10000';
                    var label = select.closest('form').querySelector('.js-loan-value-label');
                    var help = select.closest('form').querySelector('.js-loan-value-help');
                    if (label) label.textContent = percent ? 'Pourcentage du salaire à retenir chaque mois (%)' : 'Somme fixe à retenir par mois (F CFA)';
                    if (help) help.textContent = percent ? 'Ce pourcentage du salaire sera retenu automatiquement chaque mois jusqu’au remboursement complet.' : 'Cette somme sera retenue automatiquement sur le salaire chaque mois jusqu’au remboursement complet.';
                };
                select.addEventListener('change', update);
                update();
            });
        </script>
    @else
        @if(auth()->user()->isAdmin())
        <section class="fm-card"><div class="fm-section-head"><div><h2>Désigner le responsable des achats de matériel</h2><p>Cette désignation se fait une fois. Ensuite, cette personne crée elle-même chaque demande depuis son onglet « Achats de matériel » ; tu interviens pour valider le montant, remettre l’argent et contrôler les reçus.</p></div></div>
            @if($purchaseSetting?->assignee)<p class="fm-current-assignee">Responsable actuel : <strong>{{ $purchaseSetting->assignee->name }}</strong></p>@endif
            <form class="fm-form-grid" method="POST" action="{{ route('finance.management.purchases.responsible') }}">@csrf
                <label>Personne responsable<select class="form-control" name="assigned_to" required><option value="">Choisir un employé</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int)($purchaseSetting?->assignee_user_id ?? 0)===(int)$employee->id)>{{ $employee->name }}</option>@endforeach</select></label>
                <div class="fm-form-action"><button class="btn btn-primary">{{ $purchaseSetting?->assignee ? 'Enregistrer le responsable' : 'Désigner le responsable' }}</button></div>
            </form>
        </section>
        @endif
        <section class="fm-card fm-purchases-card"><div class="fm-section-head"><div><h2>Suivi des demandes d’achat</h2><p>Le DG autorise le montant, puis le responsable de la caisse générale remet les fonds. Tu peux suivre les reçus et confirmer le retour du reliquat.</p></div><span class="fm-purchase-total">{{ $purchaseRequests->total() }} dossier{{ $purchaseRequests->total() === 1 ? '' : 's' }}</span></div>
            <div class="fm-purchase-list">
                @forelse($purchaseRequests as $purchaseRequestItem)
                    @php
                        $purchaseStates = [
                            'assigned' => ['À compléter par le responsable', 'À préparer', 'pending'],
                            'awaiting_director' => ['Décision du DG requise', 'À valider', 'action'],
                            'authorized_to_pay' => ['DG autorisé · remise par le caissier', 'À remettre', 'action'],
                            'funds_issued' => ['Fonds remis · achat en cours', 'En cours', 'progress'],
                            'submitted_for_review' => [!$purchaseRequestItem->receipt_received_at ? 'En attente du caissier pour les reçus' : (((int) $purchaseRequestItem->returned_amount > 0 && !$purchaseRequestItem->returned_received_at) ? 'En attente du caissier pour le reliquat' : 'Reçus et reliquat reçus · contrôle DG'), (!$purchaseRequestItem->receipt_received_at || ((int) $purchaseRequestItem->returned_amount > 0 && !$purchaseRequestItem->returned_received_at)) ? 'À réceptionner' : 'À contrôler', (!$purchaseRequestItem->receipt_received_at || ((int) $purchaseRequestItem->returned_amount > 0 && !$purchaseRequestItem->returned_received_at)) ? 'action' : 'review'],
                            'closed' => ['Achat contrôlé et clôturé', 'Clôturé', 'done'],
                        ];
                        $purchaseState = $purchaseStates[$purchaseRequestItem->status] ?? ['Demande à suivre', 'En cours', 'pending'];
                    @endphp
                    <article class="fm-purchase-item">
                        <div class="fm-purchase-top"><div><span class="fm-purchase-ref">{{ $purchaseRequestItem->reference }}</span><h3>{{ $purchaseRequestItem->title }}</h3><p>Responsable : <strong>{{ $purchaseRequestItem->assignee?->name ?? 'Non renseigné' }}</strong>@if($purchaseRequestItem->submitted_at) · demande du {{ $purchaseRequestItem->submitted_at->format('d/m/Y à H:i') }}@endif</p></div><span class="fm-purchase-state state-{{ $purchaseState[2] }}">{{ $purchaseState[1] }}<small>{{ $purchaseState[0] }}</small></span></div>
                        <div class="fm-purchase-facts">
                            <div class="fm-purchase-fact fm-purchase-need"><span>Articles demandés</span><p>{{ $purchaseRequestItem->needed_items ?: 'La liste des articles reste à compléter.' }}</p></div>
                            <div class="fm-purchase-fact"><span>Montant demandé</span><strong>{{ $purchaseRequestItem->requested_amount ? number_format($purchaseRequestItem->requested_amount,0,',',' ').' F' : '—' }}</strong></div>
                            <div class="fm-purchase-fact"><span>Argent remis</span><strong>{{ number_format($purchaseRequestItem->cash_issued,0,',',' ') }} F</strong></div>
                            @if($purchaseRequestItem->spent_amount !== null)<div class="fm-purchase-fact"><span>Dépensé</span><strong>{{ number_format($purchaseRequestItem->spent_amount,0,',',' ') }} F</strong></div><div class="fm-purchase-fact"><span>Reliquat déclaré</span><strong>{{ number_format($purchaseRequestItem->returned_amount,0,',',' ') }} F</strong></div>@endif
                        </div>
                        <div class="fm-purchase-trace" aria-label="Traçabilité du dossier">
                            <span>Demande créée par <strong>{{ $purchaseRequestItem->creator?->name ?? $purchaseRequestItem->assignee?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->created_at?->format('d/m/Y à H:i') ?? '—' }}</span>
                            @if($purchaseRequestItem->approved_at)<span>Validée par <strong>{{ $purchaseRequestItem->approver?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->approved_at->format('d/m/Y à H:i') }} · montant : {{ number_format((int) $purchaseRequestItem->approved_amount,0,',',' ') }} F</span>@endif
                            @if($purchaseRequestItem->issued_at)<span>Fonds remis par <strong>{{ $purchaseRequestItem->issuer?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->issued_at->format('d/m/Y à H:i') }} · {{ number_format((int) $purchaseRequestItem->cash_issued,0,',',' ') }} F</span>@endif
                            @if($purchaseRequestItem->receipt_paths)<span>Reçus transmis par <strong>{{ $purchaseRequestItem->assignee?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->report_submitted_at?->format('d/m/Y à H:i') ?? '—' }}</span>@endif
                            @if($purchaseRequestItem->receipt_received_at)<span>Réception des reçus confirmée par <strong>{{ $purchaseRequestItem->receiptReceiver?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->receipt_received_at->format('d/m/Y à H:i') }}</span>@elseif($purchaseRequestItem->receipt_paths)<span class="fm-purchase-trace-pending">Réception des reçus en attente de confirmation par le caissier général.</span>@endif
                            @if((int) $purchaseRequestItem->returned_amount > 0 && $purchaseRequestItem->returned_received_at)<span>Reliquat de {{ number_format((int) $purchaseRequestItem->returned_amount,0,',',' ') }} F reçu dans la caisse par <strong>{{ $purchaseRequestItem->returnedReceiver?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->returned_received_at->format('d/m/Y à H:i') }}</span>@elseif((int) $purchaseRequestItem->returned_amount > 0)<span class="fm-purchase-trace-pending">Reliquat de {{ number_format((int) $purchaseRequestItem->returned_amount,0,',',' ') }} F déclaré · en attente de confirmation du caissier.</span>@endif
                            @if($purchaseRequestItem->reviewed_at)<span>Contrôle final par <strong>{{ $purchaseRequestItem->reviewer?->name ?? '—' }}</strong> · {{ $purchaseRequestItem->reviewed_at->format('d/m/Y à H:i') }}</span>@endif
                        </div>
                        @if($purchaseRequestItem->assignment_notes)<div class="fm-purchase-message"><strong>Consignes</strong><p>{{ $purchaseRequestItem->assignment_notes }}</p></div>@endif
                        @if($purchaseRequestItem->report_note)<div class="fm-purchase-message"><strong>Compte rendu du responsable</strong><p>{{ $purchaseRequestItem->report_note }}</p></div>@endif
                        @if($purchaseRequestItem->review_note)<div class="fm-purchase-message"><strong>Dernier retour du DG</strong><p>{{ $purchaseRequestItem->review_note }}</p></div>@endif
                        @if($purchaseRequestItem->receipt_paths)<div class="fm-purchase-receipts"><strong>Reçus transmis</strong>@foreach($purchaseRequestItem->receipt_paths as $i=>$path)<a href="{{ route('finance.management.purchases.receipt',[$purchaseRequestItem,$i]) }}" target="_blank" rel="noopener"><span class="material-icons">description</span> Reçu {{ $loop->iteration }}</a>@endforeach</div>@endif
                        @if($purchaseRequestItem->status==='awaiting_director' && auth()->user()->isAdmin())
                            <form class="fm-purchase-action fm-purchase-approve" method="POST" action="{{ route('finance.management.purchases.authorize',$purchaseRequestItem) }}">@csrf<div><strong>Autoriser le montant demandé</strong><small>Le responsable unique de la caisse générale remettra ensuite les fonds.</small></div><label>Montant autorisé (F CFA)<input class="form-control" type="number" name="approved_amount" min="1" max="{{ $purchaseRequestItem->requested_amount }}" value="{{ $purchaseRequestItem->requested_amount }}" required></label><button class="btn btn-primary">Autoriser les fonds</button></form>
                        @endif
                        @if($purchaseRequestItem->status==='submitted_for_review' && auth()->user()->isAdmin() && $purchaseRequestItem->receipt_received_at && ((int) $purchaseRequestItem->returned_amount === 0 || $purchaseRequestItem->returned_received_at))
                            <div class="fm-purchase-action fm-purchase-review"><div class="fm-purchase-review-heading"><strong>Contrôle final</strong><small>Le caissier a confirmé les reçus et, s’il y en avait un, le reliquat a déjà été inscrit dans la caisse générale.</small></div><form method="POST" action="{{ route('finance.management.purchases.review',$purchaseRequestItem) }}">@csrf<input type="hidden" name="decision" value="accepted"><button class="btn btn-primary"><span class="material-icons">task_alt</span> Confirmer et clôturer</button></form><form class="fm-purchase-correction" method="POST" action="{{ route('finance.management.purchases.review',$purchaseRequestItem) }}">@csrf<input type="hidden" name="decision" value="correction"><input class="form-control" name="review_note" placeholder="Précision à demander au responsable" aria-label="Précision à demander au responsable" required><button class="btn btn-outline-primary">Demander un complément</button></form></div>
                        @elseif($purchaseRequestItem->status==='submitted_for_review' && auth()->user()->isAdmin())
                            <div class="fm-purchase-action fm-purchase-trace-pending"><strong>Contrôle final en attente</strong><small>@if(!$purchaseRequestItem->receipt_received_at)Le caissier général doit d’abord confirmer qu’il a reçu les reçus.@endif @if((int) $purchaseRequestItem->returned_amount > 0 && !$purchaseRequestItem->returned_received_at)Le caissier général doit confirmer qu’il a reçu le reliquat de {{ number_format((int) $purchaseRequestItem->returned_amount,0,',',' ') }} F avant qu’il soit ajouté à la caisse.@endif</small></div>
                        @endif
                    </article>
                @empty
                    <div class="fm-purchase-empty"><span class="material-icons">inventory_2</span><strong>Aucune demande d’achat pour le moment</strong><p>Les demandes envoyées par le responsable désigné apparaîtront ici.</p></div>
                @endforelse
            </div>
            <div class="fm-pagination">{{ $purchaseRequests->onEachSide(1)->links() }}</div>
        </section>
    @endif
</main>
<style>
.fm-page{max-width:1500px;padding:clamp(12px,2vw,24px) clamp(10px,2.2vw,28px) 44px;color:var(--app-text)}.fm-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.fm-heading a{color:var(--app-primary);font-weight:700}.fm-heading h1{margin:7px 0;font-size:clamp(24px,3vw,34px);font-weight:850}.fm-heading p,.fm-section-head p{margin:0;color:var(--app-muted,#788496);line-height:1.55}.fm-tabs{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:16px}.fm-tabs a{display:flex;align-items:center;gap:8px;min-width:0;padding:12px 14px;background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius);color:var(--app-text);font-weight:750;text-decoration:none}.fm-tabs a .material-icons{color:var(--app-primary)}.fm-tabs a b{margin-left:auto;color:var(--app-primary);font-size:12px;white-space:nowrap}.fm-tabs a.is-active{background:var(--app-primary);border-color:var(--app-primary);color:#fff!important}.fm-tabs a.is-active .material-icons,.fm-tabs a.is-active b{color:#fff!important}.fm-pagination{display:flex;justify-content:center;overflow-x:auto;margin:14px 0 4px}.fm-pagination nav{max-width:100%}.fm-pagination .pagination{flex-wrap:wrap;justify-content:center;gap:3px}.fm-pagination .page-link{color:var(--app-primary)}.fm-pagination .page-item.active .page-link{background:var(--app-primary);border-color:var(--app-primary);color:var(--app-primary-contrast)}.fm-card,.fm-kpis article{background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius);padding:clamp(14px,2vw,22px);margin-bottom:16px;min-width:0}.fm-scope-note{margin-bottom:12px;padding:13px 16px}.fm-scope-note strong{display:block;color:var(--app-text)}.fm-scope-note p{margin:4px 0 0;color:var(--app-muted,#788496);font-size:13px;line-height:1.5}.fm-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.fm-section-head h2{font-size:19px;font-weight:800;margin:0 0 5px;color:var(--app-text)}.fm-section-head p{font-size:13px}.fm-search{display:flex;gap:8px;min-width:min(440px,100%)}.fm-search .form-control{min-width:0}.fm-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin-bottom:16px}.fm-kpis article{margin:0;padding:15px}.fm-kpis span,.fm-kpis strong{display:block}.fm-kpis span{color:var(--app-muted,#788496);font-size:12px;font-weight:700}.fm-kpis strong{margin-top:6px;font-size:clamp(18px,2vw,25px);color:var(--app-primary)}.fm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0 0 20px}.fm-form-grid label{display:block;min-width:0;margin:0;color:var(--app-text);font-size:13px;font-weight:700}.fm-form-grid .form-control{margin-top:5px;min-width:0;width:100%}.fm-form-grid small,.fm-table small{display:block;margin-top:4px;color:var(--app-muted,#788496);font-weight:400}.fm-form-action{display:flex;align-items:end}.fm-form-action .btn{min-height:40px}.fm-span-all{grid-column:1/-1}.fm-decision-actions{display:grid;gap:6px;min-width:220px}.fm-decision-actions form{display:flex;gap:6px}.fm-decision-actions .form-control{min-width:0}.fm-purchase-receipts{display:flex;flex-wrap:wrap;gap:8px;margin:7px 0}.fm-purchase-receipts a{color:var(--app-primary);font-weight:700}.fm-table{min-width:780px;margin:8px 0 0;color:var(--app-text)}.fm-table thead th{background:color-mix(in srgb,var(--app-primary) 7%,var(--app-surface));color:var(--app-muted,#788496);font-size:11px;white-space:nowrap;border-color:color-mix(in srgb,var(--app-text) 14%,var(--app-surface))}.fm-table td{vertical-align:middle;border-color:color-mix(in srgb,var(--app-text) 12%,var(--app-surface));font-size:13px}.fm-table td strong{color:var(--app-text)}.fm-due,.fm-over{color:#b53e36!important}.fm-empty{text-align:center!important;padding:26px!important;color:var(--app-muted,#788496)!important}.fm-footnote{margin:14px 0 0;color:var(--app-muted,#788496);font-size:12px;line-height:1.55}.fm-inline-form{display:flex;gap:6px;min-width:170px}.fm-inline-form input{width:110px;min-width:0}.fm-tag{display:inline-block;padding:5px 9px;border-radius:999px;background:color-mix(in srgb,var(--app-accent) 18%,var(--app-surface));color:var(--app-text);font-size:12px;font-weight:700}
@media(max-width:850px){.fm-tabs{grid-template-columns:repeat(2,minmax(0,1fr))}.fm-section-head{display:block}.fm-search{min-width:0;margin-top:12px}.fm-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.fm-page{padding:10px 8px 28px}.fm-tabs{gap:6px}.fm-tabs a{padding:10px 8px;font-size:12px;gap:5px}.fm-tabs a .material-icons{font-size:18px}.fm-tabs a b{font-size:10px}.fm-card{padding:13px}.fm-form-grid{grid-template-columns:minmax(0,1fr)}.fm-search{display:grid;grid-template-columns:minmax(0,1fr) auto}.fm-search .btn{padding:8px 10px}.fm-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<style>.fm-current-assignee{margin:0 0 12px;padding:10px 12px;background:color-mix(in srgb,var(--app-primary) 6%,var(--app-surface));border-radius:8px;color:var(--app-text)}</style>
<style>.fm-purchase-trace{display:grid;gap:5px;margin:12px 0;padding:11px 13px;border:1px solid color-mix(in srgb,var(--app-primary) 15%,var(--app-surface));border-radius:8px;background:color-mix(in srgb,var(--app-primary) 4%,var(--app-surface));color:var(--app-muted);font-size:12px;line-height:1.5}.fm-purchase-trace strong{color:var(--app-text)}.fm-purchase-trace-pending{color:#946512!important;font-weight:700}.fm-purchase-action.fm-purchase-trace-pending{display:grid;gap:4px}.fm-purchase-action.fm-purchase-trace-pending small{color:var(--app-muted)}</style>
<style>
.fm-purchases-card .fm-section-head{align-items:center}.fm-purchase-total{flex:none;padding:7px 11px;border:1px solid var(--app-border);border-radius:999px;color:var(--app-muted);font-size:12px;font-weight:750}.fm-purchase-list{display:grid;gap:12px}.fm-purchase-item{min-width:0;padding:16px;border:1px solid var(--app-border);border-radius:12px;background:var(--app-surface)}.fm-purchase-top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px}.fm-purchase-ref{color:var(--app-muted);font-size:11px;font-weight:850;letter-spacing:.06em}.fm-purchase-top h3{margin:4px 0;font-size:17px;font-weight:820;overflow-wrap:anywhere}.fm-purchase-top p{margin:0;color:var(--app-muted);font-size:12px}.fm-purchase-top p strong{color:var(--app-text)}.fm-purchase-state{flex:none;min-width:115px;padding:7px 10px;border-radius:9px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));color:var(--app-primary);font-size:11px;font-weight:850;text-align:center}.fm-purchase-state small{display:block;margin-top:2px;color:var(--app-muted);font-size:10px;font-weight:600}.fm-purchase-state.state-action,.fm-purchase-state.state-review{background:#fff3df;color:#8d5200}.fm-purchase-state.state-progress{background:#eaf2ff;color:#285b9a}.fm-purchase-state.state-done{background:#e9f7ef;color:#187344}.fm-purchase-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:8px;margin-top:14px}.fm-purchase-fact{min-width:0;padding:10px 11px;border:1px solid var(--app-border);border-radius:9px;background:color-mix(in srgb,var(--app-text) 3%,var(--app-surface))}.fm-purchase-fact>span{display:block;color:var(--app-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.035em}.fm-purchase-fact strong{display:block;margin-top:5px;font-size:14px;overflow-wrap:anywhere}.fm-purchase-need{grid-column:span 2}.fm-purchase-need p{margin:5px 0 0;font-size:12px;line-height:1.45;overflow-wrap:anywhere}.fm-purchase-message{margin-top:10px;padding:10px 12px;border-left:3px solid var(--app-primary);border-radius:6px;background:var(--app-primary-soft)}.fm-purchase-message strong{font-size:11px}.fm-purchase-message p{margin:3px 0 0;color:var(--app-text);font-size:12px;line-height:1.45;overflow-wrap:anywhere}.fm-purchase-receipts{display:flex;align-items:center;flex-wrap:wrap;gap:7px;margin-top:12px}.fm-purchase-receipts>strong{margin-right:3px;font-size:11px}.fm-purchase-receipts a{display:inline-flex;align-items:center;gap:5px;padding:7px 9px;border:1px solid var(--app-border);border-radius:8px;color:var(--app-primary);font-size:11px;font-weight:750;text-decoration:none}.fm-purchase-receipts a:hover{background:var(--app-primary-soft)}.fm-purchase-receipts .material-icons{font-size:16px}.fm-purchase-action{display:grid;grid-template-columns:minmax(190px,1fr) minmax(150px,.55fr) auto;align-items:end;gap:12px;margin-top:13px;padding:13px;border:1px solid var(--app-primary-border);border-radius:10px;background:color-mix(in srgb,var(--app-primary) 4%,var(--app-surface))}.fm-purchase-action>div strong,.fm-purchase-action>div small{display:block}.fm-purchase-action>div strong{font-size:13px}.fm-purchase-action>div small{margin-top:3px;color:var(--app-muted);font-size:11px;line-height:1.4}.fm-purchase-action label{margin:0;font-size:11px;font-weight:750}.fm-purchase-action .form-control{margin-top:4px}.fm-purchase-action>.btn,.fm-purchase-review form>.btn{min-height:39px;white-space:normal}.fm-purchase-review{grid-template-columns:minmax(190px,1fr) auto minmax(260px,1.35fr)}.fm-purchase-review-heading{align-self:center}.fm-purchase-review form{display:flex;align-items:center;gap:7px;min-width:0}.fm-purchase-review form>.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px}.fm-purchase-review .material-icons{font-size:17px}.fm-purchase-correction .form-control{min-width:0}.fm-purchase-empty{display:grid;justify-items:center;gap:7px;padding:34px 14px;text-align:center;color:var(--app-muted)}.fm-purchase-empty>.material-icons{font-size:30px;color:var(--app-primary)}.fm-purchase-empty strong{color:var(--app-text);font-size:15px}.fm-purchase-empty p{margin:0;font-size:12px}
html[data-app-theme="dark"] .fm-purchase-state.state-action,html[data-app-theme="dark"] .fm-purchase-state.state-review{background:#49391b;color:#f3d28b}html[data-app-theme="dark"] .fm-purchase-state.state-progress{background:#20364a;color:#acd4f1}html[data-app-theme="dark"] .fm-purchase-state.state-done{background:#203d30;color:#a6dfbf}
@media(max-width:1000px){.fm-purchase-facts{grid-template-columns:repeat(2,minmax(0,1fr))}.fm-purchase-need{grid-column:1/-1}.fm-purchase-review{grid-template-columns:1fr 1fr}.fm-purchase-review-heading{grid-column:1/-1}.fm-purchase-review .fm-purchase-correction{grid-column:1/-1}}
@media(max-width:600px){.fm-purchases-card{padding:12px}.fm-purchases-card .fm-section-head{align-items:flex-start;flex-direction:column}.fm-purchase-item{padding:12px}.fm-purchase-top{flex-direction:column}.fm-purchase-state{align-self:flex-start}.fm-purchase-facts{grid-template-columns:1fr 1fr}.fm-purchase-action,.fm-purchase-review{grid-template-columns:1fr}.fm-purchase-action>*,.fm-purchase-review-heading,.fm-purchase-review .fm-purchase-correction{grid-column:1}.fm-purchase-action>.btn,.fm-purchase-review form>.btn{width:100%}.fm-purchase-review form{align-items:stretch;flex-direction:column}.fm-purchase-review .fm-purchase-correction .form-control{width:100%}}
@media(max-width:760px){
    .fm-page,.fm-purchases-card,.fm-purchase-item{min-width:0;max-width:100%}
    .fm-tabs a{overflow-wrap:anywhere;line-height:1.3}
    .fm-purchase-top>div{min-width:0;width:100%}
    .fm-purchase-top p,.fm-purchase-trace>span{overflow-wrap:anywhere}
    .fm-purchase-action,.fm-purchase-review{grid-template-columns:minmax(0,1fr)}
    .fm-purchase-action>* ,.fm-purchase-review-heading,.fm-purchase-review .fm-purchase-correction{grid-column:1/-1;min-width:0}
    .fm-purchase-action>.btn,.fm-purchase-review form>.btn{width:100%;min-width:0}
    .fm-purchase-review form{align-items:stretch;flex-direction:column}
    .fm-purchase-review .fm-purchase-correction .form-control{width:100%}
    .fm-form-action .btn{width:100%}
}
@media(max-width:420px){
    .fm-tabs{grid-template-columns:minmax(0,1fr)}
    .fm-tabs a{min-height:42px}
    .fm-purchase-facts{grid-template-columns:minmax(0,1fr)}
    .fm-purchase-need{grid-column:1/-1}
    .fm-purchase-state{max-width:100%;white-space:normal;text-align:left}
    .fm-purchase-receipts a{max-width:100%;overflow-wrap:anywhere}
}
</style>
<style>
.fm-loan-approval{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));align-items:end}
.fm-loan-approval small,.fm-loan-approval button{grid-column:1/-1}
.fm-loan-approval label{min-width:0;font-size:11px}
.fm-loan-plan-form{display:grid;gap:5px;min-width:160px;margin-top:7px}
.fm-loan-plan-form .form-control{min-width:0;width:100%;font-size:11px}
.fm-team-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin:0 0 14px}
.fm-team-summary article{min-width:0;padding:15px 17px;background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius)}
.fm-team-summary span,.fm-team-summary strong{display:block}
.fm-team-summary span{color:var(--app-muted);font-size:12px;font-weight:700}
.fm-team-summary strong{margin-top:6px;color:var(--app-text);font-size:clamp(19px,2.2vw,26px);font-weight:850;overflow-wrap:anywhere}
.fm-count{flex:none;padding:7px 10px;border-radius:999px;background:var(--app-primary-soft);color:var(--app-primary);font-size:12px;font-weight:800}
.fm-advance-list{display:grid;gap:12px}
.fm-advance-card{min-width:0;padding:15px;border:1px solid var(--app-border);border-radius:11px;background:var(--app-surface)}
.fm-advance-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.fm-advance-heading h3{margin:3px 0 0;color:var(--app-text);font-size:16px;font-weight:800;overflow-wrap:anywhere}
.fm-advance-kind{color:var(--app-muted);font-size:11px;font-weight:800;letter-spacing:.025em}
.fm-advance-amount{flex:none;color:var(--app-primary);font-size:18px;font-weight:850;text-align:right;overflow-wrap:anywhere}
.fm-advance-meta{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-top:10px;color:var(--app-muted);font-size:12px}
.fm-advance-meta p{max-width:68%;margin:0;color:var(--app-text);white-space:pre-line;overflow-wrap:anywhere}
.fm-advance-actions{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(220px,.8fr);gap:12px;margin-top:14px;padding-top:13px;border-top:1px solid var(--app-border)}
.fm-loan-approval{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;align-items:end}
.fm-loan-approval label,.fm-reject-form label,.fm-inline-form label{display:block;min-width:0;margin:0;color:var(--app-text);font-size:11px;font-weight:750}
.fm-loan-approval .form-control,.fm-reject-form .form-control,.fm-inline-form .form-control{margin-top:4px;min-width:0;width:100%}
.fm-loan-approval small{grid-column:1/-1;color:var(--app-muted);font-size:11px}
.fm-loan-approval button{grid-column:1/-1;justify-self:start}
.fm-reject-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:end}
.fm-reject-form .btn{min-height:38px}
.fm-team-empty{display:grid;justify-items:center;gap:5px;padding:26px 14px;text-align:center;color:var(--app-muted)}
.fm-team-empty .material-icons{color:var(--app-primary);font-size:27px}
.fm-team-empty strong{color:var(--app-text);font-size:14px}
.fm-team-empty p{margin:0;font-size:12px}
.fm-disbursement-card>.btn{margin-top:13px}
.fm-advance-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:8px;margin-top:13px}
.fm-advance-facts>div{min-width:0;padding:9px 10px;border-radius:8px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface))}
.fm-advance-facts span,.fm-advance-facts strong{display:block;overflow-wrap:anywhere}
.fm-advance-facts span{color:var(--app-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.025em}
.fm-advance-facts strong{margin-top:4px;color:var(--app-text);font-size:13px}
.fm-advance-reason{margin:10px 0 0;color:var(--app-muted);font-size:12px;white-space:pre-line;overflow-wrap:anywhere}
.fm-tag-open{background:#fff3df;color:#8d5200}.fm-tag-closed{background:#e9f7ef;color:#187344}
.fm-repayment-history{margin-top:10px;border-top:1px solid var(--app-border);padding-top:9px}
.fm-repayment-history summary{color:var(--app-primary);font-size:12px;font-weight:750;cursor:pointer}
.fm-repayment-history ul{display:grid;gap:5px;margin:9px 0 0;padding:0;list-style:none}
.fm-repayment-history li{display:flex;justify-content:space-between;gap:10px;color:var(--app-muted);font-size:12px}
.fm-repayment-history li strong{flex:none;color:var(--app-text)}
.fm-advance-admin-actions{display:grid;grid-template-columns:minmax(0,1fr) minmax(240px,.8fr);gap:12px;align-items:end;margin-top:12px;padding-top:12px;border-top:1px solid var(--app-border)}
.fm-advance-admin-actions .fm-inline-form{min-width:0;align-items:end}
.fm-advance-admin-actions .fm-loan-plan-form{min-width:0;margin:0}
.fm-advance-admin-actions .fm-loan-plan-form>strong{color:var(--app-text);font-size:11px}
.fm-advance-admin-actions .fm-loan-plan-form>div{display:grid;grid-template-columns:minmax(0,1fr) minmax(100px,.8fr) auto;gap:6px}
@media(max-width:760px){.fm-advance-actions,.fm-advance-admin-actions{grid-template-columns:1fr}.fm-reject-form{grid-template-columns:minmax(0,1fr) auto}}
@media(max-width:560px){.fm-team-summary{grid-template-columns:1fr 1fr}.fm-team-summary article{padding:12px}.fm-team-summary article:first-child{grid-column:1/-1}.fm-advance-card{padding:12px}.fm-advance-heading{align-items:flex-start}.fm-advance-amount{max-width:45%;font-size:16px}.fm-advance-meta{display:block}.fm-advance-meta p{max-width:none;margin-top:6px}.fm-loan-approval{grid-template-columns:1fr}.fm-loan-approval small,.fm-loan-approval button{grid-column:1}.fm-loan-approval button,.fm-reject-form .btn,.fm-disbursement-card>.btn{width:100%}.fm-reject-form{grid-template-columns:1fr}.fm-advance-facts{grid-template-columns:repeat(2,minmax(0,1fr))}.fm-advance-admin-actions .fm-loan-plan-form>div{grid-template-columns:1fr}.fm-advance-admin-actions .fm-inline-form{display:grid;grid-template-columns:minmax(0,1fr) auto}.fm-advance-admin-actions .fm-inline-form label{grid-column:1/-1}.fm-advance-admin-actions .fm-inline-form button{grid-column:1/-1}}
.fm-team-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 14px;padding:6px;background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius)}
.fm-team-tabs a{display:flex;align-items:center;gap:8px;padding:10px 13px;border-radius:8px;color:var(--app-text);font-size:13px;font-weight:750;text-decoration:none}
.fm-team-tabs a.is-active{background:var(--app-primary);color:var(--app-primary-contrast)}
.fm-team-tabs a.is-active,.fm-team-tabs a.is-active:visited,.fm-team-tabs a.is-active:hover{color:#fff!important}
.fm-team-tabs b{display:inline-grid;place-items:center;min-width:21px;height:21px;padding:0 5px;border-radius:999px;background:color-mix(in srgb,var(--app-primary) 12%,var(--app-surface));color:var(--app-primary);font-size:11px}
.fm-team-tabs a.is-active b{background:color-mix(in srgb,var(--app-primary-contrast) 18%,transparent);color:var(--app-primary-contrast)}
.fm-loan-search-label{display:block;margin:0 0 5px;color:var(--app-text);font-size:12px;font-weight:750}
.fm-loan-search{max-width:460px;margin-bottom:13px}
.fm-employee-loan-list{display:grid;gap:8px}
.fm-employee-loan{border:1px solid var(--app-border);border-radius:10px;background:var(--app-surface);overflow:hidden}
.fm-employee-loan[hidden]{display:none}
.fm-employee-loan>summary{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 15px;cursor:pointer;list-style:none}
.fm-employee-loan>summary::-webkit-details-marker{display:none}
.fm-employee-loan>summary:after{content:'+';flex:none;color:var(--app-primary);font-size:20px;font-weight:700}
.fm-employee-loan[open]>summary:after{content:'−'}
.fm-employee-loan>summary span{display:grid;gap:2px;min-width:0}
.fm-employee-loan>summary strong{color:var(--app-text);font-size:14px;overflow-wrap:anywhere}
.fm-employee-loan>summary small{color:var(--app-muted);font-size:11px}
.fm-employee-loan>summary>b{margin-left:auto;color:var(--app-primary);font-size:13px;text-align:right;white-space:nowrap}
.fm-employee-loan-dossiers{display:grid;gap:8px;padding:0 9px 9px;border-top:1px solid var(--app-border)}
.fm-employee-loan-dossiers>.fm-advance-card{margin-top:9px}
.fm-recorded-advance{padding:16px 18px}
.fm-recorded-advance .fm-advance-heading{align-items:center}
.fm-recorded-advance .fm-advance-facts{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:12px}
.fm-recorded-advance .fm-advance-reason{margin-top:12px;padding:9px 11px;border-left:3px solid var(--app-border);background:color-mix(in srgb,var(--app-text) 3%,var(--app-surface));border-radius:0 7px 7px 0}
.fm-early-repayment{grid-column:1/-1;min-width:0;padding:11px 13px;border:1px solid var(--app-border);border-radius:9px;background:color-mix(in srgb,var(--app-text) 2%,var(--app-surface))}
.fm-early-repayment summary{color:var(--app-primary);font-size:12px;font-weight:800;cursor:pointer}
.fm-early-repayment p{max-width:760px;margin:7px 0 10px;color:var(--app-muted);font-size:12px;line-height:1.5}
.fm-early-repayment .fm-inline-form{display:grid;grid-template-columns:minmax(180px,280px) auto;align-items:end;gap:8px;min-width:0}
.fm-early-repayment .fm-inline-form .btn{min-height:40px}
.fm-loan-no-results{margin:10px 0 0;padding:12px;border-radius:8px;background:var(--app-primary-soft);color:var(--app-muted);font-size:13px}
@media(max-width:760px){.fm-recorded-advance .fm-advance-facts{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.fm-team-tabs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px}.fm-team-tabs a{justify-content:center;padding:9px 6px;text-align:center;font-size:11px}.fm-employee-loan>summary{padding:12px}.fm-employee-loan>summary>b{font-size:11px}.fm-recorded-advance{padding:12px}.fm-recorded-advance .fm-advance-heading{align-items:flex-start;flex-direction:column}.fm-recorded-advance .fm-advance-facts{grid-template-columns:1fr 1fr}.fm-early-repayment{padding:10px}.fm-early-repayment .fm-inline-form{grid-template-columns:1fr}.fm-early-repayment .fm-inline-form .btn{width:100%}}
</style>
@endsection
