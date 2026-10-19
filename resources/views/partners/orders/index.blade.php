@extends('layouts.admin')

@section('content')
@php
    $statusLabels = [
        'awaiting_delivery_quote' => 'Frais de livraison à définir',
        'awaiting_customer_confirmation' => 'En attente de l’accord du client',
        'awaiting_prepayment' => 'En attente du paiement avant livraison',
        'preparing' => 'Commande en préparation',
        'ready_for_courier' => 'Prête, en attente d’un livreur',
        'assigned_to_courier' => 'Livreur affecté',
        'handed_to_courier' => 'Colis remis au livreur',
        'out_for_delivery' => 'En cours de livraison',
        'delivered' => 'Livrée',
        'canceled' => 'Annulée',
    ];
@endphp
<div class="container-fluid py-4">
    <div class="mb-4"><span class="text-uppercase text-danger font-weight-bold small">ESPACE PARTENAIRE · {{ $countryName }}</span><h1 class="h2 mb-1">Commandes de mon pays</h1><p class="text-muted">Confirme les frais, prépare les colis et remets-les aux livreurs.</p></div>
    <div class="row mb-4"><div class="col-md-4"><div class="card card-body"><small>À traiter</small><strong class="h3 mb-0">{{ (int) $counts->attention_count }}</strong></div></div><div class="col-md-4"><div class="card card-body"><small>En cours</small><strong class="h3 mb-0">{{ (int) $counts->active_count }}</strong></div></div><div class="col-md-4"><div class="card card-body"><small>Livrées</small><strong class="h3 mb-0">{{ (int) $counts->delivered_count }}</strong></div></div></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-header font-weight-bold">Commandes récentes</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Référence / client</th><th>Produits</th><th>Montant</th><th>Étape</th><th>Livreur</th><th></th></tr></thead><tbody>
    @forelse($orders as $order)
        <tr><td><strong>{{ $order->code }}</strong><br>{{ $order->costumer?->name }}<br><small>{{ $order->costumer?->phone }}</small></td><td>{{ $order->orderItems->count() }} produit(s)</td><td>{{ number_format((int) $order->montant, 0, ',', ' ') }} F</td><td>{{ $statusLabels[$order->partner_order_status] ?? 'Statut à vérifier' }}</td><td>{{ $order->user?->name ?? 'À affecter' }}</td><td><a class="btn btn-sm btn-outline-danger" href="{{ route('partner.orders.show', $order) }}">Ouvrir</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune commande à afficher pour le moment.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $orders->links() }}</div></div>
</div>
@endsection
