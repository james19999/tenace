@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
<main class="container-fluid py-4 partner-dashboard-page">
    <header class="partner-dashboard-hero"><div><span class="partner-dashboard-eyebrow">ESPACE PARTENAIRE · {{ $countryName }}</span><h1>Tableau de bord</h1><p>Activité des commandes livrées et suivi de ton équipe.</p></div><nav><a href="{{ route('partner.orders.index') }}">Commandes à livrer <span aria-hidden="true">→</span></a><a href="{{ route('partner.couriers.index') }}">Mes livreurs <span aria-hidden="true">→</span></a></nav></header>

    <section class="partner-dashboard-revenue" aria-label="Chiffre d’affaires livré">
        @foreach ($revenuePeriods as $period)
            <article class="partner-dashboard-revenue-card"><span>{{ $period['label'] }}</span><strong>{{ number_format($period['amount'], 0, ',', ' ') }} <small>F</small></strong><p>{{ $period['orders_count'] }} commande(s) livrée(s)</p></article>
        @endforeach
    </section>

    <section class="partner-dashboard-quick-stats" aria-label="Commandes en cours">
        <article><span class="material-icons" aria-hidden="true">pending_actions</span><div><small>À traiter</small><strong>{{ $attentionCount }}</strong></div></article>
        <article><span class="material-icons" aria-hidden="true">local_shipping</span><div><small>En cours</small><strong>{{ $activeCount }}</strong></div></article>
        <article><span class="material-icons" aria-hidden="true">groups</span><div><small>Livreurs actifs</small><strong>{{ $activeCourierCount }}</strong></div></article>
    </section>

    <section class="partner-dashboard-charts">
        <article class="partner-dashboard-chart-card partner-dashboard-chart-wide"><div class="partner-dashboard-chart-heading"><div><h2>Chiffre d’affaires mensuel</h2><p>Commandes livrées cette année, hors frais de livraison.</p></div><span>{{ now()->year }}</span></div><div class="partner-dashboard-chart-wrap"><canvas id="partner-monthly-revenue-chart" aria-label="Graphique du chiffre d’affaires mensuel" role="img"></canvas></div></article>
        <article class="partner-dashboard-chart-card"><div class="partner-dashboard-chart-heading"><div><h2>État des commandes</h2><p>Répartition depuis le début de l’activité.</p></div></div><div class="partner-dashboard-status-chart"><canvas id="partner-order-status-chart" aria-label="Graphique de répartition des commandes" role="img"></canvas></div>@if(empty($ordersByStatusValues))<p class="partner-dashboard-chart-empty">Les commandes apparaîtront ici.</p>@endif</article>
    </section>

    <section class="partner-dashboard-recent"><div class="partner-dashboard-recent-heading"><div><h2>Commandes récentes</h2><p>Dernières demandes reçues dans {{ $countryName }}.</p></div><a href="{{ route('partner.orders.index') }}">Voir les commandes à livrer <span aria-hidden="true">→</span></a></div>
        @forelse ($recentOrders as $order)
            <a class="partner-dashboard-recent-row" href="{{ route('partner.orders.show', $order) }}"><span><strong>{{ $order->code }}</strong><small>{{ $order->costumer?->name ?? 'Client' }} · {{ $order->created_at?->format('d/m/Y H:i') }}</small></span><strong>{{ number_format((int) $order->montant, 0, ',', ' ') }} F</strong><span class="material-icons" aria-hidden="true">arrow_forward</span></a>
        @empty
            <div class="partner-dashboard-recent-empty">Aucune commande reçue pour le moment.</div>
        @endforelse
    </section>
</main>

<style>
.partner-dashboard-page{--pd-brand:var(--app-primary,#147d25);--pd-ink:#202938;--pd-muted:#748093;--pd-line:#e5e9ef}
.partner-dashboard-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px 27px;margin-bottom:17px;background:var(--app-surface,#fff);border:1px solid var(--pd-line);border-left:4px solid var(--pd-brand)}
.partner-dashboard-eyebrow{color:var(--pd-brand);font-size:10px;font-weight:850;letter-spacing:.13em}
.partner-dashboard-hero h1{margin:7px 0 3px;color:var(--pd-ink);font-size:clamp(25px,3vw,34px);font-weight:850;line-height:1.15}
.partner-dashboard-hero p{margin:0;color:var(--pd-muted);font-size:13px}
.partner-dashboard-hero nav{display:flex;flex-wrap:wrap;gap:8px}
.partner-dashboard-hero nav a{padding:10px 12px;border:1px solid var(--pd-line);color:var(--pd-brand);font-size:11px;font-weight:750;white-space:nowrap}
.partner-dashboard-hero nav a:hover,.partner-dashboard-hero nav a:focus-visible{background:var(--pd-brand)!important;color:#fff!important;text-decoration:none!important;opacity:1!important}
.partner-dashboard-hero nav a:hover *,.partner-dashboard-hero nav a:focus-visible *{color:#fff!important}
.partner-dashboard-revenue{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin-bottom:13px}
.partner-dashboard-revenue-card{position:relative;min-width:0;padding:17px 19px;background:var(--app-surface,#fff);border:1px solid var(--pd-line);border-top:3px solid var(--pd-brand)}
.partner-dashboard-revenue-card:nth-child(2){border-top-color:#367faf}.partner-dashboard-revenue-card:nth-child(3){border-top-color:#8163ad}
.partner-dashboard-revenue-card>span{color:var(--pd-muted);font-size:11px;font-weight:750}
.partner-dashboard-revenue-card>strong{display:block;margin-top:7px;color:var(--pd-ink);font-size:clamp(22px,2.2vw,29px);font-weight:850;letter-spacing:-.03em;overflow-wrap:anywhere}
.partner-dashboard-revenue-card>strong small{font-size:.6em}.partner-dashboard-revenue-card>p{margin:5px 0 0;color:var(--pd-muted);font-size:10px}
.partner-dashboard-quick-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px;margin-bottom:18px}
.partner-dashboard-quick-stats article{display:flex;align-items:center;gap:12px;padding:13px 16px;background:var(--app-surface,#fff);border:1px solid var(--pd-line)}
.partner-dashboard-quick-stats article>.material-icons{display:grid;place-items:center;width:36px;height:36px;background:color-mix(in srgb,var(--pd-brand) 9%,#fff);color:var(--pd-brand);font-size:19px}
.partner-dashboard-quick-stats article div{display:grid;gap:2px}.partner-dashboard-quick-stats small{color:var(--pd-muted);font-size:10px}.partner-dashboard-quick-stats strong{color:var(--pd-ink);font-size:20px;line-height:1}
.partner-dashboard-charts{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(270px,.8fr);gap:13px;margin-bottom:18px}
.partner-dashboard-chart-card,.partner-dashboard-recent{min-width:0;padding:17px 19px;background:var(--app-surface,#fff);border:1px solid var(--pd-line)}
.partner-dashboard-chart-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.partner-dashboard-chart-heading h2,.partner-dashboard-recent-heading h2{margin:0;color:var(--pd-ink);font-size:14px;font-weight:800}
.partner-dashboard-chart-heading p,.partner-dashboard-recent-heading p{margin:4px 0 0;color:var(--pd-muted);font-size:10px}
.partner-dashboard-chart-heading>span{padding:5px 7px;background:color-mix(in srgb,var(--pd-brand) 8%,#fff);color:var(--pd-brand);font-size:10px;font-weight:800}
.partner-dashboard-chart-wrap{position:relative;height:250px;margin-top:12px}.partner-dashboard-status-chart{position:relative;height:205px;margin-top:12px}
.partner-dashboard-chart-empty{margin:0;color:var(--pd-muted);font-size:10px;text-align:center}
.partner-dashboard-recent{padding:0;overflow:hidden}.partner-dashboard-recent-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 19px;border-bottom:1px solid var(--pd-line)}
.partner-dashboard-recent-heading>a{color:var(--pd-brand);font-size:10px;font-weight:750;white-space:nowrap}
.partner-dashboard-recent-row{display:grid;grid-template-columns:minmax(0,1fr) auto 18px;align-items:center;gap:12px;padding:11px 19px;border-bottom:1px solid var(--pd-line);color:var(--pd-ink)}
.partner-dashboard-recent-row:last-child{border-bottom:0}.partner-dashboard-recent-row:hover{background:color-mix(in srgb,var(--pd-brand) 4%,var(--app-surface,#fff));color:var(--pd-ink);text-decoration:none}
.partner-dashboard-recent-row>span:first-child{display:grid;gap:3px;min-width:0}.partner-dashboard-recent-row>span:first-child strong{color:var(--pd-brand);font-size:10px}
.partner-dashboard-recent-row small{color:var(--pd-muted);font-size:10px;overflow-wrap:anywhere}.partner-dashboard-recent-row>strong{font-size:11px;white-space:nowrap}
.partner-dashboard-recent-row>.material-icons{color:var(--pd-muted);font-size:17px}.partner-dashboard-recent-empty{padding:20px;color:var(--pd-muted);font-size:11px}
html[data-app-theme="dark"] .partner-dashboard-page{--pd-ink:var(--app-text);--pd-muted:var(--app-muted);--pd-line:var(--app-border)}
html[data-app-theme="dark"] .partner-dashboard-hero,html[data-app-theme="dark"] .partner-dashboard-revenue-card,html[data-app-theme="dark"] .partner-dashboard-quick-stats article,html[data-app-theme="dark"] .partner-dashboard-chart-card,html[data-app-theme="dark"] .partner-dashboard-recent{background:var(--app-surface)}
html[data-app-theme="dark"] .partner-dashboard-chart-heading>span,html[data-app-theme="dark"] .partner-dashboard-quick-stats article>.material-icons{background:#28323d}
html[data-app-theme="dark"] .partner-dashboard-recent-row:hover{background:#252b34}
@media(max-width:900px){.partner-dashboard-charts{grid-template-columns:minmax(0,1fr)}.partner-dashboard-status-chart{height:230px}}
@media(max-width:650px){.partner-dashboard-page{padding:13px 10px 25px!important}.partner-dashboard-hero{align-items:stretch;flex-direction:column;padding:18px 16px}.partner-dashboard-hero nav{display:grid;grid-template-columns:1fr 1fr}.partner-dashboard-hero nav a{text-align:center;white-space:normal}.partner-dashboard-revenue{grid-template-columns:1fr;gap:8px}.partner-dashboard-revenue-card{padding:14px 16px}.partner-dashboard-revenue-card>strong{font-size:24px}.partner-dashboard-quick-stats{grid-template-columns:1fr;gap:7px}.partner-dashboard-quick-stats article{padding:10px 13px}.partner-dashboard-charts{gap:9px}.partner-dashboard-chart-card{padding:14px}.partner-dashboard-chart-wrap{height:220px}.partner-dashboard-recent-heading{align-items:flex-start;flex-direction:column}.partner-dashboard-recent-row{padding:10px 13px}}
</style>

<script>
window.addEventListener('load', function () {
    if (!window.Chart) return;
    const textColor = document.documentElement.dataset.appTheme === 'dark' ? '#a8b1bd' : '#748093';
    const gridColor = document.documentElement.dataset.appTheme === 'dark' ? '#343b46' : '#e9edf2';
    const brandColor = getComputedStyle(document.querySelector('.partner-dashboard-page')).getPropertyValue('--pd-brand').trim() || '#147d25';
    const monthlyCanvas = document.getElementById('partner-monthly-revenue-chart');
    if (monthlyCanvas) new Chart(monthlyCanvas.getContext('2d'), {type:'bar',data:{labels:@json($monthLabels),datasets:[{label:'Chiffre d’affaires livré (F)',data:@json($monthlyChartValues),backgroundColor:brandColor+'aa',borderColor:brandColor,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,legend:{display:false},scales:{yAxes:[{ticks:{beginAtZero:true,fontColor:textColor},gridLines:{color:gridColor}}],xAxes:[{ticks:{fontColor:textColor},gridLines:{display:false}}]},tooltips:{callbacks:{label:function(item){return new Intl.NumberFormat('fr-FR').format(item.yLabel)+' F';}}}}});
    const statusCanvas = document.getElementById('partner-order-status-chart');
    if (statusCanvas) new Chart(statusCanvas.getContext('2d'), {type:'doughnut',data:{labels:@json($ordersByStatusLabels),datasets:[{data:@json($ordersByStatusValues),backgroundColor:['#177b32','#4285b4','#8b6ab0','#e6a23c','#e0674a','#55a69a','#bd6684','#6381bb','#48a775','#8993a0'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,legend:{position:'bottom',labels:{fontColor:textColor,boxWidth:11,padding:12}},cutoutPercentage:62}});
});
</script>
@endsection
