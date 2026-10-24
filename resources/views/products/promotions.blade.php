@extends('layouts.admin')

@section('content')
<style>
    .quantity-promo{--qp-ink:#242a36;--qp-muted:#748092;--qp-line:#e4e8ee;--qp-red:#861b1b;color:var(--qp-ink);padding:24px 26px 40px}
    .qp-heading{display:flex;align-items:center;gap:15px;margin:0 0 22px}.qp-heading-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:12px;background:#f8eeee;color:var(--qp-red)}.qp-heading-icon .material-icons{font-size:25px}.qp-heading h1{margin:0;font-size:27px;font-weight:800;letter-spacing:-.03em}.qp-heading p{margin:5px 0 0;color:var(--qp-muted);font-size:14px}
    .qp-card{max-width:1000px;overflow:hidden;border:1px solid var(--qp-line);border-radius:12px;background:#fff;box-shadow:0 4px 18px rgba(30,42,58,.045)}.qp-card-head{padding:19px 22px;border-bottom:1px solid var(--qp-line);background:#fbfcfd}.qp-card-head h2{margin:0;font-size:17px;font-weight:800}.qp-card-head p{margin:5px 0 0;color:var(--qp-muted);font-size:13px}.qp-body{padding:22px}
    .qp-status{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 18px;margin-bottom:22px;border:1px solid #eedcdd;border-radius:9px;background:#fffafa}.qp-status strong{display:block;font-size:14px}.qp-status small{display:block;margin-top:4px;color:var(--qp-muted);line-height:1.45}.qp-toggle{display:inline-flex;align-items:center;gap:10px;margin:0;color:#354052;font-size:14px;font-weight:750;cursor:pointer}.qp-toggle input{width:19px;height:19px;margin:0;accent-color:var(--qp-red)}
    .qp-field{margin-bottom:20px}.qp-field>label,.qp-field-label{display:block;margin-bottom:7px;color:#414b5c;font-size:13px;font-weight:750}.qp-hint{display:block;margin-top:7px;color:var(--qp-muted);font-size:12px;line-height:1.45}
    .qp-schedule{margin:22px 0 24px;padding:17px;border:1px solid var(--qp-line);border-radius:9px;background:#fbfcfd}.qp-schedule-head{margin-bottom:14px}.qp-schedule-head h3{margin:0;font-size:15px;font-weight:800}.qp-schedule-head p{margin:4px 0 0;color:var(--qp-muted);font-size:12px}.qp-schedule-fields{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(150px,.6fr);gap:13px}.qp-schedule-fields label{display:block;margin-bottom:6px;color:#536074;font-size:12px;font-weight:700}.qp-schedule-fields .form-control{height:42px;border:1px solid #d9e0e9;border-radius:7px}.qp-countdown{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:14px;padding:12px 14px;border:1px solid #e5e9ef;border-radius:8px;background:#fff}.qp-countdown-label{color:#5e697a;font-size:12px;font-weight:700}.qp-countdown-value{color:#343c4b;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums;text-align:right}.qp-countdown.is-running{border-color:#d6e9da;background:#f5fbf6}.qp-countdown.is-running .qp-countdown-label,.qp-countdown.is-running .qp-countdown-value{color:#28643a}.qp-countdown.is-ended{border-color:#f0d6d6;background:#fff7f7}.qp-countdown.is-ended .qp-countdown-label,.qp-countdown.is-ended .qp-countdown-value{color:#a33}
    .qp-all-products{display:flex;align-items:center;justify-content:space-between;gap:18px;max-width:620px;margin:0;padding:14px 16px;border:1px solid var(--qp-line);border-radius:9px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s,box-shadow .15s}.qp-field>.qp-all-products{display:flex;margin:0}.qp-all-products:hover{border-color:#c9a5a6;background:#fffdfd}.qp-all-products:has(.qp-switch-input:checked){border-color:#d8b6b7;background:#fffafa;box-shadow:0 0 0 2px rgba(134,27,27,.04)}.qp-all-copy{display:flex;align-items:center;gap:11px;min-width:0}.qp-all-icon{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:8px;background:#f8eeee;color:var(--qp-red)}.qp-all-icon .material-icons{font-size:20px}.qp-all-copy strong{display:block;color:#303a4b;font-size:13px;font-weight:800}.qp-all-copy small{display:block;margin-top:3px;color:var(--qp-muted);font-size:12px;font-weight:400;line-height:1.4}.qp-switch{position:relative;display:block;flex:0 0 42px;width:42px;height:24px}.qp-switch-input{position:absolute;width:1px;height:1px;opacity:0}.qp-switch-ui{position:absolute;inset:0;border-radius:20px;background:#c8ced7;transition:background .18s;cursor:pointer}.qp-switch-ui:after{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(20,30,45,.22);content:'';transition:transform .18s}.qp-switch-input:checked+.qp-switch-ui{background:var(--qp-red)}.qp-switch-input:checked+.qp-switch-ui:after{transform:translateX(18px)}.qp-switch-input:focus-visible+.qp-switch-ui{outline:3px solid rgba(134,27,27,.22);outline-offset:2px}
    .qp-product-picker{display:none;margin:0 0 24px;padding:16px;border:1px solid var(--qp-line);border-radius:9px;background:#fbfcfd}.qp-product-picker.is-visible{display:block}.qp-picker-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.qp-picker-head .qp-field-label{margin:0}.qp-selected-count{padding:4px 9px;border-radius:20px;background:#f1f3f6;color:#5f6b7c;font-size:11px;font-weight:750;white-space:nowrap}.qp-product-search{height:42px;margin-bottom:10px;border-radius:7px}.qp-product-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;padding:1px}.qp-product-option{display:flex;align-items:center;gap:9px;min-width:0;margin:0;padding:10px 11px;border:1px solid var(--qp-line);border-radius:7px;background:#fff;color:#3e4859;font-size:13px;cursor:pointer}.qp-product-option:hover{border-color:#c9a5a6;background:#fffdfd}.qp-product-option[hidden]{display:none}.qp-product-option input{width:17px;height:17px;margin:0;accent-color:var(--qp-red);flex:0 0 auto}.qp-product-option span{overflow-wrap:anywhere}.qp-product-empty{display:none;padding:12px;color:var(--qp-muted);font-size:13px;text-align:center}.qp-product-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding-top:11px;border-top:1px solid var(--qp-line)}.qp-product-page-info{color:var(--qp-muted);font-size:12px}.qp-product-page-actions{display:flex;align-items:center;gap:6px}.qp-product-page-actions button{min-width:34px;height:32px;padding:4px 9px;border:1px solid var(--qp-line);border-radius:6px;background:#fff;color:#465267;font-size:12px;font-weight:700;cursor:pointer}.qp-product-page-actions button:hover:not(:disabled){border-color:#c9a5a6;color:var(--qp-red)}.qp-product-page-actions button:disabled{opacity:.45;cursor:not-allowed}
    .qp-tiers-title{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:24px 0 12px}.qp-tiers-title h3{margin:0;font-size:16px;font-weight:800}.qp-tiers-title p{margin:4px 0 0;color:var(--qp-muted);font-size:12px}.qp-tier-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.qp-tier{min-width:0;padding:15px;border:1px solid var(--qp-line);border-radius:9px;background:#fff}.qp-tier-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:13px;color:#455064;font-size:13px;font-weight:800}.qp-tier-head span{display:flex;align-items:center;gap:7px}.qp-tier-head span:before{content:'';width:7px;height:7px;border-radius:50%;background:#b58139}.qp-tier-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}.qp-tier label{display:block;margin:0 0 6px;color:#647084;font-size:12px;font-weight:650}.qp-tier .form-group{min-width:0;margin:0}.qp-tier input{height:41px;border:1px solid #d9e0e9;border-radius:7px}.qp-remove,.qp-add{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-height:36px;padding:7px 11px;border:1px solid var(--qp-line);border-radius:7px;background:#fff;color:#465267;font-size:12px;font-weight:750;cursor:pointer}.qp-remove{border-color:#f0d6d6;color:#a33}.qp-remove:hover{background:#fff6f6}.qp-add{margin-top:12px;border-color:#d6dce5}.qp-add:hover{border-color:#c9a5a6;color:var(--qp-red)}.qp-add:disabled{opacity:.5;cursor:not-allowed}.qp-submit{display:flex;justify-content:flex-end;margin-top:24px;padding-top:18px;border-top:1px solid #edf0f4}.qp-submit button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:210px;min-height:44px;padding:11px 17px;border:0;border-radius:7px;background:var(--qp-red);color:#fff;font-size:14px;font-weight:750;box-shadow:0 3px 8px rgba(134,27,27,.15)}.qp-submit button:hover{background:#711515}.quantity-promo .alert{max-width:1000px;border-radius:8px}
    @media(max-width:900px){.qp-product-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:760px){.quantity-promo{padding:17px 12px 28px}.qp-heading{align-items:flex-start;margin-bottom:16px}.qp-heading-icon{flex-basis:42px;width:42px;height:42px}.qp-heading h1{font-size:23px}.qp-body{padding:15px}.qp-card-head{padding:16px}.qp-status{align-items:flex-start;flex-direction:column;padding:14px}.qp-tier-list,.qp-schedule-fields{grid-template-columns:1fr}.qp-countdown{align-items:flex-start;flex-direction:column;gap:6px}.qp-countdown-value{text-align:left}.qp-submit button{width:100%}}
    @media(max-width:420px){.qp-heading p{font-size:13px}.qp-picker-head{align-items:flex-start;flex-direction:column}.qp-product-list{grid-template-columns:1fr}.qp-tier-fields{grid-template-columns:1fr 1fr}.qp-card-head p{line-height:1.5}}
</style>

<main class="container-fluid quantity-promo">
    <header class="qp-heading"><span class="qp-heading-icon"><span class="material-icons" aria-hidden="true">local_offer</span></span><div><h1>Promotions</h1><p>Configure les réductions par quantité et choisis les produits concernés.</p></div></header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('quantity-promotions.save') }}" class="qp-card" data-promotion-form>
        @csrf
        <div class="qp-card-head"><h2>Règles de promotion</h2><p>Les réductions sont calculées à partir du prix unitaire en vigueur, y compris un tarif grossiste déjà atteint.</p></div>
        <div class="qp-body">
            <section class="qp-schedule" aria-labelledby="qp-schedule-title">
                <div class="qp-schedule-head"><h3 id="qp-schedule-title">Période de la promotion</h3><p>Choisis sa date de début et sa durée. Elle s’arrêtera automatiquement à la fin du compte à rebours.</p></div>
                <div class="qp-schedule-fields">
                    <div><label for="promotion-starts-at">Début de la promotion</label><input id="promotion-starts-at" class="form-control" type="datetime-local" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\\TH:i')) }}" @required(old('is_active', $promotion->is_active))></div>
                    <div><label for="promotion-duration-days">Durée (jours)</label><input id="promotion-duration-days" class="form-control" type="number" name="duration_days" min="1" max="365" value="{{ old('duration_days', $promotion->duration_days) }}" placeholder="Ex. 7" @required(old('is_active', $promotion->is_active))></div>
                </div>
                <small class="qp-hint">La promotion expirera automatiquement après le nombre de jours indiqué.</small>
                <div class="qp-countdown" data-promotion-countdown data-active="{{ $promotion->is_active ? '1' : '0' }}" data-start="{{ $promotion->starts_at?->timestamp ?? '' }}" data-end="{{ $promotion->ends_at?->timestamp ?? '' }}" role="status" aria-live="polite">
                    <span class="qp-countdown-label" data-countdown-label>Compte à rebours</span><span class="qp-countdown-value" data-countdown-value>Renseigne la période puis enregistre.</span>
                </div>
            </section>

            <div class="qp-status">
                <div><strong>Activer les promotions</strong><small>Les réductions sont appliquées lorsque la quantité minimale d’un palier est atteinte.</small></div>
                <label class="qp-toggle"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active))> Promotions actives</label>
            </div>

            <div class="qp-field">
                <input type="hidden" name="apply_to_all" value="0">
                <label class="qp-all-products">
                    <span class="qp-all-copy"><span class="qp-all-icon"><span class="material-icons" aria-hidden="true">inventory_2</span></span><span><strong>Appliquer à tous les produits</strong><small>La promotion sera disponible sur chaque produit de la boutique.</small></span></span>
                    <span class="qp-switch"><input class="qp-switch-input" type="checkbox" name="apply_to_all" value="1" data-apply-to-all @checked(old('apply_to_all', $promotion->application_mode !== 'product')) aria-label="Appliquer la promotion à tous les produits"><span class="qp-switch-ui" aria-hidden="true"></span></span>
                </label>
                <small class="qp-hint">Désactive cette option pour sélectionner les produits concernés.</small>
            </div>

            @php $selectedPromotionProducts = array_map('intval', old('product_ids', $promotion->product_ids ?? [])); @endphp
            <div class="qp-product-picker {{ old('apply_to_all', $promotion->application_mode !== 'product') ? '' : 'is-visible' }}" data-product-picker data-page-size="12">
                <div class="qp-picker-head"><label class="qp-field-label" for="promotion-product-search">Choisir les produits concernés</label><span class="qp-selected-count" data-selected-count>0 sélectionné</span></div>
                <input id="promotion-product-search" class="form-control qp-product-search" type="search" placeholder="Rechercher un produit…" data-product-search>
                <div class="qp-product-list" data-product-list>
                    @foreach($products as $product)
                        <label class="qp-product-option" data-product-option data-product-name="{{ mb_strtolower($product->name) }}">
                            <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array((int) $product->id, $selectedPromotionProducts, true))>
                            <span>{{ $product->name }}</span>
                        </label>
                    @endforeach
                </div>
                <small class="qp-product-empty" data-product-empty>Aucun produit ne correspond à cette recherche.</small>
                <div class="qp-product-pagination" data-product-pagination>
                    <span class="qp-product-page-info" data-page-info></span>
                    <span class="qp-product-page-actions"><button type="button" data-page-prev aria-label="Page précédente">‹</button><button type="button" data-page-next aria-label="Page suivante">›</button></span>
                </div>
            </div>

            @php
                $promotionTiers = old('min_quantity') !== null
                    ? collect(old('min_quantity', []))->map(fn ($minimum, $index) => ['min_quantity' => $minimum, 'discount_percent' => old('discount_percent.'.$index)])->all()
                    : ($promotion->tiers ?? []);
                $promotionTiers = array_slice($promotionTiers ?: [['min_quantity' => null, 'discount_percent' => null]], 0, 8);
            @endphp
            <div class="qp-tiers-title"><div><h3>Paliers de promotion</h3><p>Chaque réduction s’applique dès que la quantité du produit atteint le seuil.</p></div></div>
            <div class="qp-tier-list" data-promotion-tiers>
                @foreach($promotionTiers as $index => $tier)
                    <div class="qp-tier">
                        <div class="qp-tier-head"><span>Palier {{ $index + 1 }}</span><button type="button" class="qp-remove" data-remove-promo-tier>Retirer</button></div>
                        <div class="qp-tier-fields"><div class="form-group"><label for="promo-min-{{ $index }}">Quantité minimale</label><input id="promo-min-{{ $index }}" class="form-control" type="number" min="1" max="999999" name="min_quantity[]" value="{{ $tier['min_quantity'] ?? '' }}" placeholder="Ex. 3"></div>
                        <div class="form-group"><label for="promo-discount-{{ $index }}">Réduction (%)</label><input id="promo-discount-{{ $index }}" class="form-control" type="number" min="1" max="90" name="discount_percent[]" value="{{ $tier['discount_percent'] ?? '' }}" placeholder="Ex. 5"></div></div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="qp-add" data-add-promo-tier>+ Ajouter un palier</button>
            <template data-promotion-tier-template>
                <div class="qp-tier">
                    <div class="qp-tier-head"><span>Palier __NUMBER__</span><button type="button" class="qp-remove" data-remove-promo-tier>Retirer</button></div>
                    <div class="qp-tier-fields"><div class="form-group"><label for="promo-min-__INDEX__">Quantité minimale</label><input id="promo-min-__INDEX__" class="form-control" type="number" min="1" max="999999" name="min_quantity[]" placeholder="Ex. 3"></div>
                    <div class="form-group"><label for="promo-discount-__INDEX__">Réduction (%)</label><input id="promo-discount-__INDEX__" class="form-control" type="number" min="1" max="90" name="discount_percent[]" placeholder="Ex. 5"></div></div>
                </div>
            </template>
            <div class="qp-submit"><button type="submit"><span class="material-icons" aria-hidden="true">save</span> Enregistrer les promotions</button></div>
        </div>
    </form>
</main>

<script>
    (function () {
        var form = document.querySelector('[data-promotion-form]');
        if (!form) return;
        var applyToAll = form.querySelector('[data-apply-to-all]');
        var productPicker = form.querySelector('[data-product-picker]');
        var syncMode = function () { productPicker.classList.toggle('is-visible', !applyToAll.checked); };
        applyToAll.addEventListener('change', syncMode);
        var productSearch = form.querySelector('[data-product-search]');
        var selectedCount = form.querySelector('[data-selected-count]');
        var productOptions = Array.from(form.querySelectorAll('[data-product-option]'));
        var pageSize = Number(productPicker.dataset.pageSize) || 12;
        var currentPage = 1;
        var updateSelectedCount = function () {
            var count = form.querySelectorAll('[data-product-option] input:checked').length;
            selectedCount.textContent = count + (count === 1 ? ' sélectionné' : ' sélectionnés');
        };
        var renderProductPage = function () {
            var query = productSearch.value.trim().toLocaleLowerCase();
            var filtered = productOptions.filter(function (option) { return option.dataset.productName.includes(query); });
            var pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
            currentPage = Math.min(currentPage, pageCount);
            var start = (currentPage - 1) * pageSize;
            var visibleOptions = new Set(filtered.slice(start, start + pageSize));
            productOptions.forEach(function (option) { option.hidden = !visibleOptions.has(option); });
            form.querySelector('[data-product-empty]').style.display = filtered.length ? 'none' : 'block';
            form.querySelector('[data-product-pagination]').style.display = filtered.length > pageSize ? 'flex' : 'none';
            form.querySelector('[data-page-info]').textContent = filtered.length ? (start + 1) + '–' + Math.min(start + pageSize, filtered.length) + ' sur ' + filtered.length + ' produits · page ' + currentPage + '/' + pageCount : '';
            form.querySelector('[data-page-prev]').disabled = currentPage <= 1;
            form.querySelector('[data-page-next]').disabled = currentPage >= pageCount;
        };
        form.addEventListener('change', function (event) {
            if (event.target.closest('[data-product-option]')) updateSelectedCount();
        });
        updateSelectedCount();
        var countdown = form.querySelector('[data-promotion-countdown]');
        var countdownLabel = countdown.querySelector('[data-countdown-label]');
        var countdownValue = countdown.querySelector('[data-countdown-value]');
        var startsAtField = form.querySelector('#promotion-starts-at');
        var durationField = form.querySelector('#promotion-duration-days');
        var activeField = form.querySelector('input[type="checkbox"][name="is_active"]');
        var formatRemaining = function (seconds) {
            var days = Math.floor(seconds / 86400);
            var hours = Math.floor((seconds % 86400) / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var remainingSeconds = seconds % 60;
            return (days ? days + ' j ' : '') + String(hours).padStart(2, '0') + ' h ' + String(minutes).padStart(2, '0') + ' min ' + String(remainingSeconds).padStart(2, '0') + ' s';
        };
        var updateCountdown = function () {
            var now = Math.floor(Date.now() / 1000);
            var startsAt = startsAtField.value ? Math.floor(new Date(startsAtField.value).getTime() / 1000) : 0;
            var durationDays = Number(durationField.value || 0);
            var endsAt = startsAt && durationDays ? startsAt + durationDays * 86400 : 0;
            var isActive = activeField.checked;
            countdown.classList.remove('is-running', 'is-ended');
            if (!startsAt || !endsAt) {
                countdownLabel.textContent = 'Compte à rebours';
                countdownValue.textContent = 'Renseigne la période puis enregistre.';
            } else if (now < startsAt) {
                countdownLabel.textContent = isActive ? 'Début dans' : 'Début prévu dans · promotion désactivée';
                countdownValue.textContent = formatRemaining(startsAt - now);
            } else if (!isActive) {
                countdownLabel.textContent = 'État de la promotion';
                countdownValue.textContent = 'Promotion désactivée.';
            } else if (now < endsAt) {
                countdown.classList.add('is-running');
                countdownLabel.textContent = 'Fin de la promotion dans';
                countdownValue.textContent = formatRemaining(endsAt - now);
            } else {
                countdown.classList.add('is-ended');
                countdownLabel.textContent = 'Promotion terminée';
                countdownValue.textContent = 'Elle ne s’applique plus.';
            }
        };
        updateCountdown();
        window.setInterval(updateCountdown, 1000);
        startsAtField.addEventListener('input', updateCountdown);
        durationField.addEventListener('input', updateCountdown);
        activeField.addEventListener('change', updateCountdown);
        var initialTierCount = form.querySelectorAll('[data-promotion-tiers] .qp-tier').length;
        form.querySelector('[data-add-promo-tier]').disabled = initialTierCount >= 8;
        productSearch.addEventListener('input', function () {
            currentPage = 1;
            renderProductPage();
        });
        form.querySelector('[data-page-prev]').addEventListener('click', function () { currentPage--; renderProductPage(); });
        form.querySelector('[data-page-next]').addEventListener('click', function () { currentPage++; renderProductPage(); });
        renderProductPage();
        form.addEventListener('click', function (event) {
            var add = event.target.closest('[data-add-promo-tier]');
            if (add) {
                var list = form.querySelector('[data-promotion-tiers]');
                var template = form.querySelector('[data-promotion-tier-template]');
                var count = list.querySelectorAll('.qp-tier').length;
                if (count >= 8) return;
                list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(count)).replaceAll('__NUMBER__', String(count + 1)));
                add.disabled = list.querySelectorAll('.qp-tier').length >= 8;
                return;
            }
            var remove = event.target.closest('[data-remove-promo-tier]');
            if (remove) {
                remove.closest('.qp-tier')?.remove();
                var addButton = form.querySelector('[data-add-promo-tier]');
                addButton.disabled = form.querySelectorAll('.qp-tier').length >= 8;
                form.querySelectorAll('.qp-tier-head span').forEach(function (label, index) { label.textContent = 'Palier ' + (index + 1); });
            }
        });
    })();
</script>
@endsection
