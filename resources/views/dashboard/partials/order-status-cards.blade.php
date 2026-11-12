@php
    $orderStatusCards = [
        ['tab' => 'canceled', 'label' => 'Commandes annulées', 'icon' => 'cancel', 'count' => $orderStatusCounts['canceled'], 'tone' => 'canceled'],
        ['tab' => 'in_progress', 'label' => 'Commandes en cours', 'icon' => 'local_shipping', 'count' => $orderStatusCounts['in_progress'], 'tone' => 'progress'],
        ['tab' => 'waiting', 'label' => 'Commandes en attente', 'icon' => 'hourglass_top', 'count' => $orderStatusCounts['waiting'], 'tone' => 'waiting'],
        ['tab' => 'delivered', 'label' => 'Commandes livrées', 'icon' => 'task_alt', 'count' => $orderStatusCounts['delivered'], 'tone' => 'delivered'],
    ];
@endphp

@foreach ($orderStatusCards as $statusCard)
    <a href="{{ route('orders.status-board', ['tab' => $statusCard['tab'], 'date_debut' => now()->startOfWeek()->toDateString(), 'date_fin' => now()->endOfWeek()->toDateString()]) }}" class="col-12 col-sm-6 col-md-6 col-lg-3 dashboard-kpi-link dashboard-kpi-orders dashboard-status-kpi is-{{ $statusCard['tone'] }}">
        <div class="card">
            <div class="card-body">
                <div class="dashboard-status-kpi-copy">
                    <h5 class="text-uppercase card-title">{{ $statusCard['label'] }}</h5>
                    <strong class="h1">{{ number_format($statusCard['count'], 0, ',', ' ') }}</strong>
                    <small>Cette semaine · ouvrir la liste</small>
                </div>
                <span class="material-icons dashboard-status-kpi-icon" aria-hidden="true">{{ $statusCard['icon'] }}</span>
            </div>
        </div>
    </a>
@endforeach
