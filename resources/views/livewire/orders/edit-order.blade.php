<div class="order-edit">
    <style>
        .order-edit { --oe-red:#851b1b; --oe-ink:#282d38; --oe-muted:#737d8d; --oe-line:#e5e9ef; width:100%; max-width:1380px; min-width:0; margin:0 auto; padding:clamp(8px,2vw,24px); color:var(--oe-ink); }
        .order-edit, .order-edit * { box-sizing:border-box; }
        .oe-hero { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:14px; padding:20px 24px; color:#fff; background:linear-gradient(120deg,#5e1115,#8c1d20 65%,#a43b32); border-radius:16px; box-shadow:0 10px 24px rgba(94,17,21,.13); }
        .oe-eyebrow { margin:0 0 6px; color:#ffd6cc; font-size:11px; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
        .oe-hero h1 { margin:0; color:#fff; font-size:clamp(22px,3vw,32px); line-height:1.2; font-weight:800; overflow-wrap:anywhere; }
        .oe-client-line { display:flex; align-items:center; gap:8px; margin:9px 0 0; color:rgba(255,255,255,.84); font-size:13px; }
        .oe-client-line span { color:#ffd6cc; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .oe-client-line strong { color:#fff; font-weight:700; }
        .oe-order-chip { flex:none; padding:9px 13px; border:1px solid rgba(255,255,255,.26); border-radius:10px; background:rgba(255,255,255,.11); font-size:12px; font-weight:750; }
        .oe-count { flex:none; padding:7px 10px; border:1px solid var(--oe-line); border-radius:8px; color:var(--oe-muted); background:#f7f8fa; font-size:11px; font-weight:750; }
        .oe-section { min-width:0; margin-bottom:12px; padding:17px 18px; border:1px solid var(--oe-line); border-radius:14px; background:#fff; box-shadow:0 6px 18px rgba(28,38,55,.035); }
        .oe-section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:16px; }
        .oe-section-title { display:flex; align-items:center; gap:10px; margin:0; font-size:17px; font-weight:800; }
        .oe-step { display:inline-grid; place-items:center; width:28px; height:28px; flex:none; border-radius:8px; background:#f8eded; color:var(--oe-red); font-size:12px; }
        .oe-section-help { margin:4px 0 0 38px; color:var(--oe-muted); font-size:13px; line-height:1.5; }
        .oe-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .oe-field { min-width:0; }
        .oe-field-wide { grid-column:1/-1; }
        .oe-field label { display:block; margin:0 0 6px; color:#454c59; font-size:12px; font-weight:750; }
        .oe-field label small { color:var(--oe-muted); font-size:11px; font-weight:500; }
        .oe-field .form-control { width:100%; min-width:0; min-height:44px; border:1px solid #d9dee7; border-radius:9px; box-shadow:none; }
        .oe-field textarea.form-control { min-height:82px; resize:vertical; }
        .oe-field .form-control:focus { border-color:#a94c4c; box-shadow:0 0 0 3px rgba(133,27,27,.09); }
        .oe-help { display:block; margin-top:5px; color:var(--oe-muted); font-size:11px; line-height:1.45; }
        .oe-validation { display:block; margin-top:5px; color:#b42318; font-size:12px; }
        .oe-search { position:relative; }
        .oe-search input { padding-left:40px; }
        .oe-search-icon { position:absolute; top:50%; left:14px; color:#929baa; transform:translateY(-50%); pointer-events:none; }
        .oe-results { margin-top:14px; overflow:hidden; border:1px solid var(--oe-line); border-radius:11px; }
        .oe-results-head { padding:11px 14px; background:#f7f8fa; font-size:13px; font-weight:800; }
        .oe-table-scroll { width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; overscroll-behavior-inline:contain; }
        .oe-table { width:100%; min-width:660px; margin:0; border-collapse:collapse; }
        .oe-table th { padding:11px 12px; color:#687386; background:#f7f8fa; font-size:10px; font-weight:800; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
        .oe-table td { padding:10px 12px; border-top:1px solid #edf0f4; vertical-align:middle; }
        .oe-table tbody tr:hover { background:#fcf8f8; }
        .oe-product-name { min-width:180px; font-weight:750; }
        .oe-price-input { width:125px; min-width:100px; }
        .oe-quantity { display:flex; align-items:center; width:max-content; }
        .oe-quantity .btn { width:38px; height:40px; padding:0; border-color:#d9dee7; font-size:18px; }
        .oe-quantity .btn:first-child { border-radius:8px 0 0 8px; }
        .oe-quantity .btn:last-child { border-radius:0 8px 8px 0; }
        .oe-quantity input { width:64px; min-width:54px; height:40px; padding:4px; border-radius:0; text-align:center; }
        .oe-action-btn { min-height:38px; padding:7px 11px; border-radius:8px; font-size:12px; font-weight:700; white-space:nowrap; }
        .oe-btn-add { color:#176b43; border:1px solid #bce3cb; background:#effaf3; }
        .oe-btn-remove { color:#941b1b; border:1px solid #edcaca; background:#fff6f6; }
        .oe-empty { padding:24px 16px !important; color:var(--oe-muted); text-align:center; }
        .oe-items-error { margin:0 0 12px; padding:10px 12px; border:1px solid #f0c7c7; border-radius:8px; color:#a11c1c; background:#fff6f6; font-size:13px; }
        .oe-summary { display:grid; grid-template-columns:minmax(0,1fr) minmax(250px,.7fr); gap:18px; align-items:stretch; }
        .oe-summary-lines { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 20px; align-content:start; }
        .oe-summary-lines h3 { grid-column:1/-1; margin:0 0 6px; font-size:14px; font-weight:800; }
        .oe-summary-row { display:flex; justify-content:space-between; gap:12px; padding:10px 0; border-bottom:1px solid #edf0f4; color:var(--oe-muted); font-size:13px; }
        .oe-summary-row strong { color:var(--oe-ink); white-space:nowrap; }
        .oe-total-due { display:flex; flex-direction:column; justify-content:center; padding:17px; border:1px solid #ead2d2; border-radius:12px; background:linear-gradient(145deg,#fff8f8,#fff 90%); }
        .oe-total-due span { color:var(--oe-muted); font-size:13px; font-weight:700; }
        .oe-total-due strong { margin-top:7px; color:var(--oe-red); font-size:clamp(25px,3vw,34px); line-height:1.1; }
        .oe-feedback { margin:14px 0 0; padding:11px 14px; border:1px solid #bce3cb; border-radius:9px; color:#176b43; background:#effaf3; }
        .oe-footer { display:flex; justify-content:flex-end; padding-top:16px; border-top:1px solid var(--oe-line); }
        .oe-save { min-height:46px; padding:10px 18px; border:1px solid var(--oe-red); border-radius:9px; color:#fff; background:var(--oe-red); font-weight:750; }
        .oe-save:hover { color:#fff; background:#6f1515; }
        .oe-save:disabled { opacity:.7; cursor:wait; }
        @media (max-width: 900px) {
            .oe-summary { grid-template-columns:1fr; }
            .oe-total-due { min-height:120px; }
        }
        @media (max-width: 600px) {
            .order-edit { padding:8px; }
            .oe-hero { align-items:flex-start; flex-direction:column; gap:12px; margin-bottom:12px; padding:17px; border-radius:13px; }
            .oe-order-chip { align-self:flex-start; }
            .oe-section { margin-bottom:10px; padding:13px 11px; border-radius:11px; }
            .oe-section-head { flex-wrap:wrap; margin-bottom:11px; }
            .oe-section-title { font-size:15px; }
            .oe-section-help { margin-left:38px; font-size:12px; }
            .oe-fields { grid-template-columns:1fr; gap:11px; }
            .oe-field-wide { grid-column:auto; }
            .oe-summary-lines { grid-template-columns:1fr; }
            .oe-summary-lines h3 { grid-column:auto; }
            .oe-total-due { min-height:0; padding:14px; }
            .oe-footer { padding-top:12px; }
            .oe-save { width:100%; }
        }
        @media (max-width: 380px) {
            .oe-table { min-width:620px; }
            .oe-quantity .btn { width:34px; }
            .oe-quantity input { width:54px; }
        }
    </style>

    <header class="oe-hero">
        <div>
            <p class="oe-eyebrow">Gestion des commandes · Modification</p>
            <h1>Commande {{ $order->code }}</h1>
            <p class="oe-client-line"><span>Cliente</span><strong>{{ $order->costumer->name }}</strong></p>
        </div>
        <span class="oe-order-chip">{{ $order->orderItems->count() }} article(s) dans la commande</span>
    </header>

    <section class="oe-section">
        <div class="oe-section-head">
            <div>
                <h2 class="oe-section-title"><span class="oe-step">01</span> Livraison et tarifs</h2>
                <p class="oe-section-help">Ces informations concernent uniquement cette commande.</p>
            </div>
        </div>

        <div class="oe-fields">
            <div class="oe-field oe-field-wide">
                <label for="edit-delivery-address">Adresse de livraison</label>
                <textarea id="edit-delivery-address" class="form-control @error('deliveryAddress') is-invalid @enderror" rows="2" wire:model.live="deliveryAddress" maxlength="1000" required></textarea>
                @error('deliveryAddress') <span class="oe-validation">{{ $message }}</span> @enderror
            </div>

            <div class="oe-field">
                <label for="edit-delivery-phone">Téléphone à appeler <small>Facultatif</small></label>
                <input id="edit-delivery-phone" type="tel" class="form-control @error('deliveryPhone') is-invalid @enderror" wire:model.live="deliveryPhone" maxlength="50" placeholder="Laisser vide pour appeler {{ $order->costumer?->phone ?: 'le numéro du client' }}">
                <small class="oe-help">Un autre numéro peut être indiqué pour cette livraison sans modifier la fiche cliente.</small>
                @error('deliveryPhone') <span class="oe-validation">{{ $message }}</span> @enderror
            </div>

            <div class="oe-field">
                <label for="edit-delivery-fee">Frais de livraison (F CFA)</label>
                <input id="edit-delivery-fee" type="number" min="0" step="1" class="form-control @error('tax') is-invalid @enderror" wire:model.live="tax">
                @error('tax') <span class="oe-validation">{{ $message }}</span> @enderror
            </div>

            <div class="oe-field">
                <label for="edit-discount">Remise (%)</label>
                <input id="edit-discount" type="number" min="0" max="100" step="0.01" class="form-control @error('remis') is-invalid @enderror" wire:model.live="remis">
                @error('remis') <span class="oe-validation">{{ $message }}</span> @enderror
            </div>
        </div>
    </section>

    <section class="oe-section">
        <div class="oe-section-head">
            <div>
                <h2 class="oe-section-title"><span class="oe-step">02</span> Ajouter des produits</h2>
                <p class="oe-section-help">Recherche un article puis ajoute-le à la commande.</p>
            </div>
        </div>

        <div class="oe-field oe-search">
            <label for="edit-product-search">Rechercher un produit</label>
            <span class="oe-search-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <input id="edit-product-search" type="search" class="form-control" placeholder="Saisir le nom du produit…" wire:model.debounce.300ms="search">
        </div>

        @if (count($products))
            <div class="oe-results">
                <div class="oe-results-head">Produits trouvés</div>
                <div class="oe-table-scroll">
                    <table class="oe-table">
                        <thead>
                            <tr><th>Produit</th><th>Prix</th><th>Stock</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr wire:key="edit-search-product-{{ $product->id }}">
                                    <td class="oe-product-name">{{ $product->name }}</td>
                                    <td>{{ number_format($product->price) }} F CFA</td>
                                    <td>{{ $product->qt_initial }}</td>
                                    <td><button type="button" class="oe-action-btn oe-btn-add" wire:click="addProduct({{ $product->id }})">+ Ajouter</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

    <section class="oe-section">
        <div class="oe-section-head">
            <div>
                <h2 class="oe-section-title"><span class="oe-step">03</span> Articles de la commande</h2>
                <p class="oe-section-help">Ajuste les prix et les quantités, ou retire un article.</p>
            </div>
            <span class="oe-count">{{ count($items) }} article(s)</span>
        </div>

        @error('items') <div class="oe-items-error">{{ $message }}</div> @enderror
        <div class="oe-table-scroll">
            <table class="oe-table">
                <thead>
                    <tr><th>#</th><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Total</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        <tr wire:key="edit-order-item-{{ $item['id'] ?? $item['product_id'] }}-{{ $index }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="oe-product-name">{{ $item['name'] }}</td>
                            <td><input aria-label="Prix unitaire de {{ $item['name'] }}" type="number" min="0" step="1" class="form-control oe-price-input" wire:model.live="items.{{ $index }}.price"></td>
                            <td>
                                <div class="oe-quantity">
                                    <button type="button" class="btn btn-light" aria-label="Diminuer la quantité" wire:click="decrement({{ $index }})">−</button>
                                    <input aria-label="Quantité de {{ $item['name'] }}" type="number" class="form-control" min="1" step="1" wire:model.live="items.{{ $index }}.quantity">
                                    <button type="button" class="btn btn-light" aria-label="Augmenter la quantité" wire:click="increment({{ $index }})">+</button>
                                </div>
                            </td>
                            <td><strong>{{ number_format($item['price'] * $item['quantity']) }} F CFA</strong></td>
                            <td><button type="button" class="oe-action-btn oe-btn-remove" wire:click="remove({{ $index }})">Supprimer</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="oe-empty">Aucun produit dans cette commande. Utilise la recherche ci-dessus pour en ajouter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="oe-section">
        <div class="oe-summary">
            <div class="oe-summary-lines">
                <h3>Récapitulatif des montants</h3>
                <div class="oe-summary-row"><span>Sous-total</span><strong>{{ number_format($subtotal) }} F CFA</strong></div>
                <div class="oe-summary-row"><span>Remise</span><strong>{{ $remis }} %</strong></div>
                <div class="oe-summary-row"><span>Total des articles après remise</span><strong>{{ number_format($total) }} F CFA</strong></div>
                <div class="oe-summary-row"><span>Frais de livraison</span><strong>{{ number_format($tax) }} F CFA</strong></div>
            </div>
            <div class="oe-total-due"><span>Montant total à payer</span><strong>{{ number_format($montant) }} F CFA</strong></div>
        </div>

        @if (session()->has('success'))
            <div class="oe-feedback" role="status">{{ session('success') }}</div>
        @endif

        <div class="oe-footer">
            <button type="button" class="oe-save" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Enregistrer les modifications</span>
                <span wire:loading wire:target="save">Enregistrement en cours…</span>
            </button>
        </div>
    </section>
</div>
