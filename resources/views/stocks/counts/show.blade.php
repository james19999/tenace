@extends('layouts.admin')

@section('content')
<div class="stock-control-page">
    <header class="stock-control-hero"><div class="stock-control-hero-copy"><span class="stock-control-kicker">INVENTAIRE · {{ $count->count_number }}</span><h1>{{ $count->location->name }}</h1><p>{{ $count->is_opening_balance ? 'Reprise initiale' : 'Comptage physique' }} · {{ $count->scheduled_at?->format('d/m/Y à H:i') }}</p></div><div class="transfer-detail-heading-actions">@if($count->status==='completed')<a href="{{ route('stock-control.counts.print', $count) }}" target="_blank" rel="noopener" class="stock-control-hero-action"><span class="material-icons">print</span>Bon d’inventaire</a>@endif<a href="{{ route('stock-control.counts.index') }}" class="stock-control-hero-action"><span class="material-icons">arrow_back</span>Inventaires</a></div><div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">fact_check</span></div></header>
    @if(session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="stock-control-alert stock-control-alert-error"><span class="material-icons">error_outline</span><div><strong>Vérifie les informations.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <section class="stock-control-card stock-count-summary"><div><span>État</span><strong>{{ ['in_progress'=>'À compter','submitted'=>'À appliquer','completed'=>'Terminé'][$count->status] ?? $count->status }}</strong></div><div><span>Responsable du comptage</span><strong>{{ $count->counter?->name ?? '—' }}</strong></div><div><span>Créé par</span><strong>{{ $count->creator?->name ?? '—' }}</strong></div><div><span>Produits saisis</span><strong>{{ $lineCount }}</strong></div></section>
    @if($count->status==='in_progress' && $canCount)
        <form method="POST" action="{{ route('stock-control.counts.submit',$count) }}" class="stock-control-card stock-count-entry">@csrf
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">COMPTAGE À L’AVEUGLE</span><h2>Quantités physiques</h2></div><span class="stock-control-count">{{ $count->lines->count() }} produit(s)</span></div>
            <aside class="stock-count-counter-guide">
                <span class="material-icons" aria-hidden="true">tips_and_updates</span>
                <div><strong>Comment remplir ce comptage</strong>
                    <ol><li>Recherche les produits que tu comptes à <b>{{ $count->location->name }}</b>, puis saisis leur quantité physique.</li><li>Si un produit de ton périmètre est absent, recherche-le et saisis <b>0</b>.</li>
                        @if($count->is_opening_balance)<li>Il s’agit d’une reprise initiale : ne recopie pas le stock global du produit. Chaque emplacement doit recevoir son propre comptage réel.</li>@endif
                    </ol>
                    <small>Les produits sont recherchés à la demande. La quantité théorique reste masquée pour garantir un comptage indépendant.</small>
                </div>
            </aside>
            <div class="stock-count-product-picker" data-product-search-url="{{ route('stock-control.counts.products.search', $count) }}">
                <label class="stock-control-field"><span>Rechercher et ajouter un produit</span><input type="search" class="stock-count-product-search" placeholder="Tape au moins 2 caractères" autocomplete="off" aria-controls="stockCountSearchResults"></label>
                <div class="stock-count-search-results" id="stockCountSearchResults" role="listbox" aria-live="polite"></div>
                <div class="stock-count-product-list" aria-live="polite"><div class="stock-count-product-empty">Les produits sélectionnés apparaîtront ici.</div></div>
            </div>
            <label class="stock-control-field stock-control-field-notes"><span>Note du comptage</span><textarea name="notes" rows="3" placeholder="Écarts, produits manquants ou observations">{{ old('notes') }}</textarea></label>
            <div class="stock-control-form-footer"><small>À l’enregistrement, les écarts seront appliqués directement au stock.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">save</span>Enregistrer le comptage</button></div>
        </form>
        <script>
            (() => {
                const picker = document.querySelector('.stock-count-product-picker');
                const form = document.querySelector('.stock-count-entry');
                if (!picker || !form) return;
                const search = picker.querySelector('.stock-count-product-search');
                const results = picker.querySelector('.stock-count-search-results');
                const list = picker.querySelector('.stock-count-product-list');
                const submit = form.querySelector('button[type="submit"]');
                let timer;
                let request;

                const selectedIds = () => new Set([...list.querySelectorAll('[data-product-id]')].map(row => row.dataset.productId));
                const addProduct = product => {
                    const id = String(product.product_id);
                    if (selectedIds().has(id)) return;
                    list.querySelector('.stock-count-product-empty')?.remove();
                    const row = document.createElement('div');
                    row.className = 'stock-count-product';
                    row.dataset.productId = id;
                    const name = document.createElement('span');
                    const title = document.createElement('strong');
                    title.textContent = product.name;
                    const reference = document.createElement('small');
                    reference.textContent = `Réf. produit ${product.product_id}`;
                    name.append(title, reference);
                    const input = document.createElement('input');
                    input.type = 'number'; input.min = '0'; input.step = '1'; input.placeholder = 'Quantité'; input.required = true;
                    input.name = `quantities[${id}]`;
                    input.setAttribute('aria-label', `Quantité comptée pour ${product.name}`);
                    const remove = document.createElement('button');
                    remove.type = 'button'; remove.className = 'stock-count-product-remove'; remove.textContent = 'Retirer';
                    remove.addEventListener('click', () => {
                        row.remove();
                        if (!list.querySelector('[data-product-id]')) {
                            const empty = document.createElement('div'); empty.className = 'stock-count-product-empty'; empty.textContent = 'Les produits sélectionnés apparaîtront ici.'; list.append(empty);
                        }
                    });
                    row.append(name, input, remove);
                    list.append(row);
                    input.focus();
                };

                search.addEventListener('input', () => {
                    window.clearTimeout(timer);
                    request?.abort();
                    const term = search.value.trim();
                    results.replaceChildren();
                    if (term.length < 2) return;
                    results.textContent = 'Recherche…';
                    timer = window.setTimeout(async () => {
                        request = new AbortController();
                        try {
                            const url = new URL(picker.dataset.productSearchUrl, window.location.origin);
                            url.searchParams.set('search', term);
                            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
                            if (!response.ok) throw new Error('search failed');
                            const products = await response.json();
                            results.replaceChildren();
                            const selected = selectedIds();
                            const available = products.filter(product => !selected.has(String(product.product_id)));
                            if (!available.length) { results.textContent = 'Aucun produit correspondant à ajouter.'; return; }
                            available.forEach(product => {
                                const button = document.createElement('button');
                                button.type = 'button'; button.className = 'stock-count-search-result'; button.setAttribute('role', 'option');
                                button.textContent = `${product.name} · Réf. ${product.product_id}`;
                                button.addEventListener('click', () => { addProduct(product); search.value = ''; results.replaceChildren(); search.focus(); });
                                results.append(button);
                            });
                        } catch (error) {
                            if (error.name !== 'AbortError') results.textContent = 'La recherche a échoué. Réessaie.';
                        }
                    }, 250);
                });

                form.addEventListener('submit', event => {
                    if (!list.querySelector('[data-product-id]')) {
                        event.preventDefault(); search.focus(); results.textContent = 'Ajoute au moins un produit avant de soumettre le comptage.';
                    }
                });

                const productId = new URLSearchParams(window.location.search).get('product_id');
                if (productId) {
                    const url = new URL(picker.dataset.productSearchUrl, window.location.origin);
                    url.searchParams.set('product_id', productId);
                    fetch(url, { headers: { Accept: 'application/json' } })
                        .then(response => response.ok ? response.json() : Promise.reject())
                        .then(products => {
                            if (products.length) {
                                addProduct(products[0]);
                                results.textContent = 'Produit ajouté depuis sa fiche. Saisis sa quantité physique.';
                            }
                        })
                        .catch(() => { results.textContent = 'Le produit demandé est introuvable dans cet inventaire.'; });
                }
            })();
        </script>
    @else
        <section class="stock-control-card stock-count-review"><div class="stock-control-section-head"><div><span class="stock-control-kicker">INVENTAIRE</span><h2>{{ $count->status==='completed' ? 'Résultat de l’inventaire' : 'Comptage saisi' }}</h2></div>@if($count->status==='submitted' && $canApprove && $secondCountComplete)<button type="button" class="stock-control-button stock-control-button-primary" id="openCountApproval"><span class="material-icons">task_alt</span>Appliquer les quantités</button>@endif</div>
            <div class="stock-count-table-wrap"><table class="stock-count-table"><thead><tr><th>Produit</th><th>Attendu</th><th>Quantité comptée</th><th>Écart</th><th>Valeur indicative</th></tr></thead><tbody>@foreach($count->lines as $line)@php($delta=(float)($line->counted_quantity ?? 0)-(float)$line->expected_quantity)<tr><td data-label="Produit"><strong>{{ $line->product->name }}</strong></td><td data-label="Attendu">{{ number_format((float)$line->expected_quantity,0,',',' ') }}</td><td data-label="Quantité comptée">{{ $line->counted_quantity===null?'—':number_format((float)$line->counted_quantity,0,',',' ') }}</td><td data-label="Écart"><span class="{{ $delta<0?'stock-count-negative':($delta>0?'stock-count-positive':'') }}">{{ $line->counted_quantity===null?'—':sprintf('%+d',(int)$delta) }}</span></td><td data-label="Valeur indicative">{{ $line->counted_quantity===null?'—':number_format(abs($delta)*(float)$line->product->price_market,0,',',' ').' F' }}</td></tr>@endforeach</tbody></table></div>
            @if($count->status==='submitted' && $requiresSecondCount && $differentLines->isNotEmpty() && $canSecondCount)
                <form method="POST" action="{{ route('stock-control.counts.second-count',$count) }}" class="stock-second-count-form">@csrf
                    <div><strong>Recompter les écarts</strong><small>Un second comptage indépendant est requis avant validation. Chaque résultat doit confirmer le premier comptage.</small></div>
                    <label class="stock-second-count-all"><input type="checkbox" data-confirm-all-recounts><span><strong>Tout est identique au premier comptage</strong><small>Confirme les quantités retrouvées pour tous les produits concernés.</small></span></label>
                    <div class="stock-second-count-lines">@foreach($differentLines as $line)<div class="stock-second-count-line"><div class="stock-second-count-product"><strong>{{ $line->product->name }}</strong><small>1er comptage : {{ number_format((float)$line->counted_quantity,0,',',' ') }}</small><label><input type="checkbox" data-confirm-recount data-counted="{{ (int)$line->counted_quantity }}"><span>Identique</span></label></div><input type="number" min="0" step="1" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id,$line->second_count_quantity) }}" placeholder="Saisir la quantité" data-recount-quantity required></div>@endforeach</div>
                    <button class="stock-control-button" type="submit"><span class="material-icons">refresh</span>Enregistrer le second comptage</button>
                </form>
            @endif
            @if($count->status==='submitted' && $requiresSecondCount && $differentLines->isNotEmpty() && !$canSecondCount)<div class="stock-count-help">Un second comptage indépendant est requis. Une autre personne que le compteur doit le saisir.</div>@endif
            @if($count->status==='submitted' && !$canApprove)<div class="stock-count-help">{{ $approvalMessage }}</div>@elseif($count->status==='submitted' && !$secondCountComplete)<div class="stock-count-help">L’approbation sera disponible après confirmation de chaque écart par un second comptage.</div>@endif
            @if($count->status==='submitted' && $canApprove && $secondCountComplete)
                <dialog class="stock-approval-dialog" id="countApprovalDialog" aria-labelledby="countApprovalTitle">
                    <div class="stock-approval-dialog-head"><span class="material-icons">fact_check</span><button type="button" class="stock-approval-dialog-close" data-close-count-approval aria-label="Fermer">×</button></div>
                    <h2 id="countApprovalTitle">Appliquer cet inventaire ?</h2>
                    <p>Les quantités comptées et les écarts seront inscrits dans le journal du stock.</p>
                    @if($count->is_opening_balance)
                        <p class="stock-count-approval-note">Pour cette reprise initiale, les quantités seront affectées à l’entrepôt et le stock global des fiches produit sera synchronisé sur le comptage, sans additionner deux fois les unités.</p>
                    @endif
                    <div class="stock-approval-dialog-actions">
                        <button type="button" class="stock-control-button" data-close-count-approval>Annuler</button>
                        <form method="POST" action="{{ route('stock-control.counts.approve',$count) }}">@csrf<button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">task_alt</span>Appliquer les quantités</button></form>
                    </div>
                </dialog>
                <script>
                    (() => {
                        const dialog = document.getElementById('countApprovalDialog');
                        const openButton = document.getElementById('openCountApproval');
                        if (!dialog || !openButton) return;
                        openButton.addEventListener('click', () => dialog.showModal());
                        dialog.querySelectorAll('[data-close-count-approval]').forEach(button => button.addEventListener('click', () => dialog.close()));
                        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
                    })();
                </script>
            @endif
            @if($count->status==='submitted' && $requiresSecondCount && $differentLines->isNotEmpty() && $canSecondCount)
                <script>
                    (() => {
                        const form = document.querySelector('.stock-second-count-form');
                        if (!form) return;
                        const all = form.querySelector('[data-confirm-all-recounts]');
                        const rows = [...form.querySelectorAll('.stock-second-count-line')];
                        const setSame = (row, checked, clearWhenUnchecked = true) => {
                            const choice = row.querySelector('[data-confirm-recount]');
                            const quantity = row.querySelector('[data-recount-quantity]');
                            choice.checked = checked;
                            quantity.readOnly = checked;
                            if (checked) quantity.value = choice.dataset.counted;
                            else if (clearWhenUnchecked) quantity.value = '';
                        };
                        rows.forEach(row => row.querySelector('[data-confirm-recount]').addEventListener('change', event => {
                            setSame(row, event.target.checked);
                            all.checked = rows.every(item => item.querySelector('[data-confirm-recount]').checked);
                        }));
                        all.addEventListener('change', () => rows.forEach(row => setSame(row, all.checked)));
                    })();
                </script>
            @endif
        </section>
    @endif
    @include('stocks.partials.audit-timeline', ['auditLogs' => $auditLogs])
</div>
@endsection
