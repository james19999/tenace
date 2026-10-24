<div class="col-12 product-catalog-page">
    @php
        $isAdmin = Auth::user()->isAdmin();
        $isPartner = $isPartner ?? false;
    @endphp

    <div class="catalog-header">
        <div>
            <span class="catalog-eyebrow">GESTION DU CATALOGUE</span>
            <h1>Produits</h1>
            <p>{{ $isPartner ? 'Consulte les produits proposés et leurs prix dans ' . ($countryNames[$partnerCountryCode] ?? 'ton pays') . '.' : 'Retrouve rapidement un produit, vérifie son stock et ajoute-le à une commande.' }}</p>
        </div>
        @if ($isAdmin)
            <div class="catalog-header-actions">
                <a href="{{ route('getProductOrders') }}" class="catalog-report-link">
                    <span class="material-icons">insights</span>
                    <span>Rapport des ventes <small>Produits les plus vendus ce mois</small></span>
                </a>
                <a href="{{ route('productform') }}" class="btn catalog-add-button">
                    <span class="material-icons">add</span> Ajouter un produit
                </a>
            </div>
        @endif
    </div>

    @if ($cartMessage)
        <div class="catalog-alert {{ $cartMessageIsError ? 'catalog-alert-error' : '' }}" role="status"><span class="material-icons">{{ $cartMessageIsError ? 'error_outline' : 'check_circle' }}</span>{{ $cartMessage }}</div>
    @endif
    @if (Session::has('messages'))
        <div class="catalog-alert" role="status"><span class="material-icons">check_circle</span>{{ session('messages') }}</div>
    @endif

    @if (!$isPartner)<section class="catalog-metrics" aria-label="Résumé du stock">
        <article class="catalog-metric">
            <span class="catalog-metric-icon"><span class="material-icons">inventory_2</span></span>
            <div><small>Produits référencés</small><strong>{{ number_format((int) $inventory->total_products, 0, ',', ' ') }}</strong></div>
        </article>
        <article class="catalog-metric catalog-metric-good">
            <span class="catalog-metric-icon"><span class="material-icons">task_alt</span></span>
            <div><small>Stock disponible</small><strong>{{ number_format((int) $inventory->available_count, 0, ',', ' ') }}</strong></div>
        </article>
        <article class="catalog-metric catalog-metric-warning">
            <span class="catalog-metric-icon"><span class="material-icons">warning_amber</span></span>
            <div><small>À réapprovisionner</small><strong>{{ number_format((int) $inventory->low_stock_count + (int) $inventory->out_of_stock_count, 0, ',', ' ') }}</strong></div>
        </article>
        @if ($isAdmin)
            <article class="catalog-metric catalog-metric-finance">
                <span class="catalog-metric-icon"><span class="material-icons">account_balance_wallet</span></span>
                <div><small>Valeur d’achat du stock</small><strong>{{ number_format((float) $inventory->purchase_stock_value, 0, ',', ' ') }} F</strong></div>
            </article>
            <article class="catalog-metric catalog-metric-finance">
                <span class="catalog-metric-icon"><span class="material-icons">trending_up</span></span>
                <div><small>Valeur potentielle de vente</small><strong>{{ number_format((float) $inventory->retail_stock_value, 0, ',', ' ') }} F</strong></div>
            </article>
        @endif
    </section>@endif

    <section class="catalog-panel">
        <div class="catalog-toolbar">
            <div>
                <h2>Catalogue</h2>
                <p>{{ $Products->total() }} produit(s) correspondent à ta sélection</p>
            </div>
            <div class="catalog-filters">
                <label class="catalog-search">
                    <span class="material-icons">search</span>
                    <input type="search" wire:model.debounce.300ms="search" placeholder="Rechercher un produit…" aria-label="Rechercher un produit">
                    <span wire:loading wire:target="search" class="spinner-border spinner-border-sm" role="status"></span>
                </label>
                @unless ($isPartner)<select wire:model="stockFilter" class="catalog-stock-filter" aria-label="Filtrer par niveau de stock">
                    <option value="all">Tous les stocks</option>
                    <option value="available">Disponibles</option>
                    <option value="low">Stock faible</option>
                    <option value="out">En rupture</option>
                </select>@endunless
            </div>
        </div>

        @if ($Products->count())
            <div class="catalog-grid">
                @foreach ($Products as $product)
                    @php
                        $stock = (int) ($product->shop_quantity ?? 0);
                        $wholesaleStock = (int) ($product->wholesale_shop_quantity ?? 0);
                        $combinedStock = $stock + $wholesaleStock;
                        $partnerStock = $partnerStockSummary[$product->id] ?? ['reserve' => 0, 'couriers' => 0, 'available' => 0];
                        $isOut = ! $isPartner && $stock <= 0 && $wholesaleStock <= 0;
                        $isLow = ! $isPartner && ! $isOut && $product->qts_seuil !== null && $combinedStock <= (int) $product->qts_seuil;
                    @endphp
                    <article class="product-card">
                        <div class="product-card-top">
                            <div class="product-image">
                                @if ($product->img)
                                    <img src="{{ url('image/' . $product->img) }}" alt="{{ $product->name }}" loading="lazy" onerror="this.remove()">
                                @endif
                                <span class="material-icons product-image-fallback">inventory_2</span>
                            </div>
                            @if ($isPartner)
                                <span class="stock-badge {{ $partnerStock['available'] > 0 ? 'stock-available' : 'stock-out' }}"><span></span>{{ $partnerStock['available'] > 0 ? 'Disponible' : 'Rupture de stock' }} · {{ $countryNames[$partnerCountryCode] ?? $partnerCountryCode }}</span>
                            @elseif ($isOut)
                                <span class="stock-badge stock-out"><span></span>Rupture</span>
                            @elseif ($isLow)
                                <span class="stock-badge stock-low"><span></span>Stock faible</span>
                            @else
                                <span class="stock-badge stock-available"><span></span>{{ $stock > 0 ? 'Boutique principale · '.$stock : 'Boutique grossiste · '.$wholesaleStock }}</span>
                            @endif
                        </div>

                        <div class="product-card-content">
                            <div class="product-title-row">
                                <div>
                                    <span class="product-reference">Réf. {{ $product->id }}</span>
                                    <h3>{{ $product->name }}</h3>
                                </div>
                                @if ($isPartner)
                                    <span class="product-stock-number product-partner-stock-number {{ $partnerStock['available'] < 1 ? 'text-danger' : '' }}" aria-label="{{ $partnerStock['available'] }} unité(s) disponibles pour ce partenaire">
                                        {{ number_format($partnerStock['available'], 0, ',', ' ') }}
                                        <small>disponible(s)</small>
                                        <small>Réserve {{ number_format($partnerStock['reserve'], 0, ',', ' ') }} · Livreurs {{ number_format($partnerStock['couriers'], 0, ',', ' ') }}</small>
                                    </span>
                                @else<span class="product-stock-number {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : '') }}">
                                    {{ $stock }} <small>principale</small>
                                    @if($hasWholesaleLocation)<small>Grossiste {{ $wholesaleStock }}</small>@endif
                                </span>@endif
                            </div>

                            @if ($product->description)
                                <p class="product-description">{{ \Illuminate\Support\Str::limit($product->description, 100) }}</p>
                            @endif

                            @if ($isPartner)
                                @php($countryPrice = $product->countryPrices->first())
                                <div class="product-prices product-partner-price"><div><small>Prix de vente · {{ $countryNames[$partnerCountryCode] ?? $partnerCountryCode }}</small><strong>{{ number_format((float) $countryPrice->price, 0, ',', ' ') }} <span>F</span></strong></div></div>
                            @else
                            <div class="product-prices">
                                <div><small>Prix de vente boutique</small><strong>{{ number_format((float) $product->price, 0, ',', ' ') }} <span>F</span></strong></div>
                            </div>
                            @if($product->quantityPriceTiers->isNotEmpty())
                                <div class="product-country-prices"><div class="product-country-prices-heading"><span>Prix selon la quantité</span><small>Tarif appliqué automatiquement</small></div>@foreach($product->quantityPriceTiers as $tier)<div class="product-country-price"><span>Dès {{ $tier->min_quantity }} unités</span><strong>{{ number_format((float) $tier->unit_price, 0, ',', ' ') }} F / unité</strong></div>@endforeach</div>
                            @endif
                            <div class="product-country-prices">
                                <div class="product-country-prices-heading"><span>Prix de vente par pays</span><small>Catalogue partenaire</small></div>
                                @forelse ($product->countryPrices as $countryPrice)
                                    <div class="product-country-price {{ $countryPrice->is_available ? '' : 'is-disabled' }}">
                                        <span>{{ $countryNames[$countryPrice->country_code] ?? $countryPrice->country_code }}</span>
                                        <strong>{{ number_format((float) $countryPrice->price, 0, ',', ' ') }} F</strong>
                                        @unless ($countryPrice->is_available)<small>Inactif</small>@endunless
                                    </div>
                                @empty
                                    <p class="product-country-prices-empty">Aucun prix pays configuré</p>
                                @endforelse
                            </div>
                            @endif
                            @if (!$isPartner)<div class="product-commission"><span>Commission</span><strong>{{ number_format((float) $product->commission_amount, 0, ',', ' ') }} F</strong></div>@endif

                            @if ($isPartner)
                                <div class="product-cart-actions product-partner-cart-action">
                                    @php($partnerCartQuantity = (int) ($partnerCartQuantities[$product->id] ?? 0))
                                    @php($partnerStockReached = $partnerStock['available'] < 1 || $partnerCartQuantity >= $partnerStock['available'])
                                    <button type="button" class="btn product-add-button" wire:click="AddToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="AddToCart({{ $product->id }})" @disabled($partnerStockReached)>
                                        <span class="material-icons">{{ $partnerStockReached ? 'block' : 'add_shopping_cart' }}</span> {{ $partnerStock['available'] < 1 ? 'Stock épuisé' : ($partnerStockReached ? 'Quantité maximale atteinte' : 'Ajouter au panier') }}
                                    </button>
                                </div>
                            @else
                            <div class="product-cart-actions">
                                <button type="button" class="btn product-add-button" wire:click="AddToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="AddToCart({{ $product->id }})" @disabled($isOut)>
                                    <span class="material-icons">add_shopping_cart</span> Ajouter
                                </button>
                            </div>
                            @endif

                            @if ($isAdmin)
                                <div class="product-admin-actions">
                                    <a href="{{ route('show-product', $product) }}"><span class="material-icons">visibility</span> Voir</a>
                                    <a href="{{ route('editproduct', $product) }}"><span class="material-icons">edit</span> Modifier</a>
                                    <form method="POST" action="{{ route('delteproduct', $product) }}" data-product-delete-confirm data-product-name="{{ $product->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"><span class="material-icons">delete</span> Supprimer</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($Products->hasPages())
                <div class="catalog-pagination">
                    <span>Page {{ $Products->currentPage() }} sur {{ $Products->lastPage() }}</span>
                    {{ $Products->onEachSide(1)->links() }}
                </div>
            @endif
        @else
            <div class="catalog-empty">
                <span class="material-icons">search_off</span>
                <h3>Aucun produit trouvé</h3>
                <p>{{ $isPartner ? 'Aucun produit n’est actuellement proposé dans ton pays.' : 'Essaie un autre nom ou modifie le filtre de stock.' }}</p>
                @if ($search || $stockFilter !== 'all')
                    <button class="btn catalog-reset-button" wire:click="resetFilters">Effacer les filtres</button>
                @endif
            </div>
        @endif
    </section>

    @if ($isAdmin)
        <dialog class="product-delete-dialog" id="productDeleteDialog" aria-labelledby="productDeleteTitle" aria-describedby="productDeleteMessage">
            <div class="product-delete-dialog-mark"><span class="material-icons" aria-hidden="true">delete_outline</span><button type="button" data-product-delete-cancel aria-label="Fermer">×</button></div>
            <h2 id="productDeleteTitle">Supprimer ce produit ?</h2>
            <p id="productDeleteMessage"></p>
            <div class="product-delete-dialog-actions"><button type="button" data-product-delete-cancel class="product-delete-cancel">Annuler</button><button type="button" id="productDeleteAccept" class="product-delete-accept"><span class="material-icons">delete</span>Supprimer le produit</button></div>
        </dialog>
        <script>
            (() => {
                const dialog = document.getElementById('productDeleteDialog');
                const accept = document.getElementById('productDeleteAccept');
                if (!dialog || !accept || dialog.dataset.ready) return;
                dialog.dataset.ready = '1';
                let pendingForm = null;
                document.addEventListener('submit', (event) => {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-product-delete-confirm')) return;
                    event.preventDefault();
                    pendingForm = form;
                    dialog.querySelector('#productDeleteMessage').textContent = `Tu vas supprimer « ${form.dataset.productName} ». Cette action est définitive.`;
                    dialog.showModal();
                });
                dialog.querySelectorAll('[data-product-delete-cancel]').forEach(button => button.addEventListener('click', () => dialog.close()));
                dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
                dialog.addEventListener('close', () => { pendingForm = null; });
                accept.addEventListener('click', () => {
                    if (!pendingForm) return;
                    const form = pendingForm;
                    pendingForm = null;
                    form.removeAttribute('data-product-delete-confirm');
                    dialog.close();
                    form.requestSubmit();
                });
            })();
        </script>
    @endif

    <style>
        .product-catalog-page{--catalog-burgundy:var(--app-primary, #7e1615);--catalog-ink:#272b35;--catalog-muted:#78808e;--catalog-line:#e8e9ed;--catalog-bg:#f7f7f9;min-width:0;padding-top:14px;padding-bottom:28px;color:var(--catalog-ink)}
        .catalog-header{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin:0 0 22px}
        .catalog-eyebrow{display:block;color:var(--catalog-burgundy);font-size:11px;font-weight:800;letter-spacing:1.5px;margin-bottom:7px}
        .catalog-header h1{font-size:30px;font-weight:800;letter-spacing:-.6px;margin:0 0 5px;color:var(--catalog-ink)}
        .catalog-header p,.catalog-toolbar p{color:var(--catalog-muted);margin:0;font-size:14px}
        .catalog-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
        .catalog-report-link{display:flex;align-items:center;gap:10px;padding:10px 13px;border:1px solid var(--catalog-line);border-radius:11px;color:var(--catalog-ink);text-decoration:none;background:#fff;font-size:13px;font-weight:700}
        .catalog-report-link>.material-icons{font-size:22px;color:var(--catalog-burgundy)}
        .catalog-report-link small{display:block;color:var(--catalog-muted);font-weight:500;margin-top:2px}
        .catalog-add-button{display:flex;align-items:center;gap:6px;padding:11px 15px;border-radius:10px;background:var(--catalog-burgundy);color:#fff!important;font-weight:700;white-space:nowrap}
        .catalog-add-button:hover{background:#63110f}
        .catalog-alert{display:flex;align-items:center;gap:9px;margin-bottom:16px;padding:12px 15px;border:1px solid #bde7d0;border-radius:10px;background:#f0fbf5;color:#216b44;font-weight:600}
        .catalog-alert-error{border-color:#f0c4c4;background:#fff5f5;color:#a32a2a}
        .catalog-alert .material-icons{font-size:20px}
        .catalog-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}
        .catalog-metric{display:flex;align-items:center;gap:13px;min-width:0;padding:15px 16px;border:1px solid var(--catalog-line);border-radius:13px;background:#fff;box-shadow:0 3px 12px rgba(27,31,41,.035)}
        .catalog-metric-icon{display:grid;place-items:center;width:42px;height:42px;flex:0 0 42px;border-radius:12px;background:#f6e9e8;color:var(--catalog-burgundy)}
        .catalog-metric-icon .material-icons{font-size:22px}
        .catalog-metric small{display:block;color:var(--catalog-muted);font-size:12px;margin-bottom:3px}
        .catalog-metric strong{display:block;overflow-wrap:anywhere;font-size:19px;line-height:1.25;font-weight:800;color:var(--catalog-ink)}
        .catalog-metric-good .catalog-metric-icon{background:#e8f5ee;color:#238252}
        .catalog-metric-warning .catalog-metric-icon{background:#fff4df;color:#ad7411}
        .catalog-metric-finance{grid-column:span 1}
        .catalog-panel{padding:20px;border:1px solid var(--catalog-line);border-radius:15px;background:#fff;box-shadow:0 5px 20px rgba(27,31,41,.045)}
        .catalog-toolbar{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:18px}
        .catalog-toolbar h2{margin:0 0 4px;font-size:19px;font-weight:800}
        .catalog-toolbar p{font-size:12px}
        .catalog-filters{display:flex;gap:9px;width:min(100%,560px)}
        .catalog-search{display:flex;align-items:center;gap:8px;min-width:0;flex:1;margin:0;padding:0 12px;border:1px solid #dfe1e7;border-radius:10px;background:#fff}
        .catalog-search:focus-within{border-color:#ad6c69;box-shadow:0 0 0 3px rgba(126,22,21,.08)}
        .catalog-search>.material-icons{font-size:20px;color:#8b919d}
        .catalog-search input{width:100%;height:42px;min-width:0;border:0;outline:0;background:transparent;color:var(--catalog-ink);font-size:13px}
        .catalog-stock-filter{width:175px;max-width:42%;height:44px;padding:0 11px;border:1px solid #dfe1e7;border-radius:10px;background:#fff;color:var(--catalog-ink);font-size:13px}
        .catalog-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
        .product-card{display:flex;flex-direction:column;min-width:0;border:1px solid var(--catalog-line);border-radius:13px;background:#fff;overflow:hidden;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
        .product-card:hover{transform:translateY(-2px);border-color:#d9c4c3;box-shadow:0 9px 24px rgba(35,28,28,.08)}
        .product-card-top{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 14px 0}
        .product-image{position:relative;display:grid;place-items:center;width:54px;height:54px;flex:0 0 54px;border:1px solid #eee;border-radius:12px;background:#faf8f8;color:#a27c7a;overflow:hidden}
        .product-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:1}
        .product-image-fallback{font-size:25px}
        .stock-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:20px;font-size:10px;font-weight:750;white-space:nowrap}
        .stock-badge>span{width:6px;height:6px;border-radius:50%;background:currentColor}
        .stock-available{color:#26764d;background:#eaf6ef}
        .stock-low{color:#9a6810;background:#fff4dc}
        .stock-out{color:#a12b28;background:#fbeceb}
        .product-card-content{display:flex;flex:1;flex-direction:column;min-width:0;padding:13px 14px 14px}
        .product-title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
        .product-reference{display:block;margin-bottom:3px;color:#9a9eaa;font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
        .product-title-row h3{margin:0;color:var(--catalog-ink);font-size:16px;line-height:1.3;font-weight:800;overflow-wrap:anywhere}
        .product-stock-number{flex:0 0 auto;padding-top:12px;text-align:right;font-weight:800;font-size:14px}
        .product-stock-number small{display:block;color:var(--catalog-muted);font-size:9px;font-weight:500;white-space:nowrap}
        .product-partner-stock-number{max-width:145px;padding-top:2px;color:#26764d;font-size:16px}
        .product-partner-stock-number small{white-space:normal;line-height:1.35}
        .product-description{min-height:34px;margin:8px 0 0;color:var(--catalog-muted);font-size:11px;line-height:1.5}
        .product-prices{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:13px;padding:11px 0;border-top:1px solid #f0f0f2;border-bottom:1px solid #f0f0f2}
        .product-prices small{display:block;color:var(--catalog-muted);font-size:10px;margin-bottom:4px}
        .product-prices strong{font-size:15px;color:var(--catalog-ink);font-weight:800;overflow-wrap:anywhere}
        .product-prices strong span{font-size:11px;color:var(--catalog-muted)}
        .product-country-prices{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px;margin-top:10px}
        .product-country-prices-heading{grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:1px;color:var(--catalog-ink);font-size:11px;font-weight:750}
        .product-country-prices-heading small{color:var(--catalog-muted);font-size:9px;font-weight:500}
        .product-country-price{display:flex;justify-content:space-between;align-items:center;gap:6px;min-width:0;padding:6px 8px;border:1px solid #eee;border-radius:8px;background:#fafafa;font-size:10px}
        .product-country-price>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--catalog-muted)}
        .product-country-price strong{white-space:nowrap;color:var(--catalog-ink);font-size:10px;font-weight:800}
        .product-country-price.is-disabled{opacity:.58}
        .product-country-price>small{color:#9a6810;font-size:9px}
        .product-country-prices-empty{grid-column:1/-1;margin:0;padding:7px 8px;border-radius:8px;background:#f7f7f9;color:var(--catalog-muted);font-size:10px}
        .product-commission{display:flex;justify-content:space-between;gap:8px;padding:8px 0;color:var(--catalog-muted);font-size:11px}
        .product-commission strong{color:#525865;font-weight:700}
        .product-cart-actions{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:7px;margin-top:auto;padding-top:8px}
        .product-cart-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:39px;border-radius:9px;font-size:11px;font-weight:750;white-space:nowrap}
        .product-add-button{background:var(--catalog-burgundy);color:white}
        .product-add-button:hover:not(:disabled){background:#63110f;color:#fff}
        .product-add-button .material-icons{font-size:17px}
        .product-wholesale-button{border:1px solid #dedfe4;background:#fff;color:#424754;padding:0 14px}
        .product-cart-actions .btn:disabled{opacity:.48;cursor:not-allowed}
        .product-admin-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:11px;padding-top:10px;border-top:1px solid #f1f1f3}
        .product-admin-actions a,.product-admin-actions button{display:inline-flex;align-items:center;gap:4px;padding:0;border:0;background:none;color:#747b88;font-size:10px;text-decoration:none}
        .product-admin-actions a:hover,.product-admin-actions button:hover{color:var(--catalog-burgundy)}
        .product-admin-actions .material-icons{font-size:15px}
        .product-delete-dialog{width:min(440px,calc(100vw - 28px));max-width:none;padding:22px;border:1px solid #e3e5ea;border-radius:4px;background:#fff;color:#292d38;box-shadow:0 20px 60px rgba(24,28,38,.25)}
        .product-delete-dialog::backdrop{background:rgba(20,24,31,.56)}
        .product-delete-dialog-mark{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
        .product-delete-dialog-mark>.material-icons{display:grid;width:40px;height:40px;place-items:center;background:#faeded;color:#851b1b}
        .product-delete-dialog-mark button{width:34px;height:34px;border:1px solid #e3e5ea;background:#fff;color:#596170;font-size:22px;line-height:1}
        .product-delete-dialog h2{margin:0 0 8px;font-size:18px;font-weight:850}
        .product-delete-dialog p{margin:0;color:#667080;font-size:13px;line-height:1.6}
        .product-delete-dialog-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:22px}
        .product-delete-dialog-actions button{display:inline-flex;min-height:40px;align-items:center;justify-content:center;gap:6px;padding:8px 13px;border:1px solid #dfe2e8;border-radius:3px;background:#fff;color:#343a46;font-size:12px;font-weight:800;cursor:pointer}
        .product-delete-dialog-actions .product-delete-accept{border-color:#851b1b;background:#851b1b;color:#fff}
        .product-delete-dialog-actions .product-delete-accept:hover{background:#681313}
        .product-delete-dialog-actions .material-icons{font-size:17px}
        @media(max-width:480px){.product-delete-dialog{padding:17px}.product-delete-dialog-actions{display:grid;grid-template-columns:1fr 1fr}.product-delete-dialog-actions button{width:100%}}
        .product-admin-actions form{margin:0}
        .catalog-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:20px;padding-top:15px;border-top:1px solid #f0f0f2}
        .catalog-pagination>span{color:var(--catalog-muted);font-size:12px;white-space:nowrap}
        .catalog-pagination .pagination{flex-wrap:wrap;justify-content:flex-end;margin:0}
        .catalog-empty{display:flex;flex-direction:column;align-items:center;padding:55px 15px;text-align:center}
        .catalog-empty>.material-icons{font-size:42px;color:#aeb3bd}
        .catalog-empty h3{margin:10px 0 5px;font-size:18px;font-weight:800}
        .catalog-empty p{margin:0;color:var(--catalog-muted);font-size:13px}
        .catalog-reset-button{margin-top:15px;border:1px solid #dec6c5;color:var(--catalog-burgundy)}
        @media(max-width:1199.98px){.catalog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:767.98px){
            .product-catalog-page{padding:8px 0 20px}
            .catalog-header{display:block;margin-bottom:16px}
            .catalog-header h1{font-size:25px}
            .catalog-header p{font-size:12px;line-height:1.5}
            .catalog-header-actions{display:grid;grid-template-columns:1fr 1fr;margin-top:13px;gap:8px}
            .catalog-report-link{padding:8px;font-size:11px;line-height:1.3}
            .catalog-report-link small{font-size:9px}
            .catalog-add-button{justify-content:center;padding:9px 8px;font-size:11px;white-space:normal;text-align:center}
            .catalog-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:12px}
            .catalog-metric{gap:8px;padding:11px 9px;border-radius:11px}
            .catalog-metric-icon{width:34px;height:34px;flex-basis:34px;border-radius:10px}
            .catalog-metric-icon .material-icons{font-size:18px}
            .catalog-metric small{font-size:9px;line-height:1.25}
            .catalog-metric strong{font-size:14px}
            .catalog-metric-finance{grid-column:span 1}
            .catalog-panel{padding:13px 10px;border-radius:12px}
            .catalog-toolbar{display:block;margin-bottom:13px}
            .catalog-toolbar h2{font-size:17px}
            .catalog-toolbar p{font-size:10px}
            .catalog-filters{display:grid;grid-template-columns:minmax(0,1fr) 130px;width:100%;gap:7px;margin-top:11px}
            .catalog-search{padding:0 8px;gap:5px}
            .catalog-search input{height:40px;font-size:11px}
            .catalog-search>.material-icons{font-size:17px}
            .catalog-stock-filter{width:100%;max-width:none;height:42px;padding:0 6px;font-size:10px}
            .catalog-grid{grid-template-columns:1fr;gap:10px}
            .product-card-top{padding:10px 11px 0}
            .product-image{width:44px;height:44px;flex-basis:44px}
            .product-card-content{padding:10px 11px 11px}
            .product-title-row h3{font-size:14px}
            .product-description{min-height:0;font-size:10px}
            .product-prices{margin-top:10px;padding:9px 0}
            .product-prices strong{font-size:13px}
            .product-admin-actions{gap:10px}
            .catalog-pagination{display:flex;flex-direction:column;align-items:center;gap:8px}
            .catalog-pagination .pagination{justify-content:center;max-width:100%}
            .catalog-pagination .page-item:not(:first-child):not(:last-child):not(.active){display:none}
        }
        @media(max-width:359.98px){.catalog-filters{grid-template-columns:1fr}.catalog-stock-filter{height:40px}.catalog-header-actions{grid-template-columns:1fr}.catalog-metric{padding:9px 7px}.catalog-metric strong{font-size:12px}}

        /* Catalogue refresh: straight edges, clear spacing, restrained surfaces. */
        .product-catalog-page{padding-top:8px;color:#292d38}
        .catalog-header{align-items:center;gap:24px;margin-bottom:20px;padding:20px 22px;border:1px solid #e1e4e9;background:#fff}
        .catalog-header>div:first-child{min-width:0}
        .catalog-header h1{font-size:28px;line-height:1.2}
        .catalog-header p{margin-top:7px;line-height:1.55}
        .catalog-header-actions{gap:8px}
        .catalog-report-link,.catalog-add-button,.catalog-alert,.catalog-metric,.catalog-metric-icon,.catalog-panel,.catalog-search,.catalog-stock-filter,.product-card,.product-image,.stock-badge,.product-cart-actions .btn,.catalog-reset-button,.catalog-pagination .page-link{border-radius:0!important}
        .catalog-report-link{min-height:48px;padding:8px 12px;box-shadow:none}
        .catalog-add-button{min-height:44px;padding:0 14px}
        .catalog-alert{border-left:3px solid #238252}
        .catalog-metrics{gap:10px;margin-bottom:16px}
        .catalog-metric{min-height:82px;padding:13px 15px;box-shadow:none}
        .catalog-metric-icon{width:38px;height:38px;flex-basis:38px}
        .catalog-metric strong{font-size:18px}
        .catalog-panel{padding:0;border:1px solid #e1e4e9;box-shadow:none}
        .catalog-toolbar{margin:0;padding:17px 19px;border-bottom:1px solid #e1e4e9}
        .catalog-toolbar h2{font-size:18px}
        .catalog-filters{max-width:580px}
        .catalog-search,.catalog-stock-filter{border-radius:0!important}
        .catalog-search:focus-within{box-shadow:inset 0 0 0 1px #ad6c69}
        .catalog-grid{padding:16px;gap:12px}
        .product-card{box-shadow:none;transition:border-color .15s ease}
        .product-card:hover{transform:none;border-color:#b8a2a1;box-shadow:none}
        .product-card-top{padding:14px 15px 0}
        .product-image{width:52px;height:52px;flex-basis:52px}
        .stock-badge{padding:6px 8px}
        .stock-badge>span{border-radius:0}
        .product-card-content{padding:13px 15px 15px}
        .product-title-row{gap:12px}
        .product-title-row h3{line-height:1.4}
        .product-prices{gap:12px;margin-top:14px;padding:12px 0}
        .product-cart-actions{grid-template-columns:minmax(0,1fr) minmax(0,auto);gap:8px}
        .product-cart-actions .btn{min-height:40px;padding:0 11px}
        .product-admin-actions{gap:7px}
        .product-admin-actions a,.product-admin-actions button{min-height:32px;padding:0 8px;border:1px solid #dfe1e7;background:#fff}
        .product-admin-actions a:hover,.product-admin-actions button:hover{border-color:#b98987;background:#faf5f5}
        .catalog-pagination{padding:14px 18px 18px}
        .catalog-pagination .page-link{border-radius:0!important;color:#555d6b}
        .catalog-pagination .page-item.active .page-link{border-color:var(--app-primary, #7e1615);background:var(--app-primary, #7e1615);color:#fff}
        .catalog-empty{padding:48px 18px}
        @media(max-width:767.98px){
            .catalog-header{padding:15px 14px}
            .catalog-header h1{font-size:24px}
            .catalog-header-actions{grid-template-columns:1fr 1fr}
            .catalog-metric{min-height:72px;padding:10px 9px}
            .catalog-panel{padding:0}
            .catalog-toolbar{padding:14px 12px}
            .catalog-grid{padding:10px;gap:10px}
            .product-card-top{padding:11px 12px 0}
            .product-card-content{padding:10px 12px 12px}
            .product-cart-actions{grid-template-columns:minmax(0,1fr) auto}
            .product-cart-actions .btn{white-space:normal;line-height:1.25}
            .catalog-pagination{padding:12px}
        }
        html[data-app-theme="dark"] .product-catalog-page { --catalog-ink:#edf1f5; --catalog-muted:#a8b1bd; --catalog-line:#373f4b; --catalog-bg:#14171c; color:#edf1f5 !important; }
        html[data-app-theme="dark"] .catalog-header,
        html[data-app-theme="dark"] .catalog-panel,
        html[data-app-theme="dark"] .catalog-metric,
        html[data-app-theme="dark"] .product-card { background:#1e232b !important; border-color:#373f4b !important; color:#edf1f5 !important; }
        html[data-app-theme="dark"] .catalog-header h1,
        html[data-app-theme="dark"] .catalog-toolbar h2,
        html[data-app-theme="dark"] .product-title-row h3,
        html[data-app-theme="dark"] .catalog-metric strong,
        html[data-app-theme="dark"] .product-prices strong,
        html[data-app-theme="dark"] .product-commission strong,
        html[data-app-theme="dark"] .catalog-empty h3 { color:#edf1f5 !important; }
        html[data-app-theme="dark"] .product-partner-stock-number:not(.text-danger){color:#75d6a2 !important}
        html[data-app-theme="dark"] .catalog-header p,
        html[data-app-theme="dark"] .catalog-toolbar p,
        html[data-app-theme="dark"] .catalog-metric small,
        html[data-app-theme="dark"] .product-reference,
        html[data-app-theme="dark"] .product-stock-number small,
        html[data-app-theme="dark"] .product-description,
        html[data-app-theme="dark"] .product-prices small,
        html[data-app-theme="dark"] .product-prices strong span,
        html[data-app-theme="dark"] .product-commission,
        html[data-app-theme="dark"] .catalog-pagination > span,
        html[data-app-theme="dark"] .catalog-empty p { color:#a8b1bd !important; }
        html[data-app-theme="dark"] .catalog-report-link,
        html[data-app-theme="dark"] .catalog-search,
        html[data-app-theme="dark"] .catalog-stock-filter,
        html[data-app-theme="dark"] .product-wholesale-button,
        html[data-app-theme="dark"] .product-admin-actions a,
        html[data-app-theme="dark"] .product-admin-actions button,
        html[data-app-theme="dark"] .catalog-pagination .page-link { background:#252b34 !important; border-color:#414a57 !important; color:#edf1f5 !important; }
        html[data-app-theme="dark"] .catalog-report-link small { color:#a8b1bd !important; }
        html[data-app-theme="dark"] .catalog-search input,
        html[data-app-theme="dark"] .catalog-stock-filter { color:#edf1f5 !important; }
        html[data-app-theme="dark"] .catalog-search input::placeholder { color:#929dab !important; }
        html[data-app-theme="dark"] .catalog-metric-icon,
        html[data-app-theme="dark"] .product-image { background:#252b34 !important; border-color:#414a57 !important; color:#c5cbd4 !important; }
        html[data-app-theme="dark"] .catalog-metric-good .catalog-metric-icon { background:#173b2a !important; color:#75d6a2 !important; }
        html[data-app-theme="dark"] .catalog-metric-warning .catalog-metric-icon { background:#423414 !important; color:#f1c66c !important; }
        html[data-app-theme="dark"] .stock-available { background:#173b2a !important; color:#75d6a2 !important; }
        html[data-app-theme="dark"] .stock-low { background:#423414 !important; color:#f1c66c !important; }
        html[data-app-theme="dark"] .stock-out { background:#482525 !important; color:#ff9994 !important; }
        html[data-app-theme="dark"] .product-add-button { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .product-add-button:hover:not(:disabled) { background:var(--app-primary-hover) !important; }
        html[data-app-theme="dark"] .product-admin-actions { border-color:#373f4b !important; }
        html[data-app-theme="dark"] .product-prices { border-color:#373f4b !important; }
        html[data-app-theme="dark"] .catalog-pagination { border-color:#373f4b !important; }
        html[data-app-theme="dark"] .catalog-alert { background:#173b2a !important; border-color:#285b40 !important; color:#a7e4bf !important; }
        html[data-app-theme="dark"] .catalog-pagination .page-item.active .page-link { background:var(--app-primary) !important; border-color:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .product-delete-dialog { background:#1e232b !important; border-color:#373f4b !important; color:#edf1f5 !important; }
        html[data-app-theme="dark"] .product-delete-dialog p { color:#a8b1bd !important; }
        html[data-app-theme="dark"] .product-delete-dialog-mark button,
        html[data-app-theme="dark"] .product-delete-dialog-actions .product-delete-cancel { background:#252b34 !important; border-color:#414a57 !important; color:#edf1f5 !important; }
    </style>
</div>
