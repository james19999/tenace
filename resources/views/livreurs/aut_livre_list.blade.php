@extends('layouts.admin')

@section('content')
<div class="col-12 courier-workspace">
    @if (Auth::user()?->user_type === 'LVS')
        @include('livreurs.partials.section-navigation')
    @endif

    <header class="courier-page-heading">
        <div><span class="courier-kicker">Espace livreur</span><h1>Mes livraisons</h1><p>Retrouve tes commandes attribuées et ouvre un dossier pour confirmer le résultat.</p></div>
    </header>

    @if (Session::has('messages'))
        <div class="alert alert-success" role="status">{{ session('messages') }}</div>
    @endif

    @php
        $toDeliverOrders = $orders->filter(fn ($order) => $order->status === 'ordered' && ! $order->delivery_waiting)->values();
        $waitingOrders = $orders->filter(fn ($order) => $order->status === 'ordered' && $order->delivery_waiting)->values();
        $closedOrders = $orders->filter(fn ($order) => in_array($order->status, ['delivered', 'canceled'], true))->values();
        $activeTab = $toDeliverOrders->isNotEmpty() ? 'to-deliver' : ($waitingOrders->isNotEmpty() ? 'waiting' : 'closed');
        $deliveryTabs = [
            'to-deliver' => ['label' => 'À livrer', 'icon' => 'local_shipping', 'orders' => $toDeliverOrders],
            'waiting' => ['label' => 'En attente', 'icon' => 'schedule', 'orders' => $waitingOrders],
            'closed' => ['label' => 'Clôturées aujourd’hui', 'icon' => 'task_alt', 'orders' => $closedOrders],
        ];
    @endphp
    <div class="courier-summary-grid" aria-label="Résumé de mes livraisons">
        <div class="courier-summary-card"><span class="courier-summary-icon material-icons" aria-hidden="true">local_shipping</span><div><span>À livrer</span><strong>{{ $toDeliverOrders->count() }}</strong></div></div>
        <div class="courier-summary-card"><span class="courier-summary-icon material-icons" aria-hidden="true">schedule</span><div><span>En attente</span><strong>{{ $waitingOrders->count() }}</strong></div></div>
        <div class="courier-summary-card"><span class="courier-summary-icon material-icons" aria-hidden="true">task_alt</span><div><span>Clôturées aujourd’hui</span><strong>{{ $closedOrders->count() }}</strong></div></div>
    </div>

    <section class="courier-section-panel">
        <div class="courier-panel-heading"><div><h2>Mes commandes</h2><p>Les livraisons à effectuer sont affichées en premier.</p></div><span class="courier-count">{{ $orders->count() }}</span></div>
        @if ($orders->isEmpty())
            <div class="courier-empty"><span class="material-icons" aria-hidden="true">inventory_2</span><strong>Aucune livraison attribuée</strong><p>Les nouvelles commandes qui te seront confiées apparaîtront ici.</p></div>
        @else
            <div class="courier-order-tabs" role="tablist" aria-label="Filtrer mes commandes">
                @foreach ($deliveryTabs as $tabKey => $tab)
                    <button type="button" class="courier-order-tab{{ $activeTab === $tabKey ? ' is-active' : '' }}" id="courier-tab-{{ $tabKey }}" role="tab" aria-controls="courier-panel-{{ $tabKey }}" aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}" tabindex="{{ $activeTab === $tabKey ? '0' : '-1' }}" data-courier-tab="{{ $tabKey }}">
                        <span class="material-icons" aria-hidden="true">{{ $tab['icon'] }}</span><span>{{ $tab['label'] }}</span><strong>{{ $tab['orders']->count() }}</strong>
                    </button>
                @endforeach
            </div>
            @foreach ($deliveryTabs as $tabKey => $tab)
                <div id="courier-panel-{{ $tabKey }}" class="courier-tab-panel" role="tabpanel" aria-labelledby="courier-tab-{{ $tabKey }}" data-courier-panel="{{ $tabKey }}" @if($activeTab !== $tabKey) hidden @endif>
                    @if ($tab['orders']->isEmpty())
                        <div class="courier-empty courier-tab-empty"><span class="material-icons" aria-hidden="true">{{ $tab['icon'] }}</span><strong>{{ $tabKey === 'to-deliver' ? 'Aucune livraison à effectuer' : ($tabKey === 'waiting' ? 'Aucune livraison en attente' : 'Aucune commande clôturée aujourd’hui') }}</strong><p>{{ $tabKey === 'to-deliver' ? 'Les commandes attribuées qui attendent une livraison apparaîtront ici.' : ($tabKey === 'waiting' ? 'Les livraisons mises en attente apparaîtront ici.' : 'Tes commandes livrées ou annulées aujourd’hui apparaîtront ici.') }}</p></div>
                    @else
                        <div class="courier-order-grid">
                            @foreach ($tab['orders'] as $order)
                    @php
                        $phone = $order->delivery_phone ?: ($order->costumer->phone ?? '');
                        $phoneLink = preg_replace('/[^0-9+]/', '', $phone);
                        $address = $order->delivery_address ?: ($order->costumer->adresse ?? '');
                        $statusClass = $order->status === 'ordered' && $order->delivery_waiting ? 'is-waiting' : ($order->status === 'delivered' ? 'is-delivered' : ($order->status === 'canceled' ? 'is-canceled' : ''));
                        $statusText = $order->status === 'ordered' && $order->delivery_waiting ? 'En attente' : ($order->status === 'ordered' ? 'À livrer' : ($order->status === 'delivered' ? 'Livrée' : ($order->status === 'canceled' ? 'Annulée' : $order->status)));
                    @endphp
                    <article class="courier-order-card">
                        <div class="courier-order-top"><div class="courier-order-code"><small>Commande</small><strong>{{ $order->code ?: 'N° '.$order->id }}</strong></div><span class="courier-status {{ $statusClass }}">{{ $statusText }}</span></div>
                        <div class="courier-customer"><span class="courier-customer-icon material-icons" aria-hidden="true">person</span><div class="courier-customer-details"><strong>{{ $order->costumer->name ?? 'Client' }}</strong>@if($phone)<a class="courier-call-link" href="tel:{{ $phoneLink }}">{{ $phone }} · Appeler</a>@else<span>Téléphone non renseigné</span>@endif</div></div>
                        <div class="courier-order-facts">
                            <div class="courier-order-fact"><span>Articles</span><strong>{{ number_format((float) $order->subtotal, 0, ',', ' ') }} F</strong></div>
                            <div class="courier-order-fact"><span>Livraison</span><strong>{{ number_format((float) $order->tax, 0, ',', ' ') }} F</strong></div>
                            <div class="courier-order-fact is-total"><span>Total commande</span><strong>{{ number_format((float) $order->total, 0, ',', ' ') }} F</strong></div>
                            <div class="courier-order-fact"><span>Heure prévue</span><strong>{{ $order->time ?: 'À confirmer' }}</strong></div>
                            <div class="courier-order-fact" style="grid-column:1/-1"><span>Adresse de livraison</span><strong>{{ $address ?: 'Adresse non renseignée' }}</strong></div>
                        </div>
                        <div class="courier-order-actions"><a class="courier-action-button" href="{{ route('livrableshow', $order) }}"><span class="material-icons" aria-hidden="true">visibility</span>Ouvrir la livraison</a></div>
                    </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </section>
</div>
<script>
    (() => {
        const tabs = document.querySelectorAll('[data-courier-tab]');
        const panels = document.querySelectorAll('[data-courier-panel]');
        if (!tabs.length || !panels.length) return;
        tabs.forEach((tab) => tab.addEventListener('click', () => {
            const target = tab.dataset.courierTab;
            tabs.forEach((item) => {
                const selected = item === tab;
                item.classList.toggle('is-active', selected);
                item.setAttribute('aria-selected', selected ? 'true' : 'false');
                item.tabIndex = selected ? 0 : -1;
            });
            panels.forEach((panel) => { panel.hidden = panel.dataset.courierPanel !== target; });
        }));
    })();
</script>
@endsection
