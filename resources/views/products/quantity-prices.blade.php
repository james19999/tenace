@extends('layouts.admin')

@section('content')
<style>
    .wholesale-prices { --wp-ink: #202938; --wp-muted: #718096; --wp-border: #e3e8ef; --wp-accent: #861619; }
    .wholesale-prices .wp-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:22px; }
    .wholesale-prices .wp-heading h2 { margin:0 0 5px; color:var(--wp-ink); font-weight:700; }
    .wholesale-prices .wp-heading p { margin:0; color:var(--wp-muted); }
    .wholesale-prices .wp-product { border:1px solid var(--wp-border); border-radius:10px; margin-bottom:12px; overflow:hidden; background:#fff; box-shadow:0 2px 8px rgba(28,39,56,.035); }
    .wholesale-prices .wp-product-summary { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:15px 18px; cursor:pointer; list-style:none; }
    .wholesale-prices .wp-product-summary::-webkit-details-marker { display:none; }
    .wholesale-prices .wp-product-summary:after { content:'+'; display:grid; place-items:center; flex:0 0 30px; width:30px; height:30px; border:1px solid var(--wp-border); color:var(--wp-accent); font-size:20px; font-weight:500; }
    .wholesale-prices details[open] > .wp-product-summary:after { content:'−'; }
    .wholesale-prices .wp-product-summary-main { min-width:0; }
    .wholesale-prices .wp-product-summary-sub { display:flex; flex-wrap:wrap; align-items:center; gap:8px 14px; margin-top:6px; color:var(--wp-muted); font-size:12px; }
    .wholesale-prices .wp-product-name { color:var(--wp-ink); font-size:17px; font-weight:700; }
    .wholesale-prices .wp-stock { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:20px; background:#edf7f0; color:#267443; font-size:13px; font-weight:600; white-space:nowrap; }
    .wholesale-prices .wp-stock-empty { background:#fff3e8; color:#a65713; }
    .wholesale-prices .wp-product-form { border-top:1px solid var(--wp-border); }
    .wholesale-prices .wp-product-body { padding:18px 20px; }
    .wholesale-prices .wp-base-row { display:grid; grid-template-columns:minmax(180px, 260px) 1fr; align-items:end; gap:18px; margin-bottom:18px; }
    .wholesale-prices label { display:block; margin-bottom:6px; color:#465267; font-size:13px; font-weight:600; }
    .wholesale-prices .form-control { height:42px; border-color:#d9e0e9; border-radius:7px; }
    .wholesale-prices .wp-tier-title { margin:0 0 9px; color:#465267; font-size:13px; font-weight:700; }
    .wholesale-prices .wp-tier-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .wholesale-prices .wp-tier { min-width:0; padding:11px; border:1px solid var(--wp-border); border-radius:8px; background:#fff; }
    .wholesale-prices .wp-tier-heading { margin-bottom:9px; color:var(--wp-muted); font-size:12px; font-weight:700; }
    .wholesale-prices .wp-tier-heading { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .wholesale-prices .wp-tier-remove { padding:3px 7px; border:1px solid #f0d6d6; background:#fff; color:#a33; font-size:11px; font-weight:700; cursor:pointer; }
    .wholesale-prices .wp-add-tier { margin-top:12px; padding:9px 13px; border:1px solid #d6dce5; background:#fff; color:#394253; font-weight:700; cursor:pointer; }
    .wholesale-prices .wp-add-tier:disabled { opacity:.5; cursor:not-allowed; }
    .wholesale-prices .wp-tier .form-group { margin:0 0 9px; }
    .wholesale-prices .wp-tier .form-group:last-child { margin-bottom:0; }
    .wholesale-prices .wp-tier .form-control { height:38px; }
    .wholesale-prices .wp-product-actions { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:17px; padding-top:15px; border-top:1px solid #edf0f4; }
    .wholesale-prices .wp-available { display:flex; align-items:center; gap:8px; margin:0; color:#465267; font-size:14px; font-weight:600; }
    .wholesale-prices .wp-available input { width:17px; height:17px; margin:0; accent-color:var(--wp-accent); }
    .wholesale-prices .wp-save { min-width:150px; padding:10px 18px; border:0; border-radius:7px; background:var(--wp-accent); color:#fff; font-weight:700; }
    .wholesale-prices .wp-save:hover { background:#6e1114; }
    .wholesale-prices .wp-empty { padding:28px; border:1px dashed #cbd4df; border-radius:10px; color:var(--wp-muted); text-align:center; }
    .wholesale-prices .wp-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin:18px 0; }
    .wholesale-prices .wp-result-count { color:var(--wp-muted); font-size:13px; white-space:nowrap; }
    .wholesale-prices .wp-search { display:flex; width:min(100%, 480px); gap:8px; }
    .wholesale-prices .wp-search .form-control { min-width:0; }
    .wholesale-prices .wp-search-button,.wholesale-prices .wp-clear-search { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:8px 13px; border-radius:7px; font-size:13px; font-weight:700; white-space:nowrap; }
    .wholesale-prices .wp-search-button { border:0; background:#343b49; color:#fff; }
    .wholesale-prices .wp-clear-search { border:1px solid var(--wp-border); color:#596477; text-decoration:none; }
    .wholesale-prices .wp-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:20px 0 5px; }
    .wholesale-prices .wp-pagination-summary { color:var(--wp-muted); font-size:13px; }
    .wholesale-prices .wp-pagination .pagination { margin:0; }
    @media (max-width: 991.98px) { .wholesale-prices .wp-tier-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 575.98px) {
        .wholesale-prices { padding-left:10px; padding-right:10px; }
        .wholesale-prices .wp-heading { align-items:flex-start; flex-direction:column; margin-bottom:16px; }
        .wholesale-prices .wp-heading h2 { font-size:22px; }
        .wholesale-prices .wp-toolbar { align-items:stretch; flex-direction:column; }
        .wholesale-prices .wp-search { width:100%; }
        .wholesale-prices .wp-pagination { align-items:flex-start; flex-direction:column; }
        .wholesale-prices .wp-product-summary { padding:13px; }
        .wholesale-prices .wp-product-summary:after { flex-basis:28px; width:28px; height:28px; }
        .wholesale-prices .wp-product-body { padding:14px; }
        .wholesale-prices .wp-base-row { grid-template-columns:1fr; gap:12px; margin-bottom:15px; }
        .wholesale-prices .wp-tier-grid { grid-template-columns:1fr 1fr; gap:8px; }
        .wholesale-prices .wp-tier { padding:9px; }
        .wholesale-prices .wp-product-actions { align-items:stretch; flex-direction:column; }
        .wholesale-prices .wp-save { width:100%; }
    }
</style>

<div class="container-fluid py-3 wholesale-prices">
    <header class="wp-heading">
        <div>
            <h2>Tarifs dégressifs par produit</h2>
            <p>Définis les seuils et prix unitaires de gros pour la boutique TENACOS.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <div class="wp-toolbar">
        <span class="wp-result-count">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} sur {{ $products->total() }} produit(s)</span>
        <form method="GET" action="{{ route('product-tier-prices.index') }}" class="wp-search" role="search">
            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Rechercher un produit…" aria-label="Rechercher un produit" autocomplete="off" data-live-product-search>
            <button class="wp-search-button" type="submit" aria-label="Lancer la recherche"><span class="material-icons" aria-hidden="true">search</span></button>
            @if($search !== '')<a class="wp-clear-search" href="{{ route('product-tier-prices.index') }}">Effacer</a>@endif
        </form>
    </div>

    @forelse($products as $product)
        @php($tiers = $product->quantityPriceTiers)
        @php($useOldValues = (int) old('product_id', 0) === (int) $product->id)
        @php($oldMinimums = $useOldValues ? old('min_quantity', []) : [])
        @php($displayTierCount = min(8, max(1, $tiers->count(), is_array($oldMinimums) ? count($oldMinimums) : 0)))
        <details class="wp-product" @if($errors->any() && $useOldValues) open @endif>
            <summary class="wp-product-summary">
                <div class="wp-product-summary-main">
                    <div class="wp-product-name">{{ $product->name }}</div>
                    <div class="wp-product-summary-sub">
                        <span>Prix de base : <strong>{{ number_format((float) $product->price, 0, ',', ' ') }} F</strong></span>
                        <span>{{ $tiers->count() }} palier(s) configuré(s)</span>
                        @foreach($tiers as $tier)<span class="wp-stock">Dès {{ $tier->min_quantity }} · {{ number_format((float) $tier->unit_price, 0, ',', ' ') }} F/u</span>@endforeach
                    </div>
                </div>
            </summary>
            <form method="POST" action="{{ route('product-tier-prices.save', $product) }}" class="wp-product-form" data-tier-form>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="wp-product-body">
                <div class="wp-base-row">
                    <div class="wp-tier-note text-muted small">Le prix de base reste {{ number_format((float) $product->price, 0, ',', ' ') }} F. Chaque tarif ci-dessous s’applique dès que la quantité minimale est atteinte.</div>
                </div>

                <div class="wp-tier-title">Tarifs par quantité <span class="font-weight-normal text-muted">(facultatif)</span></div>
                <div class="wp-tier-grid">
                    @foreach(range(0, $displayTierCount - 1) as $i)
                        @php($tier = $tiers[$i] ?? null)
                        <div class="wp-tier">
                            <div class="wp-tier-heading"><span>Palier {{ $i + 1 }}</span><button type="button" class="wp-tier-remove" data-remove-tier>Retirer</button></div>
                            <div class="form-group">
                                <label for="min-quantity-{{ $product->id }}-{{ $i }}">À partir de (unités)</label>
                                <input id="min-quantity-{{ $product->id }}-{{ $i }}" class="form-control" type="number" min="2" name="min_quantity[]" value="{{ $useOldValues ? old('min_quantity.'.$i) : $tier?->min_quantity }}" placeholder="Ex. 3">
                            </div>
                            <div class="form-group">
                                <label for="unit-price-{{ $product->id }}-{{ $i }}">Prix par unité</label>
                                <input id="unit-price-{{ $product->id }}-{{ $i }}" class="form-control" type="number" min="1" name="unit_price[]" value="{{ $useOldValues ? old('unit_price.'.$i) : $tier?->unit_price }}" placeholder="Ex. 8 000">
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="wp-add-tier" data-add-tier @disabled($displayTierCount >= 8)>+ Ajouter un palier</button>
                <template data-tier-template>
                    <div class="wp-tier">
                        <div class="wp-tier-heading"><span>Palier __NUMBER__</span><button type="button" class="wp-tier-remove" data-remove-tier>Retirer</button></div>
                        <div class="form-group"><label for="min-quantity-{{ $product->id }}-__INDEX__">À partir de (unités)</label><input id="min-quantity-{{ $product->id }}-__INDEX__" class="form-control" type="number" min="2" name="min_quantity[]" placeholder="Ex. 3"></div>
                        <div class="form-group"><label for="unit-price-{{ $product->id }}-__INDEX__">Prix par unité</label><input id="unit-price-{{ $product->id }}-__INDEX__" class="form-control" type="number" min="1" name="unit_price[]" placeholder="Ex. 8 000"></div>
                    </div>
                </template>

                <div class="wp-product-actions">
                    <span></span><button class="wp-save" type="submit">Enregistrer les tarifs</button>
                </div>
            </div>
            </form>
        </details>
    @empty
        <div class="wp-empty">{{ $search !== '' ? 'Aucun produit ne correspond à cette recherche.' : 'Aucun produit n’est approvisionné dans ta boutique pour le moment.' }}</div>
    @endforelse

    @if($products->hasPages())
        <div class="wp-pagination">
            <span class="wp-pagination-summary">Page {{ $products->currentPage() }} sur {{ $products->lastPage() }}</span>
            {{ $products->links() }}
        </div>
    @endif
</div>
<script>
    (function () {
        document.addEventListener('click', function (event) {
            var addButton = event.target.closest('[data-add-tier]');
            if (addButton) {
                var form = addButton.closest('[data-tier-form]');
                var grid = form && form.querySelector('.wp-tier-grid');
                var template = form && form.querySelector('[data-tier-template]');
                if (!grid || !template) return;
                var count = grid.querySelectorAll('.wp-tier').length;
                if (count >= 8) return;
                var html = template.innerHTML.replaceAll('__INDEX__', String(count)).replaceAll('__NUMBER__', String(count + 1));
                grid.insertAdjacentHTML('beforeend', html);
                addButton.disabled = grid.querySelectorAll('.wp-tier').length >= 8;
                return;
            }
            var removeButton = event.target.closest('[data-remove-tier]');
            if (removeButton) {
                var tier = removeButton.closest('.wp-tier');
                var parentForm = removeButton.closest('[data-tier-form]');
                if (tier && parentForm) {
                    tier.remove();
                    var add = parentForm.querySelector('[data-add-tier]');
                    if (add) add.disabled = false;
                    parentForm.querySelectorAll('.wp-tier-heading span').forEach(function (label, index) { label.textContent = 'Palier ' + (index + 1); });
                }
            }
        });

        var search = document.querySelector('[data-live-product-search]');
        if (!search) return;
        var form = search.form;
        var timer;
        search.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                if (search.value.trim() !== @json($search)) form.requestSubmit();
            }, 350);
        });
        form.addEventListener('submit', function () { window.clearTimeout(timer); });
    })();
</script>
@endsection
