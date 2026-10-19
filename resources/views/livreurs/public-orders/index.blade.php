@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/courier-public-orders.css') }}?v=20261019.2">
@php
    $statusLabels = [
        'awaiting_delivery_quote' => 'Frais de livraison à définir',
        'awaiting_customer_confirmation' => 'En attente de l’accord du client',
        'awaiting_prepayment' => 'En attente du paiement avant livraison',
        'preparing' => 'Commande en préparation',
        'ready_for_courier' => 'Prête, en attente d’un livreur',
        'assigned_to_courier' => 'Commande affectée — en attente du colis',
        'handed_to_courier' => 'Colis remis — confirme sa réception',
        'out_for_delivery' => 'En cours de livraison',
        'delivered' => 'Livrée',
        'canceled' => 'Annulée',
    ];
@endphp
<div class="container-fluid py-4 courier-orders-page">
    <div class="courier-orders-heading"><span class="courier-orders-eyebrow">LIVRAISONS PARTENAIRES · {{ auth()->user()->country_code }}</span><h1>Commandes de mon pays</h1><p>Prends une commande disponible ou suis celles qui te sont affectées.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <section class="courier-order-section">
        <div class="courier-section-heading"><div><span>À PRENDRE</span><h2>Commandes disponibles</h2></div><small>Premier livreur à accepter la commande</small></div>
        <div class="courier-orders-grid">
            @forelse($available as $order)
                <article class="courier-order-card">
                    <div class="courier-order-top"><div><span class="courier-order-ref">{{ $order->code }}</span><span class="courier-order-caption">Nouvelle commande</span></div><strong class="courier-order-total">{{ number_format((int) $order->montant, 0, ',', ' ') }} <small>F</small></strong></div>
                    <div class="courier-customer"><strong>{{ $order->costumer?->name }}</strong><a href="tel:{{ $order->costumer?->phone }}">{{ $order->costumer?->phone }}</a><span>{{ $order->costumer?->adresse }}</span></div>
                    <div class="courier-order-items"><span class="courier-detail-label">PRODUITS</span><ul>@foreach($order->orderItems as $item)<li><span>{{ $item->product?->name ?? 'Produit' }}</span><strong>× {{ $item->quantity }}</strong></li>@endforeach</ul></div>
                    <div class="courier-payment-note"><span class="courier-detail-label">PAIEMENT</span><strong>{{ $order->payment_timing === 'on_delivery' ? 'À la livraison · '.($order->requested_payment_method === 'mobile_money' ? 'Mobile Money' : 'Espèces') : 'Prévu avant livraison' }}</strong></div>
                    <form method="POST" action="{{ route('courier.public-orders.claim', $order) }}">@csrf<button class="btn btn-danger courier-action-button">Prendre cette commande</button></form>
                </article>
            @empty
                <div class="courier-empty">Aucune commande disponible pour le moment.</div>
            @endforelse
        </div>
        @if($available->hasPages())<div class="courier-pagination">{{ $available->links() }}</div>@endif
    </section>
    <section class="courier-order-section courier-assigned-section">
        <div class="courier-section-heading"><div><span>MON SUIVI</span><h2>Mes commandes affectées</h2></div></div>
        <div class="courier-orders-grid">
            @forelse($assigned as $order)
                <article class="courier-order-card">
                    <div class="courier-order-top"><div><span class="courier-order-ref">{{ $order->code }}</span><span class="courier-order-caption">Commande affectée</span></div><span class="courier-status">{{ $statusLabels[$order->partner_order_status] ?? 'Statut à vérifier' }}</span></div>
                    <div class="courier-customer"><strong>{{ $order->costumer?->name }}</strong><a href="tel:{{ $order->costumer?->phone }}">{{ $order->costumer?->phone }}</a><span>{{ $order->costumer?->adresse }}</span></div>
                    @if($order->partner)<div class="courier-partner"><span class="courier-detail-label">PARTENAIRE</span><strong>{{ $order->partner->name }}</strong><a href="tel:{{ $order->partner->phone }}">{{ $order->partner->phone }}</a></div>@endif
                    @if($order->partner_order_status === 'handed_to_courier')
                        <form method="POST" action="{{ route('courier.public-orders.receive', $order) }}">@csrf<button class="btn btn-outline-danger courier-action-button">Confirmer la réception du colis</button></form>
                    @elseif($order->partner_order_status === 'out_for_delivery')
                        @if($order->payment_timing === 'on_delivery' && (int) $order->paid_amount < (int) $order->montant)<div class="courier-payment-alert">À la livraison, encaisse <strong>{{ number_format((int) $order->montant - (int) $order->paid_amount, 0, ',', ' ') }} F</strong>. Le partenaire confirmera ensuite la remise des fonds.</div>@endif
                        <form method="POST" action="{{ route('courier.public-orders.deliver', $order) }}">@csrf
                            @if($order->payment_timing === 'on_delivery' && (int) $order->paid_amount < (int) $order->montant)<div class="courier-payment-fields"><label>Moyen de paiement reçu<select name="payment_method" class="form-control" required><option value="cash">Espèces</option><option value="mobile_money">Mobile Money</option></select></label><label>Référence Mobile Money <span>(facultative)</span><input name="transaction_reference" class="form-control" maxlength="160"></label></div>@endif
                            <button class="btn btn-success courier-action-button">Confirmer la livraison</button>
                        </form>
                    @else
                        <p class="courier-waiting-note">En attente de la remise du colis par le partenaire.</p>
                    @endif
                </article>
            @empty
                <div class="courier-empty">Aucune commande ne t’est affectée.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
