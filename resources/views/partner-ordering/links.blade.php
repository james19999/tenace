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
    .partner-link-actions { display:flex; align-items:center; justify-content:flex-start; flex-wrap:wrap; gap:6px; min-width:0; }
    .partner-link-actions a, .partner-link-actions button { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:34px; padding:6px 9px; border:1px solid var(--app-border,#e3e8ef); background:var(--app-surface,#fff); color:var(--app-primary,#147d25); font-size:11px; font-weight:700; text-decoration:none; cursor:pointer; }
    .partner-link-actions a:hover, .partner-link-actions button:hover { background:var(--app-primary,#147d25)!important; border-color:var(--app-primary,#147d25)!important; color:#fff!important; text-decoration:none; }
    .partner-link-actions form { margin:0; }
    .partner-link-actions form button { color:#a33b3b; }
    .partner-link-actions .delete-link-button:hover { background:#a12b28!important; border-color:#a12b28!important; color:#fff!important; }
    .partner-link-actions .partner-link-copy.is-copied { background:#e8f5ed; border-color:#b9dec7; color:#247347; }
    html[data-app-theme="dark"] .partner-link-actions a, html[data-app-theme="dark"] .partner-link-actions button { background:var(--app-surface); border-color:var(--app-border); color:var(--app-text); }
    html[data-app-theme="dark"] .partner-link-actions .partner-link-copy.is-copied { background:#173b2b; border-color:#27583d; color:#85d6a5; }
    .partner-link-delete-dialog { width:min(440px,calc(100vw - 28px)); padding:26px; border:1px solid var(--app-border,#e3e8ef); background:var(--app-surface,#fff); color:var(--app-text,#202938); box-shadow:0 24px 80px rgba(0,0,0,.25); }
    .partner-link-delete-dialog::backdrop { background:rgba(15,23,42,.62); backdrop-filter:blur(3px); }
    .partner-link-delete-dialog h2 { margin:0 0 8px; font-size:20px; font-weight:800; }
    .partner-link-delete-dialog p { margin:0 0 22px; color:var(--app-muted,#748093); line-height:1.5; }
    .partner-link-delete-actions { display:flex; justify-content:flex-end; gap:9px; }
    .partner-link-delete-actions button { min-height:40px; padding:8px 14px; border:1px solid var(--app-border,#e3e8ef); background:var(--app-surface,#fff); color:var(--app-text,#202938); font-weight:700; cursor:pointer; }
    .partner-link-delete-actions .confirm { border-color:#b93636; background:#b93636; color:#fff; }
    html[data-app-theme="dark"] .partner-link-delete-dialog { background:var(--app-surface,#1e232b); color:var(--app-text,#edf1f5); }
    .partner-links-page { min-width:0; }
    .partner-links-page .card { min-width:0; }
    .partner-links-page .form-control { min-width:0; }
    .partner-link-products-box { max-height:320px; overflow:auto; border:1px solid var(--app-border,#e3e8ef); padding:12px; }
    .partner-link-product { gap:8px; overflow-wrap:anywhere; }
    .partner-link-public-url { display:block; max-width:100%; overflow-wrap:anywhere; word-break:break-word; }
    .partner-link-table-wrap { width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .partner-links-table { width:100%; min-width:100%; table-layout:fixed; }
    .partner-links-table th:nth-child(1),.partner-links-table td:nth-child(1) { width:14%; }
    .partner-links-table th:nth-child(2),.partner-links-table td:nth-child(2) { width:28%; }
    .partner-links-table th:nth-child(3),.partner-links-table td:nth-child(3),.partner-links-table th:nth-child(4),.partner-links-table td:nth-child(4) { width:9%; }
    .partner-links-table th:nth-child(5),.partner-links-table td:nth-child(5) { width:12%; }
    .partner-links-table th:nth-child(6),.partner-links-table td:nth-child(6) { width:28%; }
    .partner-links-table td { white-space:normal; overflow-wrap:anywhere; vertical-align:middle; }
    .partner-link-title { overflow-wrap:anywhere; }
    @media(max-width:767.98px) {
        .partner-links-page { padding-left:12px; padding-right:12px; }
        .partner-links-page h1 { font-size:1.5rem; }
        .partner-links-page .card-header { padding:14px 16px; }
        .partner-links-page .card-body { padding:16px; }
        .partner-links-page .form-row { margin-left:0; margin-right:0; }
        .partner-links-page .form-row>[class*="col-"] { padding-left:0; padding-right:0; }
        .partner-links-page .form-group { margin-bottom:14px; }
        .partner-links-page .select2-container { width:100%!important; }
        .partner-country-shortcuts { padding:14px; }
        .partner-country-shortcut { white-space:normal; text-align:center; }
        .partner-link-actions { min-width:0; justify-content:flex-start; }
        .partner-link-delete-dialog { padding:21px 18px; }
        .partner-link-delete-actions { flex-direction:column-reverse; }
        .partner-link-delete-actions button { width:100%; }
        .partner-links-table { min-width:0; }
        .partner-links-table thead { display:none; }
        .partner-links-table,.partner-links-table tbody { display:block; width:100%; }
        .partner-links-table tbody tr { display:block; margin:0 0 12px; padding:7px 12px; border:1px solid var(--app-border,#e3e8ef); background:var(--app-surface,#fff); }
        .partner-links-table tbody tr:last-child { margin-bottom:0; }
        .partner-links-table tbody td { display:grid; grid-template-columns:minmax(88px, .7fr) minmax(0,1.3fr); align-items:start; gap:10px; padding:9px 0; border:0; border-bottom:1px solid var(--app-border,#e3e8ef); white-space:normal; overflow-wrap:anywhere; }
        .partner-links-table tbody td:last-child { border-bottom:0; }
        .partner-links-table tbody td[data-label]::before { content:attr(data-label); color:var(--app-muted,#748093); font-size:11px; font-weight:750; }
        .partner-links-table .partner-link-public-url { max-width:none; }
        .partner-links-table .partner-link-actions { grid-column:1/-1; }
        .partner-links-table tbody td[data-label="Actions"] { grid-template-columns:1fr; }
        .partner-links-table tbody td[colspan] { display:block; }
        .partner-links-table .partner-link-actions a,.partner-links-table .partner-link-actions button { flex:1 1 auto; min-width:calc(50% - 6px); }
        .partner-links-table .partner-link-actions form { flex:1 1 auto; }
        .partner-links-table .partner-link-actions form button { width:100%; }
    }
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
                    <div class="form-group col-md-4"><label for="link-country">Pays du lien</label><select class="form-control js-country-search" id="link-country" name="country_code" required><option value="">Choisir un pays</option>@foreach($countries as $code => $country)<option value="{{ $code }}" @selected(old('country_code') === $code)>{{ $country['label'] }}</option>@endforeach</select></div>
                    <div class="form-group col-md-4"><label for="link-title">Nom du lien (facultatif)</label><input class="form-control" id="link-title" name="title" value="{{ old('title') }}" maxlength="160" placeholder="Ex. Catalogue Bénin"></div>
                    <div class="form-group col-md-4"><label for="link-expiry">Expiration (facultative)</label><input class="form-control" id="link-expiry" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"></div>
                </div>
                <label for="product-search">Produits à mettre en avant <span class="text-danger">*</span></label>
                <input id="product-search" type="search" class="form-control mb-2" placeholder="Choisis d’abord le pays">
                <div id="product-search-results" class="partner-link-products-box">
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
        <div class="table-responsive partner-link-table-wrap">
            <table class="table table-hover mb-0 partner-links-table">
                <thead class="thead-light"><tr><th>Pays</th><th>Lien</th><th>Produits</th><th>Commandes</th><th>État</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td data-label="Pays">{{ $countries[$link->country_code]['label'] ?? 'À rattacher' }}</td><td data-label="Lien"><strong class="partner-link-title">{{ $link->title }}</strong><small class="d-block text-muted">Créé par {{ $link->creator?->name ?? 'Compte supprimé' }}</small></td>
                        <td data-label="Produits">{{ $link->products_count }}</td><td data-label="Commandes">{{ $link->orders_count }}</td>
                        <td data-label="État">{{ $link->isAvailable() ? 'Actif' : ($link->is_active ? 'Expiré' : 'Désactivé') }}</td>
                        <td data-label="Actions"><div class="partner-link-actions">
                            <button type="button" class="partner-link-copy" data-copy-url="{{ route('public-order.show', $link->token) }}"><span class="material-icons" aria-hidden="true">content_copy</span><span>Copier le lien</span></button>
                            <a href="{{ route('public-order.show', $link->token) }}" target="_blank" rel="noopener"><span class="material-icons" aria-hidden="true">open_in_new</span><span>Ouvrir</span></a>
                            <form method="POST" action="{{ route('partner-ordering.links.toggle', $link) }}">@csrf @method('PUT')<button type="submit"><span class="material-icons" aria-hidden="true">{{ $link->is_active ? 'link_off' : 'link' }}</span><span>{{ $link->is_active ? 'Désactiver' : 'Activer' }}</span></button></form>
                            @unless ($link->is_active)
                                <form method="POST" action="{{ route('partner-ordering.links.destroy', $link) }}" data-link-delete-confirm data-link-title="{{ $link->title }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="delete-link-button"><span class="material-icons" aria-hidden="true">delete</span><span>Supprimer</span></button>
                                </form>
                            @endunless
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun lien public créé pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($links->hasPages())<div class="card-footer">{{ $links->links() }}</div>@endif
    </section>

    <dialog class="partner-link-delete-dialog" id="partnerLinkDeleteDialog" aria-labelledby="partnerLinkDeleteTitle" aria-describedby="partnerLinkDeleteMessage">
        <h2 id="partnerLinkDeleteTitle">Supprimer ce lien désactivé ?</h2>
        <p id="partnerLinkDeleteMessage"></p>
        <div class="partner-link-delete-actions"><button type="button" data-link-delete-cancel>Annuler</button><button type="button" class="confirm" id="partnerLinkDeleteConfirm">Supprimer le lien</button></div>
    </dialog>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countrySelect = document.getElementById('link-country');
        if (countrySelect && window.jQuery && jQuery.fn.select2 && !jQuery(countrySelect).hasClass('select2-hidden-accessible')) {
            jQuery(countrySelect).select2({
                width: '100%',
                minimumResultsForSearch: 0,
                placeholder: 'Rechercher un pays…',
                language: { noResults: () => 'Aucun pays trouvé', searching: () => 'Recherche…' }
            });
        }
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
        const deleteDialog = document.getElementById('partnerLinkDeleteDialog');
        const deleteConfirm = document.getElementById('partnerLinkDeleteConfirm');
        if (deleteDialog && deleteConfirm) {
            let pendingDeleteForm = null;
            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-link-delete-confirm')) return;
                event.preventDefault();
                pendingDeleteForm = form;
                deleteDialog.querySelector('#partnerLinkDeleteMessage').textContent = `Le lien « ${form.dataset.linkTitle || 'sans titre'} » sera supprimé. Les commandes existantes seront conservées.`;
                deleteDialog.showModal();
            });
            deleteDialog.querySelectorAll('[data-link-delete-cancel]').forEach(button => button.addEventListener('click', () => deleteDialog.close()));
            deleteDialog.addEventListener('click', event => { if (event.target === deleteDialog) deleteDialog.close(); });
            deleteDialog.addEventListener('close', () => { pendingDeleteForm = null; });
            deleteConfirm.addEventListener('click', () => {
                if (!pendingDeleteForm) return;
                const form = pendingDeleteForm;
                pendingDeleteForm = null;
                form.removeAttribute('data-link-delete-confirm');
                deleteDialog.close();
                form.requestSubmit();
            });
        }
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
        if (country) $(country).on('change', () => {
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
