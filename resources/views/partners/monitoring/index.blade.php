@extends('layouts.admin')

@section('content')
@php
    $statusLabel = fn (?string $status) => [
        'awaiting_delivery_quote' => 'Frais à définir', 'awaiting_customer_confirmation' => 'Attente du client',
        'awaiting_prepayment' => 'Paiement attendu', 'preparing' => 'En préparation', 'ready_for_courier' => 'Prête au retrait',
        'assigned_to_courier' => 'Livreur affecté', 'handed_to_courier' => 'Colis remis', 'out_for_delivery' => 'En livraison',
        'delivered' => 'Livrée', 'canceled' => 'Annulée',
    ][$status ?? ''] ?? 'En attente';
@endphp
<main class="container-fluid py-4 partner-monitoring-page">
    <header class="pm-hero">
        <div><span class="pm-eyebrow">PILOTAGE DES PARTENAIRES</span><h1>Suivi de l’activité</h1><p>Chiffre d’affaires livré, commandes et activité de tous les partenaires.</p></div>
        <div class="pm-live"><span class="pm-live-dot"></span><span>Actualisation automatique</span><small>Mis à jour à <time id="pm-updated-at">{{ now()->format('H:i:s') }}</time></small></div>
    </header>

    <section class="pm-summary" aria-label="Indicateurs globaux">
        <article><span class="material-icons">groups</span><div><small>Partenaires actifs</small><strong data-summary="partners_active">{{ number_format($summary['partners_active'], 0, ',', ' ') }}</strong><em>sur <b data-summary="partners_total">{{ number_format($summary['partners_total'], 0, ',', ' ') }}</b> comptes</em></div></article>
        <article><span class="material-icons">payments</span><div><small>CA livré aujourd’hui</small><strong><span data-summary="revenue_today">{{ number_format($summary['revenue_today'], 0, ',', ' ') }}</span> F</strong><em>Commandes remises aux clients</em></div></article>
        <article><span class="material-icons">date_range</span><div><small>CA livré cette semaine</small><strong><span data-summary="revenue_week">{{ number_format($summary['revenue_week'], 0, ',', ' ') }}</span> F</strong><em>Depuis lundi</em></div></article>
        <article><span class="material-icons">trending_up</span><div><small>CA livré cette année</small><strong><span data-summary="revenue_year">{{ number_format($summary['revenue_year'], 0, ',', ' ') }}</span> F</strong><em>Depuis le 1er janvier</em></div></article>
        <article><span class="material-icons">local_shipping</span><div><small>Commandes en cours</small><strong data-summary="orders_active">{{ number_format($summary['orders_active'], 0, ',', ' ') }}</strong><em><span data-summary="orders_today">{{ number_format($summary['orders_today'], 0, ',', ' ') }}</span> reçue(s) aujourd’hui</em></div></article>
    </section>

    <section class="pm-grid">
        <article class="pm-card pm-chart-card"><div class="pm-card-heading"><div><h2>Évolution du chiffre d’affaires</h2><p>Montants des commandes livrées · 12 derniers mois</p></div><span class="pm-chart-badge">FCFA</span></div><div class="pm-chart-wrap"><canvas id="pm-revenue-chart" role="img" aria-label="Évolution mensuelle du chiffre d’affaires des partenaires"></canvas></div></article>
        <article class="pm-card pm-activity-card"><div class="pm-card-heading"><div><h2>Activité récente</h2><p>Dernières commandes créées ou mises à jour</p></div><span class="pm-live-dot"></span></div><div class="pm-activity-list" id="pm-activity-list">
            @forelse($activities as $order)
                <div class="pm-activity-row"><span class="pm-activity-icon material-icons">{{ $order->partner_order_status === 'delivered' ? 'check_circle' : 'receipt_long' }}</span><div class="pm-activity-copy"><strong>{{ $order->code }}</strong><span>{{ $order->partner?->name ?? 'Partenaire supprimé' }} · {{ \App\Support\WestAfricaShippingCatalog::countries()[strtoupper((string) $order->country_code)]['label'] ?? $order->country_code ?? '—' }}</span><small>{{ $order->updated_at?->format('d/m/Y H:i') }}</small></div><div class="pm-activity-side"><b>{{ number_format((int) ($order->total ?: $order->montant), 0, ',', ' ') }} F</b><small>{{ $statusLabel($order->partner_order_status) }}</small></div></div>
            @empty
                <div class="pm-empty">Aucune commande partenaire pour le moment.</div>
            @endforelse
        </div></article>
    </section>

    <section class="pm-card pm-partners-card">
        <div class="pm-partners-heading"><div><h2>Performance par partenaire</h2><p>Classement selon le chiffre d’affaires livré cette année.</p></div><form method="GET" action="{{ route('partner-monitoring.index') }}" class="pm-search"><span class="material-icons" aria-hidden="true">search</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Partenaire ou pays" aria-label="Rechercher un partenaire ou un pays"><button type="submit">Rechercher</button></form></div>
        <div class="pm-table-scroll"><table class="pm-table"><thead><tr><th>Partenaire</th><th>Commandes actives</th><th>CA aujourd’hui</th><th>CA semaine</th><th>CA année</th><th>Livreurs actifs</th><th>Compte</th></tr></thead><tbody id="pm-partners-body">
            @forelse($partners as $partner)
                <tr><td><strong>{{ $partner->name }}</strong><small>{{ $partner->monitor_country }}</small></td><td><span class="pm-count">{{ $partner->orders_active }}</span><small>{{ $partner->orders_total }} au total</small></td><td>{{ number_format($partner->revenue_today, 0, ',', ' ') }} F</td><td>{{ number_format($partner->revenue_week, 0, ',', ' ') }} F</td><td class="pm-year-revenue">{{ number_format($partner->revenue_year, 0, ',', ' ') }} F</td><td>{{ $partner->couriers_active }}</td><td><span class="pm-status {{ $partner->active ? 'is-active' : 'is-inactive' }}">{{ $partner->active ? 'Actif' : 'Désactivé' }}</span></td></tr>
            @empty
                <tr><td colspan="7" class="pm-table-empty">Aucun partenaire ne correspond à cette recherche.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</main>

<style>
.partner-monitoring-page{--pm-brand:var(--app-primary,#147d25);--pm-ink:#202938;--pm-muted:#748093;--pm-line:#e3e8ef;--pm-surface:var(--app-surface,#fff);color:var(--pm-ink)}
.pm-hero{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:22px 25px;margin-bottom:15px;background:var(--pm-surface);border:1px solid var(--pm-line);border-left:4px solid var(--pm-brand)}.pm-eyebrow{color:var(--pm-brand);font-size:10px;font-weight:850;letter-spacing:.14em}.pm-hero h1{margin:6px 0 3px;color:var(--pm-ink);font-size:clamp(24px,3vw,32px);font-weight:850}.pm-hero p{margin:0;color:var(--pm-muted);font-size:12px}.pm-live{display:grid;grid-template-columns:10px auto;align-items:center;column-gap:8px;padding:10px 12px;border:1px solid var(--pm-line);color:var(--pm-ink);font-size:11px;font-weight:750}.pm-live small{grid-column:2;color:var(--pm-muted);font-size:10px;font-weight:500}.pm-live-dot{width:8px;height:8px;border-radius:50%;background:#20a464;box-shadow:0 0 0 4px rgba(32,164,100,.12);animation:pm-pulse 1.8s infinite}@keyframes pm-pulse{50%{box-shadow:0 0 0 7px rgba(32,164,100,0)}}
.pm-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:13px}.pm-summary article{display:flex;align-items:flex-start;gap:11px;min-width:0;padding:14px;background:var(--pm-surface);border:1px solid var(--pm-line);border-top:3px solid var(--pm-brand)}.pm-summary article:nth-child(2){border-top-color:#3882b4}.pm-summary article:nth-child(3){border-top-color:#8765b0}.pm-summary article:nth-child(4){border-top-color:#25a078}.pm-summary article:nth-child(5){border-top-color:#df9b35}.pm-summary article>.material-icons{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;background:color-mix(in srgb,var(--pm-brand) 10%,var(--pm-surface));color:var(--pm-brand);font-size:18px}.pm-summary article div{display:grid;min-width:0;gap:3px}.pm-summary small{color:var(--pm-muted);font-size:10px;font-weight:700}.pm-summary strong{color:var(--pm-ink);font-size:clamp(16px,1.65vw,22px);font-weight:850;line-height:1.2;overflow-wrap:anywhere}.pm-summary em{color:var(--pm-muted);font-size:9px;font-style:normal}.pm-summary em b{font-weight:750}
.pm-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(350px,1fr);gap:13px;margin-bottom:13px}.pm-card{min-width:0;background:var(--pm-surface);border:1px solid var(--pm-line)}.pm-chart-card,.pm-activity-card{padding:17px}.pm-card-heading,.pm-partners-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.pm-card h2,.pm-partners-heading h2{margin:0;color:var(--pm-ink);font-size:14px;font-weight:820}.pm-card-heading p,.pm-partners-heading p{margin:4px 0 0;color:var(--pm-muted);font-size:10px}.pm-chart-badge{padding:5px 8px;background:color-mix(in srgb,var(--pm-brand) 9%,var(--pm-surface));color:var(--pm-brand);font-size:9px;font-weight:800}.pm-chart-wrap{position:relative;height:270px;margin-top:13px}.pm-activity-list{display:grid;max-height:300px;overflow:auto;margin-top:12px}.pm-activity-row{display:grid;grid-template-columns:24px minmax(0,1fr) auto;align-items:center;gap:9px;padding:9px 0;border-top:1px solid var(--pm-line)}.pm-activity-icon{color:var(--pm-brand);font-size:18px}.pm-activity-copy,.pm-activity-side{display:grid;gap:3px;min-width:0}.pm-activity-copy strong{color:var(--pm-brand);font-size:10px}.pm-activity-copy span,.pm-activity-copy small,.pm-activity-side small{color:var(--pm-muted);font-size:9px;overflow-wrap:anywhere}.pm-activity-side{text-align:right}.pm-activity-side b{color:var(--pm-ink);font-size:10px;white-space:nowrap}.pm-empty{padding:25px 5px;color:var(--pm-muted);font-size:11px;text-align:center}
.pm-partners-card{overflow:hidden}.pm-partners-heading{align-items:center;padding:16px 18px;border-bottom:1px solid var(--pm-line)}.pm-search{display:flex;align-items:center;gap:7px}.pm-search>.material-icons{color:var(--pm-muted);font-size:18px}.pm-search input{width:210px;height:36px;padding:7px 9px;border:1px solid var(--pm-line);background:var(--pm-surface);color:var(--pm-ink);font-size:11px}.pm-search button{height:36px;padding:0 11px;border:1px solid var(--pm-brand);background:var(--pm-brand);color:#fff;font-size:10px;font-weight:750;cursor:pointer}.pm-table-scroll{overflow-x:auto}.pm-table{width:100%;border-collapse:collapse;min-width:780px}.pm-table th{padding:10px 13px;background:color-mix(in srgb,var(--pm-brand) 5%,var(--pm-surface));color:var(--pm-muted);font-size:9px;font-weight:850;letter-spacing:.05em;text-align:left;text-transform:uppercase;white-space:nowrap}.pm-table td{padding:11px 13px;border-top:1px solid var(--pm-line);color:var(--pm-ink);font-size:10px;white-space:nowrap}.pm-table td:first-child{min-width:170px;white-space:normal}.pm-table td:first-child strong,.pm-table td:first-child small,.pm-table td:nth-child(2) small{display:block}.pm-table td:first-child strong{font-size:11px}.pm-table td:first-child small,.pm-table td:nth-child(2) small{margin-top:3px;color:var(--pm-muted);font-size:9px}.pm-table .pm-year-revenue{color:var(--pm-brand);font-weight:850}.pm-count{display:inline-grid;place-items:center;min-width:24px;height:22px;padding:0 6px;background:color-mix(in srgb,var(--pm-brand) 10%,var(--pm-surface));color:var(--pm-brand);font-weight:800}.pm-status{display:inline-block;padding:4px 7px;font-size:9px;font-weight:750}.pm-status.is-active{background:#e8f5ed;color:#247347}.pm-status.is-inactive{background:#f8e9e9;color:#9f3b3b}.pm-table-empty{padding:24px!important;color:var(--pm-muted)!important;text-align:center}
html[data-app-theme="dark"] .partner-monitoring-page{--pm-ink:var(--app-text);--pm-muted:var(--app-muted);--pm-line:var(--app-border);--pm-surface:var(--app-surface)}html[data-app-theme="dark"] .pm-status.is-active{background:#173b2b;color:#85d6a5}html[data-app-theme="dark"] .pm-status.is-inactive{background:#43282b;color:#f0a5a5}
@media(max-width:1100px){.pm-summary{grid-template-columns:repeat(3,minmax(0,1fr))}.pm-grid{grid-template-columns:minmax(0,1fr)}}@media(max-width:650px){.partner-monitoring-page{padding:13px 10px 25px!important}.pm-hero{align-items:stretch;flex-direction:column;padding:17px}.pm-live{align-self:flex-start}.pm-summary{grid-template-columns:1fr 1fr;gap:7px}.pm-summary article{padding:11px;gap:8px}.pm-summary article>.material-icons{flex-basis:29px;width:29px;height:29px;font-size:16px}.pm-summary strong{font-size:16px}.pm-grid{gap:9px}.pm-chart-card,.pm-activity-card{padding:13px}.pm-chart-wrap{height:230px}.pm-partners-heading{align-items:stretch;flex-direction:column}.pm-search input{width:100%;min-width:0}.pm-search{width:100%}.pm-search button{flex:0 0 auto}}
</style>

<script>
window.addEventListener('load', () => {
    const number = value => new Intl.NumberFormat('fr-FR').format(Number(value || 0));
    const labels = @json($monthlyLabels);
    const values = @json($monthlyValues);
    const brand = getComputedStyle(document.querySelector('.partner-monitoring-page')).getPropertyValue('--pm-brand').trim() || '#147d25';
    const dark = document.documentElement.dataset.appTheme === 'dark';
    let chart = null;
    if (window.Chart) {
        chart = new Chart(document.getElementById('pm-revenue-chart').getContext('2d'), {type:'bar',data:{labels:labels,datasets:[{label:'CA livré (F)',data:values,backgroundColor:brand+'aa',borderColor:brand,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,legend:{display:false},scales:{yAxes:[{ticks:{beginAtZero:true,fontColor:dark?'#a8b1bd':'#748093',callback:value=>number(value)},gridLines:{color:dark?'#343b46':'#e8ecf1'}}],xAxes:[{ticks:{fontColor:dark?'#a8b1bd':'#748093'},gridLines:{display:false}}]},tooltips:{callbacks:{label:item=>number(item.yLabel)+' F'}}}});
    }
    const make = (tag, className, text) => { const el = document.createElement(tag); if (className) el.className = className; el.textContent = text ?? ''; return el; };
    const renderPartners = partners => {
        const body = document.getElementById('pm-partners-body'); body.replaceChildren();
        if (!partners.length) { const row=make('tr'); const cell=make('td','pm-table-empty','Aucun partenaire ne correspond à cette recherche.'); cell.colSpan=7; row.append(cell); body.append(row); return; }
        partners.forEach(partner => {
            const row=make('tr'), nameCell=make('td'), name=make('strong','',partner.name), country=make('small','',partner.country), activeCell=make('td'), activeCount=make('span','pm-count',number(partner.orders_active)), total=make('small','',number(partner.orders_total)+' au total');
            nameCell.append(name,country); activeCell.append(activeCount,total); row.append(nameCell,activeCell,make('td','',number(partner.revenue_today)+' F'),make('td','',number(partner.revenue_week)+' F'),make('td','pm-year-revenue',number(partner.revenue_year)+' F'),make('td','',number(partner.couriers_active)));
            const statusCell=make('td'), status=make('span','pm-status '+(partner.active?'is-active':'is-inactive'),partner.active?'Actif':'Désactivé'); statusCell.append(status); row.append(statusCell); body.append(row);
        });
    };
    const renderActivities = activities => {
        const list=document.getElementById('pm-activity-list'); list.replaceChildren();
        if (!activities.length) { list.append(make('div','pm-empty','Aucune commande partenaire pour le moment.')); return; }
        activities.forEach(activity => { const row=make('div','pm-activity-row'), icon=make('span','pm-activity-icon material-icons',activity.status==='Livrée'?'check_circle':'receipt_long'), copy=make('div','pm-activity-copy'), side=make('div','pm-activity-side'); copy.append(make('strong','',activity.code),make('span','',activity.partner+' · '+activity.country),make('small','',activity.updated_at)); side.append(make('b','',number(activity.amount)+' F'),make('small','',activity.status)); row.append(icon,copy,side); list.append(row); });
    };
    const refresh = async () => {
        try {
            const url = new URL(@json(route('partner-monitoring.snapshot')), window.location.origin);
            const search = new URLSearchParams(window.location.search).get('search'); if (search) url.searchParams.set('search', search);
            const response = await fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},cache:'no-store'}); if (!response.ok) return;
            const data = await response.json();
            Object.entries(data.summary).forEach(([key,value]) => { const node=document.querySelector('[data-summary="'+key+'"]'); if(node) node.textContent=number(value); });
            document.getElementById('pm-updated-at').textContent=data.updated_at;
            renderPartners(data.partners); renderActivities(data.activities);
            if (chart) { chart.data.labels=data.monthly_labels; chart.data.datasets[0].data=data.monthly_values; chart.update(); }
        } catch (error) { /* Le tableau reste consultable si une actualisation réseau échoue. */ }
    };
    window.setInterval(refresh, 30000);
});
</script>
@endsection
