<div class="col-12 product-catalog-page">
    @php
        $isAdmin = Auth::user()->isAdmin();
    @endphp

    <div class="catalog-header">
        <div>
            <span class="catalog-eyebrow">GESTION DU CATALOGUE</span>
            <h1>Produits</h1>
            <p>Retrouve rapidement un produit, vérifie son stock et ajoute-le à une commande.</p>
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
        <div class="catalog-alert" role="status"><span class="material-icons">check_circle</span>{{ $cartMessage }}</div>
    @endif
    @if (Session::has('messages'))
        <div class="catalog-alert" role="status"><span class="material-icons">check_circle</span>{{ session('messages') }}</div>
    @endif

    <section class="catalog-metrics" aria-label="Résumé du stock">
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
    </section>

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
                <select wire:model="stockFilter" class="catalog-stock-filter" aria-label="Filtrer par niveau de stock">
                    <option value="all">Tous les stocks</option>
                    <option value="available">Disponibles</option>
                    <option value="low">Stock faible</option>
                    <option value="out">En rupture</option>
                </select>
            </div>
        </div>

        @if ($Products->count())
            <div class="catalog-grid">
                @foreach ($Products as $product)
                    @php
                        $stock = (int) ($product->qt_initial ?? 0);
                        $isOut = $product->qt_initial === null || $stock <= 0;
                        $isLow = ! $isOut && $product->qts_seuil !== null && $stock <= (int) $product->qts_seuil;
                    @endphp
                    <article class="product-card">
                        <div class="product-card-top">
                            <div class="product-image">
                                @if ($product->img)
                                    <img src="{{ url('image/' . $product->img) }}" alt="{{ $product->name }}" loading="lazy" onerror="this.remove()">
                                @endif
                                <span class="material-icons product-image-fallback">inventory_2</span>
                            </div>
                            @if ($isOut)
                                <span class="stock-badge stock-out"><span></span>Rupture</span>
                            @elseif ($isLow)
                                <span class="stock-badge stock-low"><span></span>Stock faible</span>
                            @else
                                <span class="stock-badge stock-available"><span></span>Disponible</span>
                            @endif
                        </div>

                        <div class="product-card-content">
                            <div class="product-title-row">
                                <div>
                                    <span class="product-reference">Réf. {{ $product->id }}</span>
                                    <h3>{{ $product->name }}</h3>
                                </div>
                                <span class="product-stock-number {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : '') }}">
                                    {{ $stock }} <small>en stock</small>
                                </span>
                            </div>

                            @if ($product->description)
                                <p class="product-description">{{ \Illuminate\Support\Str::limit($product->description, 100) }}</p>
                            @endif

                            <div class="product-prices">
                                <div><small>Prix de vente</small><strong>{{ number_format((float) $product->price, 0, ',', ' ') }} <span>F</span></strong></div>
                                <div><small>Prix en gros</small><strong>{{ $product->high_price ? number_format((float) $product->high_price, 0, ',', ' ') . ' F' : 'Non défini' }}</strong></div>
                            </div>
                            <div class="product-commission"><span>Commission</span><strong>{{ number_format((float) $product->commission_amount, 0, ',', ' ') }} F</strong></div>

                            <div class="product-cart-actions">
                                <button type="button" class="btn product-add-button" wire:click="AddToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="AddToCart({{ $product->id }})" @disabled($isOut)>
                                    <span class="material-icons">add_shopping_cart</span> Ajouter
                                </button>
                                <button type="button" class="btn product-wholesale-button" wire:click="AddToCartHighPrice({{ $product->id }})" wire:loading.attr="disabled" wire:target="AddToCartHighPrice({{ $product->id }})" @disabled($isOut || ! $product->high_price)>
                                    Gros · {{ $product->high_price ? number_format((float) $product->high_price, 0, ',', ' ') . ' F' : 'Non défini' }}
                                </button>
                            </div>

                            @if ($isAdmin)
                                <div class="product-admin-actions">
                                    <a href="{{ route('show-product', $product) }}"><span class="material-icons">visibility</span> Voir</a>
                                    <a href="{{ route('editproduct', $product) }}"><span class="material-icons">edit</span> Modifier</a>
                                    <form method="POST" action="{{ route('delteproduct', $product) }}" onsubmit="return confirm('Supprimer ce produit ?')">
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
                <p>Essaie un autre nom ou modifie le filtre de stock.</p>
                @if ($search || $stockFilter !== 'all')
                    <button class="btn catalog-reset-button" wire:click="resetFilters">Effacer les filtres</button>
                @endif
            </div>
        @endif
    </section>

    <style>
        .product-catalog-page{--catalog-burgundy:#7e1615;--catalog-ink:#272b35;--catalog-muted:#78808e;--catalog-line:#e8e9ed;--catalog-bg:#f7f7f9;min-width:0;padding-top:14px;padding-bottom:28px;color:var(--catalog-ink)}
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
        .product-description{min-height:34px;margin:8px 0 0;color:var(--catalog-muted);font-size:11px;line-height:1.5}
        .product-prices{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:13px;padding:11px 0;border-top:1px solid #f0f0f2;border-bottom:1px solid #f0f0f2}
        .product-prices small{display:block;color:var(--catalog-muted);font-size:10px;margin-bottom:4px}
        .product-prices strong{font-size:15px;color:var(--catalog-ink);font-weight:800;overflow-wrap:anywhere}
        .product-prices strong span{font-size:11px;color:var(--catalog-muted)}
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
    </style>
</div>
