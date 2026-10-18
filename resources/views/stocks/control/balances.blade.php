@extends('layouts.admin')

@section('content')
    @php
        $blockedTotal = (int) ($locationTotals['blocked'] ?? 0);
        $transitTotal = (int) ($locationTotals['transit'] ?? 0);
    @endphp

    <div class="stock-control-page stock-balance-page">
        <header class="stock-control-hero">
            <div class="stock-control-hero-copy">
                <span class="stock-control-kicker">TENACOS · VISIBILITÉ DES QUANTITÉS</span>
                <h1>État du stock par emplacement</h1>
                <p>Le stock global correspond aux entrepôts. Les quantités des boutiques et des zones de contrôle restent affichées séparément.</p>
            </div>
            <div class="transfer-detail-heading-actions">
                <a href="{{ route('stock-control.counts.create', ['warehouse_only' => 1, 'opening_balance' => 1]) }}" class="stock-control-hero-action"><span class="material-icons">playlist_add_check</span>Saisir le stock réel de l’entrepôt</a>
                <a href="{{ route('stock-control.receipts.index') }}" class="stock-control-hero-action"><span class="material-icons">move_to_inbox</span>Réceptions</a>
            </div>
            <div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">inventory</span></div>
        </header>

        @if(abs($unallocated) > 0.0005)
            <section class="stock-control-alert stock-balance-notice" role="status">
                <span class="material-icons">info</span>
                <div>
                    @if($unallocated > 0)
                        <strong>Il reste {{ number_format($unallocated, 0, ',', ' ') }} unité(s) catalogue à affecter à un entrepôt.</strong>
                        <p>Quand un seul entrepôt est actif, l’application affecte automatiquement ces quantités au journal. Avec plusieurs entrepôts, il faut indiquer où se trouve le stock.</p>
                    @else
                        <strong>Le journal des entrepôts et l’ancien total catalogue présentent un écart de {{ number_format(abs($unallocated), 0, ',', ' ') }} unité(s).</strong>
                        <p>Le stock affiché ici fait foi : il est calculé à partir des mouvements enregistrés dans les entrepôts.</p>
                    @endif
                </div>
            </section>
        @endif

        <section class="stock-balance-summary" aria-label="Résumé du stock">
            <article><span class="material-icons">inventory_2</span><div><small>Stock global · entrepôts</small><strong>{{ number_format($warehouseTotal, 0, ',', ' ') }}</strong><span>Somme des quantités journalisées dans les entrepôts</span></div></article>
            <article><span class="material-icons">storefront</span><div><small>Stock dans les boutiques</small><strong>{{ number_format($shopTotal, 0, ',', ' ') }}</strong><span>Quantités transférées vers les boutiques</span></div></article>
            <article class="{{ $unallocated < 0 ? 'is-warning' : '' }}"><span class="material-icons">location_searching</span><div><small>{{ $productionCatalogueInShop ? 'Reprise du stock catalogue' : 'Écart catalogue historique / journal' }}</small><strong>{{ number_format(abs($unallocated), 0, ',', ' ') }}</strong><span>{{ $productionCatalogueInShop ? 'Quantités historiques rattachées à la boutique' : ($unallocated < 0 ? 'Le journal dépasse l’ancien total catalogue' : 'Quantité catalogue à rapprocher') }}</span></div></article>
            <article class="is-muted"><span class="material-icons">inventory</span><div><small>Zones de contrôle</small><strong>{{ number_format($blockedTotal, 0, ',', ' ') }} / {{ number_format($transitTotal, 0, ',', ' ') }}</strong><span>Bloqué / en transit</span></div></article>
        </section>

        <section class="stock-control-card stock-balance-card">
            <div class="stock-control-section-head">
                <div><span class="stock-control-kicker">CONSULTATION</span><h2>Quantités enregistrées</h2></div>
                <span class="stock-control-count">{{ number_format($balances->total(), 0, ',', ' ') }} produit(s)</span>
            </div>

            <form method="GET" action="{{ route('stock-control.balances') }}" class="stock-balance-filters">
                <label class="stock-control-field"><span>Rechercher un produit</span><input class="stock-live-search" type="search" name="search" value="{{ $search }}" placeholder="Nom du produit" autocomplete="off"></label>
                <label class="stock-control-field"><span>Emplacement</span><select name="location_id"><option value="">Tous les emplacements</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>{{ $location->name }} · {{ $location->code }}{{ $location->is_active ? '' : ' · inactif' }}</option>@endforeach</select></label>
                <label class="stock-control-field"><span>État du produit</span><select name="stock_state"><option value="all" @selected($state === 'all')>Tous les états</option><option value="available" @selected($state === 'available')>Disponible</option><option value="low" @selected($state === 'low')>Stock bas</option><option value="out" @selected($state === 'out')>En rupture</option></select></label>
                <div class="stock-balance-filter-actions"><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">search</span>Filtrer</button><a class="stock-control-button" href="{{ route('stock-control.balances') }}">Effacer</a></div>
            </form>

            @if($balances->isEmpty())
                <div class="stock-control-empty"><span class="material-icons">inventory_2</span><strong>Aucune quantité localisée pour cette recherche</strong><p>Après validation d’une réception ou d’un transfert, les produits apparaîtront ici avec leur emplacement.</p></div>
            @else
                <div class="stock-balance-table-wrap">
                    <table class="stock-balance-table">
                        <thead><tr><th>Produit</th><th>Entrepôts</th><th>Boutiques</th><th>En transit</th><th>Bloqué</th><th>Seuil bas</th><th>État global</th><th></th></tr></thead>
                        <tbody>
                            @foreach($balances as $row)
                                @php
                                    $productLocations = $locationBreakdowns->get($row->product_id, collect());
                                    $warehouseLocations = $productLocations->where('location_type', 'warehouse');
                                    $shopLocations = $productLocations->where('location_type', 'shop');
                                    $globalQuantity = (int) $row->warehouse_quantity;
                                    $threshold = (int) ($row->low_stock_threshold ?? 0);
                                    if ($globalQuantity <= 0) { $stateLabel = 'Rupture'; $stateClass = 'out'; }
                                    elseif ($threshold > 0 && $globalQuantity <= $threshold) { $stateLabel = 'Stock bas'; $stateClass = 'low'; }
                                    else { $stateLabel = 'Disponible'; $stateClass = 'available'; }
                                @endphp
                                <tr>
                                    <td><strong>{{ $row->product_name }}</strong><small>Réf. {{ $row->product_id }}</small></td>
                                    <td class="stock-balance-quantity">
                                        <strong>{{ number_format((float) $row->warehouse_quantity, 0, ',', ' ') }}</strong>
                                        @forelse($warehouseLocations as $location)
                                            <small>{{ $location->location_name }} · {{ number_format((float) $location->quantity_on_hand, 0, ',', ' ') }}</small>
                                        @empty
                                            <small>{{ $globalQuantity > 0 ? 'À affecter à un entrepôt' : 'Aucun stock localisé' }}</small>
                                        @endforelse
                                    </td>
                                    <td class="stock-balance-quantity">
                                        <strong>{{ number_format((float) $row->shop_quantity, 0, ',', ' ') }}</strong>
                                        @forelse($shopLocations as $location)
                                            <small>{{ $location->location_name }} · {{ number_format((float) $location->quantity_on_hand, 0, ',', ' ') }}</small>
                                        @empty
                                            <small>Aucune boutique approvisionnée</small>
                                        @endforelse
                                    </td>
                                    <td>{{ number_format((float) $row->transit_quantity, 0, ',', ' ') }}</td>
                                    <td>{{ number_format((float) $row->blocked_quantity, 0, ',', ' ') }}</td>
                                    <td>{{ $threshold > 0 ? number_format($threshold, 0, ',', ' ') : '—' }}</td>
                                    <td><span class="stock-balance-state {{ $stateClass }}">{{ $stateLabel }}</span></td>
                                    <td><a href="{{ route('show-product', $row->product_id) }}" class="stock-balance-open" aria-label="Voir le produit"><span class="material-icons">arrow_forward</span></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="receipt-pagination">{{ $balances->links() }}</div>
            @endif
        </section>
    </div>
    <script>
        (() => {
            const form = document.querySelector('.stock-balance-filters');
            if (!form) return;

            const search = form.querySelector('input[name="search"]');
            const filters = form.querySelectorAll('select');
            let searchTimer;

            search?.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => form.requestSubmit(), 300);
            });

            search?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    window.clearTimeout(searchTimer);
                    form.requestSubmit();
                }
            });

            filters.forEach((filter) => filter.addEventListener('change', () => form.requestSubmit()));
        })();
    </script>
@endsection
