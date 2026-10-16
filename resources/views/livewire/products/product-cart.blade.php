<div class="product-cart-page">
    @php
        $cartItems = Cart::instance('cart')->content();
        $cartSubtotal = (float) Cart::instance('cart')->subtotal(2, '.', '');
    @endphp

    <style>
        .product-cart-page{--cart-wine:#861b1b;--cart-ink:#272a35;--cart-muted:#747b89;--cart-line:#e7e9ee;--cart-bg:#f5f6f8;color:var(--cart-ink);width:100%;max-width:100%;min-width:0;padding:8px 0 34px}
        .product-cart-page *{box-sizing:border-box}
        .cart-shell{width:100%;max-width:1440px;min-width:0;margin:0 auto;padding:0 20px}
        .cart-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:8px 0 24px}
        .cart-eyebrow{color:var(--cart-wine);font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;margin:0 0 7px}
        .cart-heading h1{font-size:clamp(27px,3vw,38px);font-weight:800;line-height:1.12;margin:0;color:var(--cart-ink)}
        .cart-heading p{color:var(--cart-muted);margin:8px 0 0;font-size:15px}
        .cart-back-link{display:inline-flex;align-items:center;gap:8px;color:var(--cart-wine);font-weight:700;text-decoration:none;white-space:nowrap}
        .cart-alert{border:0;border-radius:12px;padding:14px 16px;margin:0 0 18px}
        .cart-layout{display:grid;width:100%;min-width:0;grid-template-columns:minmax(0,1.55fr) minmax(310px,.8fr);align-items:start;gap:22px}
        .cart-main,.cart-sidebar{min-width:0;display:grid;gap:18px}
        .cart-panel{width:100%;max-width:100%;min-width:0;background:#fff;border:1px solid var(--cart-line);border-radius:16px;box-shadow:0 8px 26px rgba(33,38,48,.045);overflow:hidden}
        .cart-panel-head{padding:19px 22px 16px;border-bottom:1px solid var(--cart-line);display:flex;align-items:center;justify-content:space-between;gap:12px}
        .cart-panel-head h2{font-size:17px;line-height:1.3;font-weight:800;margin:0}
        .cart-panel-head p{font-size:13px;color:var(--cart-muted);margin:4px 0 0}
        .cart-step{display:inline-grid;place-items:center;flex:0 0 32px;width:32px;height:32px;border-radius:10px;background:#f8eaea;color:var(--cart-wine);font-size:13px;font-weight:800}
        .cart-panel-body{padding:20px 22px}
        .cart-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .cart-field{min-width:0}.cart-field.full{grid-column:1/-1}
        .cart-field.anonymous-customer-help{padding:12px 14px;border-left:3px solid var(--cart-wine);background:#f8f3f3;color:#5f626c;font-size:12px;line-height:1.5}
        .cart-customer-create-actions{display:flex;flex-wrap:nowrap;align-items:stretch;gap:6px;margin-top:7px;width:100%;max-width:100%}
        .cart-customer-mode{display:inline-flex;flex:1 1 0;min-width:0;align-items:center;justify-content:center;gap:6px;min-height:36px;padding:6px 9px;border:1px solid #d8dce3;background:#fff;color:#505765;font-size:11px;font-weight:700;white-space:nowrap;cursor:pointer}
        .cart-customer-mode:hover,.cart-customer-mode.is-selected{border-color:var(--cart-wine);background:#f8eeee;color:var(--cart-wine)}
        .cart-customer-mode .material-icons{flex:0 0 auto;font-size:16px}
        .cart-field[hidden]{display:none!important}
        .cart-field label{display:block;color:#454a56;font-size:13px;font-weight:700;margin:0 0 7px}
        .cart-field .form-control{height:45px;border:1px solid #dfe2e8;border-radius:9px;box-shadow:none;color:var(--cart-ink);font-size:14px;max-width:100%}
        .cart-field .select2-container{width:100%!important}
        .cart-field .select2-container--default .select2-selection--single{height:45px;border:1px solid #dfe2e8;border-radius:9px}
        .cart-field .select2-container--default .select2-selection--single .select2-selection__rendered{padding:8px 12px;color:var(--cart-ink);line-height:27px}
        .cart-field .select2-container--default .select2-selection--single .select2-selection__arrow{height:43px;right:7px}
        .cart-field textarea.form-control{height:auto;min-height:88px;padding:11px 13px;resize:vertical}
        .cart-field .form-control:focus{border-color:#bd7777;box-shadow:0 0 0 3px rgba(134,27,27,.09)}
        .cart-help{display:block;color:var(--cart-muted);font-size:12px;line-height:1.45;margin-top:6px}
        .cart-field-error{display:block;color:#bd2525;font-size:12px;margin-top:5px}
        .cart-subsection{border-top:1px solid var(--cart-line);margin-top:20px;padding-top:18px}
        .cart-subsection-title{font-size:13px;font-weight:800;color:#535967;margin:0 0 14px}
        .cart-table-wrap{width:100%;max-width:100%;min-width:0;overflow-x:auto;overscroll-behavior-x:contain}
        .cart-table{width:100%;border-collapse:collapse;min-width:620px}
        .cart-table th{background:#f7f8fa;color:#6e7480;font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:800;padding:12px 14px;text-align:left;border-bottom:1px solid var(--cart-line)}
        .cart-table td{padding:14px;border-bottom:1px solid #edf0f3;vertical-align:middle;font-size:14px}
        .cart-table tr:last-child td{border-bottom:0}
        .cart-product{display:flex;align-items:center;gap:12px;min-width:0}
        .cart-product-thumb{width:46px;height:46px;flex:0 0 46px;border-radius:10px;display:grid;place-items:center;background:#f8eeee;color:var(--cart-wine);overflow:hidden}
        .cart-product-thumb img{width:100%;height:100%;object-fit:cover}
        .cart-product-placeholder{font-size:22px}
        .cart-product-placeholder[hidden]{display:none}
        .cart-product-name{font-weight:750;color:var(--cart-ink);line-height:1.35;overflow-wrap:anywhere}
        .cart-product-meta{font-size:12px;color:var(--cart-muted);margin-top:3px}
        .cart-price{white-space:nowrap;font-weight:650}
        .cart-quantity{display:inline-flex;align-items:center;border:1px solid var(--cart-line);border-radius:9px;overflow:hidden;white-space:nowrap}
        .cart-quantity button{width:32px;height:32px;border:0;background:#f7f8fa;color:#4e5360;font-weight:800}
        .cart-quantity button:disabled{opacity:.42;cursor:not-allowed}
        .cart-quantity span{min-width:34px;text-align:center;font-weight:750}
        .cart-remove{border:0;background:#fff1f1;color:#ad2929;border-radius:8px;width:34px;height:34px}
        .cart-empty{padding:42px 22px;text-align:center}
        .cart-empty-icon{width:58px;height:58px;margin:0 auto 14px;border-radius:18px;background:#f8eeee;color:var(--cart-wine);display:grid;place-items:center}
        .cart-empty h3{font-size:18px;font-weight:800;margin:0 0 6px}.cart-empty p{color:var(--cart-muted);margin:0 auto 18px;max-width:390px;font-size:14px}
        .cart-empty a,.cart-primary-btn{display:inline-flex;justify-content:center;align-items:center;gap:9px;background:var(--cart-wine);border:1px solid var(--cart-wine);color:white!important;border-radius:10px;padding:11px 16px;font-weight:750;text-decoration:none}
        .cart-summary{position:sticky;top:18px}
        .cart-summary .cart-panel-body{padding:20px}
        .cart-summary-line{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:10px 0;color:#656b78;font-size:14px}
        .cart-summary-line strong{color:var(--cart-ink);text-align:right;white-space:nowrap}
        .cart-summary-line.total{border-top:1px solid var(--cart-line);margin-top:8px;padding-top:16px;color:var(--cart-ink);font-size:16px;font-weight:800}
        .cart-summary-line.total strong{font-size:21px;color:var(--cart-wine)}
        .cart-summary-note{padding:11px 12px;background:#f8f8fa;border-radius:9px;color:var(--cart-muted);font-size:12px;line-height:1.5;margin-top:12px}
        .cart-primary-btn{width:100%;margin-top:16px;min-height:48px;font-size:15px;cursor:pointer}
        .cart-primary-btn:disabled{opacity:.48;cursor:not-allowed}
        .cart-mobile-submit{display:none}
        @media(max-width:991px){.cart-layout{grid-template-columns:minmax(0,1fr) 300px;gap:15px}.cart-shell{padding:0}.cart-panel-head,.cart-panel-body{padding-left:17px;padding-right:17px}}
        @media(max-width:767px){.product-cart-page{padding-top:0}.cart-shell{padding:0}.cart-heading{align-items:flex-start;margin:8px 0 18px}.cart-heading p{font-size:13px}.cart-back-link{font-size:13px}.cart-layout{grid-template-columns:minmax(0,1fr);gap:14px}.cart-summary{position:static}.cart-sidebar{order:2}.cart-main{order:1}.cart-panel{border-radius:13px}.cart-panel-head{padding:15px}.cart-panel-body{padding:16px}.cart-form-grid{grid-template-columns:minmax(0,1fr);gap:13px}.cart-field,.cart-field.full{grid-column:1/-1;width:100%;max-width:100%}.cart-field .form-control{width:100%;height:43px}.cart-customer-create-actions{gap:6px}.cart-customer-mode{min-height:42px;padding:6px 7px;font-size:11px;line-height:1.2;white-space:normal;text-align:center}.cart-table{min-width:580px}.cart-summary .cart-panel-body{padding:16px}.cart-primary-btn{margin-top:12px}}
        @media(max-width:480px){.cart-heading{display:block}.cart-back-link{margin-top:12px}.cart-heading h1{font-size:27px}.cart-form-grid{grid-template-columns:minmax(0,1fr)}.cart-field.full{grid-column:auto}.cart-panel-head h2{font-size:15px}.cart-panel-head p{font-size:12px}.cart-table th,.cart-table td{padding:11px 10px}.cart-product-thumb{width:40px;height:40px;flex-basis:40px}.cart-empty{padding:32px 16px}}
        @media(max-width:380px){.cart-customer-create-actions{gap:4px}.cart-customer-mode{gap:4px;padding:5px;font-size:10px}.cart-customer-mode .material-icons{font-size:14px}}
        /* Surfaces nettes et carrées, cohérentes avec le reste de l'application. */
        .product-cart-page .cart-panel{border-radius:0;box-shadow:none;border-color:#d9dde5}
        .product-cart-page .cart-alert,.product-cart-page .cart-step,
        .product-cart-page .cart-field .form-control,
        .product-cart-page .cart-field .select2-container--default .select2-selection--single,
        .product-cart-page .cart-product-thumb,.product-cart-page .cart-quantity,
        .product-cart-page .cart-remove,.product-cart-page .cart-empty-icon,
        .product-cart-page .cart-empty a,.product-cart-page .cart-primary-btn,
        .product-cart-page .cart-summary-note{border-radius:0}
        .product-cart-page .cart-panel-head{background:#fff}
        .product-cart-page .cart-table th{background:#f1f3f6}
        .product-cart-page .cart-quantity{border-color:#d9dde5}
        .product-cart-page .cart-remove{border:1px solid #f0d2d2}
        .product-cart-page .cart-primary-btn,.product-cart-page .cart-empty a{min-height:46px;letter-spacing:.01em}
        @media(max-width:767px){.product-cart-page .cart-shell{padding-left:0;padding-right:0}.product-cart-page .cart-panel-head,.product-cart-page .cart-panel-body{padding-left:14px;padding-right:14px}}
        @media(max-width:767px){
            .product-cart-page .cart-table-wrap{overflow-x:hidden}
            .product-cart-page .cart-table{display:block;width:100%;min-width:0}
            .product-cart-page .cart-table thead{display:none}
            .product-cart-page .cart-table tbody{display:grid;width:100%}
            .product-cart-page .cart-table tbody tr{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:2px 12px;padding:10px 14px;border-bottom:1px solid var(--cart-line)}
            .product-cart-page .cart-table tbody tr:last-child{border-bottom:0}
            .product-cart-page .cart-table td{display:flex;min-width:0;align-items:flex-start;flex-direction:column;gap:4px;padding:8px 2px;border:0;white-space:normal;font-size:12px}
            .product-cart-page .cart-table td:first-child{grid-column:1/-1;display:block;padding-bottom:10px;border-bottom:1px solid #edf0f3}
            .product-cart-page .cart-product{width:100%;min-width:0;gap:10px}
            .product-cart-page .cart-product>div:last-child{min-width:0}
            .product-cart-page .cart-table td:nth-child(2)::before{content:"Prix unitaire"}
            .product-cart-page .cart-table td:nth-child(3)::before{content:"Quantité"}
            .product-cart-page .cart-table td:nth-child(4)::before{content:"Total"}
            .product-cart-page .cart-table td:nth-child(2)::before,.product-cart-page .cart-table td:nth-child(3)::before,.product-cart-page .cart-table td:nth-child(4)::before{color:var(--cart-muted);font-size:10px;font-weight:700}
            .product-cart-page .cart-table td:nth-child(5){grid-column:2;grid-row:3;align-items:flex-end;justify-content:flex-end}
            .product-cart-page .cart-table .cart-price{font-size:13px}
        }
    </style>

    <div class="cart-shell">
        <div class="cart-heading">
            <div>
                <p class="cart-eyebrow">Gestion des commandes</p>
                <h1>Panier de commande</h1>
                <p>Vérifie les articles et les informations de livraison avant validation.</p>
            </div>
            <a class="cart-back-link" href="{{ route('product') }}"><span class="material-icons" aria-hidden="true">arrow_back</span>Retour au catalogue</a>
        </div>

        @if (Session::has('error'))
            <div class="alert alert-danger cart-alert"><strong>{{ session('error') }}</strong></div>
        @endif

        @if (Session::has('messages'))
            <div class="alert alert-success cart-alert"><strong>{{ session('messages') }}</strong></div>
        @endif

        <form method="POST" action="{{ route('palceorder') }}" id="product-cart-order-form">
            @csrf

            <div class="cart-layout">
                <main class="cart-main">
                    <section class="cart-panel" aria-labelledby="cart-products-title">
                        <div class="cart-panel-head">
                            <div><h2 id="cart-products-title">Articles de la commande</h2><p>{{ $cartItems->count() }} référence(s) · {{ Cart::instance('cart')->count() }} article(s)</p></div>
                            <span class="cart-step">01</span>
                        </div>

                        @if ($cartItems->count() > 0)
                            <div class="cart-table-wrap">
                                <table class="cart-table">
                                    <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Total</th><th><span class="sr-only">Actions</span></th></tr></thead>
                                    <tbody>
                                        @foreach ($cartItems as $items)
                                            @php
                                                $productImage = $items->model->img ?? null;
                                                $productImageUrl = null;
                                                if ($productImage) {
                                                    if (\Illuminate\Support\Str::startsWith($productImage, ['http://', 'https://', '/'])) {
                                                        $productImageUrl = $productImage;
                                                    } elseif (\Illuminate\Support\Str::startsWith($productImage, ['image/', 'storage/'])) {
                                                        $productImageUrl = asset($productImage);
                                                    } else {
                                                        $productImageUrl = url('image/' . ltrim($productImage, '/'));
                                                    }
                                                }
                                            @endphp
                                            <tr wire:key="cart-row-{{ $items->rowId }}">
                                                <td>
                                                    <div class="cart-product">
                                                        <div class="cart-product-thumb">
                                                            @if ($productImageUrl)
                                                                <img src="{{ $productImageUrl }}" alt="{{ $items->model->name ?? 'Produit' }}" loading="lazy" onerror="this.remove();var fallback=this.parentElement.querySelector('[data-product-placeholder]');if(fallback)fallback.hidden=false;">
                                                            @endif
                                                            <span class="material-icons cart-product-placeholder" data-product-placeholder aria-hidden="true" @if ($productImageUrl) hidden @endif>inventory_2</span>
                                                        </div>
                                                        <div><div class="cart-product-name">{{ $items->model->name ?? 'Produit' }}</div><div class="cart-product-meta">Réf. {{ $items->id }}</div></div>
                                                    </div>
                                                </td>
                                                <td class="cart-price">{{ number_format((float) $items->price, 0, ',', ' ') }} F</td>
                                                <td>
                                                    <div class="cart-quantity" aria-label="Quantité">
                                                        <button type="button" aria-label="Diminuer la quantité" wire:click.prevent="decrement('{{ $items->rowId }}')" @disabled($items->qty <= 1)>−</button>
                                                        <span>{{ $items->qty }}</span>
                                                        <button type="button" aria-label="Augmenter la quantité" wire:click.prevent="increment('{{ $items->rowId }}')" @disabled((int) ($items->model->qt_initial ?? 0) <= (int) $items->qty)>+</button>
                                                    </div>
                                                </td>
                                                <td class="cart-price">{{ number_format((float) $items->price * (int) $items->qty, 0, ',', ' ') }} F</td>
                                                <td><button type="button" class="cart-remove" title="Retirer le produit" aria-label="Retirer {{ $items->model->name ?? 'le produit' }}" wire:click.prevent="destroy('{{ $items->rowId }}')"><span class="material-icons" aria-hidden="true">delete_outline</span></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cart-empty">
                                <div class="cart-empty-icon"><span class="material-icons" aria-hidden="true">shopping_bag</span></div>
                                <h3>Ton panier est encore vide</h3>
                                <p>Choisis des produits dans le catalogue pour commencer à préparer cette commande.</p>
                                <a href="{{ route('product') }}"><span class="material-icons" aria-hidden="true">inventory_2</span>Voir les produits</a>
                            </div>
                        @endif
                    </section>

                    <section class="cart-panel" aria-labelledby="cart-customer-title">
                        <div class="cart-panel-head"><div><h2 id="cart-customer-title">Cliente et livraison</h2><p>Les champs nécessaires à la préparation de la commande.</p></div><span class="cart-step">02</span></div>
                        <div class="cart-panel-body">
                            <div class="cart-form-grid">
                                <div class="cart-field full">
                                    <label for="order-costumer-id">Cliente</label>
                                    <div wire:ignore>
                                        <select id="order-costumer-id" class="js-customer-search form-control" data-ajax-url="{{ route('costumer.search') }}" name="costumer_id" style="width:100%">
                                            <option value="default" @selected(old('costumer_id', 'default') === 'default')>Nouveau client avec nom</option>
                                            <option value="anonymous" @selected(old('costumer_id') === 'anonymous')>Client anonyme · générer un identifiant</option>
                                            @if ($selectedCostumer)<option value="{{ $selectedCostumer->id }}" selected>{{ $selectedCostumer->name }} | {{ $selectedCostumer->phone }}</option>@endif
                                        </select>
                                    </div>
                                    <div class="cart-customer-create-actions" role="group" aria-label="Type de nouveau client">
                                        <button type="button" class="cart-customer-mode" data-customer-mode="default" aria-pressed="false"><span class="material-icons" aria-hidden="true">person_add</span>Nouveau client avec nom</button>
                                        <button type="button" class="cart-customer-mode" data-customer-mode="anonymous" aria-pressed="false"><span class="material-icons" aria-hidden="true">visibility_off</span>Client anonyme</button>
                                    </div>
                                    @error('costumer_id')<span class="cart-field-error">{{ $message }}</span>@enderror
                                </div>

                                <div class="cart-field new-customer-field new-customer-name-field"><label for="name">Nom du client</label><input type="text" class="form-control" id="name" name="name" placeholder="Nom complet" value="{{ old('name') }}">@error('name')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                <div class="cart-field new-customer-field"><label for="phone">Téléphone</label><input type="text" class="form-control" id="phone" name="phone" placeholder="Numéro de téléphone" value="{{ old('phone') }}">@error('phone')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                <div class="cart-field new-customer-field"><label for="adresse">Adresse de livraison</label><input type="text" class="form-control" id="adresse" name="adresse" placeholder="Quartier, repère…" value="{{ old('adresse') }}">@error('adresse')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                <div class="cart-field full anonymous-customer-help" hidden>Le nom ne sera pas demandé. Un identifiant client sera généré automatiquement ; le téléphone et l’adresse servent à organiser la livraison.</div>
                                <div class="cart-field full"><label for="tax">Frais de livraison (F CFA) <span class="text-danger">*</span></label><input type="number" min="0" step="1" class="form-control" id="tax" name="tax" value="{{ old('tax', 0) }}" data-has-old-value="{{ old('tax') !== null ? '1' : '0' }}" placeholder="0">@error('tax')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                            </div>

                            <div class="cart-subsection"><p class="cart-subsection-title">Planification et détails</p>
                                <div class="cart-form-grid">
                                    <div class="cart-field"><label for="date_order">Date de livraison <span class="text-danger">*</span></label><input type="date" class="form-control" id="date_order" name="date_order" value="{{ old('date_order', now()->toDateString()) }}">@error('date_order')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                    <div class="cart-field"><label for="time">Heure de livraison <span class="text-danger">*</span></label><input type="time" name="time" id="time" value="{{ old('time') }}" class="form-control">@error('time')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                    <div class="cart-field"><label for="order-type">Type de commande</label><select name="type" id="order-type" class="form-control"><option value="PU" @selected(old('type', 'PU') === 'PU')>Publique</option><option value="PR" @selected(old('type') === 'PR')>Privée</option></select>@error('type')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                    <div class="cart-field"><label for="remise">Remise (%)</label><input type="number" min="0" max="100" step="1" class="form-control" id="remise" name="remis" placeholder="0" value="{{ old('remis', 0) }}" data-has-old-value="{{ old('remis') !== null ? '1' : '0' }}">@error('remis')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                    <div class="cart-field"><label for="customer-follow-up-at">Prochaine relance cliente <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" id="customer-follow-up-at" name="follow_up_at" data-delay-days="{{ $defaultFollowUpDays }}" value="{{ old('follow_up_at', $defaultFollowUpAt) }}" required>@error('follow_up_at')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                    <div class="cart-field full"><label for="avis">Commentaire (facultatif)</label><textarea name="avis" id="avis" placeholder="Informations utiles pour la préparation ou la livraison" class="form-control" rows="3">{{ old('avis') }}</textarea>@error('avis')<span class="cart-field-error">{{ $message }}</span>@enderror</div>
                                </div>
                                <small class="cart-help">La prochaine relance est proposée {{ $defaultFollowUpDays }} jours après la livraison. Tu peux la modifier.</small>
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="cart-sidebar">
                    <section class="cart-panel cart-summary" aria-labelledby="cart-summary-title">
                        <div class="cart-panel-head"><div><h2 id="cart-summary-title">Résumé de la commande</h2><p>Montants avant confirmation.</p></div><span class="cart-step">03</span></div>
                        <div class="cart-panel-body">
                            <div class="cart-summary-line"><span>Sous-total des articles</span><strong id="cart-subtotal-display" data-subtotal="{{ number_format($cartSubtotal, 2, '.', '') }}">{{ number_format($cartSubtotal, 0, ',', ' ') }} F</strong></div>
                            <div class="cart-summary-line"><span>Remise <span id="cart-discount-rate">({{ (int) old('remis', 0) }} %)</span></span><strong id="cart-discount-display">− {{ number_format($cartSubtotal * min(100, max(0, (int) old('remis', 0))) / 100, 0, ',', ' ') }} F</strong></div>
                            <div class="cart-summary-line"><span>Livraison</span><strong id="cart-delivery-display">{{ number_format((float) old('tax', 0), 0, ',', ' ') }} F</strong></div>
                            <div class="cart-summary-line total"><span>Total estimé</span><strong id="cart-total-display">{{ number_format($cartSubtotal * (1 - min(100, max(0, (int) old('remis', 0))) / 100) + (float) old('tax', 0), 0, ',', ' ') }} F</strong></div>
                            <div class="cart-summary-note">Le total inclut les frais de livraison et la remise saisie. Vérifie le numéro, l’adresse et la date avant validation.</div>
                            <button type="submit" class="cart-primary-btn" @disabled($cartItems->isEmpty())><span class="material-icons" aria-hidden="true">check_circle</span>Valider la commande</button>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var root = document.currentScript && document.currentScript.closest('.product-cart-page');
            if (!root) return;

            var deliveryDate = root.querySelector('#date_order');
            var followUpDate = root.querySelector('#customer-follow-up-at');
            var customerSelect = root.querySelector('#order-costumer-id');
            var storageKey = 'tenacos-product-cart-pricing';
            var cartIsEmpty = {{ $cartItems->isEmpty() ? 'true' : 'false' }};
            var manuallyChanged = false;
            var updatingFromDeliveryDate = false;
            var delayDays = parseInt(followUpDate && followUpDate.getAttribute('data-delay-days'), 10) || 14;

            if (followUpDate && deliveryDate) {
                followUpDate.addEventListener('input', function () { if (!updatingFromDeliveryDate) manuallyChanged = true; });
                deliveryDate.addEventListener('change', function () {
                    if (manuallyChanged || !deliveryDate.value) return;
                    var parts = deliveryDate.value.split('-').map(Number);
                    var nextDate = new Date(parts[0], parts[1] - 1, parts[2] + delayDays);
                    followUpDate.value = nextDate.getFullYear() + '-' + String(nextDate.getMonth() + 1).padStart(2, '0') + '-' + String(nextDate.getDate()).padStart(2, '0') + 'T09:00';
                    updatingFromDeliveryDate = true;
                    followUpDate.dispatchEvent(new Event('input', { bubbles: true }));
                    updatingFromDeliveryDate = false;
                });
            }

            function currentPricingInputs() {
                return {
                    discount: root.querySelector('#remise'),
                    delivery: root.querySelector('#tax')
                };
            }

            function restorePricing(preserveOldValues) {
                var inputs = currentPricingInputs();
                var savedPricing = null;
                try { savedPricing = JSON.parse(sessionStorage.getItem(storageKey) || 'null'); } catch (error) {}
                if (!savedPricing) return;

                if (inputs.delivery && (!preserveOldValues || inputs.delivery.getAttribute('data-has-old-value') !== '1')) {
                    inputs.delivery.value = savedPricing.tax ?? inputs.delivery.value;
                }
                if (inputs.discount && (!preserveOldValues || inputs.discount.getAttribute('data-has-old-value') !== '1')) {
                    inputs.discount.value = savedPricing.remis ?? inputs.discount.value;
                }
            }

            function updateSummary() {
                var inputs = currentPricingInputs();
                var subtotalElement = root.querySelector('#cart-subtotal-display');
                var subtotal = Math.max(0, parseFloat(subtotalElement && subtotalElement.getAttribute('data-subtotal')) || 0);
                var discountRate = Math.min(100, Math.max(0, parseFloat(inputs.discount && inputs.discount.value) || 0));
                var delivery = Math.max(0, parseFloat(inputs.delivery && inputs.delivery.value) || 0);
                var discount = subtotal * discountRate / 100;
                var formatter = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });
                root.querySelector('#cart-discount-rate').textContent = '(' + formatter.format(discountRate) + ' %)';
                root.querySelector('#cart-discount-display').textContent = '− ' + formatter.format(discount) + ' F';
                root.querySelector('#cart-delivery-display').textContent = formatter.format(delivery) + ' F';
                root.querySelector('#cart-total-display').textContent = formatter.format(subtotal - discount + delivery) + ' F';
                if (!cartIsEmpty && inputs.delivery && inputs.discount) {
                    try { sessionStorage.setItem(storageKey, JSON.stringify({ tax: inputs.delivery.value, remis: inputs.discount.value })); } catch (error) {}
                }
            }
            if (cartIsEmpty) {
                try { sessionStorage.removeItem(storageKey); } catch (error) {}
            } else {
                restorePricing(true);
            }
            root.addEventListener('input', function (event) {
                if (event.target.matches('#remise, #tax')) updateSummary();
            });
            updateSummary();
            window.addEventListener('cart-count-updated', function () {
                window.setTimeout(function () {
                    restorePricing(false);
                    updateSummary();
                }, 0);
            });
            var orderForm = root.querySelector('#product-cart-order-form');
            if (orderForm) orderForm.addEventListener('submit', function () {
                try { sessionStorage.removeItem(storageKey); } catch (error) {}
            });

            function toggleNewCustomerFields() {
                var selection = customerSelect ? customerSelect.value : 'default';
                var isAnonymous = selection === 'anonymous';
                var isNew = selection === 'default' || isAnonymous;
                root.querySelectorAll('.new-customer-field').forEach(function (field) {
                    field.style.display = isNew && !(isAnonymous && field.classList.contains('new-customer-name-field')) ? '' : 'none';
                });
                var nameInput = root.querySelector('#name');
                if (nameInput) nameInput.required = selection === 'default';
                var anonymousHelp = root.querySelector('.anonymous-customer-help');
                if (anonymousHelp) anonymousHelp.hidden = !isAnonymous;
                root.querySelectorAll('.cart-customer-mode').forEach(function (button) {
                    var selected = button.getAttribute('data-customer-mode') === selection;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            }
            if (customerSelect) {
                customerSelect.addEventListener('change', toggleNewCustomerFields);
                if (window.jQuery) window.jQuery(customerSelect).on('select2:select select2:clear', toggleNewCustomerFields);
                root.querySelectorAll('.cart-customer-mode').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var mode = button.getAttribute('data-customer-mode');
                        if (window.jQuery) window.jQuery(customerSelect).val(mode).trigger('change');
                        else {
                            customerSelect.value = mode;
                            customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        toggleNewCustomerFields();
                    });
                });
                toggleNewCustomerFields();
            }
        })();
    </script>
</div>
