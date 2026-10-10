@if ($order->status === 'ordered')
    <span class="badge orders-status orders-status-progress">En cours</span>
@elseif ($order->status === 'delivered')
    <span class="badge orders-status orders-status-delivered">Livrée</span>
@elseif ($order->status === 'canceled')
    <span class="badge orders-status orders-status-canceled">Annulée</span>
@else
    <span class="badge orders-status orders-status-other">{{ $order->status ?: 'Statut inconnu' }}</span>
@endif
