@extends('layouts.admin')

@section('content')
<main class="container-fluid courier-consignment-page">
    <header class="cc-hero"><div><span class="cc-kicker">TENACOS · STOCK ET LIVRAISON</span><h1>{{ $isCourier ? 'Ma tournée' : 'Consignation des livreurs' }}</h1><p>{{ $isCourier ? 'Déclare les produits vendus, reverse l’argent et rends le stock restant en fin de tournée.' : 'Confie le stock au livreur le matin, ajoute des produits si besoin, puis rapproche la tournée et la caisse le soir.' }}</p></div>@if($shop)<span class="cc-location">Boutique · {{ $shop->name }}</span>@endif</header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(!$isCourier && !$shop)<div class="alert alert-warning">Aucune boutique active n’est affectée à ton compte. Demande à l’administration de définir ta boutique principale ou de te désigner comme responsable boutique.</div>@endif

    @if(!$isCourier && $shop)
        <section class="cc-panel">
            <div class="cc-section-heading"><div><h2>Consigner ou réassortir un livreur</h2><p>Chaque consignation sort du stock boutique et entre dans le stock de tournée.</p></div></div>
            @if($couriers->isEmpty() || $products->isEmpty())<p class="cc-empty-inline">@if($couriers->isEmpty())Aucun livreur actif n’est disponible.@elseAucun produit n’a de stock disponible dans cette boutique.@endif</p>@else
                <form method="POST" action="{{ route('courier-consignments.assign') }}" class="cc-assignment-form">@csrf
                    <div class="cc-courier-picker">
                        <label class="cc-field" for="cc-courier-search">Rechercher un livreur</label>
                        <input type="search" id="cc-courier-search" class="cc-courier-search" placeholder="Nom ou numéro de téléphone…" autocomplete="off" aria-controls="cc-courier-select">
                        <label class="cc-field" for="cc-courier-select">Livreur à affecter</label>
                        <select id="cc-courier-select" name="courier_id" required>
                            <option value="">Choisir un livreur</option>
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->id }}" data-search="{{ mb_strtolower($courier->name.' '.$courier->phone) }}">{{ $courier->name }}{{ $courier->phone ? ' · '.$courier->phone : '' }}</option>
                            @endforeach
                        </select>
                        <small class="cc-courier-result" aria-live="polite">{{ $couriers->count() }} livreur(s) disponible(s)</small>
                    </div>
                    <p class="cc-help">Une tournée ouverte aujourd’hui sera complétée : cela permet le réassort de midi sans créer un nouveau décompte.</p>
                    <div class="cc-product-picker">
                        <div class="cc-product-picker-head"><div><strong>Produits à remettre</strong><small>Choisis une quantité pour chaque produit.</small></div><input type="search" class="cc-product-search" placeholder="Rechercher un produit…" aria-label="Rechercher un produit"></div>
                        <div class="cc-product-list">
                            @foreach($products as $product)
                                <div class="cc-product-row" data-product-name="{{ mb_strtolower($product->name) }}">
                                    <input type="hidden" name="lines[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                                    <div class="cc-product-info"><strong>{{ $product->name }}</strong><span class="cc-product-meta">Prix de vente : {{ number_format($product->price, 0, ',', ' ') }} F</span></div>
                                    <span class="cc-stock-badge">{{ number_format($product->shop_quantity, 0, ',', ' ') }} en stock</span>
                                    <div class="cc-quantity" aria-label="Quantité à remettre pour {{ $product->name }}">
                                        <button type="button" class="cc-quantity-step" data-step="-1" aria-label="Retirer une unité">−</button>
                                        <input type="number" name="lines[{{ $loop->index }}][quantity]" min="0" max="{{ $product->shop_quantity }}" step="1" value="0" inputmode="numeric" aria-label="Quantité de {{ $product->name }}">
                                        <button type="button" class="cc-quantity-step" data-step="1" aria-label="Ajouter une unité">+</button>
                                    </div>
                                </div>
                            @endforeach
                            <p class="cc-no-products" hidden>Aucun produit ne correspond à cette recherche.</p>
                        </div>
                        <nav class="cc-product-pagination" aria-label="Pages des produits">
                            <button type="button" class="cc-page-button cc-page-prev" disabled>Précédent</button>
                            <span class="cc-page-status" aria-live="polite"></span>
                            <button type="button" class="cc-page-button cc-page-next">Suivant</button>
                        </nav>
                        <div class="cc-picker-footer"><span><strong class="cc-selected-count">0</strong> produit(s) avec une quantité</span><span>Maximum selon le stock disponible</span></div>
                    </div>
                    <label class="cc-field">Note facultative<textarea name="notes" rows="2" maxlength="1000" placeholder="Ex. Réassort de midi"></textarea></label>
                    <button class="btn btn-primary cc-submit-assignment" type="submit"><span class="cc-submit-label">Confirmer la remise des produits</span><span class="cc-submit-loading" hidden><span class="cc-spinner" aria-hidden="true"></span>Remise en cours…</span></button>
                </form>
            @endif
        </section>
    @endif

    <section class="cc-panel">
        <div class="cc-section-heading"><div><h2>{{ $isCourier ? 'Mes tournées récentes' : 'Suivi des tournées' }}</h2><p>{{ $isCourier ? 'Consulte les produits confiés, tes ventes et les versements déjà remis.' : 'Les dossiers sont regroupés par livreur pour suivre chaque tournée au même endroit.' }}</p></div><span class="cc-run-count">{{ $totalRunCount }} tournée(s)</span></div>
        <div class="cc-run-list cc-courier-groups">
            @forelse($runs as $courierRuns)
                @php
                    $groupCourier = $courierRuns->first()->courier;
                    $groupOutstanding = $courierRuns->sum(fn ($item) => max(0, (int) $item->cash_expected - (int) $item->cash_received));
                    $groupOpenCount = $courierRuns->where('status', 'open')->count();
                @endphp
                <details class="cc-courier-group" @if($loop->first) open @endif>
                    <summary class="cc-courier-group-head">
                        <span class="cc-courier-group-icon material-icons" aria-hidden="true">local_shipping</span>
                        <span class="cc-courier-group-name"><strong>{{ $groupCourier->name }}</strong><small>{{ $courierRuns->count() }} dossier(s) · {{ $groupOpenCount }} tournée(s) en cours</small></span>
                        <span class="cc-courier-group-balance"><small>À remettre</small><strong>{{ number_format($groupOutstanding,0,',',' ') }} F</strong></span>
                        <span class="cc-courier-group-chevron material-icons" aria-hidden="true">expand_more</span>
                    </summary>
                    <div class="cc-courier-runs">
                    @foreach($courierRuns as $run)
                @php
                    $soldValue = (int) round((float) $run->lines->sum(fn ($line) => (float) $line->quantity_sold * (int) $line->unit_price));
                    $cashExpected = (int) $run->cash_expected;
                    $outstandingCash = max(0, $cashExpected - (int) $run->cash_received);
                    $quantityLeft = (float) $run->lines->sum(fn ($line) => (float) $line->quantity_assigned - (float) $line->quantity_sold - (float) $line->quantity_returned);
                @endphp
                <article class="cc-run-card">
                    <header class="cc-run-header">
                        <div class="cc-run-identity">
                                <span class="cc-run-avatar material-icons" aria-hidden="true">receipt_long</span>
                            <div class="cc-run-person">
                                <span class="cc-reference">{{ $run->reference }}</span>
                                <h3>Tournée du {{ $run->run_date->format('d/m/Y') }}</h3>
                                <div class="cc-run-meta"><span>{{ $run->shop->name }}</span></div>
                            </div>
                        </div>
                        <span class="cc-status {{ $run->status==='closed'?'is-closed':'is-open' }}">{{ $run->status==='closed'?'Tournée clôturée':'Tournée en cours' }}</span>
                    </header>
                    <div class="cc-totals">
                        <div><span>Unités confiées</span><strong>{{ rtrim(rtrim(number_format((float)$run->lines->sum('quantity_assigned'),3,',',' '),'0'),',') }}</strong></div>
                        <div><span>Unités vendues</span><strong>{{ rtrim(rtrim(number_format((float)$run->lines->sum('quantity_sold'),3,',',' '),'0'),',') }}</strong></div>
                        <div><span>Valeur des produits vendus</span><strong>{{ number_format($soldValue,0,',',' ') }} F</strong></div>
                        <div class="cc-total-due"><span>Part des produits à remettre</span><strong>{{ number_format($outstandingCash,0,',',' ') }} F</strong></div>
                    </div>
                    <section class="cc-run-products">
                        <div class="cc-run-subheading"><h4>Détail des produits</h4><span>{{ $run->lines->count() }} référence(s)</span></div>
                        <div class="table-responsive"><table class="table cc-table"><thead><tr><th>Produit</th><th>Confié</th><th>Vendu</th><th>Retourné</th><th>Prix unitaire</th></tr></thead><tbody>@foreach($run->lines as $line)<tr><td><strong>{{ $line->product->name }}</strong></td><td>{{ rtrim(rtrim(number_format((float)$line->quantity_assigned,3,',',' '),'0'),',') }}</td><td>{{ rtrim(rtrim(number_format((float)$line->quantity_sold,3,',',' '),'0'),',') }}</td><td>{{ rtrim(rtrim(number_format((float)$line->quantity_returned,3,',',' '),'0'),',') }}</td><td>{{ number_format($line->unit_price,0,',',' ') }} F</td></tr>@endforeach</tbody></table></div>
                    </section>
                    @if($run->status==='open' && $isCourier)
                        <div class="cc-courier-auto-note"><span class="material-icons" aria-hidden="true">sync</span><div><strong>Ventes mises à jour automatiquement</strong><p>Les quantités vendues et les espèces encaissées se mettent à jour à chaque livraison validée. Le responsable boutique vérifiera le stock rendu et confirmera la remise d’argent.</p></div></div>
                    @elseif($run->status==='open')
                        <details class="cc-settlement"><summary>Vérifier le retour et enregistrer la remise</summary>
                            <form method="POST" action="{{ route('courier-consignments.settle',$run) }}" class="cc-settlement-form">@csrf
                                <p class="cc-help">Les quantités vendues viennent automatiquement des commandes livrées. Compte le stock physiquement rendu et saisis les espèces réellement reçues.</p>
                                @foreach($run->lines as $line)
                                    @php($remainingProduct=max(0,(float)$line->quantity_assigned-(float)$line->quantity_sold-(float)$line->quantity_returned))
                                    <div class="cc-settlement-line"><div><strong>{{ $line->product->name }}</strong><small>Confié : {{ number_format((float)$line->quantity_assigned,0,',',' ') }} · vendu automatiquement : {{ number_format((float)$line->quantity_sold,0,',',' ') }} · retour attendu : {{ number_format($remainingProduct,0,',',' ') }}</small></div><input type="hidden" name="returned[{{ $loop->index }}][product_id]" value="{{ $line->product_id }}"><label>Quantité physiquement rendue<input class="cc-return-input" type="number" name="returned[{{ $loop->index }}][quantity]" min="0" max="{{ (int)$remainingProduct }}" step="1" inputmode="numeric" value="{{ (int)$remainingProduct }}"></label></div>
                                @endforeach
                                <div class="cc-settlement-actions"><label class="cc-field">Espèces des ventes de produits remises maintenant (F CFA)<input class="cc-cash-input" type="number" name="cash_amount" min="0" max="1000000000" step="1" inputmode="numeric" value="{{ $outstandingCash }}" required><small>Frais de livraison exclus. Part des produits encaissée sur les livraisons : {{ number_format($cashExpected,0,',',' ') }} F · déjà remise à la boutique : {{ number_format($run->cash_received,0,',',' ') }} F. Un versement partiel est possible.</small></label><label class="cc-field">Note facultative<textarea name="notes" rows="2" maxlength="1000"></textarea></label></div>
                                <div class="cc-buttons"><button class="btn btn-outline-primary" type="submit">Enregistrer ce point et ce versement</button><button class="btn btn-primary" type="submit" name="close_run" value="1">Clôturer et générer le bon</button></div>
                            </form>
                        </details>
                    @else
                        <a class="btn btn-outline-primary" href="{{ route('courier-consignments.receipt',$run) }}">Voir le bon de déconsignation</a>
                    @endif
                    @if($run->events->isNotEmpty())
                        <details class="cc-history">
                            <summary>Historique de la tournée · {{ $run->events->count() }} opération(s)</summary>
                            <ul>
                                @foreach($run->events as $event)
                                    <li>
                                        <strong>{{ ['assignment' => 'Produits remis', 'sales' => 'Livraison enregistrée automatiquement', 'returns' => 'Produits retournés', 'cash_deposit' => 'Argent remis à la boutique', 'closed' => 'Tournée clôturée'][$event->event_type] ?? $event->event_type }}</strong>
                                        <span>
                                            {{ $event->occurred_at->format('d/m/Y H:i') }} · {{ $event->actor?->name ?? 'Utilisateur' }}
                                            @if($event->amount > 0)
                                                · {{ number_format($event->amount, 0, ',', ' ') }} F
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </article>
                    @endforeach
                    </div>
                </details>
            @empty
                <div class="cc-empty"><span class="material-icons">local_shipping</span><strong>Aucune tournée à afficher</strong><p>Les consignations et tournées de la boutique apparaîtront ici.</p></div>
            @endforelse
        </div>
        <div class="cc-pagination">{{ $runs->links() }}</div>
    </section>
</main>
<script>
function ccNormalize(value) {
    return (value || '').toLocaleLowerCase('fr').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
}
var courierSearch = document.querySelector('.cc-courier-search');
var courierSelect = document.querySelector('.cc-courier-picker select');
if (courierSearch && courierSelect) {
    var courierResult = document.querySelector('.cc-courier-result');
    var courierOptions = Array.from(courierSelect.options).slice(1);
    courierSearch.addEventListener('input', function () {
        var query = ccNormalize(courierSearch.value);
        var visibleCount = 0;
        courierOptions.forEach(function (option) {
            var visible = ccNormalize(option.dataset.search || option.textContent).includes(query);
            option.hidden = !visible;
            if (visible) visibleCount++;
        });
        courierResult.textContent = visibleCount ? visibleCount + ' livreur(s) trouvé(s)' : 'Aucun livreur trouvé';
        if (courierSelect.selectedOptions[0]?.hidden) courierSelect.value = '';
    });
    courierSelect.addEventListener('change', function () {
        var selected = courierSelect.selectedOptions[0];
        if (selected && selected.value) courierSearch.value = selected.textContent.trim();
    });
}
document.querySelectorAll('.cc-product-search').forEach(function (search) {
    var picker = search.closest('.cc-product-picker');
    var rows = Array.from(picker.querySelectorAll('.cc-product-row'));
    var pageSize = 5;
    var currentPage = 0;
    var pageStatus = picker.querySelector('.cc-page-status');
    var previous = picker.querySelector('.cc-page-prev');
    var next = picker.querySelector('.cc-page-next');
    function renderProducts() {
        var query = ccNormalize(search.value);
        var matches = rows.filter(function (row) { return ccNormalize(row.dataset.productName).includes(query); });
        var pageCount = Math.max(1, Math.ceil(matches.length / pageSize));
        picker.querySelector('.cc-product-pagination').hidden = matches.length <= pageSize;
        currentPage = Math.min(currentPage, pageCount - 1);
        var pageStart = currentPage * pageSize;
        var pageRows = new Set(matches.slice(pageStart, pageStart + pageSize));
        rows.forEach(function (row) { row.hidden = !pageRows.has(row); });
        picker.querySelector('.cc-no-products').hidden = matches.length > 0;
        pageStatus.textContent = matches.length ? 'Page ' + (currentPage + 1) + ' sur ' + pageCount + ' · ' + matches.length + ' produit(s)' : '0 produit';
        previous.disabled = currentPage === 0;
        next.disabled = currentPage >= pageCount - 1;
    }
    search.addEventListener('input', function () {
        currentPage = 0;
        renderProducts();
    });
    previous.addEventListener('click', function () {
        currentPage = Math.max(0, currentPage - 1);
        renderProducts();
    });
    next.addEventListener('click', function () {
        currentPage++;
        renderProducts();
    });
    renderProducts();
});
document.querySelectorAll('.cc-quantity').forEach(function (quantity) {
    var input = quantity.querySelector('input[type="number"]');
    quantity.querySelectorAll('.cc-quantity-step').forEach(function (button) {
        button.addEventListener('click', function () {
            var next = Number(input.value || 0) + Number(button.dataset.step || 0);
            input.value = String(Math.max(Number(input.min || 0), Math.min(Number(input.max || Infinity), next)));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            updateSelectedProductCount();
        });
    });
    input.addEventListener('input', updateSelectedProductCount);
});
function updateSelectedProductCount() {
    var count = document.querySelectorAll('.cc-product-row input[type="number"]').length;
    var selected = Array.from(document.querySelectorAll('.cc-product-row input[type="number"]')).filter(function (input) { return Number(input.value || 0) > 0; }).length;
    document.querySelectorAll('.cc-selected-count').forEach(function (node) { node.textContent = String(selected); });
    document.querySelectorAll('.cc-assignment-form button[type="submit"]').forEach(function (button) { button.disabled = count > 0 && selected === 0; });
}
updateSelectedProductCount();
document.querySelectorAll('.cc-assignment-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var button = form.querySelector('.cc-submit-assignment');
        if (form.dataset.submitting === 'true' || !button || button.disabled) {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.querySelector('.cc-submit-label').hidden = true;
        button.querySelector('.cc-submit-loading').hidden = false;
    });
});
</script>
<style>
.courier-consignment-page{max-width:1320px;padding:clamp(14px,2vw,28px);color:var(--app-text)}.cc-hero,.cc-panel,.cc-run-card{background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius);box-shadow:0 4px 16px rgba(20,30,45,.035)}.cc-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:22px;margin-bottom:16px}.cc-kicker,.cc-reference{color:var(--app-primary);font-size:10px;font-weight:850;letter-spacing:.06em}.cc-hero h1{margin:5px 0;font-size:clamp(24px,3vw,34px);font-weight:850}.cc-hero p,.cc-section-heading p{margin:0;color:var(--app-muted);font-size:13px}.cc-location{padding:9px 12px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));border-radius:7px;font-size:12px;font-weight:750}.cc-panel{padding:20px;margin-bottom:16px}.cc-section-heading{display:flex;justify-content:space-between;gap:14px;margin-bottom:15px}.cc-section-heading h2{margin:0 0 4px;font-size:19px;font-weight:820}.cc-assignment-form{display:grid;gap:13px}.cc-field{display:grid;gap:6px;margin:0;font-size:12px;font-weight:750}.cc-field input,.cc-field select,.cc-field textarea,.cc-product-search,.cc-quantity input,.cc-settlement-line input{min-width:0;width:100%;padding:9px 11px;border:1px solid var(--app-border);border-radius:6px;background:var(--app-surface);color:var(--app-text)}.cc-field select,.cc-field input,.cc-quantity input,.cc-settlement-line input{height:42px}.cc-help,.cc-field small{margin:0;color:var(--app-muted);font-size:11px;line-height:1.45}.cc-product-picker{border:1px solid var(--app-border);border-radius:8px;overflow:hidden}.cc-product-picker-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface))}.cc-product-picker-head strong{font-size:13px}.cc-product-search{max-width:310px;background:var(--app-surface)}.cc-product-list{max-height:370px;overflow:auto}.cc-product-row{display:grid;grid-template-columns:minmax(0,1fr) 130px;align-items:center;gap:12px;padding:10px 12px;margin:0;border-top:1px solid var(--app-border)}.cc-product-row[hidden]{display:none}.cc-product-row>span:first-child{display:grid;gap:3px;min-width:0}.cc-product-row strong{font-size:12px;overflow-wrap:anywhere}.cc-product-row small,.cc-run-header small{color:var(--app-muted);font-size:10px}.cc-quantity{display:grid;grid-template-columns:1fr auto;align-items:center;gap:5px}.cc-quantity small{font-size:10px;color:var(--app-muted)}.cc-run-list{display:grid;gap:13px}.cc-run-card{padding:16px}.cc-run-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.cc-run-header h3{margin:3px 0;font-size:17px;font-weight:800}.cc-status{padding:6px 9px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap}.cc-status.is-open{background:#fff2d7;color:#895a08}.cc-status.is-closed{background:#e6f4eb;color:#217342}.cc-totals{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:13px 0}.cc-totals>div{display:grid;gap:3px;padding:10px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));border-radius:6px}.cc-totals span{color:var(--app-muted);font-size:10px}.cc-totals strong{font-size:14px;overflow-wrap:anywhere}.cc-table{margin:0}.cc-table th{color:var(--app-muted);font-size:10px;text-transform:uppercase}.cc-table td{font-size:12px}.cc-settlement,.cc-history{margin-top:12px;padding-top:11px;border-top:1px solid var(--app-border)}.cc-settlement summary,.cc-history summary{color:var(--app-primary);font-size:12px;font-weight:800;cursor:pointer}.cc-settlement-form{display:grid;gap:10px;padding-top:12px}.cc-settlement-line{display:grid;grid-template-columns:minmax(0,1fr) 125px 125px;gap:10px;align-items:center;padding:9px 10px;border:1px solid var(--app-border);border-radius:7px}.cc-settlement-line>div{display:grid;gap:4px;min-width:0}.cc-settlement-line>div strong{font-size:12px}.cc-settlement-line>div small{color:var(--app-muted);font-size:10px}.cc-settlement-line label{display:grid;gap:3px;margin:0;font-size:10px;font-weight:750}.cc-settlement-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}.cc-buttons{display:flex;flex-wrap:wrap;gap:8px}.cc-history ul{display:grid;gap:8px;margin:10px 0 0;padding:0;list-style:none}.cc-history li{display:flex;justify-content:space-between;gap:10px;padding:8px 10px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));font-size:11px}.cc-history li span{color:var(--app-muted);text-align:right}.cc-empty,.cc-empty-inline{padding:23px;color:var(--app-muted);text-align:center}.cc-empty .material-icons{font-size:30px;color:var(--app-primary)}.cc-empty strong{display:block;color:var(--app-text)}.cc-empty p{margin:4px 0 0;font-size:12px}.cc-pagination{margin-top:14px}
.cc-courier-picker{display:grid;grid-template-columns:minmax(0,1fr);gap:6px;max-width:620px;padding:14px;border:1px solid var(--app-border);border-radius:9px;background:color-mix(in srgb,var(--app-text) 2%,var(--app-surface))}.cc-courier-picker select,.cc-courier-search{height:44px;min-width:0;padding:10px 12px;border:1px solid var(--app-border);border-radius:7px;background:var(--app-surface);color:var(--app-text);font:inherit}.cc-courier-picker select:focus,.cc-courier-search:focus,.cc-product-search:focus,.cc-quantity input:focus{outline:2px solid color-mix(in srgb,var(--app-primary) 28%,transparent);outline-offset:1px;border-color:var(--app-primary)}.cc-courier-result{color:var(--app-muted);font-size:11px}.cc-product-picker-head>div{display:grid;gap:3px}.cc-product-picker-head>div small{color:var(--app-muted);font-size:11px}.cc-product-row{grid-template-columns:minmax(0,1fr) auto 148px;gap:14px;padding:12px 14px}.cc-product-row[hidden]{display:none}.cc-product-info{display:grid;gap:4px;min-width:0}.cc-product-info strong{font-size:13px;line-height:1.35}.cc-product-meta{color:var(--app-muted);font-size:11px}.cc-stock-badge{padding:6px 9px;border-radius:20px;background:color-mix(in srgb,#2d8a58 10%,var(--app-surface));color:#287149;font-size:10px;font-weight:750;white-space:nowrap}.cc-quantity{display:grid;grid-template-columns:34px minmax(45px,1fr) 34px;align-items:center;gap:5px;padding:4px;border:1px solid var(--app-border);border-radius:8px;background:var(--app-surface)}.cc-quantity input{height:34px;padding:4px;border:0;border-radius:4px;text-align:center;font-size:15px;font-weight:800;appearance:textfield}.cc-quantity input::-webkit-inner-spin-button,.cc-quantity input::-webkit-outer-spin-button{margin:0;appearance:none}.cc-quantity-step{display:grid;place-items:center;width:34px;height:34px;padding:0;border:0;border-radius:6px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));color:var(--app-primary);font-size:20px;font-weight:700;line-height:1;cursor:pointer}.cc-quantity-step:hover{background:var(--app-primary);color:#fff}.cc-quantity-step:focus-visible{outline:2px solid var(--app-primary);outline-offset:2px}.cc-product-pagination{display:flex;align-items:center;justify-content:center;gap:12px;padding:9px;border-top:1px solid var(--app-border)}.cc-page-status{min-width:150px;color:var(--app-muted);font-size:11px;text-align:center}.cc-page-button{min-height:34px;padding:6px 11px;border:1px solid var(--app-border);border-radius:6px;background:var(--app-surface);color:var(--app-primary);font-size:11px;font-weight:750;cursor:pointer}.cc-page-button:hover:not(:disabled){border-color:var(--app-primary);background:color-mix(in srgb,var(--app-primary) 7%,var(--app-surface))}.cc-page-button:disabled{opacity:.45;cursor:not-allowed}.cc-picker-footer{display:flex;justify-content:space-between;gap:12px;padding:10px 14px;border-top:1px solid var(--app-border);background:color-mix(in srgb,var(--app-text) 3%,var(--app-surface));color:var(--app-muted);font-size:11px}.cc-picker-footer strong{color:var(--app-primary)}.cc-no-products{margin:0;padding:18px;text-align:center;color:var(--app-muted);font-size:12px}.cc-assignment-form>button:disabled{opacity:.55;cursor:not-allowed}
@media(max-width:760px){.cc-hero{align-items:flex-start;flex-direction:column}.cc-totals{grid-template-columns:repeat(2,minmax(0,1fr))}.cc-settlement-line{grid-template-columns:minmax(0,1fr) 1fr 1fr}.cc-settlement-line>div{grid-column:1/-1}.cc-settlement-actions{grid-template-columns:1fr}}
@media(max-width:760px){.cc-product-row{grid-template-columns:minmax(0,1fr) auto}.cc-product-info{grid-column:1}.cc-stock-badge{grid-column:2;grid-row:1}.cc-quantity{grid-column:1/-1;justify-self:end;width:min(100%,190px)}}
@media(max-width:520px){.courier-consignment-page{padding:12px 9px}.cc-panel,.cc-run-card{padding:12px}.cc-product-picker-head{align-items:stretch;flex-direction:column}.cc-product-search{max-width:none}.cc-product-row{grid-template-columns:minmax(0,1fr) auto;padding:10px}.cc-stock-badge{font-size:9px;padding:5px 7px}.cc-quantity{grid-template-columns:40px minmax(45px,1fr) 40px;width:100%;justify-self:stretch}.cc-quantity-step{width:40px;height:38px}.cc-quantity input{height:38px}.cc-product-pagination{gap:6px;padding:8px 4px}.cc-page-button{padding:6px 8px;font-size:10px}.cc-page-status{min-width:0;flex:1;font-size:10px}.cc-picker-footer{align-items:flex-start;flex-direction:column;gap:4px}.cc-courier-picker{max-width:none;padding:11px}.cc-totals>div{padding:8px}.cc-run-header{align-items:flex-start}.cc-settlement-line{grid-template-columns:1fr 1fr}.cc-settlement-line>div{grid-column:1/-1}.cc-buttons{display:grid}.cc-buttons .btn{width:100%}.cc-history li{display:grid}.cc-history li span{text-align:left}}
.cc-quantity input{height:36px;min-width:55px;padding:4px;border:1px solid var(--app-border);border-radius:6px;background:var(--app-surface);color:var(--app-text);text-align:center;font-size:15px;font-weight:800;appearance:textfield}.cc-quantity input:focus{outline:2px solid color-mix(in srgb,var(--app-primary) 25%,transparent);outline-offset:1px;border-color:var(--app-primary)}.cc-settlement-line{background:color-mix(in srgb,var(--app-text) 2%,var(--app-surface));padding:13px}.cc-settlement-line label{gap:7px;font-size:11px}.cc-settlement-line label input,.cc-settlement-actions .cc-field>input,.cc-settlement-actions .cc-field>textarea{display:block;width:100%;min-width:0;min-height:44px;padding:10px 12px;border:1px solid #cbd3df!important;border-radius:7px!important;background:#fff!important;color:#283142!important;box-shadow:0 1px 2px rgba(20,30,45,.06);font:inherit;font-size:14px}.cc-settlement-actions .cc-field>textarea{min-height:90px;resize:vertical}.cc-settlement-line label input:focus,.cc-settlement-actions .cc-field>input:focus,.cc-settlement-actions .cc-field>textarea:focus{outline:2px solid color-mix(in srgb,var(--app-primary) 28%,transparent);outline-offset:1px;border-color:var(--app-primary)!important}.cc-settlement-actions{gap:16px;margin-top:4px}.cc-submit-assignment{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:44px}.cc-submit-loading{align-items:center;justify-content:center;gap:9px}.cc-submit-loading:not([hidden]){display:inline-flex}.cc-spinner{width:16px;height:16px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:cc-spin .7s linear infinite}@keyframes cc-spin{to{transform:rotate(360deg)}}
.cc-run-count{align-self:flex-start;padding:7px 10px;border-radius:20px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));color:var(--app-primary);font-size:11px;font-weight:800;white-space:nowrap}.cc-run-card{padding:0;overflow:hidden}.cc-run-header{align-items:center;padding:17px 18px;border-bottom:1px solid var(--app-border);background:color-mix(in srgb,var(--app-text) 2%,var(--app-surface))}.cc-run-identity{display:flex;align-items:center;gap:12px;min-width:0}.cc-run-avatar{display:grid;place-items:center;width:42px;height:42px;flex:none;border-radius:50%;background:color-mix(in srgb,var(--app-primary) 9%,var(--app-surface));color:var(--app-primary);font-size:21px}.cc-run-person{min-width:0}.cc-run-person h3{margin:3px 0 5px;font-size:17px;line-height:1.2;overflow-wrap:anywhere}.cc-run-meta{display:flex;flex-wrap:wrap;gap:6px}.cc-run-meta span{padding:3px 7px;border-radius:4px;background:var(--app-surface);color:var(--app-muted);font-size:10px}.cc-status{padding:7px 10px;font-size:10px}.cc-totals{gap:1px;margin:0;padding:12px 18px;background:var(--app-border)}.cc-totals>div{align-content:center;min-height:66px;padding:11px 13px;border-radius:0;background:var(--app-surface)}.cc-totals>div:first-child{border-radius:7px 0 0 7px}.cc-totals>div:last-child{border-radius:0 7px 7px 0}.cc-totals span{font-size:10px;line-height:1.35}.cc-totals strong{font-size:15px}.cc-totals .cc-total-due{background:color-mix(in srgb,var(--app-primary) 7%,var(--app-surface))}.cc-total-due strong{color:var(--app-primary)}.cc-run-products{padding:14px 18px 16px}.cc-run-subheading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}.cc-run-subheading h4{margin:0;font-size:13px;font-weight:800}.cc-run-subheading>span{color:var(--app-muted);font-size:10px}.cc-table{border:1px solid var(--app-border);border-radius:6px;overflow:hidden}.cc-table thead th{padding:9px 11px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));white-space:nowrap}.cc-table tbody td{padding:9px 11px;vertical-align:middle}.cc-table tbody td:first-child{min-width:145px}.cc-run-card>.cc-settlement,.cc-run-card>.cc-history,.cc-run-card>.btn{display:block;margin:0 18px 14px;padding:12px;border:1px solid var(--app-border);border-radius:7px}.cc-run-card>.cc-settlement[open]{background:color-mix(in srgb,var(--app-text) 2%,var(--app-surface))}.cc-run-card>.cc-history{margin-top:0}.cc-run-card>.btn{width:max-content}
.cc-courier-auto-note{display:flex;align-items:flex-start;gap:10px;margin:0 18px 14px;padding:12px 14px;border:1px solid color-mix(in srgb,#287149 22%,var(--app-border));border-radius:7px;background:color-mix(in srgb,#287149 5%,var(--app-surface));color:var(--app-text)}.cc-courier-auto-note>.material-icons{color:#287149;font-size:19px}.cc-courier-auto-note strong{font-size:12px}.cc-courier-auto-note p{margin:3px 0 0;color:var(--app-muted);font-size:11px;line-height:1.5}
@media(max-width:760px){.cc-run-header{align-items:flex-start;padding:14px}.cc-totals{grid-template-columns:repeat(2,minmax(0,1fr));gap:1px;padding:1px}.cc-totals>div,.cc-totals>div:first-child,.cc-totals>div:last-child{min-height:61px;border-radius:0}.cc-run-products{padding:12px}.cc-run-card>.cc-settlement,.cc-run-card>.cc-history,.cc-run-card>.btn{margin-right:12px;margin-left:12px}}
@media(max-width:520px){.cc-section-heading{align-items:flex-start}.cc-run-count{font-size:9px}.cc-run-header{gap:8px}.cc-run-identity{gap:8px}.cc-run-avatar{width:36px;height:36px;font-size:18px}.cc-run-person h3{font-size:15px}.cc-status{max-width:110px;white-space:normal;text-align:center}.cc-totals{grid-template-columns:repeat(2,minmax(0,1fr))}.cc-totals>div{padding:9px}.cc-totals strong{font-size:14px}.cc-run-products{padding:10px}.cc-run-products .table-responsive{margin-right:-2px}.cc-table th,.cc-table td{padding:8px;font-size:10px}.cc-run-card>.cc-settlement,.cc-run-card>.cc-history,.cc-run-card>.btn{margin-right:10px;margin-left:10px}.cc-run-card>.btn{width:calc(100% - 20px);text-align:center}}
@media(max-width:760px){.cc-courier-auto-note{margin-right:12px;margin-left:12px}}
.cc-courier-group{overflow:hidden;border:1px solid var(--app-border);border-radius:10px;background:var(--app-surface);box-shadow:0 3px 12px rgba(20,30,45,.035)}.cc-courier-group-head{display:grid;grid-template-columns:42px minmax(0,1fr) auto 20px;align-items:center;gap:12px;padding:14px 16px;list-style:none;cursor:pointer}.cc-courier-group-head::-webkit-details-marker{display:none}.cc-courier-group[open]>.cc-courier-group-head{border-bottom:1px solid var(--app-border);background:color-mix(in srgb,var(--app-text) 2%,var(--app-surface))}.cc-courier-group-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:50%;background:color-mix(in srgb,var(--app-primary) 9%,var(--app-surface));color:var(--app-primary);font-size:21px}.cc-courier-group-name{display:grid;gap:4px;min-width:0}.cc-courier-group-name strong{font-size:15px;overflow-wrap:anywhere}.cc-courier-group-name small,.cc-courier-group-balance small{color:var(--app-muted);font-size:10px}.cc-courier-group-balance{display:grid;gap:3px;text-align:right}.cc-courier-group-balance strong{color:var(--app-primary);font-size:14px;white-space:nowrap}.cc-courier-group-chevron{color:var(--app-muted);transition:transform .18s ease}.cc-courier-group[open] .cc-courier-group-chevron{transform:rotate(180deg)}.cc-courier-runs{display:grid;gap:12px;padding:12px}.cc-courier-runs .cc-run-card{border-radius:8px;box-shadow:none}
@media(max-width:520px){.cc-courier-group-head{grid-template-columns:34px minmax(0,1fr) 18px;gap:9px;padding:11px}.cc-courier-group-icon{width:34px;height:34px;font-size:18px}.cc-courier-group-name strong{font-size:13px}.cc-courier-group-name small{font-size:9px}.cc-courier-group-balance{grid-column:2;grid-row:2;display:flex;align-items:center;gap:6px;text-align:left}.cc-courier-group-balance strong{font-size:12px}.cc-courier-group-chevron{grid-column:3;grid-row:1}.cc-courier-runs{gap:9px;padding:8px}}
</style>
@endsection
