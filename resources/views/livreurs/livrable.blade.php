@extends('layouts.admin')

@section('content')
<div class="col-12 courier-workspace">
    @if (Auth::user()?->user_type === 'LVS')
        @include('livreurs.partials.section-navigation')
    @endif

    <header class="courier-page-heading">
        <div><span class="courier-kicker">Nouvelles courses</span><h1>Livraisons disponibles</h1><p>Choisis une commande à prendre en charge et contacte le client si nécessaire.</p></div>
    </header>

    @if (Session::has('messages'))<div class="alert alert-success" role="status">{{ session('messages') }}</div>@endif
    @if (Session::has('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if (Session::has('waring'))<div class="alert alert-warning" role="alert">{{ session('waring') }}</div>@endif

    @if (Auth::user()->active == true)
        <div class="courier-summary-grid" aria-label="Résumé des livraisons disponibles">
            <div class="courier-summary-card"><span class="courier-summary-icon material-icons" aria-hidden="true">local_shipping</span><div><span>Courses disponibles</span><strong>{{ $orders->count() }}</strong></div></div>
            <div class="courier-summary-card"><span class="courier-summary-icon material-icons" aria-hidden="true">payments</span><div><span>Valeur des commandes</span><strong>{{ number_format((float) $orders->sum('total'), 0, ',', ' ') }} F</strong></div></div>
            <div class="courier-summary-card"><span class="courier-summary-icon material-icons" aria-hidden="true">today</span><div><span>Pour aujourd’hui</span><strong>{{ now()->format('d/m') }}</strong></div></div>
        </div>
        <section class="courier-section-panel">
            <div class="courier-panel-heading"><div><h2>À prendre en charge</h2><p>Vérifie le contact, l’adresse et le montant avant de prendre la course.</p></div><span class="courier-count">{{ $orders->count() }}</span></div>
            @if ($orders->isEmpty())
                <div class="courier-empty"><span class="material-icons" aria-hidden="true">done_all</span><strong>Aucune course disponible</strong><p>Les nouvelles livraisons s’afficheront ici dès qu’elles seront prêtes.</p></div>
            @else
                <div class="courier-order-grid">
                    @foreach ($orders as $order)
                        @php
                            $phone = $order->delivery_phone ?: ($order->costumer->phone ?? '');
                            $phoneLink = preg_replace('/[^0-9+]/', '', $phone);
                            $address = $order->delivery_address ?: ($order->costumer->adresse ?? '');
                        @endphp
                        <article class="courier-order-card">
                            <div class="courier-order-top"><div class="courier-order-code"><small>Nouvelle commande</small><strong>{{ $order->code ?: 'N° '.$order->id }}</strong></div><span class="courier-status">À prendre</span></div>
                            <div class="courier-customer"><span class="courier-customer-icon material-icons" aria-hidden="true">person</span><div class="courier-customer-details"><strong>{{ $order->costumer->name ?? 'Client' }}</strong>@if($phone)<a class="courier-call-link" href="tel:{{ $phoneLink }}">{{ $phone }} · Appeler</a>@else<span>Téléphone non renseigné</span>@endif</div></div>
                            <div class="courier-order-facts">
                                <div class="courier-order-fact"><span>Articles</span><strong>{{ number_format((float) $order->subtotal, 0, ',', ' ') }} F</strong></div>
                                <div class="courier-order-fact"><span>Frais de livraison</span><strong>{{ number_format((float) $order->tax, 0, ',', ' ') }} F</strong></div>
                                <div class="courier-order-fact is-total"><span>Total à livrer</span><strong>{{ number_format((float) $order->total, 0, ',', ' ') }} F</strong></div>
                                <div class="courier-order-fact"><span>Heure souhaitée</span><strong>{{ $order->time ?: 'À confirmer' }}</strong></div>
                                <div class="courier-order-fact" style="grid-column:1/-1"><span>Adresse de livraison</span><strong>{{ $address ?: 'Adresse non renseignée' }}</strong></div>
                            </div>
                            <div class="courier-order-actions">
                                <form method="POST" action="{{ route('checklivraison', $order->id) }}" style="display:flex;flex:1">@csrf<button class="courier-action-button" type="submit" style="width:100%"><span class="material-icons" aria-hidden="true">add_task</span>Prendre cette livraison</button></form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="courier-section-panel"><div class="courier-empty"><span class="material-icons" aria-hidden="true">lock</span><strong>Compte temporairement bloqué</strong><p>Contacte ton responsable pour réactiver ton accès aux livraisons.</p></div></section>
    @endif
</div>
@endsection
