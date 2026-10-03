@extends('layouts.admin')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header / Navigation -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1">
                    @unless(Auth::user()->isCallCenter())
                    <li class="breadcrumb-item"><a href="{{ route('costumer.index') }}">Clients</a></li>
                    @endunless
                    @unless(Auth::user()->isCallCenter())
                    <li class="breadcrumb-item"><a href="{{ route('top-costumers') }}">Top clients</a></li>
                    @endunless
                    <li class="breadcrumb-item active" aria-current="page">Fiche client</li>
                </ol>
            </nav>
            <h3 class="font-weight-bold mb-0 text-dark">
                {{ $costumers->name }}
            </h3>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('costumer.follow-up', ['search' => $costumers->phone ?: $costumers->name]) }}" class="btn btn-outline-primary btn-sm mr-2 shadow-sm">
                <i class="fas fa-bell mr-1"></i> Suivi & Relances
            </a>
            @unless(Auth::user()->isCallCenter())
            <a href="{{ route('top-costumers') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left mr-1"></i> Retour aux top clients
            </a>
            @endunless
        </div>
    </div>

    <!-- Client Info & Summary Cards -->
    <div class="row mb-4">
        <!-- Client Details -->
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bold text-dark">{{ $costumers->name }}</h5>
                            <small class="text-muted">Client #{{ $costumers->id }}</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <span class="text-muted"><i class="fas fa-phone mr-2 text-primary"></i>Téléphone :</span>
                            <strong class="ml-1 text-dark">
                                @if ($costumers->phone)
                                    <a href="tel:{{ $costumers->phone }}" class="text-dark">{{ $costumers->phone }}</a>
                                @else
                                    <span class="text-muted font-italic">Non renseigné</span>
                                @endif
                            </strong>
                        </li>
                        <li class="mb-2">
                            <span class="text-muted"><i class="fas fa-envelope mr-2 text-primary"></i>E-mail :</span>
                            <span class="ml-1 text-dark">
                                {{ $costumers->email ?: 'Non renseigné' }}
                            </span>
                        </li>
                        <li class="mb-0">
                            <span class="text-muted"><i class="fas fa-map-marker-alt mr-2 text-primary"></i>Adresse :</span>
                            <span class="ml-1 text-dark">
                                {{ $costumers->adresse ?: 'Non renseignée' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Orders Count -->
        <div class="col-12 col-sm-6 col-md-4 mb-3 mb-sm-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted text-uppercase mb-1 small font-weight-bold">Total Commandes</h6>
                            <h2 class="mb-0 font-weight-bold text-primary">{{ $costumers->orders_count }}</h2>
                        </div>
                        <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        Total articles achetés : <strong class="text-dark">{{ $purchasedProducts->sum('total_quantity') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted text-uppercase mb-1 small font-weight-bold">Montant Total Acheté</h6>
                            <h2 class="mb-0 font-weight-bold text-success">
                                {{ number_format($costumers->orders_sum_total ?? 0, 0, ',', ' ') }} <small style="font-size: 60%;">FCFA</small>
                            </h2>
                        </div>
                        <div class="rounded-circle bg-light text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        Panier moyen : <strong class="text-dark">{{ $costumers->orders_count > 0 ? number_format(($costumers->orders_sum_total ?? 0) / $costumers->orders_count, 0, ',', ' ') : 0 }} FCFA</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Synthèse des produits commandés -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0 font-weight-bold text-dark">
                <i class="fas fa-layer-group text-primary mr-2"></i>Produits commandés par ce client
                <span class="badge badge-primary ml-2">{{ $purchasedProducts->count() }}</span>
            </h5>
            <small class="text-muted mt-1 mt-sm-0">Classés par quantité totale achetée</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Produit</th>
                            <th class="text-center" style="width: 140px;">Quantité totale</th>
                            <th class="text-right" style="width: 150px;">Prix unitaire</th>
                            <th class="text-right" style="width: 170px;">Montant total</th>
                            <th class="text-center" style="width: 140px;">Commandes</th>
                            <th style="width: 160px;">Dernier achat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchasedProducts as $index => $prod)
                            <tr>
                                <td class="text-muted font-weight-bold">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded bg-light border d-flex align-items-center justify-content-center mr-2 text-secondary" style="width: 36px; height: 36px;">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div>
                                            <strong class="text-dark">{{ $prod['name'] }}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary px-3 py-1 font-weight-bold" style="font-size: 0.95rem;">
                                        {{ $prod['total_quantity'] }}
                                    </span>
                                </td>
                                <td class="text-right font-weight-500">
                                    {{ number_format($prod['unit_price'], 0, ',', ' ') }} F
                                </td>
                                <td class="text-right font-weight-bold text-success">
                                    {{ number_format($prod['total_amount'], 0, ',', ' ') }} F
                                </td>
                                <td class="text-center text-muted">
                                    <span class="badge badge-light border">{{ $prod['orders_count'] }} commande(s)</span>
                                </td>
                                <td class="text-muted">
                                    {{ $prod['last_purchased_at'] ? \Illuminate\Support\Carbon::parse($prod['last_purchased_at'])->format('d/m/Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2 d-block text-secondary"></i>
                                    Aucun produit commandé par ce client pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Historique chronologique des commandes -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0 font-weight-bold text-dark">
                <i class="fas fa-history text-primary mr-2"></i>Historique détaillé des commandes
                <span class="badge badge-secondary ml-2">{{ $costumers->orders->count() }}</span>
            </h5>
            <small class="text-muted mt-1 mt-sm-0">Classées de la plus récente à la plus ancienne</small>
        </div>
        <div class="card-body p-3">
            @forelse ($costumers->orders as $orde)
                <div class="card mb-3 border shadow-none bg-white">
                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap border-bottom">
                        <div class="d-flex align-items-center flex-wrap my-1">
                            <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">
                                N° {{ $orde->code ?: ('CMD-'.$orde->id) }}
                            </span>
                            <span class="text-muted mr-3 small">
                                <i class="far fa-calendar-alt mr-1"></i>
                                {{ \Illuminate\Support\Carbon::parse($orde->date_order ?: $orde->created_at)->format('d/m/Y à H:i') }}
                            </span>
                            @if ($orde->status == 'ordered')
                                <span class="badge badge-warning">En cours</span>
                            @elseif($orde->status == 'delivered')
                                <span class="badge badge-success">Livré</span>
                            @elseif ($orde->status == 'canceled')
                                <span class="badge badge-danger">Annulée</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center my-1">
                            <span class="mr-3 font-weight-bold text-dark">
                                Total : <span class="text-success font-weight-bold">{{ number_format($orde->total, 0, ',', ' ') }} F CFA</span>
                            </span>
                            <a href="{{ route('ordershow', $orde->id) }}" class="btn btn-sm btn-outline-primary shadow-sm" title="Voir les détails de la commande">
                                <i class="fas fa-eye mr-1"></i> Détails
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="pl-3">Article / Produit</th>
                                        <th class="text-right" style="width: 140px;">Prix unitaire</th>
                                        <th class="text-center" style="width: 120px;">Quantité</th>
                                        <th class="text-right pr-3" style="width: 160px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($orde->orderItems as $item)
                                        @php
                                            $itemPrice = $item->price ?? ($item->product->price ?? 0);
                                            $itemTotal = $itemPrice * $item->quantity;
                                        @endphp
                                        <tr>
                                            <td class="pl-3">
                                                <i class="fas fa-check-circle text-success mr-1 small"></i>
                                                <strong>{{ $item->product->name ?? 'Produit personnalisé' }}</strong>
                                            </td>
                                            <td class="text-right">
                                                {{ number_format($itemPrice, 0, ',', ' ') }} F
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-light border font-weight-bold px-2 py-1">
                                                    {{ $item->quantity }}
                                                </span>
                                            </td>
                                            <td class="text-right pr-3 font-weight-bold text-dark">
                                                {{ number_format($itemTotal, 0, ',', ' ') }} F
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-2">
                                                Aucun article dans cette commande.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-shopping-bag fa-3x mb-3 text-secondary"></i>
                    <p class="mb-0">Ce client n'a passé aucune commande pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
