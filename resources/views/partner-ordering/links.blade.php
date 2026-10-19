@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
<style>
    .partner-link-product[hidden] { display: none !important; }
</style>
<div class="container-fluid py-3 partner-portal partner-links-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-danger font-weight-bold small">COMMANDES PARTENAIRES</span>
            <h1 class="h3 mb-1">Liens de commande</h1>
            <p class="text-muted mb-0">Crée une sélection à partager. Le client choisira son pays et les prix configurés s’afficheront.</p>
        </div>
        <a href="{{ route('partner-ordering.prices', 'TG') }}" class="btn btn-outline-danger">Configurer les prix par pays</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="card shadow-sm mb-4">
        <div class="card-header"><h2 class="h5 mb-0">Créer un lien public</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('partner-ordering.links.create') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-7"><label for="link-title">Nom de la sélection</label><input class="form-control" id="link-title" name="title" value="{{ old('title') }}" maxlength="160" placeholder="Ex. Produits recommandés"></div>
                    <div class="form-group col-md-5"><label for="link-expiry">Expiration (facultative)</label><input class="form-control" id="link-expiry" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"></div>
                </div>
                <label for="product-search">Produits de départ <span class="text-danger">*</span></label>
                <input id="product-search" type="search" class="form-control mb-2" placeholder="Filtrer les produits…">
                <div id="product-search-results" class="border rounded p-3" style="max-height: 320px; overflow: auto">
                    @forelse ($products as $product)
                        <label class="d-flex align-items-center py-1 partner-link-product" data-name="{{ mb_strtolower($product->name) }}" @unless (in_array($product->id, old('product_ids', []))) hidden @endunless>
                            <input class="mr-2" type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, old('product_ids', [])))>
                            <span>{{ $product->name }}</span>
                        </label>
                    @empty
                        <p class="text-muted mb-0">Configure au moins un prix actif par pays avant de créer un lien.</p>
                    @endforelse
                    @if ($products->isNotEmpty())
                        <p id="product-search-prompt" class="text-muted mb-0">Saisis le nom d’un produit pour le rechercher. Les produits sélectionnés resteront affichés.</p>
                        <p id="product-search-empty" class="text-muted mb-0" hidden>Aucun produit ne correspond à cette recherche.</p>
                    @endif
                </div>
                <button class="btn btn-danger mt-3" type="submit" @disabled($products->isEmpty())>Créer le lien</button>
            </form>
        </div>
    </section>

    <section class="card shadow-sm">
        <div class="card-header"><h2 class="h5 mb-0">Liens créés</h2></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr><th>Sélection</th><th>Produits</th><th>Commandes</th><th>État</th><th>Lien public</th><th></th></tr></thead>
                <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td><strong>{{ $link->title }}</strong><small class="d-block text-muted">Créé par {{ $link->creator?->name ?? 'Compte supprimé' }}</small></td>
                        <td>{{ $link->products_count }}</td><td>{{ $link->orders_count }}</td>
                        <td>{{ $link->isAvailable() ? 'Actif' : ($link->is_active ? 'Expiré' : 'Désactivé') }}</td>
                        <td><a href="{{ route('public-order.show', $link->token) }}" target="_blank" rel="noopener">{{ route('public-order.show', $link->token) }}</a></td>
                        <td><form method="POST" action="{{ route('partner-ordering.links.toggle', $link) }}">@csrf @method('PUT')<button class="btn btn-sm {{ $link->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">{{ $link->is_active ? 'Désactiver' : 'Activer' }}</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun lien public créé pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($links->hasPages())<div class="card-footer">{{ $links->links() }}</div>@endif
    </section>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('product-search');
        if (!search) return;
        const items = Array.from(document.querySelectorAll('.partner-link-product'));
        const prompt = document.getElementById('product-search-prompt');
        const empty = document.getElementById('product-search-empty');
        const filterProducts = function () {
            const query = search.value.trim().toLocaleLowerCase();
            let visibleCount = 0;
            items.forEach((item) => {
                const checked = item.querySelector('input[type="checkbox"]')?.checked;
                const matches = query !== '' && item.dataset.name.includes(query);
                item.hidden = !checked && !matches;
                if (!item.hidden) visibleCount++;
            });
            if (prompt) prompt.hidden = query !== '' || visibleCount > 0;
            if (empty) empty.hidden = query === '' || visibleCount > 0;
        };
        search.addEventListener('input', filterProducts);
        items.forEach((item) => item.querySelector('input[type="checkbox"]')?.addEventListener('change', filterProducts));
        filterProducts();
    });
</script>
@endsection
