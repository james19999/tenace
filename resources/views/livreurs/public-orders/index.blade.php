@extends('layouts.admin')

@section('content')
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
<div class="container-fluid py-4">
    <div class="mb-4"><span class="text-uppercase text-danger font-weight-bold small">LIVRAISONS PARTENAIRES · {{ auth()->user()->country_code }}</span><h1 class="h2 mb-1">Commandes de mon pays</h1><p class="text-muted">Prends une commande disponible ou suis celles qui te sont affectées.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <section class="card mb-4"><div class="card-header font-weight-bold">Commandes disponibles · premier livreur servi</div><div class="card-body"><div class="row">
        @forelse($available as $order)<div class="col-xl-6 mb-3"><article class="border rounded p-3 h-100"><div class="d-flex justify-content-between"><strong>{{ $order->code }}</strong><strong>{{ number_format((int) $order->montant, 0, ',', ' ') }} F</strong></div><p class="mb-1 mt-2">{{ $order->costumer?->name }} · {{ $order->costumer?->phone }}</p><p class="text-muted">{{ $order->costumer?->adresse }}</p><ul>@foreach($order->orderItems as $item)<li>{{ $item->product?->name }} × {{ $item->quantity }}</li>@endforeach</ul><p class="small text-muted">Paiement : {{ $order->payment_timing === 'on_delivery' ? 'à la livraison · '.($order->requested_payment_method === 'mobile_money' ? 'Mobile Money' : 'espèces') : 'déjà prévu avant livraison' }}</p><form method="POST" action="{{ route('courier.public-orders.claim', $order) }}">@csrf<button class="btn btn-danger">Prendre cette commande</button></form></article></div>@empty<div class="col-12"><p class="text-muted mb-0">Aucune commande disponible pour le moment.</p></div>@endforelse
    </div>{{ $available->links() }}</div></section>
    <section class="card"><div class="card-header font-weight-bold">Mes commandes affectées</div><div class="card-body"><div class="row">
        @forelse($assigned as $order)<div class="col-xl-6 mb-3"><article class="border rounded p-3 h-100"><div class="d-flex justify-content-between"><strong>{{ $order->code }}</strong><span class="badge badge-danger">{{ $statusLabels[$order->partner_order_status] ?? 'Statut à vérifier' }}</span></div><p class="mb-1 mt-2">{{ $order->costumer?->name }} · {{ $order->costumer?->phone }}</p><p class="text-muted">{{ $order->costumer?->adresse }}</p>@if($order->partner)<p class="mb-2">Partenaire : {{ $order->partner->name }} · {{ $order->partner->phone }}</p>@endif
            @if($order->partner_order_status === 'handed_to_courier')<form method="POST" action="{{ route('courier.public-orders.receive', $order) }}">@csrf<button class="btn btn-outline-danger">Confirmer la réception du colis</button></form>@elseif($order->partner_order_status === 'out_for_delivery')
                @if($order->payment_timing === 'on_delivery' && (int) $order->paid_amount < (int) $order->montant)<div class="alert alert-warning py-2">À la livraison, encaisse {{ number_format((int) $order->montant - (int) $order->paid_amount, 0, ',', ' ') }} F. Le partenaire confirmera ensuite la remise des fonds.</div>@endif
                <form method="POST" action="{{ route('courier.public-orders.deliver', $order) }}">@csrf
                    @if($order->payment_timing === 'on_delivery' && (int) $order->paid_amount < (int) $order->montant)<label>Moyen de paiement reçu</label><select name="payment_method" class="form-control mb-2" required><option value="cash">Espèces</option><option value="mobile_money">Mobile Money</option></select><label>Référence Mobile Money (si applicable)</label><input name="transaction_reference" class="form-control mb-2" maxlength="160">@endif
                    <button class="btn btn-success">Confirmer la livraison</button>
                </form>
            @else<p class="text-muted mb-0">En attente de la remise du colis par le partenaire.</p>@endif
        </article></div>@empty<div class="col-12"><p class="text-muted mb-0">Aucune commande ne t’est affectée.</p></div>@endforelse
    </div></div></section>
</div>
@endsection
