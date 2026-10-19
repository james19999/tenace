@php($partnerCartItems = \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->content())
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-4">
        <div><span class="small text-uppercase text-danger font-weight-bold">PANIER PARTENAIRE · {{ $partnerCountry }}</span><h1 class="h2 mb-1">Préparer une commande</h1><p class="text-muted">Les prix du panier sont recalculés selon le catalogue de {{ $partnerCountry }}.</p></div>
        <a href="{{ route('product') }}" class="btn btn-outline-danger">← Retour au catalogue</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('partner.orders.from-cart') }}">
        @csrf
        <div class="row">
            <div class="col-lg-7 mb-4">
                <section class="card h-100"><div class="card-header font-weight-bold">Produits sélectionnés · {{ $partnerCartItems->count() }} référence(s)</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Produit</th><th>Prix {{ $partnerCountry }}</th><th>Quantité</th><th>Total</th><th></th></tr></thead><tbody>
                    @forelse($partnerCartItems as $item)
                        <tr><td><strong>{{ $item->name }}</strong></td><td>{{ number_format((float) $item->price, 0, ',', ' ') }} F</td><td><span class="d-inline-flex align-items-center" style="gap:8px"><button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="decrement('{{ $item->rowId }}')" @disabled($item->qty <= 1)>−</button><strong>{{ $item->qty }}</strong><button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="increment('{{ $item->rowId }}')">+</button></span></td><td>{{ number_format((float) $item->price * (int) $item->qty, 0, ',', ' ') }} F</td><td><button type="button" class="btn btn-sm btn-outline-danger" wire:click.prevent="destroy('{{ $item->rowId }}')" aria-label="Retirer {{ $item->name }}">Retirer</button></td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-5">Ton panier est vide. Ajoute des produits du catalogue de {{ $partnerCountry }}.</td></tr>@endforelse
                </tbody></table></div>
                    @if($partnerCartItems->isNotEmpty())<div class="card-footer text-right"><strong>Sous-total produits : {{ number_format((float) \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->subtotal(0, '.', ''), 0, ',', ' ') }} F</strong></div>@endif
                </section>
            </div>
            <div class="col-lg-5 mb-4">
                <section class="card"><div class="card-header font-weight-bold">Informations client et livraison</div><div class="card-body">
                    <div class="form-group"><label for="partner-customer-name">Nom du client</label><input class="form-control" id="partner-customer-name" name="customer_name" value="{{ old('customer_name') }}" maxlength="160" required></div>
                    <div class="form-group"><label for="partner-customer-phone">Téléphone</label><input class="form-control" id="partner-customer-phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50" required></div>
                    <div class="form-group"><label for="partner-customer-email">E-mail (facultatif)</label><input class="form-control" id="partner-customer-email" type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="160"></div>
                    <div class="form-group"><label for="partner-customer-address">Adresse / quartier / repère</label><input class="form-control" id="partner-customer-address" name="address" value="{{ old('address') }}" maxlength="180" required></div>
                    <div class="form-group"><label for="partner-city">Ville · {{ $partnerCountry }}</label><select class="form-control" id="partner-city" name="city" required><option value="">Choisir une ville</option>@foreach($partnerCities as $city)<option value="{{ $city }}" @selected(old('city') === $city)>{{ $city }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="partner-delivery-fee">Frais de livraison (FCFA)</label><input class="form-control" type="number" id="partner-delivery-fee" name="delivery_fee" min="0" step="1" value="{{ old('delivery_fee', 0) }}" required></div>
                    <div class="form-group"><label for="partner-payment-timing">Quand le client paiera-t-il ?</label><select class="form-control" id="partner-payment-timing" name="payment_timing" required><option value="on_delivery" @selected(old('payment_timing', 'on_delivery') === 'on_delivery')>À la livraison</option><option value="before_delivery" @selected(old('payment_timing') === 'before_delivery')>Avant la livraison</option></select></div>
                    <div class="form-group"><label for="partner-payment-method">Moyen de paiement</label><select class="form-control" id="partner-payment-method" name="payment_method" required><option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Espèces</option><option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option></select></div>
                    <div class="border-top pt-3 d-flex justify-content-between"><span>Sous-total produits</span><strong>{{ number_format((float) \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->subtotal(0, '.', ''), 0, ',', ' ') }} F</strong></div>
                    <button class="btn btn-danger btn-block mt-3" type="submit" @disabled($partnerCartItems->isEmpty())>Créer la commande</button>
                    <small class="d-block text-muted mt-2">Le client recevra un lien de suivi. Si le paiement est prévu avant livraison, il pourra y déclarer son paiement pour confirmation.</small>
                </div></section>
            </div>
        </div>
    </form>
</div>
