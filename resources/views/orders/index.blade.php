@extends('layouts.admin')

@section('content')
    @php
        $activeOrdersTab = request()->query('ordersTab', 'created');
        $tabs = [
            'created' => ['label' => 'Créées aujourd’hui', 'orders' => $createdTodayOrders],
            'delivery' => ['label' => 'Livraisons prévues aujourd’hui', 'orders' => $deliveryTodayOrders],
            'assigned' => ['label' => 'Attribuées aujourd’hui', 'orders' => $assignedTodayOrders],
            'overdue' => ['label' => 'En retard', 'orders' => $overdueOrders],
            'history' => ['label' => 'Historique', 'orders' => $historyOrders],
        ];
        if (! array_key_exists($activeOrdersTab, $tabs)) $activeOrdersTab = 'created';
    @endphp

    <div class="col-12 order-list-page">
        <div class="py-2">
            <h3>Liste des commandes</h3>
        </div>

        @if (Session::has('messages'))
            <div class="alert alert-success"><strong>{{ session('messages') }}</strong></div>
        @endif

        <div class="card shadow">
            <div class="card-body">
                <ul class="nav nav-tabs order-list-tabs" role="tablist">
                    @foreach ($tabs as $key => $tab)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeOrdersTab === $key ? 'active' : '' }}"
                                id="order-list-tab-{{ $key }}" data-toggle="tab" href="#order-list-panel-{{ $key }}"
                                role="tab" aria-controls="order-list-panel-{{ $key }}"
                                aria-selected="{{ $activeOrdersTab === $key ? 'true' : 'false' }}">
                                {{ $tab['label'] }} <span class="badge badge-light">{{ $tab['orders']->total() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content pt-3">
                    @foreach ($tabs as $key => $tab)
                        <div class="tab-pane fade {{ $activeOrdersTab === $key ? 'show active' : '' }}"
                            id="order-list-panel-{{ $key }}" role="tabpanel" aria-labelledby="order-list-tab-{{ $key }}">
                            @if ($key === 'history')
                                <form method="GET" action="{{ route('order') }}" class="form-inline mb-3">
                                    <input type="hidden" name="ordersTab" value="history">
                                    <label for="order-history-filter" class="mr-2">Période de création</label>
                                    <select id="order-history-filter" name="filter" class="form-control" onchange="this.form.submit()">
                                        <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Aujourd’hui</option>
                                        <option value="week" {{ $filter === 'week' ? 'selected' : '' }}>Cette semaine</option>
                                        <option value="month" {{ $filter === 'month' ? 'selected' : '' }}>Ce mois</option>
                                        <option value="year" {{ $filter === 'year' ? 'selected' : '' }}>Cette année</option>
                                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Toutes les commandes</option>
                                    </select>
                                </form>
                            @else
                                <p class="small text-muted mb-3">
                                    @if ($key === 'created') Commandes créées aujourd’hui.
                                    @elseif ($key === 'delivery') Commandes dont la livraison est prévue aujourd’hui.
                                    @elseif ($key === 'assigned') Commandes attribuées à un livreur aujourd’hui.
                                    @else Commandes encore en cours dont la date de livraison est dépassée.
                                    @endif
                                </p>
                            @endif

                            @include('orders.partials.list-table', ['orders' => $tab['orders']])

                            @if ($tab['orders']->hasPages())
                                <div class="orders-pagination-wrap mt-3">
                                    <small class="orders-pagination-summary">Page {{ $tab['orders']->currentPage() }} sur {{ $tab['orders']->lastPage() }}</small>
                                    {{ $tab['orders']->appends(['ordersTab' => $key, 'filter' => $filter])->onEachSide(1)->links() }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <style>
        .order-list-page { min-width: 0; max-width: 100%; }
        .order-list-page .card, .order-list-page .card-body, .order-list-page .tab-content, .order-list-page .tab-pane { min-width: 0; max-width: 100%; }
        .order-list-tabs { flex-wrap: nowrap; overflow-x: auto; white-space: nowrap; gap: 4px; }
        .order-list-tabs .nav-item { flex: 0 0 auto; }
        .order-list-tabs .nav-link { display: flex; align-items: center; gap: 7px; color: #555; }
        .order-list-tabs .nav-link.active { color: #81201d; font-weight: 700; }
        .order-list-page .table-responsive { width: 100%; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .order-list-page table { min-width: 1050px; }
        .order-list-page .order-actions { display: flex; flex-wrap: wrap; gap: 5px; min-width: 230px; }
        .orders-pagination-wrap { display: flex; align-items: center; justify-content: flex-end; gap: 12px; max-width: 100%; }
        .orders-pagination-wrap .pagination { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 3px; margin: 0; }
        .orders-pagination-wrap .page-link { min-width: 38px; text-align: center; padding: 8px 10px; }
        .orders-pagination-summary { display: none; color: #687386; white-space: nowrap; }
        @media (max-width: 767.98px) {
            .order-list-tabs .nav-link { padding: 8px 10px; font-size: 12px; }
            .order-list-page .card-body { padding: 12px; }
            .order-list-page h3 { font-size: 1.25rem; }
            .order-list-page .form-inline { align-items: stretch; }
            .order-list-page .form-inline label { width: 100%; margin: 0 0 6px !important; }
            .order-list-page .form-inline select { width: 100%; }
            .orders-pagination-wrap { flex-direction: column; align-items: center; gap: 6px; }
            .orders-pagination-wrap .pagination { justify-content: center; max-width: 100%; }
            .orders-pagination-wrap .page-item:not(:first-child):not(:last-child):not(.active) { display: none; }
            .orders-pagination-wrap .page-link { min-width: 40px; padding: 8px 11px; }
            .orders-pagination-summary { display: block; }
        }
    </style>
@endsection
