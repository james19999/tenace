@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
<style>
    .partner-link-product[hidden] { display: none !important; }
    .partner-country-shortcuts { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:15px 17px; border:1px solid var(--app-border,#e3e8ef); background:var(--app-surface,#fff); }
    .partner-country-shortcuts>div:first-child { display:grid; gap:4px; }
    .partner-country-shortcuts>div:first-child strong { color:var(--app-text,#202938); font-size:13px; }
    .partner-country-shortcuts>div:first-child small { color:var(--app-muted,#748093); font-size:10px; }
    .partner-country-shortcut { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:38px; padding:8px 12px; border:1px solid var(--app-primary,#147d25); background:var(--app-primary,#147d25); color:#fff!important; font-size:11px; font-weight:750; text-decoration:none; white-space:nowrap; }
    .partner-country-shortcut span,.partner-country-shortcut .material-icons { color:#fff!important; font-size:inherit; line-height:1.2; }
    .partner-country-shortcut .material-icons { font-size:17px; }
    .partner-country-shortcut:hover,.partner-country-shortcut:focus-visible { filter:brightness(.92); color:#fff!important; text-decoration:none; }
    .partner-country-shortcut:hover span,.partner-country-shortcut:focus-visible span { color:#fff!important; }
    html[data-app-theme="dark"] .partner-country-shortcuts { background:var(--app-surface); border-color:var(--app-border); }
    html[data-app-theme="dark"] .partner-country-shortcuts>div:first-child strong { color:var(--app-text); }
    html[data-app-theme="dark"] .partner-country-shortcut,html[data-app-theme="dark"] .partner-country-shortcut span { color:#fff!important; }
    @media (max-width: 700px) { .partner-country-shortcuts { align-items:stretch; flex-direction:column; } .partner-country-shortcut { justify-content:center; } }
    .partner-link-actions { display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:6px; min-width:245px; }
    .partner-link-actions a, .partner-link-actions button { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:34px; padding:6px 9px; border:1px solid var(--app-border,#e3e8ef); background:var(--app-surface,#fff); color:var(--app-primary,#147d25); font-size:11px; font-weight:700; text-decoration:none; cursor:pointer; }
    .partner-link-actions a:hover, .partner-link-actions button:hover { background:var(--app-primary,#147d25)!important; border-color:var(--app-primary,#147d25)!important; color:#fff!important; text-decoration:none; }
    .partner-link-actions form { margin:0; }
    .partner-link-actions form button { color:#a33b3b; }
    .partner-link-actions .partner-link-copy.is-copied { background:#e8f5ed; border-color:#b9dec7; color:#247347; }
    html[data-app-theme="dark"] .partner-link-actions a, html[data-app-theme="dark"] .partner-link-actions button { background:var(--app-surface); border-color:var(--app-border); color:var(--app-text); }
    html[data-app-theme="dark"] .partner-link-actions .partner-link-copy.is-copied { background:#173b2b; border-color:#27583d; color:#85d6a5; }
</style>
<div class="container-fluid py-3 partner-portal partner-links-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-danger font-weight-bold small">COMMANDES PARTENAIRES</span>
            <h1 class="h3 mb-1">Liens de commande</h1>
            <p class="text-muted mb-0">Un lien réutilisable par pays, à partager avec tous tes clients.</p>
        </div>
    </div>

    <section class="partner-country-shortcuts mb-4" aria-label="Configurer les produits disponibles par pays">
        <div><strong>Disponibilité des produits par pays</strong><small>Choisis un pays dans l’écran de configuration pour gérer son catalogue partenaire.</small></div>
        <a class="partner-country-shortcut" href="{{ route('partner-ordering.prices', array_key_first($countries)) }}"><span class="material-icons" aria-hidden="true">tune</span><span>Configurer les produits disponibles</span><span class="material-icons" aria-hidden="true">arrow_forward</span></a>
    </section>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="card shadow-sm mb-4">
        <div class="card-header"><h2 class="h5 mb-0">Créer un lien public</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('partner-ordering.links.create') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4"><label for="link-country">Pays du lien</label><select class="form-control" id="link-country" name="country_code" required><option value="">Choisir un pays</option>@foreach($countries as $code => $country)<option value="{{ $code }}" @selected(old('country_code') === $code)>{{ $country['label'] }}</option>@endforeach</select></div>
                    <div class="form-group col-md-4"><label for="link-title">Nom du lien (facultatif)</label><input class="form-control" id="link-title" name="title" value="{{ old('title') }}" maxlength="160" placeholder="Ex. Catalogue Bénin"></div>
                    <div class="form-group col-md-4"><label for="link-expiry">Expiration (facultative)</label><input class="form-control" id="link-expiry" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"></div>
                </div>
                <label for="product-search">Produits à mettre en avant <span class="text-danger">*</span></label>
                <input id="product-search" type="search" class="form-control mb-2" placeholder="Choisis d’abord le pays">
                <div id="product-search-results" class="border rounded p-3" style="max-height: 320px; overflow: auto">
                    @forelse ($products as $product)
                        @php($productCountries = $product->countryPrices->pluck('country_code')->implode(','))
                        <label class="d-flex align-items-center py-1 partner-link-product" data-name="{{ mb_strtolower($product->name) }}" data-countries="{{ $productCountries }}" hidden>
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
                <thead class="thead-light"><tr><th>Pays</th><th>Lien</th><th>Produits</th><th>Commandes</th><th>État</th><th>Lien public</th><th></th></tr></thead>
                <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td>{{ $countries[$link->country_code]['label'] ?? 'À rattacher' }}</td><td><strong>{{ $link->title }}</strong><small class="d-block text-muted">Créé par {{ $link->creator?->name ?? 'Compte supprimé' }}</small></td>
                        <td>{{ $link->products_count }}</td><td>{{ $link->orders_count }}</td>
                        <td>{{ $link->isAvailable() ? 'Actif' : ($link->is_active ? 'Expiré' : 'Désactivé') }}</td>
                        <td><a href="{{ route('public-order.show', $link->token) }}" target="_blank" rel="noopener">{{ route('public-order.show', $link->token) }}</a></td>
                        <td><div class="partner-link-actions">
                            <button type="button" class="partner-link-copy" data-copy-url="{{ route('public-order.show', $link->token) }}"><span class="material-icons" aria-hidden="true">content_copy</span><span>Copier le lien</span></button>
                            <a href="{{ route('public-order.show', $link->token) }}" target="_blank" rel="noopener"><span class="material-icons" aria-hidden="true">open_in_new</span><span>Ouvrir</span></a>
                            <form method="POST" action="{{ route('partner-ordering.links.toggle', $link) }}">@csrf @method('PUT')<button type="submit"><span class="material-icons" aria-hidden="true">{{ $link->is_active ? 'link_off' : 'link' }}</span><span>{{ $link->is_active ? 'Désactiver' : 'Activer' }}</span></button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun lien public créé pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($links->hasPages())<div class="card-footer">{{ $links->links() }}</div>@endif
    </section>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.partner-link-copy').forEach((button) => button.addEventListener('click', async function () {
            const url = button.dataset.copyUrl;
            try {
                await navigator.clipboard.writeText(url);
            } catch (error) {
                const field = document.createElement('textarea');
                field.value = url;
                field.style.position = 'fixed'; field.style.opacity = '0';
                document.body.appendChild(field); field.select(); document.execCommand('copy'); field.remove();
            }
            const label = button.querySelector('span:last-child');
            const previousLabel = label.textContent;
            label.textContent = 'Lien copié'; button.classList.add('is-copied');
            window.setTimeout(() => { label.textContent = previousLabel; button.classList.remove('is-copied'); }, 1800);
        }));
        const search = document.getElementById('product-search');
        if (!search) return;
        const country = document.getElementById('link-country');
        const items = Array.from(document.querySelectorAll('.partner-link-product'));
        const prompt = document.getElementById('product-search-prompt');
        const empty = document.getElementById('product-search-empty');
        const filterProducts = function () {
            const query = search.value.trim().toLocaleLowerCase();
            const countryCode = country?.value || '';
            let visibleCount = 0;
            items.forEach((item) => {
                const productIsAvailable = countryCode !== '' && item.dataset.countries.split(',').includes(countryCode);
                const checked = item.querySelector('input[type="checkbox"]')?.checked;
                const matches = query !== '' && item.dataset.name.includes(query);
                item.hidden = !productIsAvailable || (!checked && !matches);
                if (!item.hidden) visibleCount++;
            });
            if (prompt) prompt.hidden = query !== '' || visibleCount > 0;
            if (empty) empty.hidden = query === '' || visibleCount > 0;
        };
        country?.addEventListener('change', () => {
            items.forEach((item) => { item.querySelector('input[type="checkbox"]').checked = false; });
            search.placeholder = country.value ? 'Filtrer les produits disponibles dans ce pays…' : 'Choisis d’abord le pays';
            filterProducts();
        });
        search.addEventListener('input', filterProducts);
        items.forEach((item) => item.querySelector('input[type="checkbox"]')?.addEventListener('change', filterProducts));
        filterProducts();
    });
</script>
@endsection
