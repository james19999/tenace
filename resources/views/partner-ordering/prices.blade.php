@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
@php($isAdmin = auth()->user()->user_type === \App\Models\User::ROLE_ADMIN)
<div class="container-fluid py-3 partner-portal partner-prices-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-danger font-weight-bold small">COMMANDES PARTENAIRES</span>
            <h1 class="h3 mb-1">Catalogue partenaire · {{ $countries[$country]['label'] }}</h1>
            <p class="text-muted mb-0">{{ $isAdmin ? 'Définis le prix de vente et les produits disponibles pour ce pays.' : ($isPartner ? 'Le prix de vente est fixé par l’administration. Consulte les quantités approvisionnées pour ton compte et configure les frais de livraison.' : 'Configure ici les produits visibles dans le catalogue partenaire de ce pays.') }}</p>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: .5rem">
            <label for="country-picker" class="mb-0">Pays</label>
            @if (count($countries) > 1)
                <select id="country-picker" class="form-control js-country-search" style="width: auto" onchange="if(this.value) window.location.href=this.value">
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

    <form id="partner-prices-form" method="POST" action="{{ route('partner-ordering.prices.save', $country) }}">
        @csrf
        @method('PUT')
    </form>

    @if (! $isPartner && count($countryPartners) > 0)
        <form method="GET" action="{{ route('partner-ordering.prices', $country) }}" class="card shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap align-items-end" style="gap: .75rem">
                <div class="form-group mb-0" style="min-width: 260px">
                    <label for="stock-partner-picker">Partenaire à approvisionner</label>
                    <select class="form-control" id="stock-partner-picker" name="partner_id" required>
                        <option value="">Choisir un partenaire</option>
                        @foreach ($countryPartners as $countryPartner)
                            <option value="{{ $countryPartner->id }}" @selected((int) request('partner_id', $stockPartner?->id) === (int) $countryPartner->id)>{{ $countryPartner->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Afficher son stock</button>
            </div>
        </form>
    @endif

    @if ($showPartnerStock)
        <section class="card shadow-sm mb-3 partner-stock-summary">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center" style="gap: 1rem">
                <div><strong>Stock de {{ $stockPartner->name }} · {{ $countries[$country]['label'] }}</strong><div class="text-muted">Quantités approvisionnées, réserve actuelle et stock encore chez les livreurs.</div></div>
                @if ($canReplenishPartnerStock && ! $warehouseSnapshot['warehouse'])
                    <span class="badge badge-warning p-2">Réapprovisionnement indisponible pour ce pays</span>
                @endif
            </div>
        </section>
    @endif

        @if ($isPartner)
            <section class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="form-group mb-2">
                        <label for="order-delivery-fee">Forfait global par commande (FCFA)</label>
                        <input class="form-control" form="partner-prices-form" type="number" min="0" step="1" id="order-delivery-fee" name="order_delivery_fee" value="{{ old('order_delivery_fee', (int) ($deliverySetting->order_delivery_fee ?? 0) > 0 ? $deliverySetting->order_delivery_fee : '') }}" placeholder="Montant en FCFA">
                        <small class="text-muted">Ce forfait s’applique une seule fois si au moins un produit du panier n’a pas de frais spécifique.</small>
                    </div>
                </div>
            </section>
        @endif
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light"><tr><th>Produit</th><th style="width: 200px">Prix de vente (FCFA)</th>@if($isAdmin)<th style="min-width: 280px">Prix selon quantité</th>@endif @if($showPartnerStock)<th style="width: 150px">Approvisionné</th><th style="width: 125px">En réserve</th><th style="width: 125px">Chez les livreurs</th>@endif @if($canReplenishPartnerStock)<th style="width: 180px">Réapprovisionnement</th>@endif<th style="width: 180px">Disponible dans le pays</th></tr></thead>
                    <tbody>
                    @forelse ($products as $product)
                        @php($countryPrice = $product->countryPrices->first())
                        <tr>
                            <td><strong>{{ $product->name }}</strong><small class="d-block text-muted">Réf. {{ $product->id }}</small></td>
                            <td>@if($isAdmin)<input class="form-control" form="partner-prices-form" type="number" name="prices[{{ $product->id }}]" min="0" max="999999999" step="1" required value="{{ old('prices.'.$product->id, $countryPrice?->price ?? $product->price) }}" aria-label="Prix de vente de {{ $product->name }} en FCFA">@else<strong>{{ number_format((float) ($countryPrice?->price ?? $product->price), 0, ',', ' ') }} F</strong><small class="d-block text-muted">Tarif fixé par l’administration</small>@endif</td>
                            @if($isAdmin)
                                <td>
                                    <input form="partner-prices-form" type="hidden" name="tier_configured[{{ $product->id }}]" value="1">
                                    <details class="quantity-tier-editor">
                                        <summary>{{ $countryPrice?->quantityTiers?->count() ? $countryPrice->quantityTiers->count().' palier(s) configuré(s)' : 'Ajouter un prix dégressif' }}</summary>
                                        <div class="quantity-tier-list" data-tier-list data-product-id="{{ $product->id }}">
                                            @foreach($countryPrice?->quantityTiers ?? [] as $tierIndex => $tier)
                                                <div class="quantity-tier-row">
                                                    <label>À partir de <input form="partner-prices-form" class="form-control" type="number" min="2" max="500" name="quantity_prices[{{ $product->id }}][{{ $tierIndex }}][min_quantity]" value="{{ $tier->min_quantity }}"></label>
                                                    <label>Prix unitaire <input form="partner-prices-form" class="form-control" type="number" min="1" max="999999999" name="quantity_prices[{{ $product->id }}][{{ $tierIndex }}][unit_price]" value="{{ $tier->unit_price }}"></label>
                                                    <button class="btn btn-outline-danger btn-sm" type="button" data-remove-tier>Retirer</button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm mt-2" type="button" data-add-tier>Ajouter un palier</button>
                                        <small class="d-block text-muted mt-1">Ce prix unitaire s’applique dès cette quantité et jusqu’au palier suivant. Le dernier palier continue de s’appliquer aux quantités supérieures.</small>
                                    </details>
                                </td>
                            @endif
                            @if($showPartnerStock)
                                @php($partnerQty = (int) ($partnerStocks->get($product->id)?->quantity ?? 0))
                                @php($courierQty = (int) ($courierStocks[$product->id] ?? 0))
                                @php($suppliedQty = max((int) ($suppliedStocks[$product->id] ?? 0), $partnerQty + $courierQty))
                                <td><strong>{{ number_format($suppliedQty, 0, ',', ' ') }}</strong><small class="d-block text-muted">Total reçu jusqu’ici</small></td>
                                <td><strong data-partner-current>{{ number_format($partnerQty, 0, ',', ' ') }}</strong><small class="d-block text-muted">En réserve</small></td>
                                <td><strong>{{ number_format($courierQty, 0, ',', ' ') }}</strong><small class="d-block text-muted">À livrer</small></td>
                            @endif
                            @if($canReplenishPartnerStock)
                                @php($partnerQty = (int) ($partnerStocks->get($product->id)?->quantity ?? 0))
                                <td>
                                    @php($oldReplenishQuantity = (int) old('product_id', 0) === (int) $product->id ? old('quantity') : '')
                                    @if ($countryPrice?->is_available)
                                        <form method="POST" action="{{ route('partner-ordering.prices.replenish', $country) }}" class="partner-replenish-form">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            @if (! $isPartner)<input type="hidden" name="partner_id" value="{{ $stockPartner->id }}">@endif
                                            <input class="form-control partner-replenish-quantity" type="number" min="1" step="1" name="quantity" value="{{ $oldReplenishQuantity }}" data-partner="{{ $partnerQty }}" aria-label="Quantité à approvisionner pour {{ $product->name }}" placeholder="Quantité">
                                            <small class="d-block text-muted">Après transfert : <span data-partner-next>{{ number_format($partnerQty, 0, ',', ' ') }}</span> en réserve</small>
                                            <button class="btn btn-success btn-sm mt-2" type="submit">Approvisionner</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Rendre disponible dans ce pays d’abord</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <input form="partner-prices-form" type="hidden" name="available[{{ $product->id }}]" value="0">
                                <label class="mb-0"><input form="partner-prices-form" type="checkbox" name="available[{{ $product->id }}]" value="1" @checked(old('available.'.$product->id, $countryPrice?->is_available ?? false))> Actif</label>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ ($showPartnerStock ? 5 : 2) + ($canReplenishPartnerStock ? 1 : 0) + ($isAdmin ? 1 : 0) + 1 }}" class="text-center text-muted py-4">Aucun produit.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
            <div class="card-footer text-right"><button class="btn btn-danger" form="partner-prices-form" type="submit">{{ $isPartner ? 'Enregistrer les réglages' : ($isAdmin ? 'Enregistrer les prix et disponibilités' : 'Enregistrer les disponibilités') }}</button></div>
        </div>
</div>
@if ($canReplenishPartnerStock)
<script>
document.querySelectorAll('.partner-replenish-quantity').forEach((input) => {
    const row = input.closest('tr');
    const partnerNext = row.querySelector('[data-partner-next]');
    const format = (value) => new Intl.NumberFormat('fr-FR').format(value);
    const update = () => {
        const requested = Math.max(0, Number.parseInt(input.value || '0', 10) || 0);
        partnerNext.textContent = format((Number(input.dataset.partner) || 0) + requested);
    };
    input.addEventListener('input', update);
});
</script>
@endif
@if ($isAdmin)
<style>
.quantity-tier-editor summary{cursor:pointer;font-weight:600;color:#167d20}.quantity-tier-row{display:grid;grid-template-columns:minmax(90px,1fr) minmax(100px,1fr) auto;gap:.4rem;align-items:end;margin-top:.55rem}.quantity-tier-row label{font-size:.75rem;margin:0}.quantity-tier-row .form-control{min-width:0;padding:.35rem;height:36px}@media(max-width:767px){.quantity-tier-row{grid-template-columns:1fr 1fr}.quantity-tier-row button{grid-column:1/-1}}
</style>
<script>
document.querySelectorAll('[data-tier-list]').forEach((list) => {
    const productId = list.dataset.productId;
    let nextIndex = list.children.length;
    const addButton = list.closest('td').querySelector('[data-add-tier]');
    const makeField = (label, field, min, placeholder) => {
        const wrapper = document.createElement('label'); wrapper.textContent = label;
        const input = document.createElement('input'); input.type = 'number'; input.min = min; input.max = field === 'min_quantity' ? '500' : '999999999'; input.step = '1'; input.placeholder = placeholder;
        input.className = 'form-control'; input.setAttribute('form', 'partner-prices-form');
        input.name = `quantity_prices[${productId}][${nextIndex}][${field}]`; wrapper.append(input); return wrapper;
    };
    addButton.addEventListener('click', () => {
        nextIndex += 1;
        const row = document.createElement('div'); row.className = 'quantity-tier-row';
        row.append(makeField('À partir de', 'min_quantity', '2', '3'), makeField('Prix unitaire', 'unit_price', '1', '8000'));
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-outline-danger btn-sm'; remove.textContent = 'Retirer';
        remove.addEventListener('click', () => row.remove()); row.append(remove); list.append(row);
    });
    list.querySelectorAll('[data-remove-tier]').forEach((button) => button.addEventListener('click', () => button.closest('.quantity-tier-row').remove()));
});
</script>
@endif
@endsection
