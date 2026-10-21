<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#7e1615">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="manifest" href="{{ $link ? route('public-order.manifest', $link->token) : route('public-order.app.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <title>{{ $link?->title ?: 'Catalogue TENACOS' }}</title>
    <style>
        :root{--red:#7e1615;--ink:#252a34;--muted:#687386;--line:#e5e8ee;--bg:#f6f7f9;--green:#16794b}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{max-width:1100px;margin:auto;padding:24px 18px 70px}.head{background:white;border-radius:0;padding:28px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 8px 28px #1720330b}.brand{color:var(--red);font-size:13px;font-weight:800;letter-spacing:.16em}.head h1{font-size:clamp(26px,4vw,40px);line-height:1.15;margin:8px 0}.head p{color:var(--muted);margin:0}.install{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--red);color:#fff;border-radius:0;padding:13px 17px;font:inherit;font-weight:700;white-space:nowrap;cursor:pointer}.install[hidden],.install-help[hidden]{display:none!important}.install-help{margin-top:10px;padding:11px 13px;background:#fdf5e8;border-left:4px solid #d49a30;border-radius:0;color:#73551b;font-size:14px}.controls,.panel{background:white;border:1px solid var(--line);border-radius:0;padding:20px;margin-top:20px}.controls{display:grid;grid-template-columns:1fr;gap:16px}.field label{display:block;font-weight:700;font-size:14px;margin-bottom:7px}.field input,.field select,.field textarea{width:100%;min-height:46px;border:1px solid #d8dce5;background:white;padding:11px 12px;border-radius:0;font:inherit}.section-title{display:flex;justify-content:space-between;align-items:end;margin:26px 0 12px}.section-title h2{font-size:21px;margin:0}.section-title p{color:var(--muted);margin:3px 0 0}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.product{background:white;border:1px solid var(--line);border-radius:0;padding:14px;display:flex;flex-direction:column;min-width:0}.product img{width:100%;height:175px;object-fit:cover;background:#f0f1f3;border-radius:0;margin:12px 0 8px}.product h3{font-size:16px;line-height:1.35;margin:4px 0 8px;overflow-wrap:anywhere}.product p{color:var(--muted);font-size:13px;line-height:1.45;margin:0 0 10px}.price{font-size:19px;font-weight:800;color:var(--red);margin-top:auto}.product button,.checkout-button{display:flex;align-items:center;justify-content:center;width:100%;min-height:46px;border:0;background:var(--red);color:#fff;border-radius:0;padding:11px 12px;font:inherit;font-weight:750;margin-top:12px;cursor:pointer}.suggestions{margin-top:28px}.empty{color:var(--muted);padding:18px;border:1px solid var(--line);border-radius:0;margin-top:15px;background:#fff}.cart{background:#fff;border:1px solid var(--line);padding:22px;margin-top:22px}.cart h2{font-size:20px;margin:0 0 12px}.cart-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:14px;border-bottom:1px solid var(--line);padding:12px 0}.cart-product-name{min-width:0;overflow-wrap:anywhere;font-size:14px}.qty{display:flex;align-items:center;gap:9px}.qty button{display:grid;place-items:center;width:38px;height:38px;border:1px solid var(--line);background:#fff;border-radius:0;font-size:20px;line-height:1;font-weight:700;padding:0;cursor:pointer}.qty span{min-width:20px;text-align:center;font-weight:750}.line-total{min-width:90px;text-align:right;font-weight:800;white-space:nowrap}.cart-total{display:flex;justify-content:space-between;gap:12px;padding:16px 0;font-size:16px;font-weight:800}.notice{background:#fdf5e8;color:#73551b;border-left:4px solid #d49a30;padding:12px 14px;border-radius:0;font-size:14px}.checkout{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px}.checkout .wide{grid-column:1/-1}.checkout .field{min-width:0}.customer-summary{display:flex;align-items:center;gap:5px 8px;flex-wrap:wrap;padding:12px 14px;background:#f7f8fa;border-left:3px solid var(--red);font-size:13px}.customer-summary span{color:var(--muted)}.checkout .field small{display:block;margin-top:5px;color:var(--muted);font-size:12px}.checkout-button{font-size:15px}.errors{background:#fff0f0;color:#8c1e1b;padding:12px;border-radius:0;margin-top:16px}.status{color:var(--muted);text-align:center;padding:20px}.hidden-inputs{display:none}
        .select2-container{width:100%!important}.select2-container--default .select2-selection--single{height:46px;border:1px solid #d8dce5;border-radius:0;background:#fff}.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:44px;padding-left:12px;color:var(--ink)}.select2-container--default .select2-selection--single .select2-selection__arrow{height:44px}.select2-dropdown{border-color:#d8dce5;border-radius:0}.select2-search--dropdown .select2-search__field{min-height:38px;padding:7px 9px}
        .quantity-price-hints{margin-top:3px}.quantity-price-hint{display:block;color:var(--muted);font-size:12px;line-height:1.45;margin-top:3px}.cart-product-name small{display:block;margin-top:3px;color:var(--muted);font-size:11px;font-weight:500}.product button:disabled,.qty button:disabled{opacity:.55;cursor:not-allowed}
        @media(max-width:760px){.wrap{padding:12px 12px 36px}.head{padding:18px;align-items:stretch;flex-direction:column;gap:15px}.head h1{font-size:27px;overflow-wrap:anywhere}.head p{font-size:14px}.install-area{width:100%;justify-content:flex-start!important}.install-area>a,.install-area>.install{flex:1;min-width:0;white-space:normal;text-align:center;font-size:13px;padding:11px 9px}.install-help{width:100%;margin-top:0}.controls{grid-template-columns:1fr;gap:12px;padding:15px;margin-top:12px}.field label{font-size:13px}.field input,.field select,.field textarea{min-height:48px;font-size:16px}.section-title{align-items:flex-start;margin:22px 0 10px}.section-title h2{font-size:19px}.section-title p{font-size:13px}.grid{grid-template-columns:1fr;gap:9px}.product{padding:10px}.product img{height:clamp(150px,48vw,260px);margin:8px 0 7px}.product h3{font-size:15px}.product p{font-size:13px}.price{font-size:18px}.product button{min-height:46px;padding:9px 7px;font-size:14px;line-height:1.25}.cart{padding:16px;margin-top:17px}.cart h2{font-size:19px}.cart-row{grid-template-columns:minmax(0,1fr) auto;grid-template-areas:"name total" "qty total";gap:5px 9px;padding:12px 0}.cart-product-name{grid-area:name}.cart-row .qty{grid-area:qty}.line-total{grid-area:total;min-width:0;align-self:center;font-size:14px}.qty button{width:40px;height:40px}.cart-total{font-size:15px;padding:14px 0}.notice{font-size:13px;padding:11px 12px}.checkout{grid-template-columns:1fr;gap:12px;margin-top:14px}.checkout .wide{grid-column:auto}.checkout-button{min-height:49px;font-size:14px}.empty{font-size:14px}.status{padding:16px 8px;font-size:14px}}
        @media(max-width:390px){.wrap{padding-left:10px;padding-right:10px}.head{padding:15px}.head h1{font-size:24px}.grid{grid-template-columns:1fr;gap:10px}.product{display:grid;grid-template-columns:minmax(88px,30%) minmax(0,1fr);grid-template-rows:auto auto auto auto;column-gap:12px;align-items:start}.product img{grid-column:1;grid-row:1/5;width:100%;height:100%;min-height:125px;max-height:190px;margin:0;object-fit:cover;align-self:stretch}.product h3{grid-column:2;grid-row:1;margin:1px 0 4px}.product p{grid-column:2;grid-row:2;margin:0 0 5px}.product .price,.product .quantity-price-hints{grid-column:2;align-self:auto}.product button{grid-column:1/-1;margin-top:10px}.install-area{gap:7px!important}.install-area>a,.install-area>.install{font-size:12px;padding:10px 6px}}
    </style>
</head>
<body>
<main class="wrap">
    <header class="head">
        <div><span class="brand">TENACOS · BOUTIQUE</span><h1>{{ $link?->title ?: 'Catalogue TENACOS' }}</h1><p>Choisis ton pays pour voir tous les produits et les prix disponibles près de chez toi.</p></div>
        <div class="install-area" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end">
            <a href="{{ route('public-order.app', array_filter(['country' => $link?->country_code ?: ($initialCountry ?: null)])) }}" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 14px;border:1px solid var(--line);background:#fff;color:var(--ink);text-decoration:none;font-weight:700">Voir les produits disponibles</a>
            @if($hasPortalAccount)<a href="{{ route('customer-portal.index') }}" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 14px;border:1px solid var(--line);background:#fff;color:var(--ink);text-decoration:none;font-weight:700">Mon espace client</a>@endif
            <button type="button" id="install-app" class="install" hidden>Installer l’application</button>
            <div id="install-help" class="install-help" role="status" hidden></div>
        </div>
    </header>

    <section class="controls" aria-label="Destination">
        <div class="field"><label for="country">Pays de livraison</label><select id="country" class="js-country-search" @disabled($link?->country_code)>@unless($link?->country_code)<option value="">Choisir un pays</option>@endunless @foreach ($countries as $code => $country)<option value="{{ $code }}" @selected(strtoupper((string) $link?->country_code) === $code || (! $link?->country_code && old('country_code') === $code))>{{ $country['label'] }}</option>@endforeach</select></div>
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
        <div class="notice">Les frais de livraison te seront communiqués par le partenaire après son appel pour confirmer l’adresse.</div>

        <form id="order-form" method="POST" action="{{ $link ? route('public-order.store', $link->token) : route('public-order.app.store') }}" class="mt-3">
            @csrf
            @php
                $selectedSavedProfile = $savedProfiles->firstWhere('id', (int) old('customer_profile_id', $savedProfiles->first()?->id)) ?? $savedProfiles->first();
            @endphp
            <input type="hidden" name="country_code" id="order-country">
            <div id="order-items" class="hidden-inputs"></div>
            <div class="checkout">
                @if ($savedProfiles->isNotEmpty())
                    <div class="field wide"><label for="customer-profile">Commander au nom de</label><select id="customer-profile" name="customer_profile_id" required>@foreach($savedProfiles as $profile)<option value="{{ $profile->id }}" data-name="{{ $profile->name }}" data-phone="{{ $profile->phone }}" data-address="{{ $profile->checkout_address }}" @selected((string) old('customer_profile_id', $selectedSavedProfile->id) === (string) $profile->id)>{{ $profile->name }}{{ $profile->phone ? ' · '.$profile->phone : '' }}</option>@endforeach</select></div>
                    <div class="customer-summary wide">Commande au nom de <strong id="selected-profile-name">{{ $selectedSavedProfile->name }}</strong><span id="selected-profile-phone">{{ $selectedSavedProfile->phone }}</span></div>
                    <div class="field wide"><label for="customer-address">Adresse de livraison</label><input id="customer-address" name="address" maxlength="180" value="{{ old('address', $selectedSavedProfile->checkout_address) }}" placeholder="Ville, quartier, rue ou repère" autocomplete="street-address" required><small>Indique la ville et un repère pour faciliter la livraison.</small></div>
                @elseif ($savedCustomer)
                    <div class="customer-summary wide">Commande au nom de <strong>{{ $savedCustomer->name }}</strong><span>{{ $savedCustomer->phone }}</span></div>
                    <div class="field wide"><label for="customer-address">Adresse de livraison</label><input id="customer-address" name="address" maxlength="180" value="{{ old('address', $savedAddress) }}" placeholder="Ville, quartier, rue ou repère" autocomplete="street-address" required><small>Indique la ville et un repère pour faciliter la livraison.</small></div>
                @else
                    <div class="field"><label for="customer-name">Nom complet</label><input id="customer-name" name="name" maxlength="160" value="{{ old('name') }}" autocomplete="name" required></div>
                    <div class="field"><label for="customer-phone">Téléphone</label><input id="customer-phone" name="phone" type="tel" maxlength="50" value="{{ old('phone') }}" autocomplete="tel" required></div>
                    <div class="field wide"><label for="customer-address">Adresse de livraison</label><input id="customer-address" name="address" maxlength="180" value="{{ old('address') }}" placeholder="Ville, quartier, rue ou repère" autocomplete="street-address" required><small>Indique la ville et un repère pour faciliter la livraison.</small></div>
                @endif
                <div class="field wide"><button class="checkout-button" type="submit">Envoyer ma commande</button></div>
            </div>
        </form>
    </section>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
if (window.jQuery && jQuery.fn.select2) {
    jQuery('#country').select2({width:'100%', placeholder:'Rechercher un pays…', language:{noResults:()=>'Aucun pays trouvé',searching:()=>'Recherche…'}});
}
(() => {
    const country = document.getElementById('country');
    const status = document.getElementById('catalog-status');
    const featuredSection = document.getElementById('featured-section');
    const suggestionsSection = document.getElementById('suggestion-section');
    const featuredProducts = document.getElementById('featured-products');
    const suggestionProducts = document.getElementById('suggestion-products');
    const cartSection = document.getElementById('cart-section');
    const cartItems = document.getElementById('cart-items');
    const cartEmpty = document.getElementById('cart-empty');
    const subtotalNode = document.getElementById('cart-subtotal');
    const basket = new Map();
    let catalog = [];
    const money = (amount) => new Intl.NumberFormat('fr-FR').format(amount) + ' F';
    const priceForQuantity = (product, quantity) => {
        const eligible = (product.quantity_prices || []).filter((tier) => Number(tier.min_quantity) <= quantity);
        if (!eligible.length) return Number(product.price) || 0;
        return Number(eligible.reduce((best, tier) => Number(tier.min_quantity) > Number(best.min_quantity) ? tier : best).unit_price) || Number(product.price) || 0;
    };
    function renderBasket() {
        cartItems.replaceChildren();
        let subtotal = 0;
        basket.forEach((entry, id) => {
            const unitPrice = priceForQuantity(entry.product, entry.quantity);
            const lineTotal = unitPrice * entry.quantity;
            subtotal += lineTotal;
            const row = document.createElement('div');
            row.className = 'cart-row';
            const name = document.createElement('div'); name.className = 'cart-product-name';
            const productName = document.createElement('strong'); productName.textContent = entry.product.name; name.append(productName);
            if ((entry.product.quantity_prices || []).length) { const tierNote = document.createElement('small'); tierNote.textContent = 'Tarif unitaire calculé selon la quantité'; name.append(tierNote); }
            const controls = document.createElement('div'); controls.className = 'qty';
            const minus = document.createElement('button'); minus.type = 'button'; minus.textContent = '−'; minus.setAttribute('aria-label', 'Retirer une unité');
            minus.addEventListener('click', () => changeQuantity(id, -1));
            const count = document.createElement('span'); count.textContent = entry.quantity;
            const plus = document.createElement('button'); plus.type = 'button'; plus.textContent = '+'; plus.setAttribute('aria-label', 'Ajouter une unité');
            plus.addEventListener('click', () => changeQuantity(id, 1));
            controls.append(minus, count, plus);
            const line = document.createElement('span'); line.className = 'line-total';
            line.textContent = entry.quantity > 1 ? `${money(unitPrice)} / unité · ${money(lineTotal)}` : money(lineTotal);
            row.append(name, controls, line); cartItems.append(row);
        });
        cartEmpty.hidden = basket.size > 0;
        cartSection.hidden = !country.value;
        subtotalNode.textContent = money(subtotal);
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
        const tiers = (product.quantity_prices || []).slice().sort((a, b) => Number(a.min_quantity) - Number(b.min_quantity));
        if (tiers.length) {
            const tierHints = document.createElement('div'); tierHints.className = 'quantity-price-hints';
            tiers.forEach((tier) => {
                const tierHint = document.createElement('small'); tierHint.className = 'quantity-price-hint';
                tierHint.textContent = `Dès ${tier.min_quantity} unités : ${money(tier.unit_price)} / unité`;
                tierHints.append(tierHint);
            });
            card.append(tierHints);
        }
        const button = document.createElement('button'); button.type = 'button'; button.dataset.addProduct = product.id; button.textContent = 'Ajouter au panier'; button.addEventListener('click', () => addProduct(product)); card.append(button);
        return card;
    }

    country.addEventListener('change', async () => {
        basket.clear(); catalog = []; featuredProducts.replaceChildren(); suggestionProducts.replaceChildren();
        featuredSection.hidden = true; suggestionsSection.hidden = true; renderBasket();
        if (country.value) localStorage.setItem('tenacos_public_country', country.value);
        else localStorage.removeItem('tenacos_public_country');
        if (!country.value) { status.textContent = 'Sélectionne ton pays pour afficher le catalogue.'; return; }
        status.textContent = 'Chargement des prix et des produits…';
        try {
            const response = await fetch(@json($link ? route('public-order.catalog', $link->token) : route('public-order.app.catalog')) + '?country=' + encodeURIComponent(country.value), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Le catalogue est indisponible pour ce pays.');
            catalog = data.products;
            const featured = catalog.filter((item) => item.featured);
            const suggestions = catalog.filter((item) => !item.featured);
            featured.forEach((item) => featuredProducts.append(productCard(item)));
            suggestions.forEach((item) => suggestionProducts.append(productCard(item)));
            renderBasket();
            featuredSection.hidden = featured.length === 0;
            suggestionsSection.hidden = suggestions.length === 0;
            status.hidden = catalog.length > 0;
            if (catalog.length === 0) status.textContent = 'Aucun produit avec un prix actif dans ce pays pour le moment.';
        } catch (error) {
            status.hidden = false; status.textContent = error.message;
        }
    });

    const initialCountry = @json($initialCountry);
    const savedCountry = localStorage.getItem('tenacos_public_country');
    if (country.disabled && country.value) {
        country.dispatchEvent(new Event('change'));
    } else if (initialCountry && Array.from(country.options).some((option) => option.value === initialCountry)) {
        country.value = initialCountry;
        country.dispatchEvent(new Event('change'));
    } else if (savedCountry && Array.from(country.options).some((option) => option.value === savedCountry)) {
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
        if (!country.value || basket.size === 0) { event.preventDefault(); alert('Choisis un pays et ajoute au moins un produit.'); return; }
        document.getElementById('order-country').value = country.value;
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
