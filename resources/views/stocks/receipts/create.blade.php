@extends('layouts.admin')

@section('content')
    <div class="stock-control-page stock-receipts-page">
        <div class="receipt-page-heading">
            <a href="{{ route('stock-control.receipts.index') }}" class="receipt-back-link"><span class="material-icons">arrow_back</span>Réceptions</a>
            <span class="stock-control-kicker">NOUVEAU DOCUMENT</span>
            <h1>Préparer une réception</h1>
            <p>Déclare ce qui est attendu. Le stock sera modifié uniquement après la saisie de la livraison et son approbation.</p>
        </div>

        @if ($errors->any())
            <div class="stock-control-alert stock-control-alert-error" role="alert"><span class="material-icons">error_outline</span><div><strong>Vérifie les informations de la réception.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif

        <form method="POST" action="{{ route('stock-control.receipts.store') }}" enctype="multipart/form-data" class="receipt-form">
            @csrf
            <section class="stock-control-card receipt-form-section">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">ÉTAPE 1</span><h2>Origine et destination</h2></div></div>
                <div class="receipt-meta-grid">
                    <label class="stock-control-field"><span>Fournisseur ou origine</span><input name="origin_name" value="{{ old('origin_name') }}" placeholder="Nom du fournisseur" maxlength="160"></label>
                    <label class="stock-control-field"><span>Référence du document source</span><input name="source_reference" value="{{ old('source_reference') }}" placeholder="Facture, bon fournisseur…" maxlength="100"></label>
                    <label class="stock-control-field"><span>Emplacement destinataire</span><select name="location_id" required><option value="">Choisir un emplacement</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->name }} · {{ $location->code }}</option>@endforeach</select></label>
                    <label class="stock-control-field"><span>Date et heure du document</span><input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required></label>
                </div>
            </section>

            <section class="stock-control-card receipt-form-section">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">ÉTAPE 2</span><h2>Produits attendus</h2></div><button type="button" class="stock-control-button" id="add-receipt-line"><span class="material-icons">add</span>Ajouter une ligne</button></div>
                <p class="receipt-help">Les informations de lot sont facultatives pour cette première saisie. Elles permettront de suivre la fabrication et les dates d’expiration.</p>
                <div id="receipt-lines" class="receipt-lines">
                    @php $oldLines = old('lines', [['product_id' => '', 'lot_code' => '', 'manufactured_at' => '', 'expires_at' => '', 'quantity_expected' => '', 'unit_cost' => '']]); @endphp
                    @foreach($oldLines as $index => $line)
                        <div class="receipt-product-line" data-line-index="{{ $index }}">
                            <div class="receipt-line-top"><span class="receipt-line-number">Référence {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span><button type="button" class="receipt-remove-line" aria-label="Supprimer cette ligne"><span class="material-icons">delete_outline</span></button></div>
                            <div class="receipt-line-grid">
                                <label class="stock-control-field receipt-product-field"><span>Produit</span><select name="lines[{{ $index }}][product_id]" required><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>@endforeach</select></label>
                                <label class="stock-control-field"><span>Quantité attendue</span><input type="number" name="lines[{{ $index }}][quantity_expected]" value="{{ $line['quantity_expected'] ?? '' }}" min="1" step="1" placeholder="0" required></label>
                                <label class="stock-control-field"><span>Coût unitaire (FCFA)</span><input type="number" name="lines[{{ $index }}][unit_cost]" value="{{ $line['unit_cost'] ?? '' }}" min="0" step="1" placeholder="Coût achat"></label>
                                <label class="stock-control-field"><span>Lot (facultatif)</span><input name="lines[{{ $index }}][lot_code]" value="{{ $line['lot_code'] ?? '' }}" maxlength="100" placeholder="N° de lot"></label>
                                <label class="stock-control-field"><span>Date de fabrication</span><input type="date" name="lines[{{ $index }}][manufactured_at]" value="{{ $line['manufactured_at'] ?? '' }}"></label>
                                <label class="stock-control-field"><span>Date d’expiration</span><input type="date" name="lines[{{ $index }}][expires_at]" value="{{ $line['expires_at'] ?? '' }}"></label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <template id="receipt-line-template">
                    <div class="receipt-product-line" data-line-index="__INDEX__">
                        <div class="receipt-line-top"><span class="receipt-line-number">Nouvelle référence</span><button type="button" class="receipt-remove-line" aria-label="Supprimer cette ligne"><span class="material-icons">delete_outline</span></button></div>
                        <div class="receipt-line-grid">
                            <label class="stock-control-field receipt-product-field"><span>Produit</span><select name="lines[__INDEX__][product_id]" required><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
                            <label class="stock-control-field"><span>Quantité attendue</span><input type="number" name="lines[__INDEX__][quantity_expected]" min="1" step="1" placeholder="0" required></label>
                            <label class="stock-control-field"><span>Coût unitaire (FCFA)</span><input type="number" name="lines[__INDEX__][unit_cost]" min="0" step="1" placeholder="Coût achat"></label>
                            <label class="stock-control-field"><span>Lot (facultatif)</span><input name="lines[__INDEX__][lot_code]" maxlength="100" placeholder="N° de lot"></label>
                            <label class="stock-control-field"><span>Date de fabrication</span><input type="date" name="lines[__INDEX__][manufactured_at]"></label>
                            <label class="stock-control-field"><span>Date d’expiration</span><input type="date" name="lines[__INDEX__][expires_at]"></label>
                        </div>
                    </div>
                </template>
            </section>

            <section class="stock-control-card receipt-form-section">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">ÉTAPE 3</span><h2>Pièces justificatives</h2></div><span class="receipt-optional-tag">Facultatif</span></div>
                <div class="receipt-final-grid">
                    <label class="stock-control-field"><span>Bon, facture ou photo</span><input type="file" name="attachments[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple><small>PDF ou image, 10 Mo maximum par fichier.</small></label>
                    <label class="stock-control-field"><span>Note interne</span><textarea name="notes" rows="3" maxlength="5000" placeholder="Informations utiles sur la commande ou l’arrivée prévue">{{ old('notes') }}</textarea></label>
                </div>
            </section>

            <div class="receipt-form-actions"><a href="{{ route('stock-control.receipts.index') }}" class="stock-control-button">Annuler</a><button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">save</span>Créer le brouillon</button></div>
        </form>
    </div>

    <script>
        (function () {
            const list = document.getElementById('receipt-lines');
            const template = document.getElementById('receipt-line-template');
            const addButton = document.getElementById('add-receipt-line');
            if (!list || !template || !addButton) return;
            let nextIndex = Math.max(-1, ...Array.from(list.querySelectorAll('[data-line-index]')).map((row) => Number(row.dataset.lineIndex))) + 1;
            const updateRows = () => {
                const rows = Array.from(list.querySelectorAll('.receipt-product-line'));
                rows.forEach((row, index) => {
                    const number = row.querySelector('.receipt-line-number');
                    if (number) number.textContent = 'Référence ' + String(index + 1).padStart(2, '0');
                    const remove = row.querySelector('.receipt-remove-line');
                    if (remove) remove.disabled = rows.length === 1;
                });
            };
            addButton.addEventListener('click', () => {
                const fragment = template.content.cloneNode(true);
                const wrapper = document.createElement('div');
                wrapper.appendChild(fragment);
                list.insertAdjacentHTML('beforeend', wrapper.innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
                updateRows();
            });
            list.addEventListener('click', (event) => {
                const button = event.target.closest('.receipt-remove-line');
                if (!button || list.querySelectorAll('.receipt-product-line').length <= 1) return;
                button.closest('.receipt-product-line').remove();
                updateRows();
            });
            updateRows();
        })();
    </script>
@endsection
