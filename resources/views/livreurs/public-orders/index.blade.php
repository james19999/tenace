@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/courier-public-orders.css') }}?v=20261020.3">
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
<div class="container-fluid py-4 courier-orders-page">
    <div class="courier-orders-heading"><span class="courier-orders-eyebrow">LIVRAISONS PARTENAIRES · {{ auth()->user()->country_code }}</span><h1>Commandes disponibles</h1><p>Choisis une commande à prendre en charge. Le suivi de celles déjà prises reste plus bas.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <section class="courier-order-section">
        <div class="courier-section-heading"><div><span>À PRENDRE</span><h2>Commandes disponibles</h2></div><small>{{ $available->total() }} disponible(s) · premier livreur à accepter</small></div>
        <div class="courier-orders-grid">
            @forelse($available as $order)
                <article class="courier-order-card">
                    <div class="courier-order-top"><div><span class="courier-order-ref">{{ $order->code }}</span><span class="courier-order-caption">Nouvelle commande</span></div><strong class="courier-order-total">{{ number_format((int) $order->montant, 0, ',', ' ') }} <small>F</small></strong></div>
                    <div class="courier-customer"><strong>{{ $order->costumer?->name }}</strong><a href="tel:{{ $order->costumer?->phone }}">{{ $order->costumer?->phone }}</a><span>{{ $order->costumer?->adresse }}</span></div>
                    <div class="courier-order-items"><span class="courier-detail-label">PRODUITS</span><ul>@foreach($order->orderItems as $item)<li><span>{{ $item->product?->name ?? 'Produit' }}</span><strong>× {{ $item->quantity }}</strong></li>@endforeach</ul></div>
                    <div class="courier-payment-note"><span class="courier-detail-label">PAIEMENT</span><strong>{{ $order->payment_timing === 'on_delivery' ? 'À la livraison · '.($order->requested_payment_method === 'mobile_money' ? 'Mobile Money' : 'Espèces') : 'Prévu avant livraison' }}</strong></div>
                    <form method="POST" action="{{ route('courier.public-orders.claim', $order) }}">@csrf<button class="btn btn-danger courier-action-button">Prendre cette commande</button></form>
                </article>
            @empty
                <div class="courier-empty">Aucune commande disponible pour le moment.</div>
            @endforelse
        </div>
        @if($available->hasPages())<div class="courier-pagination">{{ $available->links() }}</div>@endif
    </section>
    @if($assigned->isNotEmpty())
        <details class="courier-assigned-fold">
            <summary>
                <span class="courier-assigned-title"><b>Mes commandes en cours</b><small>Suivi des commandes déjà prises</small></span>
                <strong class="courier-assigned-count">{{ $assigned->count() }}</strong>
                <span class="material-icons courier-assigned-chevron" aria-hidden="true">expand_more</span>
            </summary>
            <div class="courier-orders-grid courier-assigned-grid">
                @foreach($assigned as $order)
                    <article class="courier-order-card">
                        <div class="courier-order-top"><div><span class="courier-order-ref">{{ $order->code }}</span><span class="courier-order-caption">{{ $statusLabels[$order->partner_order_status] ?? 'Statut à vérifier' }}</span></div><strong class="courier-order-total">{{ number_format((int) $order->montant, 0, ',', ' ') }} <small>F</small></strong></div>
                        <div class="courier-customer"><span class="courier-detail-label">CLIENT</span><strong>{{ $order->costumer?->name }}</strong><a href="tel:{{ $order->costumer?->phone }}">{{ $order->costumer?->phone }}</a><span>{{ $order->costumer?->adresse }}</span></div>
                        <div class="courier-order-items"><span class="courier-detail-label">PRODUITS</span><ul>@foreach($order->orderItems as $item)<li><span>{{ $item->product?->name ?? 'Produit' }}</span><strong>× {{ $item->quantity }}</strong></li>@endforeach</ul></div>
                        @if($order->partner)<div class="courier-partner"><span class="courier-detail-label">PARTENAIRE</span><strong>{{ $order->partner->name }}</strong><a href="tel:{{ $order->partner->phone }}">{{ $order->partner->phone }}</a></div>@endif
                        <div class="courier-payment-note"><span class="courier-detail-label">PAIEMENT</span><strong>{{ $order->payment_timing === 'on_delivery' ? 'À la livraison · '.($order->requested_payment_method === 'mobile_money' ? 'Mobile Money' : 'Espèces') : 'Prévu avant livraison' }}</strong></div>
                        @if($order->partner_order_status === 'handed_to_courier')
                            <form method="POST" action="{{ route('courier.public-orders.receive', $order) }}">@csrf<button class="btn btn-outline-danger courier-action-button">Confirmer la réception du colis</button></form>
                        @elseif($order->partner_order_status === 'out_for_delivery')
                            @php
                                $deliveryBalance = max(0, (int) $order->montant - (int) $order->paid_amount);
                                $gatewayPayment = $order->payments->where('gateway', 'fedapay')->where('stage', 'on_delivery')->sortByDesc('id')->first();
                                $gatewayConfirmed = $gatewayPayment?->status === 'confirmed';
                            @endphp
                            @if($order->payment_timing === 'on_delivery' && $deliveryBalance > 0)
                                <div class="courier-payment-alert">Montant à recevoir : <strong>{{ number_format($deliveryBalance, 0, ',', ' ') }} F</strong>.</div>
                                <form method="POST" action="{{ route('courier.public-orders.deliver', $order) }}" class="courier-cash-delivery-form">@csrf<input type="hidden" name="payment_method" value="cash"><button class="btn btn-success courier-action-button">Paiement en espèces · confirmer la livraison</button></form>
                                @if($order->country_code === 'TG')
                                    <form method="POST" action="{{ route('courier.public-orders.mobile-payment.start', $order) }}" class="courier-payment-fields courier-fedapay-form" data-fedapay-form data-status-url="{{ $gatewayPayment?->gateway_transaction_id ? route('courier.public-orders.mobile-payment.status', [$order, $gatewayPayment]) : '' }}">@csrf
                                        <strong class="courier-mobile-title">Paiement Mobile Money sécurisé</strong>
                                        <label>Opérateur<select name="provider_method" class="form-control" required><option value="mixx">Mixx by Yas</option><option value="moov">Moov Money</option></select></label>
                                        <label>Téléphone du client<input name="phone" class="form-control" type="tel" inputmode="tel" autocomplete="tel" value="{{ $order->costumer?->phone }}" placeholder="Ex. 90 00 00 00" required></label>
                                        <label>E-mail du client<input name="email" class="form-control" type="email" autocomplete="email" value="{{ $order->costumer?->email }}" placeholder="client@exemple.com" required></label>
                                        <small>Le client recevra une demande sur son téléphone et saisira lui-même son code PIN. Ne lui demande jamais son code.</small>
                                        <button class="btn btn-danger courier-action-button" type="submit"><span class="courier-button-label">Envoyer la demande de paiement</span><span class="courier-button-loading" hidden>Envoi en cours…</span></button>
                                        <div class="courier-gateway-result" data-gateway-result role="status" aria-live="polite" @if(!$gatewayPayment || $gatewayPayment->status === 'failed') hidden @endif>{{ $gatewayPayment?->status === 'confirmed' ? 'Paiement confirmé par FedaPay.' : 'Vérification de la demande Mobile Money…' }}</div>
                                    </form>
                                @endif
                            @elseif($order->payment_timing === 'on_delivery' && $deliveryBalance === 0 && $gatewayConfirmed)
                                <div class="courier-gateway-result is-success">Paiement confirmé par FedaPay.</div>
                                <form method="POST" action="{{ route('courier.public-orders.deliver', $order) }}">@csrf<input type="hidden" name="payment_method" value="mobile_money"><button class="btn btn-success courier-action-button">Confirmer la livraison</button></form>
                            @elseif($order->payment_timing === 'on_delivery' && $deliveryBalance === 0 && $gatewayPayment?->status === 'refunded')
                                <div class="courier-gateway-result is-error">Le paiement Mobile Money a été remboursé. Contacte le partenaire avant de terminer la livraison.</div>
                            @else
                                <form method="POST" action="{{ route('courier.public-orders.deliver', $order) }}">@csrf<button class="btn btn-success courier-action-button">Confirmer la livraison</button></form>
                            @endif
                        @else
                            <p class="courier-waiting-note">En attente de la remise du colis par le partenaire.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </details>
    @endif
</div>
<script>
(() => {
    document.querySelectorAll('[data-fedapay-form]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        const buttonLabel = form.querySelector('.courier-button-label');
        const buttonLoading = form.querySelector('.courier-button-loading');
        const result = form.querySelector('[data-gateway-result]');
        let polling = false;

        const showMessage = (message, success = false) => {
            result.hidden = false;
            result.textContent = message;
            result.classList.toggle('is-success', success);
            result.classList.toggle('is-error', !success);
        };
        const poll = async (url) => {
            if (polling || !url) return;
            polling = true;
            for (let attempt = 0; attempt < 45; attempt += 1) {
                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('La vérification du paiement est temporairement indisponible.');
                    const state = await response.json();
                    showMessage(state.message || 'En attente de la confirmation du client sur son téléphone.', state.status === 'confirmed');
                    if (state.status === 'confirmed') {
                        window.setTimeout(() => window.location.reload(), 1200);
                        return;
                    }
                    if (state.status === 'failed') return;
                } catch (_) {
                    showMessage('Impossible de vérifier le paiement pour le moment. Réessaie dans quelques instants.');
                }
                await new Promise((resolve) => window.setTimeout(resolve, 8000));
            }
            showMessage('La demande est toujours en attente. Vérifie le téléphone du client avant toute nouvelle tentative.');
        };

        if (form.dataset.statusUrl) poll(form.dataset.statusUrl);
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (button.disabled) return;
            button.disabled = true;
            buttonLabel.hidden = true;
            buttonLoading.hidden = false;
            result.hidden = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'FedaPay n’a pas pu démarrer le paiement.');
                showMessage(data.message || 'Demande envoyée. Le client doit confirmer sur son téléphone.');
                if (data.status_url) {
                    form.dataset.statusUrl = data.status_url;
                    poll(data.status_url);
                }
            } catch (error) {
                showMessage(error.message || 'Erreur pendant l’envoi de la demande.');
            } finally {
                button.disabled = false;
                buttonLabel.hidden = false;
                buttonLoading.hidden = true;
            }
        });
    });
})();
</script>
@endsection
