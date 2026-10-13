@extends('layouts.admin')

@section('content')
    @php
        $locationTypeLabels = [
            'warehouse' => 'Magasin', 'shop' => 'Boutique', 'transit' => 'Transit',
            'blocked' => 'Bloqué / quarantaine', 'sales_rep' => 'Commercial terrain',
            'wholesaler' => 'Grossiste', 'other' => 'Autre',
        ];
        $blockedTotal = (int) ($locationTotals['blocked'] ?? 0);
        $transitTotal = (int) ($locationTotals['transit'] ?? 0);
    @endphp

    <div class="stock-control-page stock-balance-page">
        <header class="stock-control-hero">
            <div class="stock-control-hero-copy">
                <span class="stock-control-kicker">TENACOS · VISIBILITÉ DES QUANTITÉS</span>
                <h1>État du stock par emplacement</h1>
                <p>Consulte les quantités suivies dans chaque magasin, boutique et zone de stockage.</p>
            </div>
            <a href="{{ route('stock-control.receipts.index') }}" class="stock-control-hero-action"><span class="material-icons">move_to_inbox</span>Réceptions</a>
            <div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">inventory</span></div>
        </header>

        <section class="stock-control-alert stock-balance-notice" role="status">
            <span class="material-icons">info</span>
            <div><strong>Le stock historique reste à rapprocher.</strong><p>Les anciennes quantités ne sont pas encore réparties entre les emplacements et les ventes historiques ne sont pas encore inscrites au journal. Le rapprochement affiché est donc indicatif ; les réceptions validées ici sont bien visibles dans le journal et dans le stock global des produits.</p></div>
        </section>

        <section class="stock-balance-summary" aria-label="Résumé du stock">
            <article><span class="material-icons">inventory_2</span><div><small>Stock global produit</small><strong>{{ number_format($legacyTotal, 0, ',', ' ') }}</strong><span>Unité(s) actuellement disponibles</span></div></article>
            <article><span class="material-icons">warehouse</span><div><small>Stock vendable localisé</small><strong>{{ number_format($locatedSellable, 0, ',', ' ') }}</strong><span>Magasins, boutiques et détenteurs</span></div></article>
            <article class="{{ $unallocated < 0 ? 'is-warning' : '' }}"><span class="material-icons">location_searching</span><div><small>Écart global / journal</small><strong>{{ number_format(abs($unallocated), 0, ',', ' ') }}</strong><span>{{ $unallocated < 0 ? 'Le journal dépasse le stock global' : 'À affecter ou à rapprocher' }}</span></div></article>
            <article class="is-muted"><span class="material-icons">inventory</span><div><small>Zones de contrôle</small><strong>{{ number_format($blockedTotal, 0, ',', ' ') }} / {{ number_format($transitTotal, 0, ',', ' ') }}</strong><span>Bloqué / en transit</span></div></article>
        </section>

        <section class="stock-control-card stock-balance-card">
            <div class="stock-control-section-head">
                <div><span class="stock-control-kicker">CONSULTATION</span><h2>Quantités enregistrées</h2></div>
                <span class="stock-control-count">{{ number_format($balances->total(), 0, ',', ' ') }} ligne(s)</span>
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
                        <thead><tr><th>Produit</th><th>Emplacement</th><th>Type</th><th>Quantité sur place</th><th>Stock global actuel</th><th>Seuil bas</th><th>État global</th><th></th></tr></thead>
                        <tbody>
                            @foreach($balances as $row)
                                @php
                                    $globalQuantity = (int) $row->global_quantity;
                                    $threshold = (int) ($row->low_stock_threshold ?? 0);
                                    if ($row->location_type === 'blocked') { $stateLabel = 'Bloqué'; $stateClass = 'blocked'; }
                                    elseif ($row->location_type === 'transit') { $stateLabel = 'En transit'; $stateClass = 'transit'; }
                                    elseif ($globalQuantity <= 0) { $stateLabel = 'Rupture'; $stateClass = 'out'; }
                                    elseif ($threshold > 0 && $globalQuantity <= $threshold) { $stateLabel = 'Stock bas'; $stateClass = 'low'; }
                                    else { $stateLabel = 'Disponible'; $stateClass = 'available'; }
                                @endphp
                                <tr>
                                    <td><strong>{{ $row->product_name }}</strong><small>Réf. {{ $row->product_id }}</small></td>
                                    <td><strong>{{ $row->location_name }}</strong><small>{{ $row->location_code }}</small></td>
                                    <td>{{ $locationTypeLabels[$row->location_type] ?? 'Autre' }}</td>
                                    <td class="stock-balance-quantity">{{ number_format((float) $row->quantity_on_hand, 0, ',', ' ') }}</td>
                                    <td>{{ number_format($globalQuantity, 0, ',', ' ') }}</td>
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
@endsection
