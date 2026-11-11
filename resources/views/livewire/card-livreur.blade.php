<div class="col-12 courier-workspace courier-dashboard">
    @include('livreurs.partials.section-navigation')

    <header class="courier-dashboard-hero">
        <div class="courier-dashboard-hero-copy"><span class="courier-kicker">TENACOS · ESPACE LIVREUR</span><h1>Bonjour {{ Auth::user()->name }}</h1><p>Retrouve tes livraisons, contacte les clients et suis tes résultats de la semaine.</p></div>
        <div class="courier-dashboard-date"><span class="material-icons" aria-hidden="true">today</span><div><strong>{{ now()->locale('fr')->translatedFormat('l d F') }}</strong><small>{{ now()->format('H:i') }} · Lomé</small></div></div>
    </header>

    <div class="courier-dashboard-actions">
        <a class="courier-action-button" href="{{ route('authlivrable', Auth::id()) }}"><span class="material-icons" aria-hidden="true">assignment_ind</span>Ouvrir mes livraisons</a>
        <a class="courier-action-button is-secondary" href="{{ route('livrable') }}"><span class="material-icons" aria-hidden="true">delivery_dining</span>Voir les courses disponibles</a>
    </div>

    @if (Session::has('messages'))<div class="alert alert-success" role="status">{{ session('messages') }}</div>@endif

    <section class="courier-dashboard-kpis" aria-label="Résumé de l’activité">
        <article class="courier-dashboard-kpi"><span class="material-icons" aria-hidden="true">local_shipping</span><div><small>Livraisons suivies</small><strong>{{ $totalLivraisons }}</strong><em>Depuis le début de la semaine</em></div></article>
        <article class="courier-dashboard-kpi is-green"><span class="material-icons" aria-hidden="true">task_alt</span><div><small>Livrées avec succès</small><strong>{{ $livrees }}</strong><em>Commandes confirmées</em></div></article>
        <article class="courier-dashboard-kpi is-amber"><span class="material-icons" aria-hidden="true">pending_actions</span><div><small>En cours ou annulées</small><strong>{{ $echouees }}</strong><em>À consulter dans le suivi</em></div></article>
        <article class="courier-dashboard-kpi is-red"><span class="material-icons" aria-hidden="true">payments</span><div><small>Montant des commandes livrées</small><strong>{{ number_format((float) $montantTotal, 0, ',', ' ') }} F</strong><em>Depuis le début de la semaine</em></div></article>
        <article class="courier-dashboard-kpi is-blue"><span class="material-icons" aria-hidden="true">price_check</span><div><small>Frais de livraison encaissés</small><strong>{{ number_format((float) $fraisLivraison, 0, ',', ' ') }} F</strong><em>Sur les commandes livrées</em></div></article>
    </section>

    <section class="courier-section-panel courier-dashboard-orders">
        <div class="courier-panel-heading"><div><h2>Activité récente</h2><p>Commandes prises en compte dans ton suivi hebdomadaire.</p></div><span class="courier-count">{{ $orders->count() }}</span></div>
        @if ($orders->isEmpty())
            <div class="courier-empty"><span class="material-icons" aria-hidden="true">local_shipping</span><strong>Pas encore d’activité cette semaine</strong><p>Tu peux consulter les nouvelles courses disponibles ou retrouver tes commandes attribuées.</p><a class="courier-action-button" href="{{ route('livrable') }}">Voir les courses</a></div>
        @else
            <div class="courier-order-grid">
                @foreach ($orders as $order)
                    @php
                        $phone = $order->delivery_phone ?: ($order->costumer->phone ?? '');
                        $phoneLink = preg_replace('/[^0-9+]/', '', $phone);
                        $address = $order->delivery_address ?: ($order->costumer->adresse ?? '');
                        $statusClass = $order->status === 'ordered' && $order->delivery_waiting ? 'is-waiting' : ($order->status === 'delivered' ? 'is-delivered' : ($order->status === 'canceled' ? 'is-canceled' : ''));
                        $statusText = $order->status === 'ordered' && $order->delivery_waiting ? 'En attente' : ($order->status === 'ordered' ? 'En cours' : ($order->status === 'delivered' ? 'Livrée' : ($order->status === 'canceled' ? 'Annulée' : $order->status)));
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
                        <div class="courier-order-actions"><a class="courier-action-button" href="{{ route('livrableshow', $order) }}"><span class="material-icons" aria-hidden="true">visibility</span>Voir le dossier</a></div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
