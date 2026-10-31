@extends('layouts.admin')

@section('content')
<div class="container-fluid finance-page">
    <header class="finance-hero">
        <div><span class="finance-kicker">TENACOS · TRÉSORERIE</span><h1>Suivi financier</h1><p>Consulte le solde, son évolution et les opérations qui demandent une action.</p></div>
    </header>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><strong>Vérifie les informations.</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="finance-stat-grid">
        @if($canSeeCentral)<article class="finance-stat finance-stat-primary"><span>Caisse générale</span><strong>{{ number_format($generalCash, 0, ',', ' ') }} F</strong><small>Solde confirmé par les mouvements</small></article>@else<article class="finance-stat finance-stat-primary"><span>Espèces de ta boutique</span><strong>{{ number_format($shopCash, 0, ',', ' ') }} F</strong><small>Solde calculé à partir des mouvements</small></article>@endif
        <article class="finance-stat"><span>Variation nette · {{ $cashTrend['current_month'] }}</span><strong class="{{ $cashTrend['current_month_net'] < 0 ? 'finance-negative' : 'finance-positive' }}">{{ $cashTrend['current_month_net'] > 0 ? '+' : '' }}{{ number_format($cashTrend['current_month_net'], 0, ',', ' ') }} F</strong><small>{{ $cashTrend['previous_month'] }} : {{ $cashTrend['previous_month_net'] > 0 ? '+' : '' }}{{ number_format($cashTrend['previous_month_net'], 0, ',', ' ') }} F</small></article>
        <article class="finance-stat"><span>Fonds en transit</span><strong>{{ number_format(max(0, $inTransit), 0, ',', ' ') }} F</strong><small>En attente de confirmation du DG</small></article>
        @if($canSeeAll)<article class="finance-stat"><span>Dettes fournisseurs</span><strong>{{ number_format($supplierPayables, 0, ',', ' ') }} F</strong><small>{{ $overdueSupplierInvoices }} facture(s) échue(s)</small></article>@endif
    </div>

    <section class="finance-chart-grid">
        <article class="finance-panel finance-chart-card finance-chart-wide">
            <div class="finance-panel-heading"><div><h2>Évolution de la caisse</h2><p>Solde jour par jour · comparaison avec le mois précédent · {{ $cashTrend['custody_label'] }}</p></div><span class="finance-chart-period">{{ $cashTrend['current_month'] }}</span></div>
            <div class="finance-chart-wrap"><canvas id="finance-cash-trend" role="img" aria-label="Évolution journalière du solde et comparaison au mois précédent"></canvas></div>
        </article>
        <article class="finance-panel finance-chart-card">
            <div class="finance-panel-heading"><div><h2>Entrées et sorties</h2><p>Espèces enregistrées dans la caisse</p></div></div>
            <div class="finance-chart-wrap finance-chart-small"><canvas id="finance-cash-flow" role="img" aria-label="Comparaison des entrées et sorties de caisse"></canvas></div>
            <div class="finance-chart-legend"><span><i class="finance-legend-in"></i>Entrées</span><span><i class="finance-legend-out"></i>Sorties</span></div>
        </article>
    </section>

    <nav class="finance-action-strip" aria-label="Suivi des opérations comptables">
        <a href="{{ route('finance.remittances.index') }}"><span class="material-icons">payments</span><span><strong>Remises</strong><small>{{ $pendingRemittances }} à suivre</small></span><span class="material-icons finance-action-arrow">chevron_right</span></a>
        <a href="{{ route('finance.expenses.index') }}"><span class="material-icons">request_quote</span><span><strong>Dépenses</strong><small>{{ $pendingExpenses }} en attente</small></span><span class="material-icons finance-action-arrow">chevron_right</span></a>
        @if($canSeeAll)<a href="{{ route('finance.suppliers.index') }}"><span class="material-icons">receipt_long</span><span><strong>Factures fournisseurs</strong><small>{{ $overdueSupplierInvoices }} échue(s) · {{ number_format($supplierPayables, 0, ',', ' ') }} F à régler</small></span><span class="material-icons finance-action-arrow">chevron_right</span></a>@endif
        @if(auth()->user()->isAdmin())<a href="{{ route('finance.expenses.index', ['status' => 'awaiting_director']) }}"><span class="material-icons">verified_user</span><span><strong>Décisions du DG</strong><small>Dépenses qui attendent ton avis</small></span><span class="material-icons finance-action-arrow">chevron_right</span></a>@endif
        @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())<a href="{{ route('finance.management.index') }}"><span class="material-icons">account_balance_wallet</span><span><strong>Créances et prévisions</strong><small>Budgets, avances et immobilisations</small></span><span class="material-icons finance-action-arrow">chevron_right</span></a>@endif
    </nav>

    @if (auth()->user()->isAdmin() && ! $hasOpeningBalance)
        <details class="finance-panel finance-details" open>
            <summary><span><strong>Initialiser la caisse générale</strong><small>À faire une seule fois au démarrage</small></span><span class="material-icons">expand_more</span></summary>
            <section class="finance-opening">
            <div><h2>Enregistrer le solde initial</h2><p>Saisis le montant validé au démarrage. Cette écriture sera conservée dans le journal et ne pourra pas être modifiée directement.</p></div>
            <form method="POST" action="{{ route('finance.cash.opening') }}" class="finance-opening-form">@csrf
                <label>Montant compté (F CFA)<input class="form-control" type="number" min="0" step="1" name="amount" required></label>
                <label>Date du comptage<input class="form-control" type="date" name="occurred_at" value="{{ now()->format('Y-m-d') }}" required></label>
                <label class="finance-opening-reason">Motif / référence de validation<input class="form-control" name="reason" maxlength="2000" required></label>
                <button class="btn btn-primary" type="submit">Enregistrer le solde initial</button>
            </form>
            </section>
        </details>
    @endif

    @if($shopOpeningsNeeded->isNotEmpty() && (auth()->user()->isManager() || (auth()->user()->isAdmin() && ! $hasOpeningBalance)))
        <details class="finance-panel finance-details">
            <summary><span><strong>Fonds initiaux des boutiques</strong><small>Facultatif · {{ $shopOpeningsNeeded->count() }} boutique(s) à configurer</small></span><span class="material-icons">expand_more</span></summary>
            <section class="finance-opening">
            <div><h2>Solde initial des boutiques (facultatif)</h2><p>Tu peux renseigner l’argent déjà présent dans une boutique au démarrage. Cette saisie n’est pas nécessaire pour initialiser la caisse générale.</p></div>
            <form method="POST" action="{{ route('finance.cash.shop-opening') }}" class="finance-opening-form">@csrf
                <label>Boutique<select class="form-control" name="stock_location_id" required>@foreach($shopOpeningsNeeded as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></label>
                <label>Espèces présentes (F CFA)<input class="form-control" type="number" min="0" step="1" name="amount" required></label>
                <label>Date du comptage<input class="form-control" type="date" name="occurred_at" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required></label>
                <label class="finance-opening-reason">Motif / référence de validation<input class="form-control" name="reason" maxlength="2000" required></label>
                <button class="btn btn-primary" type="submit">Enregistrer le solde boutique</button>
            </form>
            </section>
        </details>
    @endif

    @if (auth()->user()->isAdmin() || auth()->user()->hasRole(\App\Models\User::ROLE_CASHIER))
        <details class="finance-panel finance-details">
            <summary><span><strong>Comptage de la caisse générale</strong><small>Transmettre un comptage à une autre personne pour validation</small></span><span class="material-icons">expand_more</span></summary>
            <section class="finance-opening">
            <div><h2>Comptage de la caisse générale</h2><p>Le solde théorique est calculé depuis les mouvements enregistrés. Une autre personne habilitée devra valider le comptage.</p></div>
            <form method="POST" action="{{ route('finance.cash.closures.store') }}" class="finance-opening-form">@csrf
                <label>Date du comptage<input class="form-control" type="date" name="closing_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required></label>
                <label>Montant compté (F CFA)<input class="form-control" type="number" min="0" step="1" name="counted_amount" required></label>
                <label class="finance-opening-reason">Explication de l’écart (obligatoire si différent du solde théorique)<input class="form-control" name="count_note" maxlength="2000"></label>
                <button class="btn btn-primary" type="submit">Transmettre le comptage</button>
            </form>
            </section>
        </details>
    @endif

    @if($canSeeCentral || $cashClosures->isNotEmpty())
    <details class="finance-panel finance-details">
        <summary><span><strong>Historique et validations de caisse</strong><small>{{ $cashClosures->count() }} clôture(s) récente(s)</small></span><span class="material-icons">expand_more</span></summary>
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
    </details>
    @endif

    <details class="finance-panel finance-details">
        <summary><span><strong>Mouvements récents</strong><small>{{ $recentMovements->total() }} opération(s) · chaque ligne garde sa référence</small></span><span class="material-icons">expand_more</span></summary>
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
    </details>
</div>

<style>
.finance-page{max-width:1500px;padding:18px 22px 40px}.finance-hero{display:flex;justify-content:space-between;align-items:center;gap:24px;padding:28px 32px;margin-bottom:20px;background:#187b2a;color:#fff}.finance-hero h1{margin:7px 0;font-weight:800}.finance-hero p{max-width:760px;margin:0;color:rgba(255,255,255,.82)}.finance-kicker{font-size:12px;font-weight:800;letter-spacing:.12em}.finance-hero-actions{display:flex;gap:10px;flex-wrap:wrap}.finance-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(205px,1fr));gap:14px;margin-bottom:18px}.finance-stat,.finance-panel{background:#fff;border:1px solid #e1e5eb;padding:20px}.finance-stat span{display:block;color:#677384;font-size:13px;font-weight:800;text-transform:uppercase}.finance-stat strong{display:block;margin:8px 0;font-size:28px;color:#242a35}.finance-stat small,.finance-panel-heading p{color:#788496}.finance-panel{margin-bottom:18px}.finance-panel h2{font-size:20px;font-weight:800;margin:0 0 5px}.finance-panel-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.finance-panel-heading p{margin:0}.finance-table th{font-size:12px;color:#6b7480;text-transform:uppercase;white-space:nowrap}.finance-table td{vertical-align:middle}.finance-quick-links{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.finance-quick-links>a{display:flex;align-items:center;gap:14px;padding:17px;background:#f9fafb;border:1px solid #e1e5eb;color:#202733}.finance-quick-links .material-icons{font-size:28px;color:#187b2a}.finance-quick-links strong,.finance-quick-links small{display:block}.finance-quick-links small{margin-top:4px;color:#778294}.finance-opening{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,2fr);gap:24px}.finance-opening p{color:#738092}.finance-opening-form{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:end}.finance-opening-form label{font-weight:700}.finance-opening-reason{grid-column:1 / -1}.finance-week-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.finance-week-grid div{background:#f8f9fb;padding:15px;border:1px solid #e7eaf0}.finance-week-grid small,.finance-week-grid strong{display:block}.finance-week-grid small{color:#738092}.finance-week-grid strong{margin-top:6px;font-size:20px}@media(max-width:900px){.finance-quick-links{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-opening{grid-template-columns:1fr}.finance-week-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.finance-page{padding:10px}.finance-hero{display:block;padding:22px 18px}.finance-hero-actions{margin-top:16px}.finance-stat-grid{grid-template-columns:1fr 1fr;gap:8px}.finance-stat{padding:14px}.finance-stat strong{font-size:21px}.finance-opening-form{grid-template-columns:1fr}.finance-opening-reason{grid-column:auto}.finance-panel{padding:14px}.finance-quick-links{grid-template-columns:1fr}.finance-week-grid{grid-template-columns:1fr 1fr}.finance-week-grid div{padding:10px}.finance-week-grid strong{font-size:17px}}
.finance-review-form{display:flex;gap:6px;min-width:250px}.finance-review-form input{min-width:0}.finance-review-form button{white-space:nowrap}
.finance-page,.finance-panel,.finance-opening,.finance-opening-form{min-width:0;box-sizing:border-box}.finance-opening{grid-template-columns:minmax(0,.8fr) minmax(0,2fr)}.finance-opening-form{grid-template-columns:repeat(2,minmax(0,1fr));width:100%}.finance-opening-form label{min-width:0}.finance-opening-form .form-control{width:100%;max-width:100%;min-width:0;box-sizing:border-box}@media(max-width:900px){.finance-opening{grid-template-columns:minmax(0,1fr)}}@media(max-width:600px){.finance-opening-form{grid-template-columns:minmax(0,1fr)}}
.finance-stat-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:16px}.finance-stat-primary{border-left:4px solid #187b2a}.finance-positive{color:#187b2a!important}.finance-negative{color:#bd3b3b!important}.finance-chart-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(280px,1fr);gap:16px;margin-bottom:16px}.finance-chart-card{margin:0;min-width:0}.finance-chart-card .finance-panel-heading{min-height:48px}.finance-chart-period{padding:7px 10px;background:#eef7ef;color:#187b2a;font-size:12px;font-weight:800;white-space:nowrap}.finance-chart-wrap{position:relative;height:320px;width:100%}.finance-chart-small{height:320px}.finance-chart-legend{display:flex;justify-content:center;gap:22px;color:#738092;font-size:12px}.finance-chart-legend span{display:flex;align-items:center;gap:7px}.finance-chart-legend i{width:10px;height:10px;border-radius:50%;display:inline-block}.finance-legend-in{background:#18832c}.finance-legend-out{background:#e2a348}.finance-action-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin-bottom:18px}.finance-action-strip>a{display:flex;align-items:center;gap:11px;min-width:0;padding:13px 15px;border:1px solid #e1e5eb;background:#fff;color:#26303d}.finance-action-strip>a>.material-icons:first-child{font-size:23px;color:#187b2a}.finance-action-strip strong,.finance-action-strip small{display:block}.finance-action-strip small{margin-top:3px;color:#788496}.finance-action-arrow{margin-left:auto;color:#a0a8b3}.finance-details{padding:0}.finance-details>summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;list-style:none;padding:15px 20px}.finance-details>summary::-webkit-details-marker{display:none}.finance-details>summary strong,.finance-details>summary small{display:block}.finance-details>summary small{margin-top:3px;color:#788496}.finance-details[open]>summary{border-bottom:1px solid #e1e5eb;margin-bottom:16px}.finance-details>.finance-opening{padding:0 20px 20px}.finance-details>.table-responsive{padding:0 16px 14px}.finance-details[open]>summary>.material-icons{transform:rotate(180deg)}
@media(max-width:900px){.finance-chart-grid{grid-template-columns:1fr}.finance-chart-wrap{height:280px}.finance-chart-small{height:260px}}
@media(max-width:600px){.finance-chart-grid{gap:8px}.finance-chart-card{padding:14px}.finance-chart-wrap{height:240px}.finance-chart-small{height:230px}.finance-panel-heading{align-items:flex-start}.finance-chart-period{display:none}.finance-action-strip{grid-template-columns:1fr}.finance-details>summary{padding:13px 14px}.finance-details>.finance-opening{padding:0 14px 14px}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    var trend = @json($cashTrend);
    var comparison = @json($cashFlowComparison);
    var number = function (value) { return new Intl.NumberFormat('fr-FR').format(value) + ' F'; };
    var dark = document.documentElement.getAttribute('data-app-theme') === 'dark';
    var theme = getComputedStyle(document.documentElement);
    var primary = theme.getPropertyValue('--app-primary').trim() || '#187b2a';
    var accent = theme.getPropertyValue('--app-accent').trim() || '#e2a348';
    var rgba = function (color, alpha) {
        var match = color.match(/^#([\da-f]{3}|[\da-f]{6})$/i);
        if (!match) return color;
        var hex = match[1];
        if (hex.length === 3) hex = hex.split('').map(function (part) { return part + part; }).join('');
        var value = parseInt(hex, 16);
        return 'rgba(' + ((value >> 16) & 255) + ',' + ((value >> 8) & 255) + ',' + (value & 255) + ',' + alpha + ')';
    };
    var textColor = dark ? '#a8b1bd' : '#748093';
    var gridColor = dark ? '#343b46' : '#e8ecf1';
    var trendCanvas = document.getElementById('finance-cash-trend');
    if (trendCanvas) {
        new Chart(trendCanvas.getContext('2d'), {
            type: 'line',
            data: { labels: trend.labels, datasets: [
                { label: trend.current_month, data: trend.current, borderColor: primary, backgroundColor: rgba(primary, .12), borderWidth: 3, pointRadius: 1, pointHoverRadius: 5, lineTension: .28, fill: true, spanGaps: false },
                { label: trend.previous_month, data: trend.previous, borderColor: '#9ca7b5', backgroundColor: 'rgba(156,167,181,.04)', borderWidth: 2, borderDash: [6, 5], pointRadius: 0, pointHoverRadius: 4, lineTension: .25, fill: false, spanGaps: true }
            ] },
            options: { responsive: true, maintainAspectRatio: false, legend: { display: true, position: 'bottom', labels: { fontColor: textColor, usePointStyle: true, padding: 20 } }, tooltips: { mode: 'index', intersect: false, callbacks: { label: function (item, data) { return data.datasets[item.datasetIndex].label + ' : ' + number(item.yLabel); } } }, hover: { mode: 'nearest', intersect: false }, scales: { xAxes: [{ gridLines: { display: false }, ticks: { fontColor: textColor, maxTicksLimit: 16, callback: function (value) { return 'J' + value; } } }], yAxes: [{ gridLines: { color: gridColor }, ticks: { fontColor: textColor, callback: number } }] } }
        });
    }
    var flowCanvas = document.getElementById('finance-cash-flow');
    if (flowCanvas) {
        new Chart(flowCanvas.getContext('2d'), {
            type: 'bar',
            data: { labels: comparison.labels, datasets: [
                { label: 'Entrées', data: comparison.in, backgroundColor: rgba(primary, .82), borderRadius: 5 },
                { label: 'Sorties', data: comparison.out, backgroundColor: rgba(accent, .82), borderRadius: 5 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, tooltips: { callbacks: { label: function (item, data) { return data.datasets[item.datasetIndex].label + ' : ' + number(item.yLabel); } } }, scales: { xAxes: [{ gridLines: { display: false }, ticks: { fontColor: textColor } }], yAxes: [{ gridLines: { color: gridColor }, ticks: { beginAtZero: true, fontColor: textColor, callback: number } }] } }
        });
    }
});
</script>
@endsection
