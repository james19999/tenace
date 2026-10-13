@extends('layouts.admin')

@section('content')
<div class="stock-control-page">
    <header class="stock-control-hero"><div class="stock-control-hero-copy"><span class="stock-control-kicker">INVENTAIRE · {{ $count->count_number }}</span><h1>{{ $count->location->name }}</h1><p>{{ $count->is_opening_balance ? 'Reprise initiale' : 'Comptage physique' }} · {{ $count->scheduled_at?->format('d/m/Y à H:i') }}</p></div><div class="transfer-detail-heading-actions">@if($count->status==='completed')<a href="{{ route('stock-control.counts.print', $count) }}" target="_blank" rel="noopener" class="stock-control-hero-action"><span class="material-icons">print</span>Bon d’inventaire</a>@endif<a href="{{ route('stock-control.counts.index') }}" class="stock-control-hero-action"><span class="material-icons">arrow_back</span>Inventaires</a></div><div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">fact_check</span></div></header>
    @if(session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="stock-control-alert stock-control-alert-error"><span class="material-icons">error_outline</span><div><strong>Vérifie les informations.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <section class="stock-control-card stock-count-summary"><div><span>État</span><strong>{{ ['in_progress'=>'À compter','submitted'=>'À vérifier','completed'=>'Terminé'][$count->status] ?? $count->status }}</strong></div><div><span>Responsable du comptage</span><strong>{{ $count->counter?->name ?? '—' }}</strong></div><div><span>Créé par</span><strong>{{ $count->creator?->name ?? '—' }}</strong></div><div><span>Références incluses</span><strong>{{ $count->lines->count() }}</strong></div></section>
    @if($count->status==='in_progress' && $canCount)
        <form method="POST" action="{{ route('stock-control.counts.submit',$count) }}" class="stock-control-card stock-count-entry">@csrf
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">COMPTAGE À L’AVEUGLE</span><h2>Quantités physiques</h2></div><span class="stock-control-count">{{ $count->lines->count() }} produit(s)</span></div>
            <aside class="stock-count-counter-guide">
                <span class="material-icons" aria-hidden="true">tips_and_updates</span>
                <div><strong>Comment remplir ce comptage</strong>
                    <ol><li>Compte uniquement les unités physiquement présentes à <b>{{ $count->location->name }}</b>.</li><li>Si un produit est absent de cet emplacement, saisis <b>0</b> au lieu de laisser le champ vide.</li>
                        @if($count->is_opening_balance)<li>Il s’agit d’une reprise initiale : ne recopie pas le stock global du produit. Chaque emplacement doit recevoir son propre comptage réel.</li>@endif
                    </ol>
                    <small>La quantité théorique reste masquée pour garantir un comptage indépendant.</small>
                </div>
            </aside>
            <div class="stock-count-product-list">@foreach($count->lines as $line)<label class="stock-count-product"><span><strong>{{ $line->product->name }}</strong><small>Réf. produit {{ $line->product_id }}</small></span><input type="number" min="0" step="1" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id) }}" placeholder="0" required></label>@endforeach</div>
            <label class="stock-control-field stock-control-field-notes"><span>Note du comptage</span><textarea name="notes" rows="3" placeholder="Écarts, produits manquants ou observations">{{ old('notes') }}</textarea></label>
            <div class="stock-control-form-footer"><small>Après envoi, les quantités ne seront plus modifiables par le compteur.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">send</span>Soumettre au contrôle</button></div>
        </form>
    @else
        <section class="stock-control-card stock-count-review"><div class="stock-control-section-head"><div><span class="stock-control-kicker">CONTRÔLE</span><h2>{{ $count->status==='completed' ? 'Résultat de l’inventaire' : 'Vérification du comptage' }}</h2></div>@if($count->status==='submitted' && $canApprove && $secondCountComplete)<button type="button" class="stock-control-button stock-control-button-primary" id="openCountApproval"><span class="material-icons">task_alt</span>Approuver et enregistrer</button>@endif</div>
            <div class="stock-count-table-wrap"><table class="stock-count-table"><thead><tr><th>Produit</th><th>Attendu</th><th>1er comptage</th><th>2e comptage</th><th>Écart</th><th>Valeur indicative</th></tr></thead><tbody>@foreach($count->lines as $line)@php($delta=(float)($line->counted_quantity ?? 0)-(float)$line->expected_quantity)<tr><td data-label="Produit"><strong>{{ $line->product->name }}</strong></td><td data-label="Attendu">{{ number_format((float)$line->expected_quantity,0,',',' ') }}</td><td data-label="1er comptage">{{ $line->counted_quantity===null?'—':number_format((float)$line->counted_quantity,0,',',' ') }}</td><td data-label="2e comptage">{{ $line->second_count_quantity===null?'—':number_format((float)$line->second_count_quantity,0,',',' ') }}</td><td data-label="Écart"><span class="{{ $delta<0?'stock-count-negative':($delta>0?'stock-count-positive':'') }}">{{ $line->counted_quantity===null?'—':sprintf('%+d',(int)$delta) }}</span></td><td data-label="Valeur indicative">{{ $line->counted_quantity===null?'—':number_format(abs($delta)*(float)$line->product->price_market,0,',',' ').' F' }}</td></tr>@endforeach</tbody></table></div>
            @if($count->status==='submitted' && $requiresSecondCount && $differentLines->isNotEmpty() && $canSecondCount)
                <form method="POST" action="{{ route('stock-control.counts.second-count',$count) }}" class="stock-second-count-form">@csrf
                    <div><strong>Recompter les écarts</strong><small>Un second comptage indépendant est requis avant validation. Chaque résultat doit confirmer le premier comptage.</small></div>
                    <label class="stock-second-count-all"><input type="checkbox" data-confirm-all-recounts><span><strong>Tout est identique au premier comptage</strong><small>Confirme les quantités retrouvées pour tous les produits concernés.</small></span></label>
                    <div class="stock-second-count-lines">@foreach($differentLines as $line)<div class="stock-second-count-line"><div class="stock-second-count-product"><strong>{{ $line->product->name }}</strong><small>1er comptage : {{ number_format((float)$line->counted_quantity,0,',',' ') }}</small><label><input type="checkbox" data-confirm-recount data-counted="{{ (int)$line->counted_quantity }}"><span>Identique</span></label></div><input type="number" min="0" step="1" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id,$line->second_count_quantity) }}" placeholder="Saisir la quantité" data-recount-quantity required></div>@endforeach</div>
                    <button class="stock-control-button" type="submit"><span class="material-icons">refresh</span>Enregistrer le second comptage</button>
                </form>
            @endif
            @if($count->status==='submitted' && $requiresSecondCount && $differentLines->isNotEmpty() && !$canSecondCount)<div class="stock-count-help">Un second comptage indépendant est requis. Une autre personne que le compteur doit le saisir.</div>@endif
            @if($count->status==='submitted' && !$canApprove)<div class="stock-count-help">La validation est réservée à une autre personne que celle ayant effectué le comptage.</div>@elseif($count->status==='submitted' && !$secondCountComplete)<div class="stock-count-help">L’approbation sera disponible après confirmation de chaque écart par un second comptage.</div>@endif
            @if($count->status==='submitted' && $canApprove && $secondCountComplete)
                <dialog class="stock-approval-dialog" id="countApprovalDialog" aria-labelledby="countApprovalTitle">
                    <div class="stock-approval-dialog-head"><span class="material-icons">fact_check</span><button type="button" class="stock-approval-dialog-close" data-close-count-approval aria-label="Fermer">×</button></div>
                    <h2 id="countApprovalTitle">Approuver cet inventaire ?</h2>
                    <p>La validation enregistrera les quantités comptées et les écarts dans le journal du stock.</p>
                    @if($count->is_opening_balance)
                        <p class="stock-count-approval-note">Pour cette reprise initiale, les quantités seront affectées à l’emplacement sans être ajoutées une deuxième fois au stock global des produits.</p>
                    @endif
                    <div class="stock-approval-dialog-actions">
                        <button type="button" class="stock-control-button" data-close-count-approval>Annuler</button>
                        <form method="POST" action="{{ route('stock-control.counts.approve',$count) }}">@csrf<button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">task_alt</span>Confirmer l’approbation</button></form>
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
</div>
@endsection
