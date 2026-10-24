@extends('layouts.admin')

@section('content')
<style>
    .wholesale-prices{--wp-ink:#202938;--wp-muted:#718096;--wp-border:#e3e8ef;--wp-accent:#861619;color:var(--wp-ink)}
    .wholesale-prices .wp-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:22px}
    .wholesale-prices .wp-heading h2{margin:0 0 5px;color:var(--wp-ink);font-weight:700}
    .wholesale-prices .wp-heading p{margin:0;color:var(--wp-muted)}
    .wholesale-prices .wp-product{border:1px solid var(--wp-border);border-radius:10px;margin-bottom:12px;overflow:hidden;background:#fff;box-shadow:0 2px 8px rgba(28,39,56,.035)}
    .wholesale-prices .wp-product-summary{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 18px;cursor:pointer;list-style:none}
    .wholesale-prices .wp-product-summary::-webkit-details-marker{display:none}
    .wholesale-prices .wp-product-summary:after{content:'+';display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border:1px solid var(--wp-border);color:var(--wp-accent);font-size:20px;font-weight:500}
    .wholesale-prices details[open]>.wp-product-summary:after{content:'−'}
    .wholesale-prices .wp-product-summary-main{min-width:0}
    .wholesale-prices .wp-product-summary-sub{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;margin-top:6px;color:var(--wp-muted);font-size:12px}
    .wholesale-prices .wp-product-name{color:var(--wp-ink);font-size:17px;font-weight:700}
    .wholesale-prices .wp-stock{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:20px;background:#edf7f0;color:#267443;font-size:13px;font-weight:600;white-space:nowrap}
    .wholesale-prices .wp-product-form{border-top:1px solid var(--wp-border)}
    .wholesale-prices .wp-product-body{padding:18px 20px}
    .wholesale-prices label{display:block;margin-bottom:6px;color:#465267;font-size:13px;font-weight:600}
    .wholesale-prices .form-control{height:42px;border-color:#d9e0e9;border-radius:7px}
    .wholesale-prices .wp-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:18px 0}
    .wholesale-prices .wp-result-count{color:var(--wp-muted);font-size:13px;white-space:nowrap}
    .wholesale-prices .wp-search{display:flex;width:min(100%,480px);gap:8px}
    .wholesale-prices .wp-search .form-control{min-width:0}
    .wholesale-prices .wp-search-button,.wholesale-prices .wp-clear-search{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 13px;border-radius:7px;font-size:13px;font-weight:700;white-space:nowrap}
    .wholesale-prices .wp-search-button{border:0;background:#343b49;color:#fff}
    .wholesale-prices .wp-clear-search{border:1px solid var(--wp-border);color:#596477;text-decoration:none}
    .wholesale-prices .wp-base-row{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #edf0f4;color:var(--wp-muted);font-size:13px}
    .wholesale-prices .wp-base-row strong{color:var(--wp-ink)}
    .wholesale-prices .wp-tier-title{margin:0 0 9px;color:#465267;font-size:13px;font-weight:700}
    .wholesale-prices .wp-tier-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .wholesale-prices .wp-tier{min-width:0;padding:11px;border:1px solid var(--wp-border);border-radius:8px;background:#fff}
    .wholesale-prices .wp-tier-heading{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:9px;color:var(--wp-muted);font-size:12px;font-weight:700}
    .wholesale-prices .wp-tier-remove{padding:3px 7px;border:1px solid #f0d6d6;border-radius:5px;background:#fff;color:#a33;font-size:11px;font-weight:700;cursor:pointer}
    .wholesale-prices .wp-tier .form-group{margin:0 0 9px}
    .wholesale-prices .wp-tier .form-group:last-child{margin-bottom:0}
    .wholesale-prices .wp-tier .form-control,.wholesale-prices .wp-tier select{height:38px}
    .wholesale-prices .wp-add-tier{margin-top:12px;padding:9px 13px;border:1px solid #d6dce5;border-radius:6px;background:#fff;color:#394253;font-weight:700;cursor:pointer}
    .wholesale-prices .wp-add-tier:disabled{opacity:.5;cursor:not-allowed}
    .wholesale-prices .wp-product-actions{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:17px;padding-top:15px;border-top:1px solid #edf0f4}
    .wholesale-prices .wp-save{min-width:150px;padding:10px 18px;border:0;border-radius:7px;background:var(--wp-accent);color:#fff;font-weight:700}
    .wholesale-prices .wp-save:hover{background:#6e1114}
    .wholesale-prices .wp-empty{padding:28px;border:1px dashed #cbd4df;border-radius:10px;color:var(--wp-muted);text-align:center}
    .wholesale-prices .wp-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:20px 0 5px}
    .wholesale-prices .wp-pagination-summary{color:var(--wp-muted);font-size:13px}
    .wholesale-prices .wp-pagination .pagination{margin:0}
    .wholesale-prices .wp-settings{padding:0}
    .wholesale-prices .wp-settings-heading{display:flex;align-items:center;justify-content:flex-end;gap:16px;margin-bottom:14px}
    .wholesale-prices .wp-active{display:flex;align-items:center;gap:9px;margin:0;white-space:nowrap}
    .wholesale-prices .wp-active input{width:18px;height:18px;margin:0;accent-color:var(--wp-accent)}
    .wholesale-prices .wp-periods{display:grid;gap:10px}
    .wholesale-prices .wp-period{padding:12px;border:1px solid var(--wp-border);border-radius:8px}
    .wholesale-prices .wp-period-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;color:var(--wp-muted);font-size:12px;font-weight:700}
    .wholesale-prices .wp-period-fields{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(140px,.6fr);gap:12px}
    .wholesale-prices .wp-period-start{display:flex;gap:8px;min-width:0}
    .wholesale-prices .wp-period-start input{min-width:0;flex:1}
    .wholesale-prices .wp-period-start button,.wholesale-prices .wp-add-period{padding:8px 12px;border:1px solid var(--wp-border);border-radius:7px;background:#fff;color:#465267;font-size:12px;font-weight:700;white-space:nowrap}
    .wholesale-prices .wp-period-countdown{display:flex;justify-content:space-between;gap:12px;margin-top:10px;padding:9px 11px;border:1px solid var(--wp-border);border-radius:7px;background:#fbfcfd;color:#596477;font-size:12px}
    .wholesale-prices .wp-period-countdown strong{color:var(--wp-ink)}
    .wholesale-prices .wp-period-countdown.is-running{border-color:#d6e9da;background:#f5fbf6;color:#28643a}
    .wholesale-prices .wp-period-countdown.is-ended{border-color:#f0d6d6;background:#fff7f7;color:#a33}
    .wholesale-prices .wp-save-settings{display:flex;justify-content:flex-end;margin-top:14px}
    .wholesale-prices .wp-add-period{margin-bottom:10px;cursor:pointer}
    .wholesale-prices .wp-period-empty{padding:14px;border:1px dashed #cbd4df;border-radius:8px;color:var(--wp-muted);font-size:13px}
    .wholesale-prices .wp-confirm-dialog{width:min(430px,calc(100vw - 28px));padding:0;border:1px solid #e3e6ec;border-radius:12px;background:#fff;color:var(--wp-ink);box-shadow:0 22px 70px rgba(20,27,40,.25)}
    .wholesale-prices .wp-confirm-dialog::backdrop{background:rgba(24,30,42,.48);backdrop-filter:blur(2px)}
    .wholesale-prices .wp-confirm-body{padding:22px}
    .wholesale-prices .wp-confirm-body h3{margin:0 0 7px;font-size:17px;font-weight:800}
    .wholesale-prices .wp-confirm-body p{margin:0;color:var(--wp-muted);font-size:13px;line-height:1.55}
    .wholesale-prices .wp-confirm-actions{display:flex;justify-content:flex-end;gap:9px;padding:14px 22px;border-top:1px solid #edf0f4;background:#fbfcfd}
    .wholesale-prices .wp-confirm-actions button{min-height:38px;padding:8px 14px;border:1px solid #dfe3ea;border-radius:7px;background:#fff;color:#465267;font-size:13px;font-weight:700}
    .wholesale-prices .wp-confirm-actions [data-confirm]{border-color:#a12b28;background:#a12b28;color:#fff}
    @media(max-width:991.98px){.wholesale-prices .wp-tier-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:575.98px){.wholesale-prices{padding-left:10px;padding-right:10px}.wholesale-prices .wp-heading{align-items:flex-start;flex-direction:column;margin-bottom:16px}.wholesale-prices .wp-heading h2{font-size:22px}.wholesale-prices .wp-toolbar{align-items:stretch;flex-direction:column}.wholesale-prices .wp-search{width:100%}.wholesale-prices .wp-pagination{align-items:flex-start;flex-direction:column}.wholesale-prices .wp-product-summary{padding:13px}.wholesale-prices .wp-product-summary:after{flex-basis:28px;width:28px;height:28px}.wholesale-prices .wp-product-body{padding:14px}.wholesale-prices .wp-tier-grid{grid-template-columns:minmax(0,1fr);gap:8px}.wholesale-prices .wp-tier{padding:11px}.wholesale-prices .wp-product-actions{align-items:stretch;flex-direction:column}.wholesale-prices .wp-save{width:100%}.wholesale-prices .wp-settings-heading{justify-content:flex-start}.wholesale-prices .wp-period-fields{grid-template-columns:1fr}.wholesale-prices .wp-period-start{flex-direction:column}.wholesale-prices .wp-period-start button{width:100%}.wholesale-prices .wp-period-countdown{align-items:flex-start;flex-direction:column}.wholesale-prices .wp-save-settings .wp-save{width:100%}.wholesale-prices .wp-confirm-actions{flex-direction:column-reverse;padding:12px}.wholesale-prices .wp-confirm-actions button{width:100%}}
</style>

<div class="container-fluid py-3 wholesale-prices">
    <header class="wp-heading"><div><h2>Promotions par produit</h2><p>Configure les remises par quantité et leur période d’application.</p></div></header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif

    @php($configuredPeriods = old('periods', $promotion->schedulePeriods()))
    <details class="wp-product wp-settings">
        <summary class="wp-product-summary"><div class="wp-product-summary-main"><div class="wp-product-name">Activation et période</div><div class="wp-product-summary-sub"><span>Les promotions configurées ci-dessous suivent ces dates.</span></div></div></summary>
        <div class="wp-product-body">
        <form method="POST" action="{{ route('quantity-promotions.save') }}" data-period-form>
            @csrf
            <div class="wp-settings-heading"><label class="wp-active"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active))> Activer les promotions</label></div>
            <div class="wp-periods" data-period-list>
                <div class="wp-period-empty" data-period-empty @if($configuredPeriods !== []) hidden @endif>Aucune période programmée. Ajoute une période avant d’activer les promotions.</div>
                @foreach($configuredPeriods as $index => $period)
                    <div class="wp-period" data-period data-period-index="{{ $index }}" data-saved-index="{{ $index }}">
                        <div class="wp-period-head"><span data-period-title>Période {{ $index + 1 }}</span><button type="button" class="wp-tier-remove" data-remove-period>Supprimer</button></div>
                        <div class="wp-period-fields">
                            <div><label for="period-start-{{ $index }}">Date et heure de début</label><div class="wp-period-start"><input id="period-start-{{ $index }}" class="form-control" type="datetime-local" data-period-field="starts_at" name="periods[{{ $index }}][starts_at]" value="{{ !empty($period['starts_at']) ? \Illuminate\Support\Carbon::parse($period['starts_at'])->format('Y-m-d\\TH:i') : '' }}"><button type="button" data-start-today>Commence aujourd’hui</button></div></div>
                            <div><label for="period-duration-{{ $index }}">Durée (jours)</label><input id="period-duration-{{ $index }}" class="form-control" type="number" min="1" max="365" data-period-field="duration_days" name="periods[{{ $index }}][duration_days]" value="{{ $period['duration_days'] ?? '' }}" placeholder="Ex. 7"></div>
                        </div>
                        <div class="wp-period-countdown" data-period-countdown><span data-countdown-label>Compte à rebours</span><strong data-countdown-value>Renseigne la date et la durée.</strong></div>
                    </div>
                @endforeach
            </div>
            <template data-period-template><div class="wp-period" data-period data-period-index="__INDEX__"><div class="wp-period-head"><span data-period-title></span><button type="button" class="wp-tier-remove" data-remove-period>Retirer</button></div><div class="wp-period-fields"><div><label for="period-start-__INDEX__">Date et heure de début</label><div class="wp-period-start"><input id="period-start-__INDEX__" class="form-control" type="datetime-local" data-period-field="starts_at" name="periods[__INDEX__][starts_at]"><button type="button" data-start-today>Commence aujourd’hui</button></div></div><div><label for="period-duration-__INDEX__">Durée (jours)</label><input id="period-duration-__INDEX__" class="form-control" type="number" min="1" max="365" data-period-field="duration_days" name="periods[__INDEX__][duration_days]" placeholder="Ex. 7"></div></div><div class="wp-period-countdown" data-period-countdown><span data-countdown-label>Compte à rebours</span><strong data-countdown-value>Renseigne la date et la durée.</strong></div></div></template>
            <button type="button" class="wp-add-period" data-add-period>+ Ajouter une période</button>
            <div class="wp-save-settings"><button class="wp-save" type="submit">Enregistrer les périodes et l’activation</button></div>
        </form>
        </div>
    </details>

    <div class="wp-toolbar">
        <span class="wp-result-count">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} sur {{ $products->total() }} produit(s)</span>
        <form method="GET" action="{{ route('quantity-promotions.index') }}" class="wp-search" role="search"><input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Rechercher un produit…" aria-label="Rechercher un produit" autocomplete="off" data-live-product-search><button class="wp-search-button" type="submit" aria-label="Lancer la recherche"><span class="material-icons" aria-hidden="true">search</span></button>@if($search !== '')<a class="wp-clear-search" href="{{ route('quantity-promotions.index') }}">Effacer</a>@endif</form>
    </div>

    @forelse($products as $product)
        @php($tiers = collect($tiersByProduct->get($product->id, []))->sortBy('min_quantity')->values())
        @php($oldProductId = (int) old('product_id', 0) === (int) $product->id)
        @php($oldMinimums = $oldProductId ? old('min_quantity', []) : [])
        @php($displayTierCount = min(8, max(1, $tiers->count(), is_array($oldMinimums) ? count($oldMinimums) : 0)))
        <details class="wp-product" @if($errors->any() && $oldProductId) open @endif>
            <summary class="wp-product-summary"><div class="wp-product-summary-main"><div class="wp-product-name">{{ $product->name }}</div><div class="wp-product-summary-sub"><span>Prix de base : <strong>{{ number_format((float) $product->price, 0, ',', ' ') }} F</strong></span><span>{{ $tiers->count() }} palier(s) configuré(s)</span>@foreach($tiers as $tier)<span class="wp-stock">Dès {{ $tier['min_quantity'] }} · {{ number_format((float) $tier['discount_value'], 0, ',', ' ') }} F/u</span>@endforeach</div></div></summary>
            <form method="POST" action="{{ route('quantity-promotions.products.save', ['product' => $product->id, 'search' => $search, 'page' => $products->currentPage()]) }}" class="wp-product-form" data-promo-tier-form>
                @csrf<input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="wp-product-body">
                    <div class="wp-base-row"><span>Prix normal du produit</span><strong>{{ number_format((float) $product->price, 0, ',', ' ') }} F</strong></div>
                    <div class="wp-tier-title">Paliers de remise <span class="font-weight-normal text-muted">(facultatif)</span></div>
                    <div class="wp-tier-grid">
                        @foreach(range(0, $displayTierCount - 1) as $i)
                            @php($tier = $tiers[$i] ?? null)
                            <div class="wp-tier"><div class="wp-tier-heading"><span>Palier {{ $i + 1 }}</span><button type="button" class="wp-tier-remove" data-remove-tier>Retirer</button></div>
                                <div class="form-group"><label for="min-{{ $product->id }}-{{ $i }}">À partir de (unités)</label><input id="min-{{ $product->id }}-{{ $i }}" class="form-control" type="number" min="1" max="999999" name="min_quantity[]" value="{{ $oldProductId ? old('min_quantity.'.$i, $tier['min_quantity'] ?? '') : ($tier['min_quantity'] ?? '') }}" placeholder="Ex. 8"></div>
                                <div class="form-group"><label for="value-{{ $product->id }}-{{ $i }}">Prix promotionnel par unité (F)</label><input id="value-{{ $product->id }}-{{ $i }}" class="form-control" type="number" min="1" max="999999999" name="unit_price[]" value="{{ $oldProductId ? old('unit_price.'.$i, $tier['discount_value'] ?? '') : ($tier['discount_value'] ?? '') }}" placeholder="Ex. 1 500"></div>
                            </div>
                        @endforeach
                    </div>
                    <template data-tier-template><div class="wp-tier"><div class="wp-tier-heading"><span>Palier __NUMBER__</span><button type="button" class="wp-tier-remove" data-remove-tier>Retirer</button></div><div class="form-group"><label for="min-{{ $product->id }}-__INDEX__">À partir de (unités)</label><input id="min-{{ $product->id }}-__INDEX__" class="form-control" type="number" min="1" max="999999" name="min_quantity[]" placeholder="Ex. 8"></div><div class="form-group"><label for="value-{{ $product->id }}-__INDEX__">Prix promotionnel par unité (F)</label><input id="value-{{ $product->id }}-__INDEX__" class="form-control" type="number" min="1" max="999999999" name="unit_price[]" placeholder="Ex. 1 500"></div></div></template>
                    <button type="button" class="wp-add-tier" data-add-tier @disabled($displayTierCount >= 8)>+ Ajouter un palier</button>
                    <div class="wp-product-actions"><span></span><button class="wp-save" type="submit">Enregistrer les promotions</button></div>
                </div>
            </form>
        </details>
    @empty
        <div class="wp-empty">{{ $search !== '' ? 'Aucun produit ne correspond à cette recherche.' : 'Aucun produit n’est disponible.' }}</div>
    @endforelse

    @if($products->hasPages())<div class="wp-pagination"><span class="wp-pagination-summary">Page {{ $products->currentPage() }} sur {{ $products->lastPage() }}</span>{{ $products->links() }}</div>@endif

    <dialog class="wp-confirm-dialog" data-delete-confirm data-delete-url="{{ route('quantity-promotions.periods.delete') }}"><div class="wp-confirm-body"><h3>Supprimer cette période ?</h3><p data-confirm-message>Cette période sera retirée.</p></div><div class="wp-confirm-actions"><button type="button" data-cancel>Annuler</button><button type="button" data-confirm>Supprimer</button></div></dialog>
</div>

<script>
    (function () {
        document.addEventListener('click', function (event) {
            var add = event.target.closest('[data-add-tier]');
            if (add) {
                var form = add.closest('[data-promo-tier-form]');
                var grid = form && form.querySelector('.wp-tier-grid');
                var template = form && form.querySelector('[data-tier-template]');
                if (!grid || !template) return;
                var count = grid.querySelectorAll('.wp-tier').length;
                if (count >= 8) return;
                grid.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(count)).replaceAll('__NUMBER__', String(count + 1)));
                add.disabled = grid.querySelectorAll('.wp-tier').length >= 8;
                return;
            }
            var remove = event.target.closest('[data-remove-tier]');
            if (remove) {
                var card = remove.closest('.wp-tier');
                var form = remove.closest('[data-promo-tier-form]');
                if (!card || !form) return;
                card.remove();
                form.querySelectorAll('.wp-tier-heading span').forEach(function (heading, index) { heading.textContent = 'Palier ' + (index + 1); });
                var addButton = form.querySelector('[data-add-tier]');
                if (addButton) addButton.disabled = false;
                return;
            }
            var removePeriod = event.target.closest('[data-remove-period]');
            if (removePeriod) {
                pendingPeriod = removePeriod.closest('[data-period]');
                deleteDialog.showModal();
                return;
            }
            var addPeriod = event.target.closest('[data-add-period]');
            if (addPeriod) {
                var count = periodList.querySelectorAll('[data-period]').length;
                if (count >= 12) return;
                periodList.insertAdjacentHTML('beforeend', periodTemplate.innerHTML.replaceAll('__INDEX__', String(count)));
                reindexPeriods();
                refreshCountdowns();
                return;
            }
            var today = event.target.closest('[data-start-today]');
            if (today) {
                var input = today.closest('[data-period]').querySelector('[data-period-field="starts_at"]');
                var now = new Date();
                input.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + 'T' + String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                refreshCountdowns();
            }
        });

        var search = document.querySelector('[data-live-product-search]');
        if (search) {
            var timer;
            search.addEventListener('input', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(function () { if (search.value.trim() !== @json($search)) search.form.requestSubmit(); }, 350);
            });
            search.form.addEventListener('submit', function () { window.clearTimeout(timer); });
        }

        var periodForm = document.querySelector('[data-period-form]');
        var periodList = periodForm.querySelector('[data-period-list]');
        var periodTemplate = periodForm.querySelector('[data-period-template]');
        var deleteDialog = document.querySelector('[data-delete-confirm]');
        var pendingPeriod = null;
        var deleteButton = deleteDialog.querySelector('[data-confirm]');
        var reindexPeriods = function () {
            var cards = Array.from(periodList.querySelectorAll('[data-period]'));
            cards.forEach(function (card, index) {
                card.dataset.periodIndex = index;
                card.querySelector('[data-period-title]').textContent = 'Période ' + (index + 1);
                ['starts_at', 'duration_days'].forEach(function (name) {
                    var input = card.querySelector('[data-period-field="' + name + '"]');
                    input.name = 'periods[' + index + '][' + name + ']';
                    input.id = (name === 'starts_at' ? 'period-start-' : 'period-duration-') + index;
                    card.querySelector('label[for^="' + (name === 'starts_at' ? 'period-start-' : 'period-duration-') + '"]').htmlFor = input.id;
                });
            });
            periodForm.querySelector('[data-period-empty]').hidden = cards.length > 0;
            periodForm.querySelector('[data-add-period]').disabled = cards.length >= 12;
        };
        var refreshCountdowns = function () {
            periodList.querySelectorAll('[data-period]').forEach(function (card) {
                var startValue = card.querySelector('[data-period-field="starts_at"]').value;
                var days = Number(card.querySelector('[data-period-field="duration_days"]').value);
                var label = card.querySelector('[data-countdown-label]');
                var value = card.querySelector('[data-countdown-value]');
                card.querySelector('[data-period-countdown]').classList.remove('is-running', 'is-ended');
                if (!startValue || !days) { label.textContent = 'Compte à rebours'; value.textContent = 'Renseigne la date et la durée.'; return; }
                var start = new Date(startValue).getTime();
                var end = start + days * 86400000;
                var remaining = Math.floor((end - Date.now()) / 1000);
                if (remaining <= 0) { label.textContent = 'Promotion terminée'; value.textContent = 'Elle ne s’applique plus.'; card.querySelector('[data-period-countdown]').classList.add('is-ended'); return; }
                if (Date.now() < start) { label.textContent = 'La promotion commence dans'; remaining = Math.floor((start - Date.now()) / 1000); }
                else { label.textContent = 'Fin de la promotion dans'; card.querySelector('[data-period-countdown]').classList.add('is-running'); }
                var d = Math.floor(remaining / 86400); var h = Math.floor((remaining % 86400) / 3600); var m = Math.floor((remaining % 3600) / 60); var s = remaining % 60;
                value.textContent = (d ? d + ' j ' : '') + h + ' h ' + m + ' min ' + s + ' s';
            });
        };
        periodForm.addEventListener('input', refreshCountdowns);
        deleteDialog.querySelector('[data-cancel]').addEventListener('click', function () { pendingPeriod = null; deleteDialog.close(); });
        deleteButton.addEventListener('click', function () {
            if (!pendingPeriod) return;
            var savedIndex = pendingPeriod.dataset.savedIndex;
            if (savedIndex === undefined) {
                pendingPeriod.remove(); pendingPeriod = null; deleteDialog.close(); reindexPeriods(); refreshCountdowns(); return;
            }
            deleteButton.disabled = true;
            fetch(deleteDialog.dataset.deleteUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': periodForm.querySelector('[name="_token"]').value }, body: JSON.stringify({ index: savedIndex }) })
                .then(function (response) { return response.json().then(function (payload) { if (!response.ok) throw new Error(payload.message || 'La période n’a pas pu être supprimée.'); return payload; }); })
                .then(function () {
                    var removedIndex = Number(savedIndex);
                    pendingPeriod.remove();
                    periodList.querySelectorAll('[data-period][data-saved-index]').forEach(function (card) {
                        var index = Number(card.dataset.savedIndex);
                        if (index > removedIndex) card.dataset.savedIndex = String(index - 1);
                    });
                    pendingPeriod = null; deleteDialog.close(); reindexPeriods(); refreshCountdowns();
                    if (!periodList.querySelector('[data-period]')) periodForm.querySelector('[name="is_active"][type="checkbox"]').checked = false;
                })
                .catch(function (error) { deleteDialog.querySelector('[data-confirm-message]').textContent = error.message; })
                .finally(function () { deleteButton.disabled = false; });
        });
        reindexPeriods(); refreshCountdowns(); window.setInterval(refreshCountdowns, 1000);
    })();
</script>
@endsection
