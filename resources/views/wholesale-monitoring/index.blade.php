@extends('layouts.admin')

@section('content')
<style>
    .wholesale-monitor{--wm-ink:#252b38;--wm-muted:#707989;--wm-line:#e2e6ec;--wm-red:#861b1b;color:var(--wm-ink);padding:20px 24px 36px}
    .wm-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:22px}.wm-heading h1{margin:0;font-size:28px;font-weight:800}.wm-heading p{margin:6px 0 0;color:var(--wm-muted)}
    .wm-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:22px}.wm-stat,.wm-panel{border:1px solid var(--wm-line);background:#fff}.wm-stat{padding:16px}.wm-stat span{display:block;color:var(--wm-muted);font-size:12px;font-weight:750;text-transform:uppercase;letter-spacing:.04em}.wm-stat strong{display:block;margin-top:8px;font-size:24px;font-weight:850}.wm-stat small{color:var(--wm-muted)}
    .wm-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 17px;border-bottom:1px solid var(--wm-line)}.wm-toolbar h2{margin:0;font-size:17px;font-weight:800}.wm-toolbar p{margin:4px 0 0;color:var(--wm-muted);font-size:13px}.wm-search{display:flex;gap:8px;width:min(100%,430px)}.wm-search input{min-width:0;height:42px}.wm-search button{padding:0 15px;border:0;background:var(--wm-red);color:#fff;font-weight:750}
    .wm-table-wrap{overflow-x:auto}.wm-table{width:100%;border-collapse:collapse}.wm-table th,.wm-table td{padding:13px 16px;border-bottom:1px solid #edf0f3;text-align:left;vertical-align:middle}.wm-table th{background:#f6f7f9;color:#707989;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.wm-table tr:last-child td{border-bottom:0}.wm-customer{font-weight:800}.wm-secondary{display:block;margin-top:3px;color:var(--wm-muted);font-size:12px}.wm-view{display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border:1px solid #e1caca;color:var(--wm-red);font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap}.wm-view:hover{background:#fbf3f3;color:var(--wm-red)}.wm-empty{padding:34px;text-align:center;color:var(--wm-muted)}.wm-pagination{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 17px}.wm-pagination small{color:var(--wm-muted)}.wm-pagination .pagination{margin:0}
    .wm-tabs{display:inline-flex;align-items:center;gap:5px;padding:5px;margin:0 0 18px;border:1px solid var(--wm-line);border-radius:8px;background:#f3f5f7}.wm-tab{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 16px;border:1px solid transparent;border-radius:5px;background:transparent;color:var(--wm-muted);font-weight:750;line-height:1.2;text-decoration:none;transition:background .15s ease,color .15s ease,box-shadow .15s ease}.wm-tab:hover{background:#fff;color:var(--wm-ink);text-decoration:none}.wm-tab:focus-visible{outline:2px solid #861b1b;outline-offset:2px}.wm-tab.active{border-color:#e3e6eb;background:#fff;color:var(--wm-red);box-shadow:0 1px 3px rgba(37,43,56,.08)}.wm-product{display:flex;align-items:center;gap:10px;min-width:190px}.wm-product img{width:44px;height:44px;object-fit:cover;border:1px solid var(--wm-line);background:#f7f7f7}.wm-product strong{font-weight:800}.wm-number{font-variant-numeric:tabular-nums;white-space:nowrap}.wm-positive{color:#207344}.wm-negative{color:#a62e2e}.wm-location-note{padding:17px;color:var(--wm-muted)}
    @media(max-width:1250px){.wm-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:700px){.wholesale-monitor{padding:14px 10px 28px}.wm-heading{align-items:flex-start;flex-direction:column}.wm-heading h1{font-size:23px}.wm-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.wm-stat{padding:12px}.wm-stat strong{font-size:20px}.wm-tabs{display:flex;width:100%;gap:4px}.wm-tab{flex:1;min-width:0;padding:9px 7px;font-size:12px;text-align:center}.wm-toolbar{align-items:stretch;flex-direction:column}.wm-search{width:100%}.wm-pagination{align-items:flex-start;flex-direction:column}}
</style>

<div class="container-fluid wholesale-monitor">
    <header class="wm-heading"><div><h1>Suivi des clients grossistes</h1><p>Clients identifiés automatiquement par leurs commandes ayant atteint un palier de quantité.</p></div></header>

    <nav class="wm-tabs" aria-label="Sections du suivi grossiste">
        <a class="wm-tab {{ $tab === 'customers' ? 'active' : '' }}" href="{{ route('wholesale-monitoring.index', ['tab' => 'customers']) }}">Clients et commandes</a>
        <a class="wm-tab {{ $tab === 'products' ? 'active' : '' }}" href="{{ route('wholesale-monitoring.index', ['tab' => 'products']) }}">Produits et évolution du stock</a>
    </nav>

    @if($tab === 'customers')
    <section class="wm-stats" aria-label="Résumé des commandes grossistes">
        <article class="wm-stat"><span>Clients grossistes</span><strong>{{ number_format((int) $stats->customers_count, 0, ',', ' ') }}</strong></article>
        <article class="wm-stat"><span>Commandes grossistes</span><strong>{{ number_format((int) $stats->orders_count, 0, ',', ' ') }}</strong></article>
        <article class="wm-stat"><span>Chiffre d’affaires grossiste livré</span><strong>{{ number_format((float) $stats->revenue, 0, ',', ' ') }} F</strong><small>Commandes livrées uniquement · annulations et commandes en cours exclues</small></article>
        <article class="wm-stat"><span>En cours</span><strong>{{ number_format((int) $stats->in_progress, 0, ',', ' ') }}</strong></article>
        <article class="wm-stat"><span>Livrées</span><strong>{{ number_format((int) $stats->delivered, 0, ',', ' ') }}</strong></article>
        <article class="wm-stat"><span>Annulées</span><strong>{{ number_format((int) $stats->canceled, 0, ',', ' ') }}</strong></article>
    </section>

    <section class="wm-panel">
        <div class="wm-toolbar"><div><h2>Clients qualifiés</h2><p>{{ number_format($customers->total(), 0, ',', ' ') }} client(s) avec au moins une commande grossiste</p></div>
            <form class="wm-search" method="GET" action="{{ route('wholesale-monitoring.index') }}" role="search"><input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Nom, téléphone ou courriel…" aria-label="Rechercher un client" data-live-customer-search><button type="submit">Rechercher</button></form>
        </div>
        <div class="wm-table-wrap"><table class="wm-table"><thead><tr><th>Client</th><th>Commandes grossistes</th><th>Chiffre d’affaires livré</th><th>Dernière commande</th><th></th></tr></thead><tbody>
            @forelse($customers as $customer)
                <tr><td><span class="wm-customer">{{ $customer->name ?: 'Client sans nom' }}</span><span class="wm-secondary">{{ $customer->phone ?: $customer->email ?: 'Aucun contact renseigné' }}</span></td><td>{{ number_format((int) $customer->wholesale_orders_count, 0, ',', ' ') }}</td><td><strong>{{ number_format((float) $customer->wholesale_revenue, 0, ',', ' ') }} F</strong></td><td>{{ $customer->last_wholesale_order_at ? \Illuminate\Support\Carbon::parse($customer->last_wholesale_order_at)->format('d/m/Y') : '—' }}</td><td><a class="wm-view" href="{{ route('wholesale-monitoring.show', $customer) }}">Voir le suivi <span aria-hidden="true">→</span></a></td></tr>
            @empty
                <tr><td class="wm-empty" colspan="5">{{ $search !== '' ? 'Aucun client grossiste ne correspond à cette recherche.' : 'Aucune commande grossiste enregistrée pour le moment.' }}</td></tr>
            @endforelse
        </tbody></table></div>
        @if($customers->hasPages())<div class="wm-pagination"><small>Page {{ $customers->currentPage() }} sur {{ $customers->lastPage() }}</small>{{ $customers->links() }}</div>@endif
    </section>
    @else
    <section class="wm-stats" aria-label="Résumé du stock de la boutique grossiste">
        <article class="wm-stat"><span>Produits suivis</span><strong>{{ number_format((int) $productStats->products_count, 0, ',', ' ') }}</strong></article>
        <article class="wm-stat"><span>Stock actuel total</span><strong>{{ number_format((float) $productStats->current_stock, 0, ',', ' ') }}</strong><small>Quantités actuellement dans la boutique</small></article>
        <article class="wm-stat"><span>Quantités ajoutées · 30 jours</span><strong>{{ number_format((float) $productStats->received_30d, 0, ',', ' ') }}</strong><small>Réceptions et transferts reçus</small></article>
        <article class="wm-stat"><span>Quantités sorties · 30 jours</span><strong>{{ number_format((float) $productStats->outflow_30d, 0, ',', ' ') }}</strong><small>Produits vendus ou transférés ailleurs</small></article>
    </section>

    <section class="wm-panel">
        <div class="wm-toolbar"><div><h2>Évolution des produits</h2><p>{{ $wholesaleShop ? 'Boutique : '.$wholesaleShop->name : 'La boutique grossiste n’est pas configurée.' }} · Sur 30 jours : quantités ajoutées moins quantités sorties = variation du stock.</p></div>
            <form class="wm-search" method="GET" action="{{ route('wholesale-monitoring.index') }}" role="search"><input type="hidden" name="tab" value="products"><input class="form-control" type="search" name="product_search" value="{{ $productSearch }}" placeholder="Rechercher un produit…" aria-label="Rechercher un produit" data-live-product-search><button type="submit">Rechercher</button></form>
        </div>
        @if(!$wholesaleShop)
            <div class="wm-location-note">Configure d’abord une boutique grossiste dans le contrôle des stocks pour afficher son inventaire.</div>
        @else
            <div class="wm-table-wrap"><table class="wm-table"><thead><tr><th>Produit</th><th>Stock actuel</th><th>Quantités ajoutées · 30 j</th><th>Quantités sorties · 30 j</th><th>Variation du stock · 30 j</th></tr></thead><tbody>
            @forelse($products as $product)
                <tr>
                    <td><div class="wm-product">@if($product->img)<img src="{{ \Illuminate\Support\Str::startsWith($product->img, ['http://', 'https://', '/']) ? $product->img : (\Illuminate\Support\Str::startsWith($product->img, ['image/', 'storage/']) ? asset($product->img) : url('image/'.ltrim($product->img, '/'))) }}" alt="">@endif<strong>{{ $product->name }}</strong></div></td>
                    <td class="wm-number"><strong>{{ number_format((float) $product->current_quantity, 0, ',', ' ') }}</strong></td>
                    <td class="wm-number">{{ number_format((float) $product->received_30d, 0, ',', ' ') }}</td>
                    <td class="wm-number">{{ number_format((float) $product->outflow_30d, 0, ',', ' ') }}</td>
                    <td class="wm-number {{ (float) $product->net_change_30d > 0 ? 'wm-positive' : ((float) $product->net_change_30d < 0 ? 'wm-negative' : '') }}"><strong>{{ (float) $product->net_change_30d > 0 ? '+' : '' }}{{ number_format((float) $product->net_change_30d, 0, ',', ' ') }}</strong></td>
                </tr>
            @empty
                <tr><td class="wm-empty" colspan="5">{{ $productSearch !== '' ? 'Aucun produit ne correspond à cette recherche.' : 'Aucun mouvement de stock enregistré pour cette boutique.' }}</td></tr>
            @endforelse
        </tbody></table></div>
        @if($products->hasPages())<div class="wm-pagination"><small>Page {{ $products->currentPage() }} sur {{ $products->lastPage() }}</small>{{ $products->links() }}</div>@endif
        @endif
    </section>
    @endif
</div>
<script>
    (function(){var input=document.querySelector('[data-live-customer-search]');if(!input)return;var timer;input.addEventListener('input',function(){window.clearTimeout(timer);timer=window.setTimeout(function(){if(input.value.trim()!==@json($search))input.form.requestSubmit();},350);});})();
    (function(){var input=document.querySelector('[data-live-product-search]');if(!input)return;var timer;input.addEventListener('input',function(){window.clearTimeout(timer);timer=window.setTimeout(function(){if(input.value.trim()!==@json($productSearch))input.form.requestSubmit();},350);});})();
</script>
@endsection
