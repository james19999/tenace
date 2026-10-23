@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
@php
    $statusLabels = [
        'awaiting_delivery_quote' => 'Frais de livraison à définir',
        'awaiting_customer_confirmation' => 'En attente de l’accord du client',
        'awaiting_prepayment' => 'En attente du paiement',
        'preparing' => 'En préparation',
        'ready_for_courier' => 'Prête au retrait',
        'assigned_to_courier' => 'Livreur affecté',
        'handed_to_courier' => 'Colis remis au livreur',
        'out_for_delivery' => 'En cours de livraison',
        'delivered' => 'Livrée',
        'canceled' => 'Annulée',
    ];
@endphp
<main class="container-fluid py-4 partner-portal partner-delivery-orders-page">
    <header class="partner-delivery-orders-hero"><div><a href="{{ route('partner.dashboard') }}">← Tableau de bord</a><span>ESPACE PARTENAIRE · {{ $countryName }}</span><h1>Mes commandes</h1><p>Consulte et traite les commandes de leur création à leur livraison.</p></div><nav class="partner-orders-header-actions"><a class="btn btn-outline-danger" href="{{ route('product') }}">Créer une commande</a><a class="btn btn-danger" href="{{ route('partner.couriers.index') }}">Gérer les livreurs</a></nav></header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <nav class="partner-order-tabs" aria-label="Filtrer les commandes">
        <a href="{{ route('partner.orders.index', ['scope' => 'active']) }}" class="{{ $scope === 'active' ? 'is-active' : '' }}">À traiter <b>{{ number_format($orderCounts['active'], 0, ',', ' ') }}</b></a>
        <a href="{{ route('partner.orders.index', ['scope' => 'history']) }}" class="{{ $scope === 'history' ? 'is-active' : '' }}">Historique <b>{{ number_format($orderCounts['history'], 0, ',', ' ') }}</b></a>
        <a href="{{ route('partner.orders.index', ['scope' => 'all']) }}" class="{{ $scope === 'all' ? 'is-active' : '' }}">Toutes <b>{{ number_format($orderCounts['all'], 0, ',', ' ') }}</b></a>
    </nav>
    <section class="partner-delivery-orders-list">
        @forelse($orders as $order)
            <article class="partner-delivery-order-card">
                <div class="partner-delivery-order-main"><span class="partner-delivery-order-ref">{{ $order->code }}</span><h2>{{ $order->costumer?->name ?: 'Client' }}</h2><a href="tel:{{ $order->costumer?->phone }}">{{ $order->costumer?->phone }}</a><small>{{ $order->shipping_city }} · {{ $order->orderItems->count() }} produit(s)</small></div>
                <div class="partner-delivery-order-amount"><span>Montant client</span><strong>{{ number_format((int) $order->montant, 0, ',', ' ') }} F</strong></div>
                <div class="partner-delivery-order-status"><span>{{ $statusLabels[$order->partner_order_status] ?? 'À suivre' }}</span><small>{{ $order->user?->name ? 'Livreur · '.$order->user->name : 'Livreur à affecter' }}</small></div>
                <a class="partner-delivery-order-open" href="{{ route('partner.orders.show', $order) }}">Gérer la commande <span aria-hidden="true">→</span></a>
            </article>
        @empty
            <div class="partner-delivery-orders-empty"><span class="material-icons" aria-hidden="true">{{ $scope === 'history' ? 'history' : 'task_alt' }}</span><h2>{{ $scope === 'history' ? 'Aucune commande terminée' : 'Aucune commande à afficher' }}</h2><p>{{ $scope === 'active' ? 'Les nouvelles commandes et celles qui nécessitent une action apparaîtront ici.' : 'Les commandes terminées ou annulées apparaîtront ici.' }}</p><a href="{{ route('product') }}">Créer une commande</a></div>
        @endforelse
    </section>
    @if($orders->hasPages())<div class="mt-3">{{ $orders->links() }}</div>@endif
</main>
<style>
.partner-delivery-orders-page{--po-brand:var(--app-primary,#147d25);--po-ink:#202938;--po-muted:#748093;--po-line:#e5e9ef}.partner-delivery-orders-hero{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:20px 24px;margin-bottom:17px;background:var(--app-surface,#fff);border:1px solid var(--po-line);border-left:4px solid var(--po-brand)}.partner-delivery-orders-hero>div{display:grid;gap:5px}.partner-delivery-orders-hero>div>a{color:var(--po-brand);font-size:11px;font-weight:750}.partner-delivery-orders-hero>div>span{margin-top:6px;color:var(--po-brand);font-size:9px;font-weight:850;letter-spacing:.12em}.partner-delivery-orders-hero h1{margin:0;color:var(--po-ink);font-size:27px;font-weight:820}.partner-delivery-orders-hero p{margin:0;color:var(--po-muted);font-size:12px}.partner-delivery-orders-list{display:grid;gap:9px}.partner-delivery-order-card{display:grid;grid-template-columns:minmax(180px,1.2fr) minmax(130px,.7fr) minmax(155px,.8fr) auto;align-items:center;gap:18px;padding:16px 19px;background:var(--app-surface,#fff);border:1px solid var(--po-line)}.partner-delivery-order-main{display:grid;gap:3px;min-width:0}.partner-delivery-order-ref{color:var(--po-brand);font-size:10px;font-weight:850;letter-spacing:.04em}.partner-delivery-order-main h2{margin:1px 0 0;color:var(--po-ink);font-size:14px;font-weight:800}.partner-delivery-order-main a,.partner-delivery-order-main small{color:var(--po-muted);font-size:10px;overflow-wrap:anywhere}.partner-delivery-order-amount span,.partner-delivery-order-status small{display:block;color:var(--po-muted);font-size:10px}.partner-delivery-order-amount strong{display:block;margin-top:3px;color:var(--po-ink);font-size:15px}.partner-delivery-order-status>span{display:inline-flex;padding:6px 8px;background:color-mix(in srgb,var(--po-brand) 9%,#fff);color:var(--po-brand);font-size:10px;font-weight:750}.partner-delivery-order-status small{margin-top:5px}.partner-delivery-order-open{display:inline-flex;align-items:center;gap:7px;color:var(--po-brand);font-size:11px;font-weight:800;white-space:nowrap}.partner-delivery-order-open:hover{color:var(--po-brand);text-decoration:underline}.partner-delivery-orders-empty{display:grid;justify-items:center;gap:8px;padding:45px 20px;background:var(--app-surface,#fff);border:1px solid var(--po-line);text-align:center}.partner-delivery-orders-empty>.material-icons{color:var(--po-brand);font-size:32px}.partner-delivery-orders-empty h2{margin:0;color:var(--po-ink);font-size:17px}.partner-delivery-orders-empty p{max-width:430px;margin:0;color:var(--po-muted);font-size:12px}.partner-delivery-orders-empty>a{color:var(--po-brand);font-size:12px;font-weight:750}html[data-app-theme="dark"] .partner-delivery-orders-page{--po-ink:var(--app-text);--po-muted:var(--app-muted);--po-line:var(--app-border)}html[data-app-theme="dark"] .partner-delivery-orders-hero,html[data-app-theme="dark"] .partner-delivery-order-card,html[data-app-theme="dark"] .partner-delivery-orders-empty{background:var(--app-surface)}@media(max-width:850px){.partner-delivery-order-card{grid-template-columns:minmax(0,1fr) minmax(0,auto);gap:12px}.partner-delivery-order-main{grid-column:1}.partner-delivery-order-amount{grid-column:2;grid-row:1;text-align:right}.partner-delivery-order-status{grid-column:1}.partner-delivery-order-open{grid-column:2;grid-row:2;justify-self:end}}@media(max-width:575px){.partner-delivery-orders-page{padding:13px 10px 25px!important}.partner-delivery-orders-hero{align-items:stretch;flex-direction:column;padding:16px}.partner-delivery-orders-hero h1{font-size:23px}.partner-delivery-order-card{grid-template-columns:minmax(0,1fr) auto;padding:13px;gap:10px}.partner-delivery-order-status{grid-column:1/-1;padding-top:9px;border-top:1px solid var(--po-line)}.partner-delivery-order-open{grid-column:1/-1;grid-row:auto;justify-self:stretch;justify-content:center;min-height:39px;border:1px solid var(--po-line)}}
.partner-order-tabs{display:flex;gap:6px;overflow-x:auto;margin-bottom:14px;border-bottom:1px solid var(--po-line)}.partner-order-tabs a{display:inline-flex;align-items:center;gap:8px;padding:11px 14px;border-bottom:2px solid transparent;color:var(--po-muted);font-size:12px;font-weight:750;white-space:nowrap}.partner-order-tabs a b{padding:2px 7px;border-radius:12px;background:#f0f2f5;color:inherit;font-size:10px}.partner-order-tabs a.is-active{border-color:var(--po-brand);color:var(--po-brand);text-decoration:none}.partner-order-tabs a:hover{text-decoration:none;color:var(--po-brand)}.partner-orders-header-actions{display:flex;gap:8px;flex-wrap:wrap}
@media(max-width:575px){.partner-orders-header-actions{display:grid;grid-template-columns:1fr 1fr}}
</style>
@endsection
