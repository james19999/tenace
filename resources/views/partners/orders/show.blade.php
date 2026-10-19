@extends('layouts.admin')

@section('content')
@php
    $statusLabels = [
        'awaiting_delivery_quote' => 'Frais de livraison à définir', 'awaiting_customer_confirmation' => 'En attente de l’accord du client',
        'awaiting_prepayment' => 'En attente du paiement avant livraison', 'preparing' => 'Commande en préparation', 'ready_for_courier' => 'Prête, en attente d’un livreur',
        'assigned_to_courier' => 'Livreur affecté', 'handed_to_courier' => 'Colis remis au livreur', 'out_for_delivery' => 'En livraison', 'delivered' => 'Livrée', 'canceled' => 'Annulée',
    ];
@endphp
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-4"><div><a href="{{ route('partner.orders.index') }}">← Mes commandes</a><div class="small text-uppercase text-danger font-weight-bold mt-3">COMMANDE {{ $order->code }}</div><h1 class="h2">{{ $order->costumer?->name }}</h1><p class="text-muted mb-2">{{ $order->costumer?->phone }} · {{ $order->costumer?->adresse }}</p><a href="{{ route('public-order.track', $order->public_tracking_token) }}" target="_blank" rel="noopener">Ouvrir / partager le suivi client</a></div><span class="badge badge-danger p-2">{{ $statusLabels[$order->partner_order_status] ?? 'Statut à vérifier' }}</span></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row"><div class="col-lg-7">
        <section class="card mb-4"><div class="card-header font-weight-bold">Produits demandés</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Produit</th><th>Quantité</th><th>Prix pays</th><th>Total</th></tr></thead><tbody>@foreach($order->orderItems as $item)<tr><td>{{ $item->product?->name ?? 'Produit indisponible' }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((int) $item->price, 0, ',', ' ') }} F</td><td>{{ number_format((int) $item->price * (int) $item->quantity, 0, ',', ' ') }} F</td></tr>@endforeach</tbody></table></div><div class="card-body"><div class="d-flex justify-content-between"><span>Sous-total</span><strong>{{ number_format((int) $order->subtotal, 0, ',', ' ') }} F</strong></div><div class="d-flex justify-content-between"><span>Livraison</span><strong>{{ number_format((int) $order->tax, 0, ',', ' ') }} F</strong></div><hr><div class="d-flex justify-content-between h5"><span>Total à payer</span><strong>{{ number_format((int) $order->montant, 0, ',', ' ') }} F</strong></div><div class="d-flex justify-content-between text-muted"><span>Déjà payé / collecté</span><strong>{{ number_format((int) $order->paid_amount, 0, ',', ' ') }} F</strong></div></div></section>
        <section class="card mb-4"><div class="card-header font-weight-bold">Paiements</div><div class="card-body">@forelse($order->payments as $payment)<div class="border-bottom py-3 d-flex justify-content-between flex-wrap"><div><strong>{{ $payment->stage === 'before_delivery' ? 'Paiement avant livraison' : 'Paiement à la livraison' }}</strong><br>{{ $payment->method === 'mobile_money' ? 'Mobile Money' : 'Espèces' }} · {{ number_format((int) $payment->amount, 0, ',', ' ') }} F<br><small class="text-muted">{{ $payment->transaction_reference ?: $payment->notes }} · {{ ['awaiting_partner_confirmation' => 'En attente de confirmation du partenaire', 'confirmed' => 'Paiement confirmé', 'collected' => 'Montant encaissé par le livreur, remise à confirmer'][$payment->status] ?? 'Statut de paiement à vérifier' }}</small></div>@if($payment->stage === 'before_delivery' && $payment->status === 'awaiting_partner_confirmation')<form method="POST" action="{{ route('partner.orders.payments.confirm', [$order, $payment]) }}">@csrf<button class="btn btn-success btn-sm">Confirmer le paiement</button></form>@elseif($payment->stage === 'on_delivery' && $payment->status === 'collected')<form method="POST" action="{{ route('partner.orders.remittance.confirm', [$order, $payment]) }}">@csrf<button class="btn btn-outline-success btn-sm">Confirmer la remise reçue</button></form>@endif</div>@empty<p class="text-muted mb-0">Aucun paiement enregistré.</p>@endforelse</div></section>
    </div><div class="col-lg-5">
        @if($order->partner_order_status === 'awaiting_delivery_quote')<section class="card mb-4"><div class="card-header font-weight-bold">1. Fixer les frais de livraison</div><div class="card-body"><p class="text-muted">Le client recevra le total à confirmer avant la préparation.</p><form method="POST" action="{{ route('partner.orders.delivery-fee', $order) }}">@csrf<label for="delivery-fee">Frais de livraison (FCFA)</label><input id="delivery-fee" class="form-control mb-3" type="number" min="0" step="1" name="delivery_fee" required><button class="btn btn-danger">Envoyer le devis au client</button></form></div></section>@endif
        @if($order->partner_order_status === 'preparing')<section class="card mb-4"><div class="card-header font-weight-bold">2. Préparation</div><div class="card-body"><p>Quand tous les articles sont prêts, rends la commande visible aux livreurs de {{ $order->country_code }}.</p><form method="POST" action="{{ route('partner.orders.ready', $order) }}">@csrf<button class="btn btn-danger">Commande prête pour retrait</button></form></div></section>@endif
        @if($order->partner_order_status === 'ready_for_courier')<section class="card mb-4"><div class="card-header font-weight-bold">Affectation du livreur</div><div class="card-body"><form method="POST" action="{{ route('partner.orders.assign', $order) }}">@csrf<label for="courier">Livreur du pays</label><select id="courier" class="form-control mb-3" name="courier_id" required><option value="">Choisir un livreur</option>@foreach($couriers as $courier)<option value="{{ $courier->id }}">{{ $courier->name }} · {{ $courier->phone }}</option>@endforeach</select><button class="btn btn-outline-danger">Affecter ce livreur</button></form><p class="text-muted mt-3 mb-0">Les livreurs de ce pays peuvent aussi prendre la commande eux-mêmes. Le premier à la prendre est affecté.</p></div></section>@endif
        @if($order->partner_order_status === 'assigned_to_courier')<section class="card mb-4"><div class="card-header font-weight-bold">Remise physique du colis</div><div class="card-body"><p>Remets le colis à <strong>{{ $order->user?->name }}</strong>, puis enregistre la remise.</p><form method="POST" action="{{ route('partner.orders.handover', $order) }}">@csrf<button class="btn btn-danger">Confirmer la remise au livreur</button></form></div></section>@endif
        @if ($order->courierHandoffs->isNotEmpty())
            <section class="card mb-4">
                <div class="card-header font-weight-bold">Suivi du colis</div>
                <div class="card-body">
                    @foreach ($order->courierHandoffs as $handoff)
                        <p class="mb-1"><strong>{{ $handoff->courier?->name }}</strong></p>
                        <p class="text-muted">
                            {{ ['handed_over' => 'Colis remis au livreur', 'received' => 'Colis reçu par le livreur', 'pending' => 'Remise en attente'][$handoff->status] ?? 'État du colis à vérifier' }}
                            @if ($handoff->handed_over_at)
                                · remis le {{ $handoff->handed_over_at->format('d/m/Y H:i') }}
                            @endif
                            @if ($handoff->received_at)
                                · reçu le {{ $handoff->received_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    @endforeach
                </div>
            </section>
        @endif
    </div></div>
</div>
@endsection
