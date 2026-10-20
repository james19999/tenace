@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
<div class="container-fluid py-3 partner-portal partner-prices-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-danger font-weight-bold small">COMMANDES PARTENAIRES</span>
            <h1 class="h3 mb-1">Catalogue partenaire · {{ $countries[$country]['label'] }}</h1>
            <p class="text-muted mb-0">Le prix de vente est fixé sur la fiche produit. Configure ici la disponibilité et les frais de livraison.</p>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: .5rem">
            <label for="country-picker" class="mb-0">Pays</label>
            @if (count($countries) > 1)
                <select id="country-picker" class="form-control" style="width: auto" onchange="if(this.value) window.location.href=this.value">
                    @foreach ($countries as $code => $item)
                        <option value="{{ route('partner-ordering.prices', $code) }}" @selected($code === $country)>{{ $item['label'] }}</option>
                    @endforeach
                </select>
            @else
                <strong>{{ $countries[$country]['label'] }}</strong>
            @endif
            @if (auth()->user()->user_type !== \App\Models\User::ROLE_PARTNER)
                <a href="{{ route('partner-ordering.links') }}" class="btn btn-outline-danger">Gérer les liens</a>
            @endif
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('partner-ordering.prices.save', $country) }}">
        @csrf
        @method('PUT')
        <section class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="form-group mb-2">
                    <label for="order-delivery-fee">Forfait global par commande (FCFA)</label>
                    <input class="form-control" type="number" min="0" step="1" id="order-delivery-fee" name="order_delivery_fee" value="{{ old('order_delivery_fee', (int) ($deliverySetting->order_delivery_fee ?? 0) > 0 ? $deliverySetting->order_delivery_fee : '') }}" placeholder="Montant en FCFA">
                    <small class="text-muted">Ce forfait s’applique une seule fois si au moins un produit du panier n’a pas de frais spécifique.</small>
                </div>
            </div>
        </section>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light"><tr><th>Produit</th><th style="width: 210px">Prix de vente fixé (FCFA)</th><th style="width: 260px">Frais spécifiques / unité (FCFA)</th><th style="width: 150px">Disponible dans le pays</th></tr></thead>
                    <tbody>
                    @forelse ($products as $product)
                        @php($countryPrice = $product->countryPrices->first())
                        <tr>
                            <td><strong>{{ $product->name }}</strong><small class="d-block text-muted">Réf. {{ $product->id }}</small></td>
                            <td><strong>{{ number_format((float) ($countryPrice?->price ?? $product->price), 0, ',', ' ') }} F</strong><small class="d-block text-muted">Tarif fixé · non modifiable ici</small></td>
                            <td><input class="form-control" type="number" min="0" step="1" name="delivery_fees[{{ $product->id }}]" value="{{ old('delivery_fees.'.$product->id, $countryPrice?->has_delivery_fee_override ? $countryPrice->delivery_fee : '') }}" placeholder="Forfait global" aria-label="Frais spécifiques par unité pour {{ $product->name }}"><small class="text-muted">Vide = forfait global ; 0 = livraison offerte</small></td>
                            <td>
                                <input type="hidden" name="available[{{ $product->id }}]" value="0">
                                <label class="mb-0"><input type="checkbox" name="available[{{ $product->id }}]" value="1" @checked(old('available.'.$product->id, $countryPrice?->is_available ?? false))> Actif</label>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Aucun produit.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
            <div class="card-footer text-right"><button class="btn btn-danger" type="submit">Enregistrer la disponibilité et la livraison</button></div>
        </div>
    </form>
</div>
@endsection
