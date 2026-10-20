<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#7e1615">
    <link rel="manifest" href="{{ $link ? route('public-order.manifest', $link->token) : route('public-order.app.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <title>{{ $link?->title ?: 'Catalogue TENACOS' }}</title>
    <style>
        :root{--red:#7e1615;--ink:#252a34;--muted:#687386;--line:#e5e8ee;--bg:#f6f7f9;--green:#16794b}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{max-width:1100px;margin:auto;padding:24px 18px 70px}.head{background:white;border-radius:0;padding:28px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 8px 28px #1720330b}.brand{color:var(--red);font-size:13px;font-weight:800;letter-spacing:.16em}.head h1{font-size:clamp(26px,4vw,40px);line-height:1.15;margin:8px 0}.head p{color:var(--muted);margin:0}.install{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--red);color:#fff;border-radius:0;padding:13px 17px;font:inherit;font-weight:700;white-space:nowrap;cursor:pointer}.install[hidden],.install-help[hidden]{display:none!important}.install-help{margin-top:10px;padding:11px 13px;background:#fdf5e8;border-left:4px solid #d49a30;border-radius:0;color:#73551b;font-size:14px}.controls,.panel{background:white;border:1px solid var(--line);border-radius:0;padding:20px;margin-top:20px}.controls{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field label{display:block;font-weight:700;font-size:14px;margin-bottom:7px}.field input,.field select,.field textarea{width:100%;min-height:46px;border:1px solid #d8dce5;background:white;padding:11px 12px;border-radius:0;font:inherit}.section-title{display:flex;justify-content:space-between;align-items:end;margin:26px 0 12px}.section-title h2{font-size:21px;margin:0}.section-title p{color:var(--muted);margin:3px 0 0}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.product{background:white;border:1px solid var(--line);border-radius:0;padding:14px;display:flex;flex-direction:column;min-width:0}.product img{width:100%;height:175px;object-fit:cover;background:#f0f1f3;border-radius:0;margin:12px 0 8px}.product h3{font-size:16px;line-height:1.35;margin:4px 0 8px;overflow-wrap:anywhere}.product p{color:var(--muted);font-size:13px;line-height:1.45;margin:0 0 10px}.price{font-size:19px;font-weight:800;color:var(--red);margin-top:auto}.product button,.checkout-button{display:flex;align-items:center;justify-content:center;width:100%;min-height:46px;border:0;background:var(--red);color:#fff;border-radius:0;padding:11px 12px;font:inherit;font-weight:750;margin-top:12px;cursor:pointer}.suggestions{margin-top:28px}.empty{color:var(--muted);padding:18px;border:1px solid var(--line);border-radius:0;margin-top:15px;background:#fff}.cart{background:#fff;border:1px solid var(--line);padding:22px;margin-top:22px}.cart h2{font-size:20px;margin:0 0 12px}.cart-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:14px;border-bottom:1px solid var(--line);padding:12px 0}.cart-product-name{min-width:0;overflow-wrap:anywhere;font-size:14px}.qty{display:flex;align-items:center;gap:9px}.qty button{display:grid;place-items:center;width:38px;height:38px;border:1px solid var(--line);background:#fff;border-radius:0;font-size:20px;line-height:1;font-weight:700;padding:0;cursor:pointer}.qty span{min-width:20px;text-align:center;font-weight:750}.line-total{min-width:90px;text-align:right;font-weight:800;white-space:nowrap}.cart-total{display:flex;justify-content:space-between;gap:12px;padding:16px 0;font-size:16px;font-weight:800}.notice{background:#fdf5e8;color:#73551b;border-left:4px solid #d49a30;padding:12px 14px;border-radius:0;font-size:14px}.checkout{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px}.checkout .wide{grid-column:1/-1}.checkout .field{min-width:0}.customer-summary{display:flex;align-items:center;gap:5px 8px;flex-wrap:wrap;padding:12px 14px;background:#f7f8fa;border-left:3px solid var(--red);font-size:13px}.customer-summary span{color:var(--muted)}.checkout .field small{display:block;margin-top:5px;color:var(--muted);font-size:12px}.checkout-button{font-size:15px}.errors{background:#fff0f0;color:#8c1e1b;padding:12px;border-radius:0;margin-top:16px}.status{color:var(--muted);text-align:center;padding:20px}.hidden-inputs{display:none}
        @media(max-width:760px){.wrap{padding:12px 12px 36px}.head{padding:18px;align-items:stretch;flex-direction:column;gap:15px}.head h1{font-size:27px;overflow-wrap:anywhere}.head p{font-size:14px}.install-area{width:100%;justify-content:flex-start!important}.install-area>a,.install-area>.install{flex:1;min-width:0;white-space:normal;text-align:center;font-size:13px;padding:11px 9px}.install-help{width:100%;margin-top:0}.controls{grid-template-columns:1fr;gap:12px;padding:15px;margin-top:12px}.field label{font-size:13px}.field input,.field select,.field textarea{min-height:48px;font-size:16px}.section-title{align-items:flex-start;margin:22px 0 10px}.section-title h2{font-size:19px}.section-title p{font-size:13px}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.product{padding:10px}.product img{height:clamp(105px,32vw,150px);margin:8px 0 7px}.product h3{font-size:14px}.product p{font-size:12px}.price{font-size:17px}.product button{min-height:43px;padding:9px 7px;font-size:12px;line-height:1.25}.cart{padding:16px;margin-top:17px}.cart h2{font-size:19px}.cart-row{grid-template-columns:minmax(0,1fr) auto;grid-template-areas:"name total" "qty total";gap:5px 9px;padding:12px 0}.cart-product-name{grid-area:name}.cart-row .qty{grid-area:qty}.line-total{grid-area:total;min-width:0;align-self:center;font-size:14px}.qty button{width:40px;height:40px}.cart-total{font-size:15px;padding:14px 0}.notice{font-size:13px;padding:11px 12px}.checkout{grid-template-columns:1fr;gap:12px;margin-top:14px}.checkout .wide{grid-column:auto}.checkout-button{min-height:49px;font-size:14px}.empty{font-size:14px}.status{padding:16px 8px;font-size:14px}}
        @media(max-width:390px){.wrap{padding-left:10px;padding-right:10px}.head{padding:15px}.head h1{font-size:24px}.grid{grid-template-columns:1fr;gap:10px}.product{display:grid;grid-template-columns:82px minmax(0,1fr);grid-template-rows:auto auto 1fr auto;column-gap:12px;align-items:start}.product img{grid-column:1;grid-row:1/5;width:82px;height:82px;margin:0;object-fit:cover}.product h3{grid-column:2;margin:1px 0 4px}.product p{grid-column:2;margin:0 0 5px}.product .price{grid-column:2;align-self:end}.product button{grid-column:1/-1;margin-top:10px}.install-area{gap:7px!important}.install-area>a,.install-area>.install{font-size:12px;padding:10px 6px}}
    </style>
</head>
<body>
<main class="wrap">
    <header class="head">
        <div><span class="brand">TENACOS · BOUTIQUE</span><h1>{{ $link?->title ?: 'Catalogue TENACOS' }}</h1><p>Choisis ton pays pour voir tous les produits et les prix disponibles près de chez toi.</p></div>
        <div class="install-area" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end">
            <a href="{{ route('customer-portal.index') }}" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 14px;border:1px solid var(--line);background:#fff;color:var(--ink);text-decoration:none;font-weight:700">Mon espace client</a>
            <button type="button" id="install-app" class="install" hidden>Installer l’application</button>
            <div id="install-help" class="install-help" role="status" hidden></div>
        </div>
    </header>

    <section class="controls" aria-label="Destination">
        <div class="field"><label for="country">Pays</label><select id="country"><option value="">Choisir un pays</option>@foreach ($countries as $code => $country)<option value="{{ $code }}">{{ $country['label'] }}</option>@endforeach</select></div>
        <div class="field"><label for="city">Ville</label><select id="city" disabled><option value="">Choisis d’abord le pays</option></select></div>
    </section>

    @if ($errors->any())<div class="errors"><strong>Vérifie ta commande :</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div id="catalog-status" class="status">Sélectionne ton pays pour afficher le catalogue.</div>
    <section id="featured-section" hidden><div class="section-title"><div><h2>Ta sélection</h2><p>Les produits choisis pour ce lien.</p></div></div><div id="featured-products" class="grid"></div></section>
    <section id="suggestion-section" class="suggestions" hidden><div class="section-title"><div><h2>À découvrir aussi</h2><p>D’autres produits disponibles dans ton pays.</p></div></div><div id="suggestion-products" class="grid"></div></section>

    <section class="cart" id="cart-section" hidden>
        <h2>Ta commande</h2>
        <div id="cart-empty" class="empty">Ajoute les produits qui t’intéressent.</div>
        <div id="cart-items"></div>
        <div class="cart-total"><span>Sous-total produits</span><span id="cart-subtotal">0 F</span></div>
        <div class="cart-total"><span>Frais de livraison</span><span id="cart-delivery">0 F</span></div>
        <div class="cart-total"><span>Total à payer</span><span id="cart-total">0 F</span></div>

        <form id="order-form" method="POST" action="{{ $link ? route('public-order.store', $link->token) : route('public-order.app.store') }}" class="mt-3">
            @csrf
            @php
                $selectedSavedProfile = $savedProfiles->firstWhere('id', (int) old('customer_profile_id', $savedProfiles->first()?->id)) ?? $savedProfiles->first();
            @endphp
            <input type="hidden" name="country_code" id="order-country">
            <input type="hidden" name="city" id="order-city">
            <div id="order-items" class="hidden-inputs"></div>
            <div class="checkout">
                @if ($savedProfiles->isNotEmpty())
                    <div class="field wide"><label for="customer-profile">Commander au nom de</label><select id="customer-profile" name="customer_profile_id" required>@foreach($savedProfiles as $profile)<option value="{{ $profile->id }}" data-name="{{ $profile->name }}" data-phone="{{ $profile->phone }}" data-address="{{ $profile->checkout_address }}" @selected((string) old('customer_profile_id', $selectedSavedProfile->id) === (string) $profile->id)>{{ $profile->name }}{{ $profile->phone ? ' · '.$profile->phone : '' }}</option>@endforeach</select></div>
                    <div class="customer-summary wide">Commande au nom de <strong id="selected-profile-name">{{ $selectedSavedProfile->name }}</strong><span id="selected-profile-phone">{{ $selectedSavedProfile->phone }}</span></div>
                    <div class="field wide"><label for="customer-address">Adresse de livraison</label><input id="customer-address" name="address" maxlength="180" value="{{ old('address', $selectedSavedProfile->checkout_address) }}" placeholder="Rue, quartier, repère" autocomplete="street-address" required><small>Tu peux modifier l’adresse pour cette commande.</small></div>
                @elseif ($savedCustomer)
                    <div class="customer-summary wide">Commande au nom de <strong>{{ $savedCustomer->name }}</strong><span>{{ $savedCustomer->phone }}</span></div>
                    <div class="field wide"><label for="customer-address">Adresse de livraison</label><input id="customer-address" name="address" maxlength="180" value="{{ old('address', $savedAddress) }}" placeholder="Rue, quartier, repère" autocomplete="street-address" required><small>Tu peux modifier l’adresse pour cette commande.</small></div>
                @else
                    <div class="field"><label for="customer-name">Nom complet</label><input id="customer-name" name="name" maxlength="160" value="{{ old('name') }}" autocomplete="name" required></div>
                    <div class="field"><label for="customer-phone">Téléphone</label><input id="customer-phone" name="phone" type="tel" maxlength="50" value="{{ old('phone') }}" autocomplete="tel" required></div>
                    <div class="field"><label for="customer-email">E-mail (facultatif)</label><input id="customer-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"></div>
                    <div class="field"><label for="customer-address">Adresse de livraison</label><input id="customer-address" name="address" maxlength="180" value="{{ old('address') }}" placeholder="Rue, quartier, repère" autocomplete="street-address" required></div>
                @endif
                <div class="field"><label for="payment-timing">Quand souhaites-tu payer ?</label><select id="payment-timing" name="payment_timing" required><option value="on_delivery">À la livraison</option><option value="before_delivery">Avant la livraison</option></select></div>
                <div class="field"><label for="payment-method">Mode de paiement prévu</label><select id="payment-method" name="payment_method" required><option value="mobile_money">Mobile money</option><option value="cash">Espèces</option></select></div>
                <div class="field wide"><button class="checkout-button" type="submit">Envoyer la demande au partenaire</button></div>
            </div>
        </form>
    </section>
</main>
<script>
(() => {
    const country = document.getElementById('country');
    const city = document.getElementById('city');
    const status = document.getElementById('catalog-status');
    const featuredSection = document.getElementById('featured-section');
    const suggestionsSection = document.getElementById('suggestion-section');
    const featuredProducts = document.getElementById('featured-products');
    const suggestionProducts = document.getElementById('suggestion-products');
    const cartSection = document.getElementById('cart-section');
    const cartItems = document.getElementById('cart-items');
    const cartEmpty = document.getElementById('cart-empty');
    const subtotalNode = document.getElementById('cart-subtotal');
    const deliveryNode = document.getElementById('cart-delivery');
    const totalNode = document.getElementById('cart-total');
    const basket = new Map();
    let catalog = [];
    let deliveryMode = 'priority';
    let orderDeliveryFee = 0;
    const money = (amount) => new Intl.NumberFormat('fr-FR').format(amount) + ' F';

    function renderBasket() {
        cartItems.replaceChildren();
        let subtotal = 0;
        let delivery = 0;
        basket.forEach((entry, id) => {
            subtotal += entry.product.price * entry.quantity;
            if (entry.product.delivery_fee_override) delivery += entry.product.delivery_fee * entry.quantity;
            const row = document.createElement('div');
            row.className = 'cart-row';
            const name = document.createElement('strong'); name.className = 'cart-product-name'; name.textContent = entry.product.name;
            const controls = document.createElement('div'); controls.className = 'qty';
            const minus = document.createElement('button'); minus.type = 'button'; minus.textContent = '−'; minus.setAttribute('aria-label', 'Retirer une unité');
            minus.addEventListener('click', () => changeQuantity(id, -1));
            const count = document.createElement('span'); count.textContent = entry.quantity;
            const plus = document.createElement('button'); plus.type = 'button'; plus.textContent = '+'; plus.setAttribute('aria-label', 'Ajouter une unité');
            plus.addEventListener('click', () => changeQuantity(id, 1));
            controls.append(minus, count, plus);
            const line = document.createElement('span'); line.className = 'line-total'; line.textContent = money((entry.product.price + (entry.product.delivery_fee_override ? entry.product.delivery_fee : 0)) * entry.quantity);
            row.append(name, controls, line); cartItems.append(row);
        });
        if (basket.size > 0 && Array.from(basket.values()).some((entry) => !entry.product.delivery_fee_override)) delivery += orderDeliveryFee;
        cartEmpty.hidden = basket.size > 0;
        cartSection.hidden = !country.value;
        subtotalNode.textContent = money(subtotal);
        deliveryNode.textContent = money(delivery);
        totalNode.textContent = money(subtotal + delivery);
    }

    function changeQuantity(id, delta) {
        const entry = basket.get(Number(id));
        if (!entry) return;
        entry.quantity += delta;
        if (entry.quantity <= 0) basket.delete(Number(id));
        renderBasket();
    }

    function addProduct(product) {
        const entry = basket.get(product.id);
        if (entry) entry.quantity += 1;
        else basket.set(product.id, { product, quantity: 1 });
        renderBasket();
        cartSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function productCard(product) {
        const card = document.createElement('article'); card.className = 'product';
        if (product.image) { const image = document.createElement('img'); image.src = product.image; image.alt = product.name; image.loading = 'lazy'; card.append(image); }
        const heading = document.createElement('h3'); heading.textContent = product.name; card.append(heading);
        if (product.description) { const description = document.createElement('p'); description.textContent = product.description.slice(0, 100); card.append(description); }
        const price = document.createElement('div'); price.className = 'price'; price.textContent = money(product.price); card.append(price);
        const delivery = document.createElement('p');
        delivery.textContent = product.delivery_fee_override
            ? 'Frais de livraison : ' + money(product.delivery_fee)
            : 'Frais de livraison : ' + money(orderDeliveryFee);
        card.append(delivery);
        const button = document.createElement('button'); button.type = 'button'; button.textContent = 'Ajouter au panier'; button.addEventListener('click', () => addProduct(product)); card.append(button);
        return card;
    }

    country.addEventListener('change', async () => {
        basket.clear(); catalog = []; deliveryMode = 'priority'; orderDeliveryFee = 0; featuredProducts.replaceChildren(); suggestionProducts.replaceChildren();
        featuredSection.hidden = true; suggestionsSection.hidden = true; renderBasket();
        if (country.value) localStorage.setItem('tenacos_public_country', country.value);
        else localStorage.removeItem('tenacos_public_country');
        city.replaceChildren();
        if (!country.value) { city.disabled = true; status.textContent = 'Sélectionne ton pays pour afficher le catalogue.'; return; }
        status.textContent = 'Chargement des prix et des produits…';
        city.disabled = true;
        try {
            const response = await fetch(@json($link ? route('public-order.catalog', $link->token) : route('public-order.app.catalog')) + '?country=' + encodeURIComponent(country.value), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Le catalogue est indisponible pour ce pays.');
            data.cities.forEach((name, index) => { const option = document.createElement('option'); option.value = name; option.textContent = name; if (index === 0) option.selected = true; city.append(option); });
            city.disabled = false;
            catalog = data.products;
            deliveryMode = data.delivery?.mode || 'per_product';
            orderDeliveryFee = Number(data.delivery?.order_fee || 0);
            const featured = catalog.filter((item) => item.featured);
            const suggestions = catalog.filter((item) => !item.featured);
            featured.forEach((item) => featuredProducts.append(productCard(item)));
            suggestions.forEach((item) => suggestionProducts.append(productCard(item)));
            featuredSection.hidden = featured.length === 0;
            suggestionsSection.hidden = suggestions.length === 0;
            status.hidden = catalog.length > 0;
            if (catalog.length === 0) status.textContent = 'Aucun produit avec un prix actif dans ce pays pour le moment.';
        } catch (error) {
            status.hidden = false; status.textContent = error.message;
        }
    });

    const savedCountry = localStorage.getItem('tenacos_public_country');
    if (savedCountry && Array.from(country.options).some((option) => option.value === savedCountry)) {
        country.value = savedCountry;
        country.dispatchEvent(new Event('change'));
    }

    const customerProfile = document.getElementById('customer-profile');
    if (customerProfile) {
        customerProfile.addEventListener('change', () => {
            const selected = customerProfile.selectedOptions[0];
            document.getElementById('selected-profile-name').textContent = selected.dataset.name || '';
            document.getElementById('selected-profile-phone').textContent = selected.dataset.phone || '';
            document.getElementById('customer-address').value = selected.dataset.address || '';
        });
    }

    document.getElementById('order-form').addEventListener('submit', (event) => {
        if (!country.value || !city.value || basket.size === 0) { event.preventDefault(); alert('Choisis un pays, une ville et au moins un produit.'); return; }
        document.getElementById('order-country').value = country.value;
        document.getElementById('order-city').value = city.value;
        const inputs = document.getElementById('order-items'); inputs.replaceChildren();
        let index = 0;
        basket.forEach((entry, id) => {
            [['product_id', id], ['quantity', entry.quantity]].forEach(([field, value]) => {
                const input = document.createElement('input'); input.type = 'hidden'; input.name = `items[${index}][${field}]`; input.value = value; inputs.append(input);
            });
            index += 1;
        });
    });

    let installPrompt = null;
    const installButton = document.getElementById('install-app');
    const installHelp = document.getElementById('install-help');
    const displayMode = window.matchMedia('(display-mode: standalone)');
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isSafari = /^((?!chrome|chromium|android).)*safari/i.test(navigator.userAgent);
    let appInstalled = displayMode.matches || navigator.standalone === true;
    let installPending = false;
    let installPendingTimer = null;
    const installedPwaId = @json(url('/commander'));

    function hideInstallControls() {
        installButton.hidden = true;
        installHelp.hidden = true;
    }

    function markAppInstalled() {
        appInstalled = true;
        installPending = false;
        window.clearTimeout(installPendingTimer);
        installPrompt = null;
        hideInstallControls();
    }

    if (appInstalled) {
        hideInstallControls();
    } else if (!window.isSecureContext) {
        installHelp.textContent = 'Installation indisponible sur cette adresse locale HTTP. Ouvre cette page avec une adresse HTTPS pour installer TENACOS.';
        installHelp.hidden = false;
    } else if (isIOS && isSafari) {
        installButton.hidden = false;
        installButton.textContent = 'Comment installer ?';
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        if (appInstalled || !window.isSecureContext) return;
        event.preventDefault();
        installPrompt = event;
        installButton.textContent = 'Installer l’application';
        installButton.hidden = false;
    });

    window.addEventListener('appinstalled', markAppInstalled);
    if (displayMode.addEventListener) {
        displayMode.addEventListener('change', (event) => { if (event.matches) markAppInstalled(); });
    }
    window.addEventListener('pageshow', () => {
        if (displayMode.matches || navigator.standalone === true) markAppInstalled();
    });

    if (typeof navigator.getInstalledRelatedApps === 'function') {
        navigator.getInstalledRelatedApps().then((apps) => {
            if (apps.some((app) => app.platform === 'webapp' && app.id === installedPwaId)) {
                markAppInstalled();
            }
        }).catch(() => {});
    }

    installButton.addEventListener('click', async () => {
        if (appInstalled) return hideInstallControls();
        if (!window.isSecureContext) {
            installHelp.textContent = 'Installation indisponible sur cette adresse locale HTTP. Ouvre cette page avec une adresse HTTPS pour installer TENACOS.';
            installHelp.hidden = false;
            return;
        }
        if (installPrompt) {
            const promptEvent = installPrompt;
            installPrompt = null;
            try {
                promptEvent.prompt();
                const choice = await promptEvent.userChoice;
                if (appInstalled) return;
                if (choice?.outcome === 'accepted') {
                    installPending = true;
                    installButton.disabled = true;
                    installButton.textContent = 'Installation en cours…';
                    installPendingTimer = window.setTimeout(() => {
                        if (appInstalled || !installPending) return;
                        installPending = false;
                        installButton.disabled = false;
                        installButton.textContent = 'Actualiser pour vérifier l’installation';
                    }, 15000);
                    return;
                }
                installButton.textContent = 'Actualiser pour réessayer';
                installButton.hidden = false;
            } catch (error) {
                installButton.textContent = 'Actualiser pour réessayer';
                installButton.hidden = false;
            }
            return;
        }

        if (installButton.textContent === 'Actualiser pour vérifier l’installation'
            || installButton.textContent === 'Actualiser pour réessayer') {
            window.location.reload();
            return;
        }

        if (!isIOS || !isSafari) return;
        installHelp.textContent = 'Dans Safari, ouvre le menu Partager puis choisis « Sur l’écran d’accueil » pour installer l’application.';
        installHelp.hidden = false;
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
})();
</script>
</body>
</html>
