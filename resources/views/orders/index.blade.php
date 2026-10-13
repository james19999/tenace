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

    <div class="order-list-page">
        <div class="orders-page-shell">
            <div class="orders-page-heading">
                <div>
                    <p class="orders-eyebrow">Gestion commerciale</p>
                    <h1>Commandes</h1>
                    <p>Retrouve les commandes selon leur création, leur livraison, leur attribution ou leur échéance.</p>
                </div>
                <a class="orders-new-link" href="{{ route('product') }}"><span class="material-icons" aria-hidden="true">add_shopping_cart</span>Nouvelle commande</a>
            </div>

            @if (Session::has('messages'))
                <div class="alert alert-success orders-alert"><strong>{{ session('messages') }}</strong></div>
            @endif

            <div class="orders-board">
                <div class="orders-board-top">
                    <div><h2>Suivi des commandes</h2><p>Les listes sont actualisées selon les dates prévues et les attributions.</p></div>
                </div>
                <div class="orders-tab-scroll">
                    <ul class="nav nav-tabs order-list-tabs" role="tablist">
                        @foreach ($tabs as $key => $tab)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeOrdersTab === $key ? 'active' : '' }}"
                                    id="order-list-tab-{{ $key }}" data-toggle="tab" href="#order-list-panel-{{ $key }}"
                                    role="tab" aria-controls="order-list-panel-{{ $key }}"
                                    aria-selected="{{ $activeOrdersTab === $key ? 'true' : 'false' }}">
                                    <span>{{ $tab['label'] }}</span><span class="orders-tab-count">{{ $tab['orders']->total() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="tab-content order-list-tab-content">
                    @foreach ($tabs as $key => $tab)
                        <div class="tab-pane fade {{ $activeOrdersTab === $key ? 'show active' : '' }}"
                            id="order-list-panel-{{ $key }}" role="tabpanel" aria-labelledby="order-list-tab-{{ $key }}">
                            @if ($key === 'history')
                                <form method="GET" action="{{ route('order') }}" class="order-history-filter mb-3">
                                    <input type="hidden" name="ordersTab" value="history">
                                    <label for="order-history-filter">Période de création</label>
                                    <select id="order-history-filter" name="filter" class="form-control" onchange="this.form.submit()">
                                        <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Aujourd’hui</option>
                                        <option value="week" {{ $filter === 'week' ? 'selected' : '' }}>Cette semaine</option>
                                        <option value="month" {{ $filter === 'month' ? 'selected' : '' }}>Ce mois</option>
                                        <option value="year" {{ $filter === 'year' ? 'selected' : '' }}>Cette année</option>
                                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Toutes les commandes</option>
                                    </select>
                                </form>
                            @else
                                <p class="orders-tab-description">
                                    @if ($key === 'created') Commandes créées aujourd’hui.
                                    @elseif ($key === 'delivery') Commandes dont la livraison est prévue aujourd’hui.
                                    @elseif ($key === 'assigned') Commandes attribuées à un livreur aujourd’hui.
                                    @else Commandes encore en cours dont la date de livraison est dépassée.
                                    @endif
                                </p>
                            @endif

                            @include('orders.partials.list-table', ['orders' => $tab['orders']])

                            @if ($tab['orders']->hasPages())
                                <div class="orders-pagination-wrap">
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
        .order-list-page{--orders-red:#861b1b;--orders-ink:#282b35;--orders-muted:#777d89;--orders-line:#e7e9ee;min-width:0;max-width:100%;padding:8px 0 30px;color:var(--orders-ink)}
        .orders-page-shell{max-width:1500px;margin:0 auto;padding:0 18px}
        .orders-page-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:8px 0 23px}
        .orders-eyebrow{margin:0 0 6px;color:var(--orders-red);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .orders-page-heading h1{margin:0;font-size:clamp(28px,3vw,36px);line-height:1.12;font-weight:850;color:var(--orders-ink)}
        .orders-page-heading p:not(.orders-eyebrow){margin:8px 0 0;color:var(--orders-muted);font-size:14px}
        .orders-new-link{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:10px 15px;border:1px solid var(--orders-red);border-radius:10px;background:var(--orders-red);color:#fff!important;font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap}
        .orders-alert{border:0;border-radius:11px;margin-bottom:16px}
        .orders-board{min-width:0;overflow:hidden;border:1px solid var(--orders-line);border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(32,36,46,.045)}
        .orders-board-top{padding:19px 21px 16px}.orders-board-top h2{margin:0;font-size:18px;font-weight:800}.orders-board-top p{margin:5px 0 0;color:var(--orders-muted);font-size:12px}
        .orders-tab-scroll{overflow-x:auto;border-bottom:1px solid var(--orders-line);scrollbar-width:thin}
        .order-list-tabs{display:flex;flex-wrap:nowrap;gap:5px;min-width:max-content;margin:0;padding:0 16px;border:0;white-space:nowrap}
        .order-list-tabs .nav-item{flex:0 0 auto;margin:0}
        .order-list-tabs .nav-link{position:relative;display:flex;align-items:center;gap:8px;padding:13px 12px;border:0;border-radius:9px 9px 0 0;color:#656b77;font-size:12px;font-weight:700}
        .order-list-tabs .nav-link:hover{color:var(--orders-red);background:#fcf7f6}
        .order-list-tabs .nav-link.active{color:var(--orders-red);background:#fff}
        .order-list-tabs .nav-link.active:after{position:absolute;right:9px;bottom:0;left:9px;height:3px;border-radius:3px 3px 0 0;background:var(--orders-red);content:""}
        .orders-tab-count{display:inline-grid;place-items:center;min-width:23px;height:22px;padding:0 6px;border-radius:20px;background:#f1f2f5;color:#626875;font-size:10px;font-weight:800}
        .nav-link.active .orders-tab-count{background:#f8eaea;color:var(--orders-red)}
        .order-list-tab-content{padding:18px 20px 20px;min-width:0}
        .orders-tab-description{margin:0 0 14px;color:var(--orders-muted);font-size:12px}
        .order-history-filter{display:flex;align-items:center;gap:11px;max-width:440px;padding:12px;border:1px solid var(--orders-line);border-radius:11px;background:#fafbfc}
        .order-history-filter label{margin:0;color:#4d5260;font-size:12px;font-weight:750;white-space:nowrap}
        .order-history-filter select{min-width:180px;height:40px;border:1px solid #dfe2e8;border-radius:8px}
        .order-list-page .table-responsive{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .orders-pagination-wrap{display:flex;align-items:center;justify-content:flex-end;gap:12px;max-width:100%;margin-top:16px!important}
        .orders-pagination-wrap .pagination{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:3px;margin:0}
        .orders-pagination-wrap .page-link{min-width:38px;padding:8px 10px;border-radius:7px!important;color:var(--orders-red)}
        .orders-pagination-wrap .page-item.active .page-link{border-color:var(--orders-red);background:var(--orders-red);color:#fff}
        .orders-pagination-summary{display:none;color:#687386;white-space:nowrap}
        @media(max-width:767.98px){.order-list-page{padding-top:0}.orders-page-shell{padding:0 11px}.orders-page-heading{display:block;margin:8px 0 17px}.orders-page-heading h1{font-size:27px}.orders-page-heading p:not(.orders-eyebrow){font-size:13px;line-height:1.5}.orders-new-link{width:100%;margin-top:13px}.orders-board{border-radius:12px}.orders-board-top{padding:15px}.orders-board-top h2{font-size:16px}.order-list-tab-content{padding:14px 12px}.order-list-tabs{padding:0 7px}.order-list-tabs .nav-link{padding:11px 9px;font-size:11px}.order-history-filter{align-items:stretch;flex-direction:column;max-width:none}.order-history-filter select{width:100%;min-width:0}.orders-pagination-wrap{flex-direction:column;align-items:center;gap:6px}.orders-pagination-wrap .pagination{justify-content:center;max-width:100%}.orders-pagination-wrap .page-item:not(:first-child):not(:last-child):not(.active){display:none}.orders-pagination-wrap .page-link{min-width:40px;padding:8px 11px}.orders-pagination-summary{display:block}}
        @media(max-width:360px){.orders-page-heading h1{font-size:24px}.order-list-tabs .nav-link{padding:10px 7px;font-size:10px}.orders-tab-count{min-width:20px;height:20px;padding:0 5px}}
        .order-list-page .orders-new-link,.order-list-page .orders-alert,.order-list-page .orders-board,
        .order-list-page .order-list-tabs .nav-link,.order-list-page .order-list-tabs .nav-link.active:after,
        .order-list-page .orders-tab-count,.order-list-page .order-history-filter,
        .order-list-page .order-history-filter select,.order-list-page .orders-pagination-wrap .page-link,
        .order-list-page .table-responsive,.order-list-page .table-responsive table,
        .order-list-page .table-responsive .badge,.order-list-page .table-responsive .btn{border-radius:0!important}
        .order-list-page .orders-board{box-shadow:none;border-color:#d9dde5}
        .order-list-page .orders-board-top,.order-list-page .order-list-tab-content{background:#fff}
        .order-list-page .order-list-tabs .nav-link.active:after{height:2px}
    </style>
@endsection
