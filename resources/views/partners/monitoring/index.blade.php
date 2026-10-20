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
        <article><span class="material-icons">ads_click</span><div><small>Clics sur les liens</small><strong data-summary="clicks_total">{{ number_format($summary['clicks_total'], 0, ',', ' ') }}</strong><em><span data-summary="visitors_unique">{{ number_format($summary['visitors_unique'], 0, ',', ' ') }}</span> visiteurs distincts</em></div></article>
        <article><span class="material-icons">task_alt</span><div><small>Commandes terminées</small><strong data-summary="orders_completed">{{ number_format($summary['orders_completed'], 0, ',', ' ') }}</strong><em>Livrées aux clients</em></div></article>
        <article><span class="material-icons">cancel</span><div><small>Commandes annulées</small><strong data-summary="orders_canceled">{{ number_format($summary['orders_canceled'], 0, ',', ' ') }}</strong><em>Motifs consultables ci-dessous</em></div></article>
        <article><span class="material-icons">receipt_long</span><div><small>Total des commandes</small><strong data-summary="orders_total">{{ number_format($summary['orders_total'], 0, ',', ' ') }}</strong><em>Tous états confondus</em></div></article>
    </section>

    <section class="pm-status-breakdown" aria-label="Répartition des commandes">
        <h2>Répartition des commandes</h2>
        <div id="pm-status-list">
            @forelse($orderStatuses as $status => $count)
                <span class="pm-status-chip"><b>{{ number_format($count, 0, ',', ' ') }}</b>{{ $statusLabel($status) }}</span>
            @empty
                <span class="pm-status-chip">Aucune commande enregistrée</span>
            @endforelse
        </div>
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
        <div class="pm-partners-heading"><div><h2>Performance par partenaire</h2><p>Commandes, trafic des liens et chiffre d’affaires livré.</p></div><form method="GET" action="{{ route('partner-monitoring.index') }}" class="pm-search"><span class="material-icons" aria-hidden="true">search</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Partenaire ou pays" aria-label="Rechercher un partenaire ou un pays"><button type="submit">Rechercher</button></form></div>
        <div class="pm-table-scroll"><table class="pm-table"><thead><tr><th>Partenaire</th><th>Commandes</th><th>Visiteurs / clics</th><th>CA aujourd’hui</th><th>CA semaine</th><th>CA année</th><th>Livreurs actifs</th><th>Compte</th></tr></thead><tbody id="pm-partners-body">
            @forelse($partners as $partner)
                <tr><td><strong>{{ $partner->name }}</strong><small>{{ $partner->monitor_country }}</small></td><td><span class="pm-count">{{ $partner->orders_active }}</span><small>{{ $partner->orders_completed }} livrées · {{ $partner->orders_canceled }} annulées</small><small>{{ $partner->orders_total }} au total</small></td><td>{{ $partner->link_visitors }} visiteurs · {{ $partner->link_clicks }} clics</td><td>{{ number_format($partner->revenue_today, 0, ',', ' ') }} F</td><td>{{ number_format($partner->revenue_week, 0, ',', ' ') }} F</td><td class="pm-year-revenue">{{ number_format($partner->revenue_year, 0, ',', ' ') }} F</td><td>{{ $partner->couriers_active }}</td><td><span class="pm-status {{ $partner->active ? 'is-active' : 'is-inactive' }}">{{ $partner->active ? 'Actif' : 'Désactivé' }}</span></td></tr>
            @empty
                <tr><td colspan="8" class="pm-table-empty">Aucun partenaire ne correspond à cette recherche.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>

    <section class="pm-card pm-detail-card">
        <div class="pm-detail-heading"><div><h2>Performance des liens de commande</h2><p>Visiteurs distincts par lien (session navigateur anonymisée), visites, commandes créées et leur issue.</p></div></div>
        <div class="pm-table-scroll"><table class="pm-table pm-detail-table"><thead><tr><th>Lien / pays</th><th>Partenaire</th><th>Visiteurs</th><th>Clics</th><th>Commandes</th><th>En cours</th><th>Livrées</th><th>Annulées</th><th>État du lien</th></tr></thead><tbody id="pm-links-body">
            @forelse($links as $link)
                <tr><td><strong>{{ $link['title'] }}</strong><small>{{ $link['country'] }}</small></td><td>{{ $link['partner'] }}</td><td>{{ number_format($link['visitors'], 0, ',', ' ') }}</td><td>{{ number_format($link['clicks'], 0, ',', ' ') }}</td><td>{{ $link['orders'] }}</td><td>{{ $link['active_orders'] }}</td><td>{{ $link['completed_orders'] }}</td><td>{{ $link['canceled_orders'] }}</td><td><span class="pm-status {{ $link['is_active'] ? 'is-active' : 'is-inactive' }}">{{ $link['is_active'] ? 'Actif' : 'Désactivé' }}</span></td></tr>
            @empty
                <tr><td colspan="9" class="pm-table-empty">Aucun lien de commande enregistré.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>

    <section class="pm-card pm-detail-card">
        <div class="pm-detail-heading"><div><h2>Commandes et suivi des partenaires</h2><p>Toutes les commandes · 25 par page · partenaire, lien, livreur, paiements et motif d’annulation.</p></div></div>
        <div class="pm-table-scroll"><table class="pm-table pm-detail-table pm-orders-table"><thead><tr><th>Commande</th><th>Partenaire</th><th>Lien</th><th>Client</th><th>Livreur</th><th>Montant</th><th>Livraison</th><th>Payé</th><th>Paiement</th><th>État</th><th>Motif d’annulation</th><th>Dernière activité</th></tr></thead><tbody id="pm-orders-body">
            @forelse($orders as $order)
                <tr><td><strong>{{ $order->code }}</strong></td><td>{{ $order->partner?->name ?? '—' }}</td><td>{{ $order->publicOrderLink?->title ?? 'Catalogue direct' }}</td><td>{{ $order->costumer?->name ?? 'Client non renseigné' }}</td><td>{{ $order->user?->name ?? 'Non affecté' }}</td><td>{{ number_format((int) ($order->total ?: $order->montant), 0, ',', ' ') }} F</td><td>{{ number_format((int) $order->tax, 0, ',', ' ') }} F</td><td>{{ number_format((int) $order->paid_amount, 0, ',', ' ') }} F</td><td>{{ $order->payments->pluck('status')->unique()->implode(', ') ?: 'Aucun paiement' }}</td><td><span class="pm-order-state">{{ $statusLabel($order->partner_order_status) }}</span></td><td>{{ $order->partner_order_status === 'canceled' ? ($order->motif ?: 'Motif non renseigné') : '—' }}</td><td>{{ $order->updated_at?->format('d/m/Y H:i') }}</td></tr>
            @empty
                <tr><td colspan="12" class="pm-table-empty">Aucune commande partenaire enregistrée.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($orders->hasPages())<div class="pm-orders-pagination">{{ $orders->links() }}</div>@endif
    </section>
</main>

<style>
.partner-monitoring-page{--pm-brand:var(--app-primary,#147d25);--pm-ink:#202938;--pm-muted:#748093;--pm-line:#e3e8ef;--pm-surface:var(--app-surface,#fff);color:var(--pm-ink)}
.pm-hero{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:22px 25px;margin-bottom:15px;background:var(--pm-surface);border:1px solid var(--pm-line);border-left:4px solid var(--pm-brand)}.pm-eyebrow{color:var(--pm-brand);font-size:10px;font-weight:850;letter-spacing:.14em}.pm-hero h1{margin:6px 0 3px;color:var(--pm-ink);font-size:clamp(24px,3vw,32px);font-weight:850}.pm-hero p{margin:0;color:var(--pm-muted);font-size:12px}.pm-live{display:grid;grid-template-columns:10px auto;align-items:center;column-gap:8px;padding:10px 12px;border:1px solid var(--pm-line);color:var(--pm-ink);font-size:11px;font-weight:750}.pm-live small{grid-column:2;color:var(--pm-muted);font-size:10px;font-weight:500}.pm-live-dot{width:8px;height:8px;border-radius:50%;background:#20a464;box-shadow:0 0 0 4px rgba(32,164,100,.12);animation:pm-pulse 1.8s infinite}@keyframes pm-pulse{50%{box-shadow:0 0 0 7px rgba(32,164,100,0)}}
.pm-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:13px}.pm-summary article{display:flex;align-items:flex-start;gap:11px;min-width:0;padding:14px;background:var(--pm-surface);border:1px solid var(--pm-line);border-top:3px solid var(--pm-brand)}.pm-summary article:nth-child(2){border-top-color:#3882b4}.pm-summary article:nth-child(3){border-top-color:#8765b0}.pm-summary article:nth-child(4){border-top-color:#25a078}.pm-summary article:nth-child(5){border-top-color:#df9b35}.pm-summary article>.material-icons{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;background:color-mix(in srgb,var(--pm-brand) 10%,var(--pm-surface));color:var(--pm-brand);font-size:18px}.pm-summary article div{display:grid;min-width:0;gap:3px}.pm-summary small{color:var(--pm-muted);font-size:10px;font-weight:700}.pm-summary strong{color:var(--pm-ink);font-size:clamp(16px,1.65vw,22px);font-weight:850;line-height:1.2;overflow-wrap:anywhere}.pm-summary em{color:var(--pm-muted);font-size:9px;font-style:normal}.pm-summary em b{font-weight:750}
.pm-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(350px,1fr);gap:13px;margin-bottom:13px}.pm-card{min-width:0;background:var(--pm-surface);border:1px solid var(--pm-line)}.pm-chart-card,.pm-activity-card{padding:17px}.pm-card-heading,.pm-partners-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.pm-card h2,.pm-partners-heading h2{margin:0;color:var(--pm-ink);font-size:14px;font-weight:820}.pm-card-heading p,.pm-partners-heading p{margin:4px 0 0;color:var(--pm-muted);font-size:10px}.pm-chart-badge{padding:5px 8px;background:color-mix(in srgb,var(--pm-brand) 9%,var(--pm-surface));color:var(--pm-brand);font-size:9px;font-weight:800}.pm-chart-wrap{position:relative;height:270px;margin-top:13px}.pm-activity-list{display:grid;max-height:300px;overflow:auto;margin-top:12px}.pm-activity-row{display:grid;grid-template-columns:24px minmax(0,1fr) auto;align-items:center;gap:9px;padding:9px 0;border-top:1px solid var(--pm-line)}.pm-activity-icon{color:var(--pm-brand);font-size:18px}.pm-activity-copy,.pm-activity-side{display:grid;gap:3px;min-width:0}.pm-activity-copy strong{color:var(--pm-brand);font-size:10px}.pm-activity-copy span,.pm-activity-copy small,.pm-activity-side small{color:var(--pm-muted);font-size:9px;overflow-wrap:anywhere}.pm-activity-side{text-align:right}.pm-activity-side b{color:var(--pm-ink);font-size:10px;white-space:nowrap}.pm-empty{padding:25px 5px;color:var(--pm-muted);font-size:11px;text-align:center}
.pm-partners-card{overflow:hidden}.pm-partners-heading{align-items:center;padding:16px 18px;border-bottom:1px solid var(--pm-line)}.pm-search{display:flex;align-items:center;gap:7px}.pm-search>.material-icons{color:var(--pm-muted);font-size:18px}.pm-search input{width:210px;height:36px;padding:7px 9px;border:1px solid var(--pm-line);background:var(--pm-surface);color:var(--pm-ink);font-size:11px}.pm-search button{height:36px;padding:0 11px;border:1px solid var(--pm-brand);background:var(--pm-brand);color:#fff;font-size:10px;font-weight:750;cursor:pointer}.pm-table-scroll{overflow-x:auto}.pm-table{width:100%;border-collapse:collapse;min-width:780px}.pm-table th{padding:10px 13px;background:color-mix(in srgb,var(--pm-brand) 5%,var(--pm-surface));color:var(--pm-muted);font-size:9px;font-weight:850;letter-spacing:.05em;text-align:left;text-transform:uppercase;white-space:nowrap}.pm-table td{padding:11px 13px;border-top:1px solid var(--pm-line);color:var(--pm-ink);font-size:10px;white-space:nowrap}.pm-table td:first-child{min-width:170px;white-space:normal}.pm-table td:first-child strong,.pm-table td:first-child small,.pm-table td:nth-child(2) small{display:block}.pm-table td:first-child strong{font-size:11px}.pm-table td:first-child small,.pm-table td:nth-child(2) small{margin-top:3px;color:var(--pm-muted);font-size:9px}.pm-table .pm-year-revenue{color:var(--pm-brand);font-weight:850}.pm-count{display:inline-grid;place-items:center;min-width:24px;height:22px;padding:0 6px;background:color-mix(in srgb,var(--pm-brand) 10%,var(--pm-surface));color:var(--pm-brand);font-weight:800}.pm-status{display:inline-block;padding:4px 7px;font-size:9px;font-weight:750}.pm-status.is-active{background:#e8f5ed;color:#247347}.pm-status.is-inactive{background:#f8e9e9;color:#9f3b3b}.pm-table-empty{padding:24px!important;color:var(--pm-muted)!important;text-align:center}
.pm-detail-card{margin-top:13px;overflow:hidden}.pm-detail-heading{padding:16px 18px;border-bottom:1px solid var(--pm-line)}.pm-detail-heading h2{margin:0;color:var(--pm-ink);font-size:14px;font-weight:820}.pm-detail-heading p{margin:4px 0 0;color:var(--pm-muted);font-size:10px}.pm-detail-table{min-width:980px}.pm-orders-table{min-width:1450px}.pm-order-state{display:inline-block;padding:4px 7px;background:color-mix(in srgb,var(--pm-brand) 10%,var(--pm-surface));color:var(--pm-brand);font-size:9px;font-weight:750}.pm-orders-table td:nth-child(11){white-space:normal;min-width:160px;max-width:250px;overflow-wrap:anywhere}
.pm-status-breakdown{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:13px;padding:12px 15px;background:var(--pm-surface);border:1px solid var(--pm-line)}.pm-status-breakdown h2{margin:0 5px 0 0;color:var(--pm-ink);font-size:11px;font-weight:820}.pm-status-breakdown>div{display:flex;gap:6px;flex-wrap:wrap}.pm-status-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;background:color-mix(in srgb,var(--pm-brand) 7%,var(--pm-surface));color:var(--pm-muted);font-size:9px}.pm-status-chip b{color:var(--pm-brand);font-size:10px}
.pm-orders-pagination{padding:12px 16px;border-top:1px solid var(--pm-line)}.pm-orders-pagination nav{display:flex;justify-content:flex-end}
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
        if (!partners.length) { const row=make('tr'); const cell=make('td','pm-table-empty','Aucun partenaire ne correspond à cette recherche.'); cell.colSpan=8; row.append(cell); body.append(row); return; }
        partners.forEach(partner => {
            const row=make('tr'), nameCell=make('td'), name=make('strong','',partner.name), country=make('small','',partner.country), activeCell=make('td'), activeCount=make('span','pm-count',number(partner.orders_active)), completed=make('small','',number(partner.orders_completed)+' livrées · '+number(partner.orders_canceled)+' annulées'), total=make('small','',number(partner.orders_total)+' au total');
            nameCell.append(name,country); activeCell.append(activeCount,completed,total); row.append(nameCell,activeCell,make('td','',number(partner.link_visitors)+' visiteurs · '+number(partner.link_clicks)+' clics'),make('td','',number(partner.revenue_today)+' F'),make('td','',number(partner.revenue_week)+' F'),make('td','pm-year-revenue',number(partner.revenue_year)+' F'),make('td','',number(partner.couriers_active)));
            const statusCell=make('td'), status=make('span','pm-status '+(partner.active?'is-active':'is-inactive'),partner.active?'Actif':'Désactivé'); statusCell.append(status); row.append(statusCell); body.append(row);
        });
    };
    const renderLinks = links => {
        const body=document.getElementById('pm-links-body'); body.replaceChildren();
        if (!links.length) { const row=make('tr'), cell=make('td','pm-table-empty','Aucun lien de commande enregistré.'); cell.colSpan=9; row.append(cell); body.append(row); return; }
        links.forEach(link => { const row=make('tr'), nameCell=make('td'), title=make('strong','',link.title), country=make('small','',link.country), stateCell=make('td'), state=make('span','pm-status '+(link.is_active?'is-active':'is-inactive'),link.is_active?'Actif':'Désactivé'); nameCell.append(title,country); row.append(nameCell,make('td','',link.partner),make('td','',number(link.visitors)),make('td','',number(link.clicks)),make('td','',number(link.orders)),make('td','',number(link.active_orders)),make('td','',number(link.completed_orders)),make('td','',number(link.canceled_orders))); stateCell.append(state); row.append(stateCell); body.append(row); });
    };
    const renderOrders = orders => {
        const body=document.getElementById('pm-orders-body'); body.replaceChildren();
        if (!orders.length) { const row=make('tr'), cell=make('td','pm-table-empty','Aucune commande partenaire enregistrée.'); cell.colSpan=12; row.append(cell); body.append(row); return; }
        orders.forEach(order => { const row=make('tr'), stateCell=make('td'), state=make('span','pm-order-state',order.status); stateCell.append(state); row.append(make('td','',order.code),make('td','',order.partner),make('td','',order.link),make('td','',order.customer),make('td','',order.courier),make('td','',number(order.amount)+' F'),make('td','',number(order.delivery_fee)+' F'),make('td','',number(order.paid_amount)+' F'),make('td','',order.payment_status),stateCell,make('td','',order.status_code==='canceled'?(order.reason||'Motif non renseigné'):'—'),make('td','',order.updated_at||'—')); body.append(row); });
    };
    const renderStatuses = statuses => {
        const labels={awaiting_delivery_quote:'Frais à définir',awaiting_customer_confirmation:'Attente du client',awaiting_prepayment:'Paiement attendu',preparing:'En préparation',ready_for_courier:'Prête au retrait',assigned_to_courier:'Livreur affecté',handed_to_courier:'Colis remis',out_for_delivery:'En livraison',delivered:'Livrée',canceled:'Annulée',unknown:'Autre'};
        const list=document.getElementById('pm-status-list'); list.replaceChildren();
        const entries=Object.entries(statuses||{});
        if (!entries.length) { list.append(make('span','pm-status-chip','Aucune commande enregistrée')); return; }
        entries.forEach(([status,count])=>{ const chip=make('span','pm-status-chip'), total=make('b','',number(count)); chip.append(total,document.createTextNode(labels[status]||status)); list.append(chip); });
    };
    const renderActivities = activities => {
        const list=document.getElementById('pm-activity-list'); list.replaceChildren();
        if (!activities.length) { list.append(make('div','pm-empty','Aucune commande partenaire pour le moment.')); return; }
        activities.forEach(activity => { const row=make('div','pm-activity-row'), icon=make('span','pm-activity-icon material-icons',activity.status==='Livrée'?'check_circle':'receipt_long'), copy=make('div','pm-activity-copy'), side=make('div','pm-activity-side'); copy.append(make('strong','',activity.code),make('span','',activity.partner+' · '+activity.country),make('small','',activity.updated_at)); side.append(make('b','',number(activity.amount)+' F'),make('small','',activity.status)); row.append(icon,copy,side); list.append(row); });
    };
    const refresh = async () => {
        try {
            const url = new URL(@json(route('partner-monitoring.snapshot')), window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            ['search','orders_page'].forEach(key => { const value=currentParams.get(key); if(value) url.searchParams.set(key,value); });
            const response = await fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},cache:'no-store'}); if (!response.ok) return;
            const data = await response.json();
            Object.entries(data.summary).forEach(([key,value]) => { const node=document.querySelector('[data-summary="'+key+'"]'); if(node) node.textContent=number(value); });
            document.getElementById('pm-updated-at').textContent=data.updated_at;
            renderPartners(data.partners); renderActivities(data.activities); renderLinks(data.links); renderOrders(data.orders); renderStatuses(data.order_statuses);
            if (chart) { chart.data.labels=data.monthly_labels; chart.data.datasets[0].data=data.monthly_values; chart.update(); }
        } catch (error) { /* Le tableau reste consultable si une actualisation réseau échoue. */ }
    };
    window.setInterval(refresh, 30000);
});
</script>
@endsection
