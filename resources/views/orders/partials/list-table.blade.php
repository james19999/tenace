<div class="orders-desktop-table">
    <div class="table-responsive">
        <table class="table table-hover orders-table w-100">
            <thead><tr><th>N°</th><th>Code</th><th>Cliente</th><th>Livreur</th><th>Livraison prévue</th><th>Attribution</th><th class="text-right">Sous-total</th><th>Remise</th><th class="text-right">Total</th><th>État</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ ($orders->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong class="orders-code">{{ $order->code ?: '—' }}</strong></td>
                        <td><strong>{{ $order->costumer->name ?? '—' }}</strong><small class="d-block text-muted">{{ $order->costumer->phone ?? 'Téléphone absent' }}</small></td>
                        <td>{{ $order->user->name ?? 'Non attribué' }}<small class="d-block text-muted">{{ $order->user->phone ?? '' }}</small></td>
                        <td>{{ $order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : '—' }}</td>
                        <td>{{ $order->assigned_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-right text-nowrap">{{ number_format((float) $order->subtotal, 0, ',', ' ') }} F</td>
                        <td>{{ $order->remis }} %</td>
                        <td class="text-right text-nowrap"><strong>{{ number_format((float) $order->total, 0, ',', ' ') }} F</strong></td>
                        <td>@include('orders.partials.status-badge', ['order' => $order])</td>
                        <td>@include('orders.partials.actions', ['order' => $order])</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="orders-empty-row">Aucune commande dans cet onglet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="orders-mobile-list">
    @forelse ($orders as $order)
        <article class="order-mobile-card" wire:key="order-mobile-{{ $order->id }}">
            <div class="order-mobile-top">
                <div><small>Commande</small><strong>{{ $order->code ?: '#' . $order->id }}</strong></div>
                @include('orders.partials.status-badge', ['order' => $order])
            </div>
            <div class="order-mobile-customer">
                <span class="material-icons" aria-hidden="true">person</span>
                <div><strong>{{ $order->costumer->name ?? 'Cliente inconnue' }}</strong><small>{{ $order->costumer->phone ?? 'Téléphone absent' }}</small></div>
            </div>
            <div class="order-mobile-grid">
                <div><small>Livreur</small><strong>{{ $order->user->name ?? 'Non attribué' }}</strong></div>
                <div><small>Livraison prévue</small><strong>{{ $order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : '—' }}</strong></div>
                <div><small>Attribution</small><strong>{{ $order->assigned_at?->format('d/m/Y H:i') ?? '—' }}</strong></div>
                <div><small>Remise</small><strong>{{ $order->remis ?? 0 }} %</strong></div>
            </div>
            <div class="order-mobile-total"><span>Sous-total <b>{{ number_format((float) $order->subtotal, 0, ',', ' ') }} F</b></span><strong>{{ number_format((float) $order->total, 0, ',', ' ') }} F</strong></div>
            @include('orders.partials.actions', ['order' => $order])
        </article>
    @empty
        <div class="orders-mobile-empty">Aucune commande dans cet onglet.</div>
    @endforelse
</div>

<style>
    .orders-table{min-width:1120px;margin:0;border-collapse:separate;border-spacing:0}
    .orders-table thead th{padding:12px 11px;border-top:0;border-bottom:1px solid #e6e8ed;background:#f7f8fa;color:#747a86;font-size:10px;font-weight:800;letter-spacing:.055em;text-transform:uppercase;white-space:nowrap}
    .orders-table tbody td{padding:13px 11px;border-top:1px solid #eff0f3;color:#3d414b;font-size:12px;vertical-align:middle}
    .orders-table tbody tr:hover{background:#fcfaf9}.orders-code{color:#84211f;white-space:nowrap}.orders-table td small{margin-top:3px;font-size:10px}
    .orders-empty-row,.orders-mobile-empty{padding:34px 15px!important;text-align:center;color:#7c828e!important}
    .orders-status{display:inline-flex;align-items:center;padding:6px 9px;border-radius:20px;font-size:10px;font-weight:750;white-space:nowrap}
    .orders-status-progress{background:#fff2d9;color:#8a641f}.orders-status-delivered{background:#e9f5ed;color:#34734b}.orders-status-canceled{background:#fae9e8;color:#a13b36}.orders-status-other{background:#f0f1f4;color:#626875}
    .orders-mobile-list{display:none}.order-mobile-card{margin:0 0 11px;padding:14px;border:1px solid #e7e9ee;border-radius:12px;background:#fff;box-shadow:0 4px 15px rgba(30,35,45,.035)}
    .order-mobile-top{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-bottom:11px;border-bottom:1px solid #eff0f3}
    .order-mobile-top>div{display:grid;gap:3px}.order-mobile-top small,.order-mobile-grid small{color:#838995;font-size:10px;font-weight:650}.order-mobile-top strong{color:#84211f;font-size:14px}
    .order-mobile-customer{display:flex;align-items:center;gap:9px;padding:12px 0}.order-mobile-customer>.material-icons{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#f8eded;color:#84211f;font-size:19px}
    .order-mobile-customer div{display:grid;min-width:0;gap:2px}.order-mobile-customer strong{overflow-wrap:anywhere;color:#30343d;font-size:13px}.order-mobile-customer small{color:#858b96;font-size:11px}
    .order-mobile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:11px 0;border-top:1px solid #eff0f3}
    .order-mobile-grid>div{display:grid;min-width:0;gap:3px}.order-mobile-grid strong{overflow-wrap:anywhere;color:#464b56;font-size:11px;font-weight:700}
    .order-mobile-total{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid #eff0f3}.order-mobile-total span{color:#737985;font-size:11px}.order-mobile-total span b{margin-left:4px;color:#4c515c}.order-mobile-total>strong{color:#84211f;font-size:16px;white-space:nowrap}
    .order-actions{display:flex;flex-wrap:wrap;gap:6px;min-width:220px}.order-actions form{margin:0}.order-actions .btn{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:650}.order-mobile-card>.order-actions{min-width:0;padding-top:10px;border-top:1px solid #eff0f3}.order-mobile-card>.order-actions .btn{flex:1 1 auto;justify-content:center;min-height:36px}
    @media(max-width:767.98px){.orders-desktop-table{display:none}.orders-mobile-list{display:block}.order-mobile-card .badge{white-space:nowrap}}
</style>
