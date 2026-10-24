@extends('layouts.admin')

@section('content')
<style>
    .quantity-promo{--qp-ink:#242a36;--qp-muted:#748092;--qp-line:#e4e8ee;--qp-red:#861b1b;color:var(--qp-ink);padding:24px 26px 40px}
    .qp-heading{display:flex;align-items:center;gap:15px;margin:0 0 22px}.qp-heading-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:12px;background:#f8eeee;color:var(--qp-red)}.qp-heading-icon .material-icons{font-size:25px}.qp-heading h1{margin:0;font-size:27px;font-weight:800;letter-spacing:-.03em}.qp-heading p{margin:5px 0 0;color:var(--qp-muted);font-size:14px}
    .qp-card{max-width:1000px;overflow:hidden;border:1px solid var(--qp-line);border-radius:12px;background:#fff;box-shadow:0 4px 18px rgba(30,42,58,.045)}.qp-card-head{padding:19px 22px;border-bottom:1px solid var(--qp-line);background:#fbfcfd}.qp-card-head h2{margin:0;font-size:17px;font-weight:800}.qp-card-head p{margin:5px 0 0;color:var(--qp-muted);font-size:13px}.qp-body{padding:22px}
    .qp-status{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 18px;margin-bottom:22px;border:1px solid #eedcdd;border-radius:9px;background:#fffafa}.qp-status strong{display:block;font-size:14px}.qp-status small{display:block;margin-top:4px;color:var(--qp-muted);line-height:1.45}.qp-toggle{display:inline-flex;align-items:center;gap:10px;margin:0;color:#354052;font-size:14px;font-weight:750;cursor:pointer}.qp-toggle input{width:19px;height:19px;margin:0;accent-color:var(--qp-red)}
    .qp-field{margin-bottom:20px}.qp-field>label,.qp-field-label{display:block;margin-bottom:7px;color:#414b5c;font-size:13px;font-weight:750}.qp-hint{display:block;margin-top:7px;color:var(--qp-muted);font-size:12px;line-height:1.45}
    .qp-schedule{margin:0 0 22px;padding:17px;border:1px solid var(--qp-line);border-radius:9px;background:#fbfcfd}.qp-schedule-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px}.qp-schedule-head h3{margin:0;font-size:15px;font-weight:800}.qp-schedule-head p{margin:4px 0 0;color:var(--qp-muted);font-size:12px}.qp-period-list{display:grid;gap:10px}.qp-period{padding:13px;border:1px solid var(--qp-line);border-radius:8px;background:#fff}.qp-period-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:11px;color:#455064;font-size:13px;font-weight:800}.qp-period-fields{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(140px,.6fr);gap:12px}.qp-period-fields label{display:block;margin-bottom:6px;color:#536074;font-size:12px;font-weight:700}.qp-period-start-row{display:flex;align-items:center;gap:8px}.qp-period-start-row input{min-width:0;flex:1}.qp-start-today{flex:0 0 auto;min-height:40px;padding:7px 10px;border:1px solid #ead7d7;border-radius:7px;background:#fffafa;color:var(--qp-red);font-size:12px;font-weight:750;cursor:pointer;white-space:nowrap}.qp-start-today:hover{background:#f8eeee}.qp-period-fields .form-control{height:40px;border:1px solid #d9e0e9;border-radius:7px}.qp-countdown{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:11px;padding:9px 11px;border:1px solid #e5e9ef;border-radius:7px;background:#fbfcfd}.qp-countdown-label{color:#5e697a;font-size:12px;font-weight:700}.qp-countdown-value{color:#343c4b;font-size:13px;font-weight:800;font-variant-numeric:tabular-nums;text-align:right}.qp-countdown.is-running{border-color:#d6e9da;background:#f5fbf6}.qp-countdown.is-running .qp-countdown-label,.qp-countdown.is-running .qp-countdown-value{color:#28643a}.qp-countdown.is-ended{border-color:#f0d6d6;background:#fff7f7}.qp-countdown.is-ended .qp-countdown-label,.qp-countdown.is-ended .qp-countdown-value{color:#a33}.qp-add-period{margin-top:10px;padding:8px 12px;border:1px solid var(--qp-line);border-radius:7px;background:#fff;color:#465267;font-size:12px;font-weight:750;cursor:pointer}.qp-add-period:disabled{opacity:.5;cursor:not-allowed}
    .qp-period-empty{padding:13px;border:1px dashed #d8dee7;border-radius:8px;background:#fff;color:var(--qp-muted);font-size:13px}
    .qp-all-products{display:flex;align-items:center;justify-content:space-between;gap:18px;width:min(100%,844px);margin:0;padding:14px 16px;border:1px solid var(--qp-line);border-radius:9px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s,box-shadow .15s}.qp-field>.qp-all-products{display:flex;margin:0}.qp-all-products:hover{border-color:#c9a5a6;background:#fffdfd}.qp-all-products:has(.qp-switch-input:checked){border-color:#d8b6b7;background:#fffafa;box-shadow:0 0 0 2px rgba(134,27,27,.04)}.qp-all-copy{display:flex;align-items:center;gap:11px;min-width:0}.qp-all-icon{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:8px;background:#f8eeee;color:var(--qp-red)}.qp-all-icon .material-icons{font-size:20px}.qp-all-copy strong{display:block;color:#303a4b;font-size:13px;font-weight:800}.qp-all-copy small{display:block;margin-top:3px;color:var(--qp-muted);font-size:12px;font-weight:400;line-height:1.4}.qp-switch{position:relative;display:block;flex:0 0 42px;width:42px;height:24px}.qp-switch-input{position:absolute;width:1px;height:1px;opacity:0}.qp-switch-ui{position:absolute;inset:0;border-radius:20px;background:#c8ced7;transition:background .18s;cursor:pointer}.qp-switch-ui:after{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(20,30,45,.22);content:'';transition:transform .18s}.qp-switch-input:checked+.qp-switch-ui{background:var(--qp-red)}.qp-switch-input:checked+.qp-switch-ui:after{transform:translateX(18px)}.qp-switch-input:focus-visible+.qp-switch-ui{outline:3px solid rgba(134,27,27,.22);outline-offset:2px}
    .qp-product-picker{display:none;margin:0 0 24px;padding:16px;border:1px solid var(--qp-line);border-radius:9px;background:#fbfcfd}.qp-product-picker.is-visible{display:block}.qp-picker-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.qp-picker-head .qp-field-label{margin:0}.qp-selected-count{padding:4px 9px;border-radius:20px;background:#f1f3f6;color:#5f6b7c;font-size:11px;font-weight:750;white-space:nowrap}.qp-product-search{height:42px;margin-bottom:10px;border-radius:7px}.qp-product-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;padding:1px}.qp-product-option{display:flex;align-items:center;gap:9px;min-width:0;margin:0;padding:10px 11px;border:1px solid var(--qp-line);border-radius:7px;background:#fff;color:#3e4859;font-size:13px;cursor:pointer}.qp-product-option:hover{border-color:#c9a5a6;background:#fffdfd}.qp-product-option[hidden]{display:none}.qp-product-option input{width:17px;height:17px;margin:0;accent-color:var(--qp-red);flex:0 0 auto}.qp-product-option span{overflow-wrap:anywhere}.qp-product-empty{display:none;padding:12px;color:var(--qp-muted);font-size:13px;text-align:center}.qp-product-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding-top:11px;border-top:1px solid var(--qp-line)}.qp-product-page-info{color:var(--qp-muted);font-size:12px}.qp-product-page-actions{display:flex;align-items:center;gap:6px}.qp-product-page-actions button{min-width:34px;height:32px;padding:4px 9px;border:1px solid var(--qp-line);border-radius:6px;background:#fff;color:#465267;font-size:12px;font-weight:700;cursor:pointer}.qp-product-page-actions button:hover:not(:disabled){border-color:#c9a5a6;color:var(--qp-red)}.qp-product-page-actions button:disabled{opacity:.45;cursor:not-allowed}
    .qp-tiers-title{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:24px 0 12px}.qp-tiers-title h3{margin:0;font-size:16px;font-weight:800}.qp-tiers-title p{margin:4px 0 0;color:var(--qp-muted);font-size:12px}.qp-tier-list{display:grid;grid-template-columns:1fr;gap:12px}.qp-tier{min-width:0;padding:15px;border:1px solid var(--qp-line);border-radius:9px;background:#fff}.qp-tier-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:13px;color:#455064;font-size:13px;font-weight:800}.qp-tier-head span{display:flex;align-items:center;gap:7px}.qp-tier-head span:before{content:'';width:7px;height:7px;border-radius:50%;background:#b58139}.qp-tier-fields{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(120px,.65fr) minmax(150px,.75fr) minmax(150px,.8fr);gap:10px}.qp-tier.is-global .qp-tier-fields{grid-template-columns:repeat(2,minmax(0,1fr))}.qp-tier label{display:block;margin:0 0 6px;color:#647084;font-size:12px;font-weight:650}.qp-tier .form-group{min-width:0;margin:0}.qp-tier [hidden]{display:none!important}.qp-tier input,.qp-tier select{display:block;width:100%;min-width:0;height:41px;box-sizing:border-box;border:1px solid #d9e0e9;border-radius:7px;background:#fff}.qp-tier select{padding:0 8px;color:#465267;font-size:12px}.qp-remove,.qp-add{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-height:36px;padding:7px 11px;border:1px solid var(--qp-line);border-radius:7px;background:#fff;color:#465267;font-size:12px;font-weight:750;cursor:pointer}.qp-remove{border-color:#f0d6d6;color:#a33}.qp-remove:hover{background:#fff6f6}.qp-add{margin-top:12px;border-color:#d6dce5}.qp-add:hover{border-color:#c9a5a6;color:var(--qp-red)}.qp-add:disabled{opacity:.5;cursor:not-allowed}.qp-submit{display:flex;justify-content:flex-end;margin-top:24px;padding-top:18px;border-top:1px solid #edf0f4}.qp-submit button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:210px;min-height:44px;padding:11px 17px;border:0;border-radius:7px;background:var(--qp-red);color:#fff;font-size:14px;font-weight:750;box-shadow:0 3px 8px rgba(134,27,27,.15)}.qp-submit button:hover{background:#711515}.quantity-promo .alert{max-width:1000px;border-radius:8px}
    @media(max-width:900px){.qp-product-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:760px){.quantity-promo{width:100%;min-width:0;box-sizing:border-box;padding:16px 12px 26px}.qp-heading{align-items:flex-start;gap:11px;margin-bottom:16px}.qp-heading-icon{flex-basis:42px;width:42px;height:42px}.qp-heading h1{font-size:23px}.qp-heading p{line-height:1.45}.qp-card{width:100%;max-width:none;min-width:0}.qp-body{padding:15px}.qp-card-head{padding:16px}.qp-status{align-items:flex-start;flex-direction:column;padding:14px}.qp-toggle{flex-wrap:wrap}.qp-schedule{padding:13px}.qp-schedule-head{align-items:stretch;flex-direction:column}.qp-period-fields{grid-template-columns:minmax(0,1fr)}.qp-period-start-row{align-items:stretch;flex-direction:column}.qp-period-start-row input,.qp-start-today{width:100%;min-width:0}.qp-countdown{align-items:flex-start;flex-direction:column;gap:6px}.qp-countdown-value{text-align:left;overflow-wrap:anywhere}.qp-all-products{width:100%;box-sizing:border-box;align-items:center;gap:10px;padding:12px}.qp-all-copy{align-items:flex-start;gap:9px}.qp-all-copy small{line-height:1.45}.qp-product-picker{padding:12px}.qp-picker-head{align-items:flex-start}.qp-product-list{grid-template-columns:minmax(0,1fr)}.qp-product-pagination{align-items:flex-start;flex-wrap:wrap}.qp-product-page-info{flex:1 1 100%;line-height:1.4}.qp-tier{padding:12px}.qp-tier-fields,.qp-tier.is-global .qp-tier-fields{grid-template-columns:minmax(0,1fr)}.qp-submit button{width:100%;min-width:0}.qp-confirm-actions{padding:12px}.qp-confirm-actions button{flex:1}}
    .qp-confirm-dialog{width:min(430px,calc(100vw - 28px));max-width:none;padding:0;border:1px solid #e3e6ec;border-radius:12px;background:#fff;color:var(--qp-ink);box-shadow:0 22px 70px rgba(20,27,40,.25)}.qp-confirm-dialog::backdrop{background:rgba(24,30,42,.48);backdrop-filter:blur(2px)}.qp-confirm-content{padding:22px}.qp-confirm-icon{display:grid;place-items:center;width:42px;height:42px;margin-bottom:14px;border-radius:10px;background:#fff0ee;color:#a12b28}.qp-confirm-icon .material-icons{font-size:22px}.qp-confirm-content h3{margin:0 0 7px;font-size:17px;font-weight:800}.qp-confirm-content p{margin:0;color:var(--qp-muted);font-size:13px;line-height:1.55}.qp-confirm-actions{display:flex;justify-content:flex-end;gap:9px;padding:14px 22px;border-top:1px solid #edf0f4;background:#fbfcfd}.qp-confirm-actions button{min-height:38px;padding:8px 14px;border:1px solid #dfe3ea;border-radius:7px;background:#fff;color:#465267;font-size:13px;font-weight:750;cursor:pointer}.qp-confirm-actions button[data-confirm-delete]{border-color:#a12b28;background:#a12b28;color:#fff}.qp-confirm-actions button[data-confirm-delete]:hover{background:#85211f}
    @media(max-width:420px){.quantity-promo{padding-right:9px;padding-left:9px}.qp-heading p{font-size:13px}.qp-card-head p{line-height:1.5}.qp-picker-head{flex-direction:column;gap:7px}.qp-selected-count{align-self:flex-start}.qp-tier-head{align-items:flex-start}.qp-remove{flex:0 0 auto}.qp-countdown{padding:9px}.qp-confirm-actions{flex-direction:column-reverse}.qp-confirm-actions button{width:100%}}
</style>

<main class="container-fluid quantity-promo">
    <header class="qp-heading"><span class="qp-heading-icon"><span class="material-icons" aria-hidden="true">local_offer</span></span><div><h1>Promotions</h1><p>Configure les réductions par quantité et choisis les produits concernés.</p></div></header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('quantity-promotions.save') }}" class="qp-card" data-promotion-form>
        @csrf
        <div class="qp-card-head"><h2>Règles de promotion</h2><p>Les réductions sont calculées à partir du prix unitaire en vigueur, y compris un tarif grossiste déjà atteint.</p></div>
        <div class="qp-body">
            @php
                $configuredPeriods = old('periods', $promotion->schedulePeriods());
                if (!is_array($configuredPeriods)) $configuredPeriods = [];
            @endphp
            <section class="qp-schedule" aria-labelledby="qp-schedule-title">
                <div class="qp-schedule-head"><div><h3 id="qp-schedule-title">Périodes de la promotion</h3><p>Conserve les périodes déjà enregistrées, ajoute-en d’autres ou retire celles qui ne sont plus utiles.</p></div><button type="button" class="qp-add-period" data-add-period>+ Ajouter une période</button></div>
                <div class="qp-period-list" data-period-list>
                    <div class="qp-period-empty" data-period-empty @if($configuredPeriods !== []) hidden @endif>Aucune période enregistrée. Clique sur « Ajouter une période » pour en créer une.</div>
                    @foreach($configuredPeriods as $index => $period)
                        <div class="qp-period" data-period data-period-index="{{ $index }}">
                            <div class="qp-period-head"><span data-period-title>Période {{ $index + 1 }}</span><button type="button" class="qp-remove" data-remove-period>Supprimer</button></div>
                            <div class="qp-period-fields">
                                <div><label for="period-start-{{ $index }}">Date et heure de début</label><div class="qp-period-start-row"><input id="period-start-{{ $index }}" data-period-field="starts_at" class="form-control" type="datetime-local" name="periods[{{ $index }}][starts_at]" value="{{ !empty($period['starts_at']) ? \Illuminate\Support\Carbon::parse($period['starts_at'])->format('Y-m-d\\TH:i') : '' }}"><button type="button" class="qp-start-today" data-start-today>Commence aujourd’hui</button></div></div>
                                <div><label for="period-duration-{{ $index }}">Durée (jours)</label><input id="period-duration-{{ $index }}" data-period-field="duration_days" class="form-control" type="number" name="periods[{{ $index }}][duration_days]" min="1" max="365" value="{{ $period['duration_days'] ?? '' }}" placeholder="Ex. 7"></div>
                            </div>
                            <div class="qp-countdown" data-period-countdown role="status" aria-live="polite"><span class="qp-countdown-label" data-countdown-label>Compte à rebours</span><span class="qp-countdown-value" data-countdown-value>Renseigne la date et la durée.</span></div>
                        </div>
                    @endforeach
                </div>
                <template data-period-template>
                    <div class="qp-period" data-period data-period-index="__INDEX__">
                        <div class="qp-period-head"><span data-period-title></span><button type="button" class="qp-remove" data-remove-period>Supprimer</button></div>
                        <div class="qp-period-fields">
                            <div><label for="period-start-__INDEX__">Date et heure de début</label><div class="qp-period-start-row"><input id="period-start-__INDEX__" data-period-field="starts_at" class="form-control" type="datetime-local" name="periods[__INDEX__][starts_at]"><button type="button" class="qp-start-today" data-start-today>Commence aujourd’hui</button></div></div>
                            <div><label for="period-duration-__INDEX__">Durée (jours)</label><input id="period-duration-__INDEX__" data-period-field="duration_days" class="form-control" type="number" name="periods[__INDEX__][duration_days]" min="1" max="365" placeholder="Ex. 7"></div>
                        </div>
                        <div class="qp-countdown" data-period-countdown role="status" aria-live="polite"><span class="qp-countdown-label" data-countdown-label>Compte à rebours</span><span class="qp-countdown-value" data-countdown-value>Renseigne la date et la durée.</span></div>
                    </div>
                </template>
            </section>

            <div class="qp-status">
                <div><strong>Activer les promotions</strong><small>Les réductions sont appliquées lorsque la quantité minimale d’un palier est atteinte.</small></div>
                <label class="qp-toggle"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active))> Promotions actives</label>
            </div>

            @php
                $selectedProductIds = collect(old('product_ids', $promotion->product_ids ?? []))->map(fn ($id) => (string) $id)->all();
                $hasScopedPromotion = $promotion->application_mode === 'product' && $selectedProductIds !== [];
                $applyToAll = (bool) old('apply_to_all', !$hasScopedPromotion);
            @endphp
            <label class="qp-all-products" for="apply-to-all-products">
                <span class="qp-all-copy"><span class="qp-all-icon"><span class="material-icons" aria-hidden="true">inventory_2</span></span><span><strong>Appliquer à tous les produits</strong><small>La promotion sera disponible sur chaque produit de la boutique.</small></span></span>
                <span class="qp-switch"><input id="apply-to-all-products" class="qp-switch-input" type="checkbox" name="apply_to_all" value="1" @checked($applyToAll)><span class="qp-switch-ui"></span></span>
            </label>
            <small class="qp-hint">Désactive cette option pour sélectionner les produits concernés.</small>
            <section class="qp-product-picker {{ $applyToAll ? '' : 'is-visible' }}" data-product-picker>
                <div class="qp-picker-head"><span class="qp-field-label">Choisir les produits concernés</span><span class="qp-selected-count"><span data-selected-count>0</span> sélectionné(s)</span></div>
                <input type="search" class="form-control qp-product-search" data-product-search placeholder="Rechercher un produit…" aria-label="Rechercher un produit">
                <div class="qp-product-list" data-product-list>
                    @foreach($products as $product)
                        <label class="qp-product-option" data-product-option data-product-name="{{ mb_strtolower($product->name) }}"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array((string) $product->id, $selectedProductIds, true))><span>{{ $product->name }}</span></label>
                    @endforeach
                </div>
                <div class="qp-product-empty" data-product-empty>Aucun produit trouvé.</div>
                <div class="qp-product-pagination"><span class="qp-product-page-info" data-product-page-info></span><div class="qp-product-page-actions"><button type="button" data-product-prev aria-label="Page précédente">‹</button><button type="button" data-product-next aria-label="Page suivante">›</button></div></div>
            </section>

            @php
                $promotionTiers = old('promotion_tiers', $promotionTiers);
                $promotionTiers = array_slice($promotionTiers ?: [['product_id' => 'all', 'min_quantity' => null, 'discount_type' => 'percent', 'discount_value' => null]], 0, 8);
            @endphp
            <div class="qp-tiers-title"><div><h3>Paliers de promotion</h3><p>Les paliers s’appliquent aux produits concernés ci-dessus.</p></div></div>
            <div class="qp-tier-list" data-promotion-tiers>
                @foreach($promotionTiers as $index => $tier)
                    <div class="qp-tier">
                        <div class="qp-tier-head"><span>Palier {{ $index + 1 }}</span><button type="button" class="qp-remove" data-remove-promo-tier>Retirer</button></div>
                        <div class="qp-tier-fields">
                            <div class="form-group" data-tier-product-selector><label for="promo-product-{{ $index }}">Produit concerné</label><select id="promo-product-{{ $index }}" data-tier-field="product_id" name="promotion_tiers[{{ $index }}][product_id]"><option value="all" @selected(($tier['product_id'] ?? 'all') === 'all' || empty($tier['product_id']))>Tous les produits concernés</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) ($tier['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>@endforeach</select></div>
                            <div class="form-group"><label for="promo-min-{{ $index }}">Quantité minimale</label><input id="promo-min-{{ $index }}" data-tier-field="min_quantity" class="form-control" type="number" min="1" max="999999" name="promotion_tiers[{{ $index }}][min_quantity]" value="{{ $tier['min_quantity'] ?? '' }}" placeholder="Ex. 3"></div>
                            <div class="form-group" data-tier-type-selector @if($applyToAll) hidden @endif><label for="promo-type-{{ $index }}">Type de remise</label><select id="promo-type-{{ $index }}" data-tier-field="discount_type" name="promotion_tiers[{{ $index }}][discount_type]"><option value="percent" @selected(($tier['discount_type'] ?? 'percent') === 'percent')>Pourcentage (%)</option><option value="unit_price" @selected(($tier['discount_type'] ?? 'percent') === 'unit_price')>Prix fixe par unité</option></select></div>
                            <div class="form-group"><label for="promo-value-{{ $index }}" data-tier-value-label>{{ !$applyToAll && ($tier['discount_type'] ?? 'percent') === 'unit_price' ? 'Prix unitaire (F)' : 'Réduction (%)' }}</label><input id="promo-value-{{ $index }}" data-tier-field="discount_value" class="form-control" type="number" min="1" max="{{ !$applyToAll && ($tier['discount_type'] ?? 'percent') === 'unit_price' ? 999999999 : 90 }}" name="promotion_tiers[{{ $index }}][discount_value]" value="{{ $applyToAll && ($tier['discount_type'] ?? 'percent') === 'unit_price' ? '' : ($tier['discount_value'] ?? '') }}" placeholder="{{ !$applyToAll && ($tier['discount_type'] ?? 'percent') === 'unit_price' ? 'Ex. 1 500' : 'Ex. 10' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="qp-add" data-add-promo-tier>+ Ajouter un palier</button>
            <template data-promotion-tier-template>
                <div class="qp-tier">
                    <div class="qp-tier-head"><span>Palier __NUMBER__</span><button type="button" class="qp-remove" data-remove-promo-tier>Retirer</button></div>
                    <div class="qp-tier-fields">
                        <div class="form-group" data-tier-product-selector><label for="promo-product-__INDEX__">Produit concerné</label><select id="promo-product-__INDEX__" data-tier-field="product_id" name="promotion_tiers[__INDEX__][product_id]"><option value="all">Tous les produits concernés</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                        <div class="form-group"><label for="promo-min-__INDEX__">Quantité minimale</label><input id="promo-min-__INDEX__" data-tier-field="min_quantity" class="form-control" type="number" min="1" max="999999" name="promotion_tiers[__INDEX__][min_quantity]" placeholder="Ex. 3"></div>
                        <div class="form-group" data-tier-type-selector><label for="promo-type-__INDEX__">Type de remise</label><select id="promo-type-__INDEX__" data-tier-field="discount_type" name="promotion_tiers[__INDEX__][discount_type]"><option value="percent">Pourcentage (%)</option><option value="unit_price">Prix fixe par unité</option></select></div>
                        <div class="form-group"><label for="promo-value-__INDEX__" data-tier-value-label>Réduction (%)</label><input id="promo-value-__INDEX__" data-tier-field="discount_value" class="form-control" type="number" min="1" max="90" name="promotion_tiers[__INDEX__][discount_value]" placeholder="Ex. 10"></div>
                    </div>
                </div>
            </template>
            <div class="qp-submit"><button type="submit"><span class="material-icons" aria-hidden="true">save</span> Enregistrer les promotions</button></div>
        </div>
    </form>
    <dialog class="qp-confirm-dialog" data-delete-confirm data-delete-period-url="{{ route('quantity-promotions.periods.delete') }}" aria-labelledby="qp-confirm-title" aria-describedby="qp-confirm-message">
        <div class="qp-confirm-content"><span class="qp-confirm-icon"><span class="material-icons" aria-hidden="true">delete_outline</span></span><h3 id="qp-confirm-title">Confirmer la suppression</h3><p id="qp-confirm-message">Cette suppression sera enregistrée après validation du formulaire.</p></div>
        <div class="qp-confirm-actions"><button type="button" data-cancel-delete>Annuler</button><button type="button" data-confirm-delete>Supprimer</button></div>
    </dialog>
</main>

<script>
    (function () {
        var form = document.querySelector('[data-promotion-form]');
        if (!form) return;
        var activeField = form.querySelector('input[type="checkbox"][name="is_active"]');
        var formatRemaining = function (seconds) {
            var days = Math.floor(seconds / 86400);
            var hours = Math.floor((seconds % 86400) / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var remainingSeconds = seconds % 60;
            return (days ? days + ' j ' : '') + String(hours).padStart(2, '0') + ' h ' + String(minutes).padStart(2, '0') + ' min ' + String(remainingSeconds).padStart(2, '0') + ' s';
        };
        var updatePeriodCountdown = function (card) {
            var now = Math.floor(Date.now() / 1000);
            var startsField = card.querySelector('[data-period-field="starts_at"]');
            var durationField = card.querySelector('[data-period-field="duration_days"]');
            var countdown = card.querySelector('[data-period-countdown]');
            var countdownLabel = countdown.querySelector('[data-countdown-label]');
            var countdownValue = countdown.querySelector('[data-countdown-value]');
            var startsAt = startsField.value ? Math.floor(new Date(startsField.value).getTime() / 1000) : 0;
            var durationDays = Number(durationField.value || 0);
            var endsAt = startsAt && durationDays ? startsAt + durationDays * 86400 : 0;
            var isActive = activeField.checked;
            countdown.classList.remove('is-running', 'is-ended');
            if (!startsAt || !endsAt) {
                countdownLabel.textContent = 'Compte à rebours';
                countdownValue.textContent = 'Renseigne la date et la durée.';
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
        var updateAllPeriodCountdowns = function () { form.querySelectorAll('[data-period]').forEach(updatePeriodCountdown); };
        updateAllPeriodCountdowns();
        window.setInterval(updateAllPeriodCountdowns, 1000);
        activeField.addEventListener('change', updateAllPeriodCountdowns);
        form.addEventListener('input', function (event) {
            var card = event.target.closest('[data-period]');
            if (card) updatePeriodCountdown(card);
        });
        var applyToAll = form.querySelector('[name="apply_to_all"]');
        var productPicker = form.querySelector('[data-product-picker]');
        var productSearch = form.querySelector('[data-product-search]');
        var productOptions = Array.from(form.querySelectorAll('[data-product-option]'));
        var tierList = form.querySelector('[data-promotion-tiers]');
        var updateTierDiscountMode = function () {
            if (!tierList || !applyToAll) return;
            tierList.querySelectorAll('.qp-tier').forEach(function (card) {
                var type = card.querySelector('[data-tier-field="discount_type"]');
                var selector = card.querySelector('[data-tier-type-selector]');
                var value = card.querySelector('[data-tier-field="discount_value"]');
                var valueLabel = card.querySelector('[data-tier-value-label]');
                if (!type) return;
                if (applyToAll.checked) type.value = 'percent';
                type.disabled = applyToAll.checked;
                if (selector) selector.hidden = applyToAll.checked;
                if (value && valueLabel) {
                    var fixedPrice = !applyToAll.checked && type.value === 'unit_price';
                    valueLabel.textContent = fixedPrice ? 'Prix unitaire (F)' : 'Réduction (%)';
                    value.max = fixedPrice ? '999999999' : '90';
                    value.placeholder = fixedPrice ? 'Ex. 1 500' : 'Ex. 10';
                }
            });
        };
        var updateTierProductOptions = function () {
            if (!tierList) return;
            var eligibleOptions = applyToAll.checked ? productOptions : productOptions.filter(function (option) { return option.querySelector('input').checked; });
            tierList.querySelectorAll('[data-tier-field="product_id"]').forEach(function (select) {
                var card = select.closest('.qp-tier');
                var selectorField = select.closest('[data-tier-product-selector]');
                if (applyToAll.checked) {
                    select.value = 'all';
                    select.disabled = true;
                    if (card) card.classList.add('is-global');
                    if (selectorField) selectorField.hidden = true;
                    return;
                }
                select.disabled = false;
                if (card) card.classList.remove('is-global');
                if (selectorField) selectorField.hidden = false;
                var currentValue = select.value || 'all';
                select.innerHTML = '';
                var allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'Tous les produits concernés';
                select.appendChild(allOption);
                eligibleOptions.forEach(function (option) {
                    var input = option.querySelector('input');
                    var label = option.querySelector('span');
                    var productOption = document.createElement('option');
                    productOption.value = input.value;
                    productOption.textContent = label ? label.textContent.trim() : input.value;
                    select.appendChild(productOption);
                });
                select.value = Array.from(select.options).some(function (option) { return option.value === currentValue; }) ? currentValue : 'all';
            });
        };
        var productPage = 1;
        var productPageSize = 12;
        var updateProducts = function () {
            if (!productPicker) return;
            productPicker.classList.toggle('is-visible', !applyToAll.checked);
            var term = (productSearch.value || '').trim().toLocaleLowerCase();
            var filtered = productOptions.filter(function (option) { return option.dataset.productName.indexOf(term) !== -1; });
            var pageCount = Math.max(1, Math.ceil(filtered.length / productPageSize));
            productPage = Math.min(productPage, pageCount);
            filtered.forEach(function (option, index) { option.hidden = index < (productPage - 1) * productPageSize || index >= productPage * productPageSize; });
            productOptions.filter(function (option) { return filtered.indexOf(option) === -1; }).forEach(function (option) { option.hidden = true; });
            var selectedCount = productOptions.filter(function (option) { return option.querySelector('input').checked; }).length;
            form.querySelector('[data-selected-count]').textContent = selectedCount;
            form.querySelector('[data-product-page-info]').textContent = filtered.length ? ((productPage - 1) * productPageSize + 1) + '–' + Math.min(productPage * productPageSize, filtered.length) + ' sur ' + filtered.length + ' produits · page ' + productPage + '/' + pageCount : '0 produit';
            form.querySelector('[data-product-empty]').style.display = filtered.length ? 'none' : 'block';
            form.querySelector('[data-product-prev]').disabled = productPage <= 1;
            form.querySelector('[data-product-next]').disabled = productPage >= pageCount;
            updateTierProductOptions();
            updateTierDiscountMode();
        };
        if (applyToAll) applyToAll.addEventListener('change', updateProducts);
        if (productSearch) productSearch.addEventListener('input', function () { productPage = 1; updateProducts(); });
        productPicker.addEventListener('change', updateProducts);
        form.querySelector('[data-product-prev]').addEventListener('click', function () { productPage--; updateProducts(); });
        form.querySelector('[data-product-next]').addEventListener('click', function () { productPage++; updateProducts(); });
        updateProducts();
        var initialTierCount = form.querySelectorAll('[data-promotion-tiers] .qp-tier').length;
        form.querySelector('[data-add-promo-tier]').disabled = initialTierCount >= 8;
        var reindexTiers = function () {
            tierList.querySelectorAll('.qp-tier').forEach(function (card, index) {
                card.querySelector('.qp-tier-head span').textContent = 'Palier ' + (index + 1);
                var minimum = card.querySelector('[data-tier-field="min_quantity"]');
                var discountType = card.querySelector('[data-tier-field="discount_type"]');
                var discountValue = card.querySelector('[data-tier-field="discount_value"]');
                var product = card.querySelector('[data-tier-field="product_id"]');
                product.id = 'promo-product-' + index;
                product.name = 'promotion_tiers[' + index + '][product_id]';
                minimum.id = 'promo-min-' + index;
                minimum.name = 'promotion_tiers[' + index + '][min_quantity]';
                discountType.id = 'promo-type-' + index;
                discountType.name = 'promotion_tiers[' + index + '][discount_type]';
                discountValue.id = 'promo-value-' + index;
                discountValue.name = 'promotion_tiers[' + index + '][discount_value]';
                card.querySelector('label[for^="promo-product-"]').htmlFor = product.id;
                card.querySelector('label[for^="promo-min-"]').htmlFor = minimum.id;
                card.querySelector('label[for^="promo-type-"]').htmlFor = discountType.id;
                card.querySelector('[data-tier-value-label]').htmlFor = discountValue.id;
                updateDiscountField(card);
            });
            updateTierDiscountMode();
            form.querySelector('[data-add-promo-tier]').disabled = tierList.querySelectorAll('.qp-tier').length >= 8;
            updateTierProductOptions();
        };
        var updateDiscountField = function (card) {
            var type = card.querySelector('[data-tier-field="discount_type"]');
            var value = card.querySelector('[data-tier-field="discount_value"]');
            var label = card.querySelector('[data-tier-value-label]');
            var fixedPrice = type.value === 'unit_price';
            label.textContent = fixedPrice ? 'Prix unitaire (F)' : 'Réduction (%)';
            value.max = fixedPrice ? '999999999' : '90';
            value.placeholder = fixedPrice ? 'Ex. 1 500' : 'Ex. 10';
        };
        tierList.addEventListener('change', function (event) {
            if (event.target.matches('[data-tier-field="discount_type"]')) {
                updateDiscountField(event.target.closest('.qp-tier'));
            }
        });
        reindexTiers();
        var periodList = form.querySelector('[data-period-list]');
        var periodTemplate = form.querySelector('[data-period-template]');
        var addPeriodButton = form.querySelector('[data-add-period]');
        var emptyPeriodMessage = form.querySelector('[data-period-empty]');
        var reindexPeriods = function () {
            var cards = periodList.querySelectorAll('[data-period]');
            cards.forEach(function (card, index) {
                card.querySelector('[data-period-title]').textContent = 'Période ' + (index + 1);
                card.dataset.periodIndex = index;
                var start = card.querySelector('[data-period-field="starts_at"]');
                var duration = card.querySelector('[data-period-field="duration_days"]');
                start.id = 'period-start-' + index;
                start.name = 'periods[' + index + '][starts_at]';
                duration.id = 'period-duration-' + index;
                duration.name = 'periods[' + index + '][duration_days]';
                card.querySelector('label[for^="period-start-"]').htmlFor = start.id;
                card.querySelector('label[for^="period-duration-"]').htmlFor = duration.id;
            });
            emptyPeriodMessage.hidden = cards.length > 0;
            addPeriodButton.disabled = cards.length >= 12;
        };
        reindexPeriods();
        var deleteDialog = document.querySelector('[data-delete-confirm]');
        var pendingDelete = null;
        var finishDelete = function () {
            if (!pendingDelete) return;
            pendingDelete.element.remove();
            if (pendingDelete.type === 'period') {
                reindexPeriods();
                if (periodList.querySelectorAll('[data-period]').length === 0) activeField.checked = false;
                updateAllPeriodCountdowns();
            } else {
                reindexTiers();
                if (tierList.querySelectorAll('.qp-tier').length === 0) activeField.checked = false;
            }
            pendingDelete = null;
            deleteDialog.close();
        };
        deleteDialog.querySelector('[data-cancel-delete]').addEventListener('click', function () {
            pendingDelete = null;
            deleteDialog.close();
        });
        var confirmDeleteButton = deleteDialog.querySelector('[data-confirm-delete]');
        confirmDeleteButton.addEventListener('click', function () {
            if (!pendingDelete || pendingDelete.type !== 'period') {
                finishDelete();
                return;
            }
            confirmDeleteButton.disabled = true;
            confirmDeleteButton.textContent = 'Suppression…';
            fetch(deleteDialog.dataset.deletePeriodUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({ index: pendingDelete.element.dataset.periodIndex }),
            }).then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) throw new Error(payload.message || 'La période n’a pas pu être supprimée.');
                    finishDelete();
                });
            }).catch(function (error) {
                document.querySelector('#qp-confirm-message').textContent = error.message;
            }).finally(function () {
                confirmDeleteButton.disabled = false;
                confirmDeleteButton.textContent = 'Supprimer';
            });
        });
        deleteDialog.addEventListener('click', function (event) {
            if (event.target === deleteDialog) {
                pendingDelete = null;
                deleteDialog.close();
            }
        });
        form.addEventListener('click', function (event) {
            var startToday = event.target.closest('[data-start-today]');
            if (startToday) {
                var startField = startToday.closest('[data-period]').querySelector('[data-period-field="starts_at"]');
                var today = new Date();
                var localValue = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0') + 'T' + String(today.getHours()).padStart(2, '0') + ':' + String(today.getMinutes()).padStart(2, '0');
                startField.value = localValue;
                startField.dispatchEvent(new Event('input', { bubbles: true }));
                startField.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
            var addPeriod = event.target.closest('[data-add-period]');
            if (addPeriod) {
                if (periodList.querySelectorAll('[data-period]').length >= 12) return;
                var newIndex = periodList.querySelectorAll('[data-period]').length;
                periodList.insertAdjacentHTML('beforeend', periodTemplate.innerHTML.replaceAll('__INDEX__', String(newIndex)));
                reindexPeriods();
                updateAllPeriodCountdowns();
                return;
            }
            var removePeriod = event.target.closest('[data-remove-period]');
            if (removePeriod) {
                pendingDelete = { type: 'period', element: removePeriod.closest('[data-period]') };
                document.querySelector('#qp-confirm-title').textContent = 'Supprimer cette période ?';
                document.querySelector('#qp-confirm-message').textContent = 'Cette période sera supprimée immédiatement. Les autres réglages non enregistrés ne seront pas modifiés.';
                deleteDialog.showModal();
                return;
            }
            var add = event.target.closest('[data-add-promo-tier]');
            if (add) {
                var template = form.querySelector('[data-promotion-tier-template]');
                var count = tierList.querySelectorAll('.qp-tier').length;
                if (count >= 8) return;
                tierList.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(count)).replaceAll('__NUMBER__', String(count + 1)));
                reindexTiers();
                return;
            }
            var remove = event.target.closest('[data-remove-promo-tier]');
            if (remove) {
                pendingDelete = { type: 'tier', element: remove.closest('.qp-tier') };
                document.querySelector('#qp-confirm-title').textContent = 'Supprimer ce palier ?';
                document.querySelector('#qp-confirm-message').textContent = 'Le palier sera retiré après l’enregistrement des paramètres de promotion.';
                deleteDialog.showModal();
            }
        });
    })();
</script>
@endsection
