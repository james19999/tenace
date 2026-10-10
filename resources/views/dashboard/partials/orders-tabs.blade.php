@php
    $orderTabs = [
        'created' => ['label' => 'Créées aujourd’hui', 'orders' => $createdTodayOrders],
        'delivery' => ['label' => 'Livraisons prévues aujourd’hui', 'orders' => $deliveryTodayOrders],
        'assigned' => ['label' => 'Attribuées aujourd’hui', 'orders' => $assignedTodayOrders],
        'overdue' => ['label' => 'En retard', 'orders' => $overdueOrders],
    ];
@endphp

<div class="col-lg-12">
    <div class="card shadow">
        <div class="card-body">
            <h4 class="card-title mb-2">Commandes</h4>
            <p class="small text-muted mb-3">Les commandes sont réparties selon leur création, leur date de livraison prévue, leur attribution ou leur retard.</p>

            <ul class="nav nav-tabs dashboard-order-tabs" id="dashboardOrderTabs" role="tablist">
                @foreach($orderTabs as $key => $tab)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="orders-tab-{{ $key }}" data-toggle="tab" href="#orders-panel-{{ $key }}" role="tab" aria-controls="orders-panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $tab['label'] }} <span class="badge badge-light">{{ $tab['orders']->count() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content pt-3" id="dashboardOrderTabContent">
                @foreach($orderTabs as $key => $tab)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="orders-panel-{{ $key }}" role="tabpanel" aria-labelledby="orders-tab-{{ $key }}">
                        <div class="table-responsive">
                            <table class="table table-hover dashboard-orders-table w-100">
                                <thead class="thead-light">
                                    <tr>
                                        <th>N°</th>
                                        <th>Cliente</th>
                                        <th>Livreur</th>
                                        <th>Livraison prévue</th>
                                        <th>Attribution</th>
                                        <th>Sous-total</th>
                                        <th>Remise</th>
                                        <th>Total</th>
                                        <th>État</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tab['orders'] as $order)
                                        <tr>
                                            <td>{{ $order->code ?: $order->id }}</td>
                                            <td>{{ $order->costumer->name ?? '—' }}<small class="d-block text-muted">{{ $order->costumer->phone ?? '—' }}</small></td>
                                            <td>{{ $order->user->name ?? 'Non attribué' }}<small class="d-block text-muted">{{ $order->user->phone ?? '' }}</small></td>
                                            <td>{{ $order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : '—' }}</td>
                                            <td>{{ $order->assigned_at ? $order->assigned_at->format('d/m/Y H:i') : '—' }}</td>
                                            <td>{{ number_format((float) $order->subtotal, 0, ',', ' ') }} F</td>
                                            <td>{{ $order->remis }} %</td>
                                            <td>{{ number_format((float) $order->total, 0, ',', ' ') }} F</td>
                                            <td>
                                                @if($order->status === 'ordered')
                                                    <span class="badge badge-warning">En cours</span>
                                                @elseif($order->status === 'delivered')
                                                    <span class="badge badge-success">Livrée</span>
                                                @elseif($order->status === 'canceled')
                                                    <span class="badge badge-danger">Annulée</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $order->status }}</span>
                                                @endif
                                            </td>
                                            <td><a href="{{ route('ordershow', $order) }}" class="btn btn-sm btn-success"><i class="material-icons f-16">visibility</i> Détails</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center text-muted py-4">Aucune commande dans cet onglet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-order-tabs{gap:4px;flex-wrap:nowrap;overflow-x:auto;white-space:nowrap}
    .dashboard-order-tabs .nav-link{display:flex;align-items:center;gap:7px;color:#555;border-radius:7px 7px 0 0}
    .dashboard-order-tabs .nav-link.active{color:#81201d;font-weight:700}
    .dashboard-order-tabs .badge{font-size:11px}
    .dashboard-orders-table{min-width:900px}
    @media(max-width:767.98px){.dashboard-order-tabs .nav-link{padding:8px 10px;font-size:12px}.dashboard-order-tabs .nav-item{flex:0 0 auto}}
</style>
