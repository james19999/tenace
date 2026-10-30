@extends('layouts.admin')

@section('content')
<div class="container-fluid finance-page">
    <header class="finance-hero">
        <div><span class="finance-kicker">TENACOS · TRÉSORERIE</span><h1>Suivi financier</h1><p>La caisse générale est calculée à partir des mouvements confirmés. Les fonds récupérés restent en transit jusqu’à la confirmation du DG.</p></div>
        <div class="finance-hero-actions"><a class="btn btn-light" href="{{ route('finance.remittances.index') }}">Remises de boutiques</a><a class="btn btn-outline-light" href="{{ route('finance.expenses.index') }}">Dépenses</a><a class="btn btn-outline-light" href="{{ route('finance.suppliers.index') }}">Factures fournisseurs</a></div>
    </header>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><strong>Vérifie les informations.</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="finance-stat-grid">
        @if($canSeeCentral)<article class="finance-stat"><span>Caisse générale</span><strong>{{ number_format($generalCash, 0, ',', ' ') }} F</strong><small>Fonds reçus et confirmés par le DG</small></article>@else<article class="finance-stat"><span>Espèces suivies dans ta boutique</span><strong>{{ number_format($shopCash, 0, ',', ' ') }} F</strong><small>Encaissements moins sorties enregistrées et remises</small></article>@endif
        <article class="finance-stat"><span>Fonds en transit</span><strong>{{ number_format(max(0, $inTransit), 0, ',', ' ') }} F</strong><small>Remises pas encore confirmées</small></article>
        <article class="finance-stat"><span>Remises à suivre</span><strong>{{ $pendingRemittances }}</strong><small>Signature, réception ou écart à traiter</small></article>
        <article class="finance-stat"><span>Dépenses à traiter</span><strong>{{ $pendingExpenses }}</strong><small>Contrôle financier ou décision du DG</small></article>
        @if($canSeeAll)<article class="finance-stat"><span>Dettes fournisseurs</span><strong>{{ number_format($supplierPayables, 0, ',', ' ') }} F</strong><small>{{ $overdueSupplierInvoices }} facture(s) échue(s)</small></article>@endif
    </div>

    @if (auth()->user()->isAdmin() && ! $hasOpeningBalance)
        <section class="finance-panel finance-opening">
            <div><h2>Enregistrer le solde initial</h2><p>Saisis le montant validé au démarrage. Cette écriture sera conservée dans le journal et ne pourra pas être modifiée directement.</p></div>
            <form method="POST" action="{{ route('finance.cash.opening') }}" class="finance-opening-form">@csrf
                <label>Montant compté (F CFA)<input class="form-control" type="number" min="0" step="1" name="amount" required></label>
                <label>Date du comptage<input class="form-control" type="date" name="occurred_at" value="{{ now()->format('Y-m-d') }}" required></label>
                <label class="finance-opening-reason">Motif / référence de validation<input class="form-control" name="reason" maxlength="2000" required></label>
                <button class="btn btn-primary" type="submit">Enregistrer le solde initial</button>
            </form>
        </section>
    @endif

    @if($shopOpeningsNeeded->isNotEmpty() && (auth()->user()->isManager() || (auth()->user()->isAdmin() && ! $hasOpeningBalance)))
        <section class="finance-panel finance-opening">
            <div><h2>Solde initial des boutiques (facultatif)</h2><p>Tu peux renseigner l’argent déjà présent dans une boutique au démarrage. Cette saisie n’est pas nécessaire pour initialiser la caisse générale.</p></div>
            <form method="POST" action="{{ route('finance.cash.shop-opening') }}" class="finance-opening-form">@csrf
                <label>Boutique<select class="form-control" name="stock_location_id" required>@foreach($shopOpeningsNeeded as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></label>
                <label>Espèces présentes (F CFA)<input class="form-control" type="number" min="0" step="1" name="amount" required></label>
                <label>Date du comptage<input class="form-control" type="date" name="occurred_at" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required></label>
                <label class="finance-opening-reason">Motif / référence de validation<input class="form-control" name="reason" maxlength="2000" required></label>
                <button class="btn btn-primary" type="submit">Enregistrer le solde boutique</button>
            </form>
        </section>
    @endif

    @if (auth()->user()->isAdmin() || auth()->user()->hasRole(\App\Models\User::ROLE_CASHIER))
        <section class="finance-panel finance-opening">
            <div><h2>Comptage de la caisse générale</h2><p>Le solde théorique est calculé depuis les mouvements enregistrés. Une autre personne habilitée devra valider le comptage.</p></div>
            <form method="POST" action="{{ route('finance.cash.closures.store') }}" class="finance-opening-form">@csrf
                <label>Date du comptage<input class="form-control" type="date" name="closing_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required></label>
                <label>Montant compté (F CFA)<input class="form-control" type="number" min="0" step="1" name="counted_amount" required></label>
                <label class="finance-opening-reason">Explication de l’écart (obligatoire si différent du solde théorique)<input class="form-control" name="count_note" maxlength="2000"></label>
                <button class="btn btn-primary" type="submit">Transmettre le comptage</button>
            </form>
        </section>
    @endif

    @if($canSeeCentral || $cashClosures->isNotEmpty())
    <section class="finance-panel">
        <div class="finance-panel-heading"><div><h2>Clôtures de caisse</h2><p>Chaque comptage est contrôlé par une personne distincte et conservé dans l’audit.</p></div></div>
        <div class="table-responsive"><table class="table finance-table"><thead><tr><th>Date</th><th>Compté par</th><th>Théorique</th><th>Compté</th><th>Écart</th><th>Statut / contrôle</th></tr></thead><tbody>
            @forelse($cashClosures as $closure)
                <tr><td>{{ $closure->closing_date->format('d/m/Y') }}</td><td>{{ $closure->counter?->name ?? '—' }}</td><td>{{ number_format($closure->theoretical_amount,0,',',' ') }} F</td><td>{{ number_format($closure->counted_amount,0,',',' ') }} F</td><td class="{{ $closure->variance_amount === 0 ? 'text-success' : 'text-danger' }}">{{ $closure->variance_amount > 0 ? '+' : '' }}{{ number_format($closure->variance_amount,0,',',' ') }} F</td><td>
                    @if($closure->status === 'awaiting_review' && (auth()->user()->isAdmin() || auth()->user()->isAccountant()) && (int)$closure->counted_by !== (int)auth()->id())
                        <form method="POST" action="{{ route('finance.cash.closures.review', $closure) }}" class="finance-review-form">@csrf<label class="sr-only" for="closure-note-{{ $closure->id }}">Commentaire de contrôle</label><input id="closure-note-{{ $closure->id }}" class="form-control" name="resolution_note" placeholder="Commentaire{{ $closure->variance_amount !== 0 ? ' obligatoire' : ' (facultatif)' }}" {{ $closure->variance_amount !== 0 ? 'required' : '' }}><input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-primary" type="submit">Valider</button></form>
                    @elseif($closure->status === 'closed' && auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('finance.cash.closures.review', $closure) }}" class="finance-review-form">@csrf<label class="sr-only" for="closure-reopen-{{ $closure->id }}">Motif de réouverture</label><input id="closure-reopen-{{ $closure->id }}" class="form-control" name="resolution_note" placeholder="Motif de réouverture" required><input type="hidden" name="decision" value="reopened"><button class="btn btn-sm btn-outline-danger" type="submit">Rouvrir</button></form>
                    @else<span class="badge badge-light">{{ $closure->status === 'awaiting_review' ? 'À contrôler' : ($closure->status === 'closed' ? 'Clôturée' : 'Rouverte') }}</span><small class="d-block text-muted">{{ $closure->reviewer?->name ?? 'En attente de contrôle' }}</small>@endif
                </td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune clôture enregistrée.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    @endif

    <div class="finance-quick-links">
        @if (auth()->user()->isManager())<a href="{{ route('finance.remittances.create') }}"><span class="material-icons">payments</span><span><strong>Préparer une remise</strong><small>La boutique et le collecteur confirment avec leurs comptes.</small></span></a>@else<a href="{{ route('finance.remittances.index') }}"><span class="material-icons">payments</span><span><strong>Suivre les remises</strong><small>Consulter les signatures, réceptions et écarts.</small></span></a>@endif
        <a href="{{ route('finance.expenses.create') }}"><span class="material-icons">request_quote</span><span><strong>Demander une dépense</strong><small>Le paiement reste bloqué jusqu’aux validations nécessaires.</small></span></a>
        <a href="{{ route('finance.suppliers.index') }}"><span class="material-icons">receipt_long</span><span><strong>Suivre les dettes fournisseurs</strong><small>Contrôler les factures et les soldes à régler.</small></span></a>
        <a href="{{ route('finance.expenses.index', ['status' => 'awaiting_director']) }}"><span class="material-icons">verified_user</span><span><strong>Décisions du DG</strong><small>Consulter les dépenses supérieures à 50 000 F.</small></span></a>
    </div>

    <section class="finance-panel">
        <div class="finance-panel-heading"><div><h2>Récapitulatif de la semaine</h2><p>Du {{ $weeklySummary['start']->format('d/m/Y') }} au {{ $weeklySummary['end']->format('d/m/Y') }} · espèces enregistrées dans le journal</p></div></div>
        <div class="finance-week-grid"><div><small>Ventes encaissées en espèces</small><strong>{{ number_format($weeklySummary['sales_cash'],0,',',' ') }} F</strong></div><div><small>Dépenses payées</small><strong>{{ number_format($weeklySummary['expenses_paid'],0,',',' ') }} F</strong></div><div><small>Remises prises en charge</small><strong>{{ number_format($weeklySummary['remitted'],0,',',' ') }} F</strong></div><div><small>Confirmées par le DG</small><strong>{{ number_format($weeklySummary['received'],0,',',' ') }} F</strong></div></div>
    </section>

    <section class="finance-panel">
        <div class="finance-panel-heading"><div><h2>Mouvements récents</h2><p>Chaque ligne conserve sa source et sa référence.</p></div><a href="{{ route('finance.remittances.index') }}">Voir les remises</a></div>
        <div class="table-responsive"><table class="table finance-table"><thead><tr><th>Date</th><th>Opération</th><th>Boutique</th><th>De</th><th>Vers</th><th class="text-right">Montant</th></tr></thead><tbody>
            @forelse ($recentMovements as $movement)
                <tr><td>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td><td><strong>{{ \App\Support\FinanceDisplayLabels::movement($movement->movement_type) }}</strong><small class="d-block text-muted">{{ $movement->source_reference ?? $movement->entry_number }}</small></td><td>{{ $movement->location?->name ?? '—' }}</td><td>{{ \App\Support\FinanceDisplayLabels::custody($movement->from_custody) }}</td><td>{{ \App\Support\FinanceDisplayLabels::custody($movement->to_custody) }}</td><td class="text-right font-weight-bold">{{ number_format($movement->amount, 0, ',', ' ') }} F</td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucun mouvement financier enregistré.</td></tr>@endforelse
        </tbody></table></div>
        @if ($recentMovements->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Affichage de {{ $recentMovements->firstItem() }} à {{ $recentMovements->lastItem() }} sur {{ $recentMovements->total() }} mouvements</small>
                {{ $recentMovements->links() }}
            </div>
        @endif
    </section>
</div>

<style>
.finance-page{max-width:1500px;padding:18px 22px 40px}.finance-hero{display:flex;justify-content:space-between;align-items:center;gap:24px;padding:28px 32px;margin-bottom:20px;background:#187b2a;color:#fff}.finance-hero h1{margin:7px 0;font-weight:800}.finance-hero p{max-width:760px;margin:0;color:rgba(255,255,255,.82)}.finance-kicker{font-size:12px;font-weight:800;letter-spacing:.12em}.finance-hero-actions{display:flex;gap:10px;flex-wrap:wrap}.finance-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(205px,1fr));gap:14px;margin-bottom:18px}.finance-stat,.finance-panel{background:#fff;border:1px solid #e1e5eb;padding:20px}.finance-stat span{display:block;color:#677384;font-size:13px;font-weight:800;text-transform:uppercase}.finance-stat strong{display:block;margin:8px 0;font-size:28px;color:#242a35}.finance-stat small,.finance-panel-heading p{color:#788496}.finance-panel{margin-bottom:18px}.finance-panel h2{font-size:20px;font-weight:800;margin:0 0 5px}.finance-panel-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.finance-panel-heading p{margin:0}.finance-table th{font-size:12px;color:#6b7480;text-transform:uppercase;white-space:nowrap}.finance-table td{vertical-align:middle}.finance-quick-links{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.finance-quick-links>a{display:flex;align-items:center;gap:14px;padding:17px;background:#f9fafb;border:1px solid #e1e5eb;color:#202733}.finance-quick-links .material-icons{font-size:28px;color:#187b2a}.finance-quick-links strong,.finance-quick-links small{display:block}.finance-quick-links small{margin-top:4px;color:#778294}.finance-opening{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,2fr);gap:24px}.finance-opening p{color:#738092}.finance-opening-form{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:end}.finance-opening-form label{font-weight:700}.finance-opening-reason{grid-column:1 / -1}.finance-week-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.finance-week-grid div{background:#f8f9fb;padding:15px;border:1px solid #e7eaf0}.finance-week-grid small,.finance-week-grid strong{display:block}.finance-week-grid small{color:#738092}.finance-week-grid strong{margin-top:6px;font-size:20px}@media(max-width:900px){.finance-quick-links{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-opening{grid-template-columns:1fr}.finance-week-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.finance-page{padding:10px}.finance-hero{display:block;padding:22px 18px}.finance-hero-actions{margin-top:16px}.finance-stat-grid{grid-template-columns:1fr 1fr;gap:8px}.finance-stat{padding:14px}.finance-stat strong{font-size:21px}.finance-opening-form{grid-template-columns:1fr}.finance-opening-reason{grid-column:auto}.finance-panel{padding:14px}.finance-quick-links{grid-template-columns:1fr}.finance-week-grid{grid-template-columns:1fr 1fr}.finance-week-grid div{padding:10px}.finance-week-grid strong{font-size:17px}}
.finance-review-form{display:flex;gap:6px;min-width:250px}.finance-review-form input{min-width:0}.finance-review-form button{white-space:nowrap}
.finance-page,.finance-panel,.finance-opening,.finance-opening-form{min-width:0;box-sizing:border-box}.finance-opening{grid-template-columns:minmax(0,.8fr) minmax(0,2fr)}.finance-opening-form{grid-template-columns:repeat(2,minmax(0,1fr));width:100%}.finance-opening-form label{min-width:0}.finance-opening-form .form-control{width:100%;max-width:100%;min-width:0;box-sizing:border-box}@media(max-width:900px){.finance-opening{grid-template-columns:minmax(0,1fr)}}@media(max-width:600px){.finance-opening-form{grid-template-columns:minmax(0,1fr)}}
</style>
@endsection
