@php($partnerCartItems = \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->content())
@php($partnerSubtotal = (float) \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->subtotal(0, '.', ''))
@include('partners.partials.styles')
<div class="container-fluid partner-portal partner-cart-page" data-partner-cart data-subtotal="{{ $partnerSubtotal }}" data-configured-delivery="{{ $partnerDeliveryFeeTotal }}">
    <header class="partner-cart-heading">
        <div>
            <div class="partner-cart-eyebrow">ESPACE PARTENAIRE <span></span> {{ $partnerCountry }}</div>
            <h1>Préparer une commande</h1>
            <p>Vérifie les produits, renseigne le client et confirme le montant à payer.</p>
        </div>
        <nav class="partner-cart-heading-actions"><a href="{{ route('partner.orders.index') }}" class="partner-cart-orders-link">Mes commandes</a><a href="{{ route('product') }}" class="partner-cart-back"><span aria-hidden="true">←</span> Retour au catalogue</a></nav>
    </header>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('partner.orders.from-cart') }}" class="partner-cart-layout">
        @csrf
        <main class="partner-cart-main">
            <section class="partner-cart-card">
                <div class="partner-cart-section-head">
                    <div><span class="partner-cart-step">01</span><div><h2>Produits sélectionnés</h2><p>{{ $partnerCartItems->count() }} référence(s) · Prix du catalogue {{ $partnerCountry }}</p></div></div>
                    <a href="{{ route('product') }}">Ajouter des produits</a>
                </div>

                @if ($partnerCartItems->isEmpty())
                    <div class="partner-cart-empty"><span class="material-icons" aria-hidden="true">shopping_bag</span><strong>Ton panier est vide</strong><p>Ajoute des produits du catalogue de {{ $partnerCountry }} pour préparer une commande.</p><a href="{{ route('product') }}">Parcourir le catalogue</a></div>
                @else
                    <div class="partner-cart-item-list">
                        @foreach($partnerCartItems as $item)
                            <article class="partner-cart-item">
                                <div class="partner-cart-product"><span class="partner-cart-product-icon material-icons" aria-hidden="true">inventory_2</span><div><strong>{{ $item->name }}</strong><small>{{ number_format((float) $item->price, 0, ',', ' ') }} F l’unité</small></div></div>
                                <div class="partner-cart-quantity" aria-label="Quantité de {{ $item->name }}">
                                    <button type="button" wire:click.prevent="decrement('{{ $item->rowId }}')" @disabled($item->qty <= 1) aria-label="Retirer une unité">−</button>
                                    <span>{{ $item->qty }}</span>
                                    <button type="button" wire:click.prevent="increment('{{ $item->rowId }}')" aria-label="Ajouter une unité">+</button>
                                </div>
                                <div class="partner-cart-line-total"><small>Total produits</small><strong>{{ number_format((float) $item->price * (int) $item->qty, 0, ',', ' ') }} F</strong></div>
                                <button type="button" class="partner-cart-remove" wire:click.prevent="destroy('{{ $item->rowId }}')" aria-label="Retirer {{ $item->name }}"><span class="material-icons" aria-hidden="true">delete_outline</span><span>Retirer</span></button>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="partner-cart-card partner-cart-customer">
                <div class="partner-cart-section-head">
                    <div><span class="partner-cart-step">02</span><div><h2>Client et livraison</h2><p>Ces informations serviront à organiser la remise de la commande.</p></div></div>
                </div>
                <div class="partner-cart-fields">
                    <label class="partner-cart-anonymous" for="partner-customer-anonymous">
                        <input type="checkbox" id="partner-customer-anonymous" name="customer_anonymous" value="1" @checked(old('customer_anonymous'))>
                        <span><strong>Créer une fiche anonyme</strong><small>Le nom et l’e-mail ne seront pas enregistrés. Le téléphone et l’adresse restent nécessaires pour la livraison.</small></span>
                    </label>
                    <div class="partner-cart-field" id="partner-customer-name-field"><label for="partner-customer-name">Nom du client</label><input class="form-control" id="partner-customer-name" name="customer_name" value="{{ old('customer_name') }}" maxlength="160" required autocomplete="name"></div>
                    <div class="partner-cart-field"><label for="partner-customer-phone">Téléphone</label><input class="form-control" id="partner-customer-phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50" required autocomplete="tel"></div>
                    <div class="partner-cart-field" id="partner-customer-email-field"><label for="partner-customer-email">E-mail <span>(facultatif)</span></label><input class="form-control" id="partner-customer-email" type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="160" autocomplete="email"></div>
                    <div class="partner-cart-field partner-cart-field-wide"><label for="partner-customer-address">Adresse, quartier ou repère</label><input class="form-control" id="partner-customer-address" name="address" value="{{ old('address') }}" maxlength="180" required autocomplete="street-address"></div>
                    <div class="partner-cart-field"><label for="partner-city">Ville · {{ $partnerCountry }}</label><select class="form-control" id="partner-city" name="city" required><option value="">Choisir une ville</option>@foreach($partnerCities as $city)<option value="{{ $city }}" @selected(old('city') === $city)>{{ $city }}</option>@endforeach</select></div>
                    <div class="partner-cart-field"><label for="partner-payment-timing">Moment du paiement</label><select class="form-control" id="partner-payment-timing" name="payment_timing" required><option value="on_delivery" @selected(old('payment_timing', 'on_delivery') === 'on_delivery')>À la livraison</option><option value="before_delivery" @selected(old('payment_timing') === 'before_delivery')>Avant la livraison</option></select></div>
                    <div class="partner-cart-field"><label for="partner-payment-method">Moyen de paiement</label><select class="form-control" id="partner-payment-method" name="payment_method" required><option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Espèces</option><option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option></select></div>
                </div>
            </section>
        </main>

        <aside class="partner-cart-sidebar">
            <section class="partner-cart-card partner-cart-summary">
                <div class="partner-cart-section-head"><div><span class="partner-cart-step">03</span><div><h2>Récapitulatif</h2><p>Montant final communiqué au client.</p></div></div></div>
                <div class="partner-cart-summary-body">
                    <div class="partner-cart-summary-line"><span>Sous-total produits</span><strong>{{ number_format($partnerSubtotal, 0, ',', ' ') }} F</strong></div>
                    <div class="partner-cart-field partner-cart-delivery-field"><label for="partner-delivery-fee">Frais de livraison · FCFA</label><div class="partner-cart-money-input"><input class="form-control" type="number" id="partner-delivery-fee" name="delivery_fee" min="0" step="1" value="{{ old('delivery_fee', $partnerDeliveryFeeTotal) }}" required><span>F</span></div><small>Renseigne les frais convenus avec le client pour cette commande.</small></div>
                    <div class="partner-cart-summary-line partner-cart-total"><span>Total client</span><strong id="partner-order-total">{{ number_format($partnerSubtotal + (int) old('delivery_fee', $partnerDeliveryFeeTotal), 0, ',', ' ') }} F</strong></div>
                    <button class="partner-cart-submit" type="submit" @disabled($partnerCartItems->isEmpty())><span class="material-icons" aria-hidden="true">receipt_long</span>Créer la commande</button>
                    <p class="partner-cart-footnote">Le client recevra un lien de suivi et verra ce montant avant de payer.</p>
                </div>
            </section>
        </aside>
    </form>
</div>

<style>
.partner-cart-page{--pc-ink:#202938;--pc-muted:#748093;--pc-line:#e4e8ee;--pc-paper:#fff;--pc-wash:#f6f7f9;--pc-primary:var(--app-primary,#8d1c1c);--pc-primary-contrast:var(--app-primary-contrast,#fff);max-width:1560px;margin-inline:auto;padding:28px clamp(14px,2vw,30px) 48px!important;color:var(--pc-ink)}
.partner-cart-page *{box-sizing:border-box}.partner-cart-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:25px 28px;margin-bottom:20px;background:linear-gradient(115deg,var(--pc-paper),var(--pc-paper) 68%,color-mix(in srgb,var(--pc-primary) 6%,var(--pc-paper)));border:1px solid var(--pc-line);border-left:4px solid var(--pc-primary)}
.partner-cart-heading-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.partner-cart-orders-link{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 13px;border:1px solid var(--pc-line);color:var(--pc-primary);font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap}.partner-cart-orders-link:hover{background:var(--pc-primary);color:var(--pc-primary-contrast);text-decoration:none}
.partner-cart-eyebrow{display:flex;align-items:center;gap:9px;color:var(--pc-primary);font-size:11px;font-weight:850;letter-spacing:.13em}.partner-cart-eyebrow span{width:4px;height:4px;background:currentColor;border-radius:50%}.partner-cart-heading h1{margin:8px 0 4px;color:var(--pc-ink);font-size:clamp(26px,3vw,36px);font-weight:820;letter-spacing:-.035em}.partner-cart-heading p{margin:0;color:var(--pc-muted);font-size:14px}.partner-cart-back{display:inline-flex;align-items:center;gap:9px;min-height:42px;padding:9px 13px;color:var(--pc-primary);border:1px solid color-mix(in srgb,var(--pc-primary) 28%,var(--pc-line));font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap}.partner-cart-back:hover{background:var(--pc-primary);color:var(--pc-primary-contrast);text-decoration:none}
.partner-cart-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(310px,.78fr);align-items:start;gap:20px}.partner-cart-main{display:grid;gap:18px;min-width:0}.partner-cart-sidebar{min-width:0;position:sticky;top:18px}.partner-cart-card{min-width:0;overflow:hidden;background:var(--pc-paper);border:1px solid var(--pc-line)}.partner-cart-section-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 21px;border-bottom:1px solid var(--pc-line)}.partner-cart-section-head>div{display:flex;align-items:center;gap:12px;min-width:0}.partner-cart-step{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;background:color-mix(in srgb,var(--pc-primary) 9%,var(--pc-paper));color:var(--pc-primary);font-size:12px;font-weight:850}.partner-cart-section-head h2{margin:0;color:var(--pc-ink);font-size:16px;font-weight:800}.partner-cart-section-head p{margin:3px 0 0;color:var(--pc-muted);font-size:12px}.partner-cart-section-head>a{color:var(--pc-primary);font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap}.partner-cart-item-list{padding:0 20px}.partner-cart-item{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(92px,auto) auto;align-items:center;gap:16px;padding:17px 0;border-bottom:1px solid var(--pc-line)}.partner-cart-item:last-child{border-bottom:0}.partner-cart-product{display:flex;align-items:center;gap:12px;min-width:0}.partner-cart-product-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;background:var(--pc-wash);color:var(--pc-primary);font-size:21px}.partner-cart-product>div{min-width:0}.partner-cart-product strong{display:block;color:var(--pc-ink);font-size:14px;overflow-wrap:anywhere}.partner-cart-product small{display:block;margin-top:4px;color:var(--pc-muted);font-size:11px;line-height:1.4}.partner-cart-quantity{display:inline-flex;align-items:center;border:1px solid var(--pc-line)}.partner-cart-quantity button{display:grid;place-items:center;width:34px;height:34px;border:0;background:var(--pc-wash);color:var(--pc-ink);font-size:18px;cursor:pointer}.partner-cart-quantity button:disabled{opacity:.4;cursor:not-allowed}.partner-cart-quantity span{min-width:34px;text-align:center;color:var(--pc-ink);font-size:13px;font-weight:800}.partner-cart-line-total{text-align:right;white-space:nowrap}.partner-cart-line-total small{display:block;color:var(--pc-muted);font-size:10px}.partner-cart-line-total strong{color:var(--pc-ink);font-size:13px}.partner-cart-remove{display:inline-flex;align-items:center;gap:4px;padding:7px 8px;border:1px solid #f0d7d7;background:transparent;color:#a33030;font-size:11px;font-weight:700;cursor:pointer}.partner-cart-remove .material-icons{font-size:16px}.partner-cart-empty{display:grid;justify-items:center;gap:8px;padding:42px 20px;text-align:center}.partner-cart-empty>.material-icons{display:grid;place-items:center;width:52px;height:52px;background:var(--pc-wash);color:var(--pc-primary);font-size:25px}.partner-cart-empty strong{color:var(--pc-ink);font-size:15px}.partner-cart-empty p{max-width:360px;margin:0;color:var(--pc-muted);font-size:13px}.partner-cart-empty>a{margin-top:5px;padding:10px 14px;background:var(--pc-primary);color:var(--pc-primary-contrast);font-size:12px;font-weight:750;text-decoration:none}
.partner-cart-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px;padding:20px}.partner-cart-field{min-width:0}.partner-cart-field-wide{grid-column:1/-1}.partner-cart-field[hidden]{display:none!important}.partner-cart-field>label{display:block;margin:0 0 6px;color:var(--pc-ink);font-size:12px;font-weight:750}.partner-cart-field>label span{color:var(--pc-muted);font-weight:500}.partner-cart-field .form-control{width:100%;height:44px;min-height:44px;padding:9px 11px;border:1px solid var(--pc-line);border-radius:0;background:var(--pc-paper);color:var(--pc-ink);font-size:13px;box-shadow:none}.partner-cart-field .form-control:focus{border-color:var(--pc-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--pc-primary) 12%,transparent)}.partner-cart-anonymous{display:flex;align-items:flex-start;gap:10px;grid-column:1/-1;padding:13px;border:1px solid var(--pc-line);background:var(--pc-wash);cursor:pointer}.partner-cart-anonymous input{flex:0 0 auto;width:17px;height:17px;margin:2px 0 0;accent-color:var(--pc-primary)}.partner-cart-anonymous strong,.partner-cart-anonymous small{display:block}.partner-cart-anonymous strong{color:var(--pc-ink);font-size:12px}.partner-cart-anonymous small{margin-top:3px;color:var(--pc-muted);font-size:11px;line-height:1.4}.partner-cart-summary-body{padding:20px}.partner-cart-summary-line{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:9px 0;color:var(--pc-muted);font-size:13px}.partner-cart-summary-line strong{color:var(--pc-ink);white-space:nowrap}.partner-cart-delivery-field{padding:13px 0 15px;border-bottom:1px solid var(--pc-line)}.partner-cart-delivery-field>small{display:block;margin-top:6px;color:var(--pc-muted);font-size:10px;line-height:1.45}.partner-cart-money-input{display:flex;align-items:center;border:1px solid var(--pc-line)}.partner-cart-money-input .form-control{height:44px;min-height:44px;border:0;border-radius:0;box-shadow:none}.partner-cart-money-input>span{padding:0 11px;color:var(--pc-muted);font-size:12px;font-weight:750}.partner-cart-total{align-items:center;padding:17px 0 5px;color:var(--pc-ink);font-size:15px;font-weight:800}.partner-cart-total strong{color:var(--pc-primary);font-size:21px}.partner-cart-submit{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;min-height:48px;margin-top:16px;padding:11px 16px;border:1px solid var(--pc-primary);background:var(--pc-primary);color:var(--pc-primary-contrast);font-size:13px;font-weight:800;cursor:pointer}.partner-cart-submit:disabled{opacity:.45;cursor:not-allowed}.partner-cart-submit .material-icons{font-size:18px}.partner-cart-footnote{margin:10px 0 0;color:var(--pc-muted);font-size:10px;line-height:1.5;text-align:center}.partner-cart-page .alert{border-radius:0}
html[data-app-theme="dark"] .partner-cart-page{--pc-ink:var(--app-text);--pc-muted:var(--app-muted,#a5adba);--pc-line:var(--app-border);--pc-paper:var(--app-surface);--pc-wash:#252b34;color:var(--pc-ink)}html[data-app-theme="dark"] .partner-cart-heading{background:linear-gradient(115deg,var(--pc-paper),var(--pc-paper) 70%,color-mix(in srgb,var(--pc-primary) 15%,var(--pc-paper)))}html[data-app-theme="dark"] .partner-cart-page .form-control{background:#191e25;color:var(--pc-ink);border-color:var(--pc-line)}html[data-app-theme="dark"] .partner-cart-money-input{border-color:var(--pc-line)}
@media(max-width:1050px){.partner-cart-layout{grid-template-columns:minmax(0,1.25fr) minmax(290px,.8fr);gap:14px}.partner-cart-item{grid-template-columns:minmax(0,1fr) auto auto;gap:11px}.partner-cart-line-total{grid-column:2;text-align:right}.partner-cart-remove{grid-column:3;grid-row:1/3}}
@media(max-width:767.98px){.partner-cart-page{padding:14px 12px 28px!important}.partner-cart-page>.partner-cart-heading{display:flex!important;position:relative!important;height:auto!important;min-height:0!important;overflow:visible!important;align-items:stretch;flex-direction:column;gap:12px;padding:18px 16px;margin-bottom:14px}.partner-cart-heading>div{display:block;position:static;min-width:0;width:100%}.partner-cart-heading h1{display:block;position:static!important;height:auto!important;margin:10px 0 8px!important;font-size:25px!important;line-height:1.15!important;letter-spacing:-.025em;white-space:normal!important;overflow-wrap:anywhere;transform:none!important}.partner-cart-heading p{display:block;position:static!important;margin:0!important;font-size:12px;line-height:1.5!important;white-space:normal!important}.partner-cart-heading-actions{display:grid;grid-template-columns:1fr 1fr}.partner-cart-back,.partner-cart-orders-link{width:100%;justify-content:center;white-space:normal;text-align:center;font-size:11px}.partner-cart-layout{grid-template-columns:minmax(0,1fr);gap:14px}.partner-cart-main{gap:14px}.partner-cart-sidebar{position:static;order:2}.partner-cart-section-head{align-items:flex-start;flex-wrap:wrap;padding:14px}.partner-cart-section-head>div{flex:1 1 100%;width:100%}.partner-cart-section-head h2{font-size:14px}.partner-cart-section-head p{font-size:11px;line-height:1.45}.partner-cart-section-head>a{margin-left:48px;font-size:11px}.partner-cart-item-list{padding:0 14px}.partner-cart-item{grid-template-columns:minmax(0,1fr) auto;gap:9px 12px;padding:14px 0}.partner-cart-product{grid-column:1/-1}.partner-cart-quantity{grid-column:1;grid-row:2}.partner-cart-line-total{grid-column:2;grid-row:2;align-self:center}.partner-cart-remove{grid-column:2;grid-row:3;justify-self:end}.partner-cart-fields{gap:12px;padding:15px}.partner-cart-summary-body{padding:15px}.partner-cart-empty{padding:32px 16px}}
@media(max-width:420px){.partner-cart-fields{grid-template-columns:minmax(0,1fr)}.partner-cart-field-wide,.partner-cart-anonymous{grid-column:1}.partner-cart-section-head>div{gap:9px}.partner-cart-step{flex-basis:32px;width:32px;height:32px}.partner-cart-item{gap:8px 10px}.partner-cart-remove{min-height:36px}.partner-cart-total strong{font-size:19px}}
</style>
<style>
.partner-cart-page .partner-cart-empty>a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;color:#fff!important;background:var(--pc-primary)!important;font-size:12px;font-weight:750;text-decoration:none!important}
.partner-cart-page .partner-cart-empty>a:hover,.partner-cart-page .partner-cart-empty>a:focus-visible,.partner-cart-page .partner-cart-empty>a span{color:#fff!important}
</style>

<script>
(() => {
    const anonymous = document.getElementById('partner-customer-anonymous');
    const nameField = document.getElementById('partner-customer-name-field');
    const nameInput = document.getElementById('partner-customer-name');
    const emailField = document.getElementById('partner-customer-email-field');
    const emailInput = document.getElementById('partner-customer-email');
    if (anonymous && nameField && nameInput) {
        const updateNameField = () => {
            nameField.hidden = anonymous.checked;
            nameInput.required = !anonymous.checked;
            if (anonymous.checked) nameInput.value = '';
            if (emailField) emailField.hidden = anonymous.checked;
            if (emailInput && anonymous.checked) emailInput.value = '';
        };
        anonymous.addEventListener('change', updateNameField);
        updateNameField();
    }

    const subtotal = {{ $partnerSubtotal }};
    let manuallyEditedDelivery = false;
    let manuallyEditedDeliveryValue = null;
    const money = (amount) => new Intl.NumberFormat('fr-FR').format(amount) + ' F';
    const updateTotal = () => {
        const currentFeeInput = document.getElementById('partner-delivery-fee');
        const fee = Math.max(0, Number.parseInt(currentFeeInput?.value || '0', 10) || 0);
        const currentSubtotal = Number.parseFloat(document.querySelector('[data-partner-cart]')?.dataset.subtotal || subtotal) || 0;
        const currentTotal = document.getElementById('partner-order-total');
        if (currentTotal) currentTotal.textContent = money(currentSubtotal + fee);
    };
    document.addEventListener('input', (event) => {
        if (event.target?.id === 'partner-delivery-fee') {
            manuallyEditedDelivery = true;
            manuallyEditedDeliveryValue = event.target.value;
            updateTotal();
        }
    });
    updateTotal();
    const syncAfterCartChange = () => {
        const cartRoot = document.querySelector('[data-partner-cart]');
        const currentFeeInput = document.getElementById('partner-delivery-fee');
        if (!cartRoot || !currentFeeInput) return;
        if (manuallyEditedDelivery) {
            currentFeeInput.value = manuallyEditedDeliveryValue ?? currentFeeInput.value;
        } else {
            currentFeeInput.value = cartRoot.dataset.configuredDelivery || '0';
        }
        updateTotal();
    };
    const registerLivewireRefresh = () => {
        if (window.Livewire?.hook) {
            window.Livewire.hook('message.processed', syncAfterCartChange);
        }
    };
    if (window.Livewire?.hook) registerLivewireRefresh();
    else document.addEventListener('livewire:load', registerLivewireRefresh, { once: true });
})();
</script>
