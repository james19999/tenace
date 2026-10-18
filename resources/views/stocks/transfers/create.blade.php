@extends('layouts.admin')

@section('content')
    <div class="stock-control-page stock-transfer-page">
        <div class="receipt-page-heading"><a href="{{ route('stock-control.transfers.index') }}" class="receipt-back-link"><span class="material-icons">arrow_back</span>Transferts</a><span class="stock-control-kicker">NOUVEAU TRANSFERT</span><h1>Transférer du stock</h1><p>@if(auth()->user()->user_type === 'ADMINUSER')Pour un transfert entre entrepôt et boutique, la création par un Admin expédie directement les quantités vers TRANSIT.@else Pour un transfert entre entrepôt et boutique, la demande attend l’autorisation d’un Admin. Le stock partira ensuite vers TRANSIT.@endif À l’arrivée, un utilisateur du module pourra saisir les quantités reçues et les éventuels écarts.</p></div>
        @if($errors->any())<div class="stock-control-alert stock-control-alert-error" role="alert"><span class="material-icons">error_outline</span><div><strong>Vérifie les informations du transfert.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

        <form method="POST" action="{{ route('stock-control.transfers.store') }}" class="receipt-form">
            @csrf
            <section class="stock-control-card receipt-form-section">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">ÉTAPE 1</span><h2>Trajet et motif</h2></div></div>
                <div class="receipt-meta-grid">
                    <label class="stock-control-field"><span>Emplacement source</span><select name="source_location_id" required><option value="">Choisir le site de départ</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) old('source_location_id') === (string) $location->id)>{{ $location->name }} · {{ $location->code }}</option>@endforeach</select></label>
                    <label class="stock-control-field"><span>Emplacement destinataire</span><select name="destination_location_id" required><option value="">Choisir le site d’arrivée</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) old('destination_location_id') === (string) $location->id)>{{ $location->name }} · {{ $location->code }}</option>@endforeach</select></label>
                    <label class="stock-control-field"><span>Date souhaitée d’arrivée</span><input type="datetime-local" name="due_at" value="{{ old('due_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}"></label>
                    <label class="stock-control-field"><span>Motif du transfert</span><input name="reason" value="{{ old('reason') }}" maxlength="1000" placeholder="Réapprovisionnement de la boutique…" required></label>
                </div>
                <label class="stock-control-field stock-control-field-notes" style="padding: 0 22px 20px"><span>Note complémentaire (facultatif)</span><textarea name="notes" rows="2" maxlength="5000" placeholder="Précision utile pour l’autorisation ou la préparation">{{ old('notes') }}</textarea></label>
            </section>

            <section class="stock-control-card receipt-form-section">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">ÉTAPE 2</span><h2>Produits et quantités</h2></div><button type="button" class="stock-control-button" id="add-transfer-line"><span class="material-icons">add</span>Ajouter un produit</button></div>
                <div id="transfer-lines" class="receipt-lines">
                    @php $oldLines = old('lines', [['product_id' => '', 'quantity_requested' => '']]); @endphp
                    @foreach($oldLines as $index => $line)
                        <div class="receipt-product-line" data-transfer-line-index="{{ $index }}">
                            <div class="receipt-line-top"><span class="receipt-line-number">Produit {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span><button type="button" class="receipt-remove-line" aria-label="Supprimer cette ligne"><span class="material-icons">delete_outline</span></button></div>
                            <div class="transfer-create-line-grid">
                                <label class="stock-control-field"><span>Produit</span><select name="lines[{{ $index }}][product_id]" required><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>@endforeach</select><small class="transfer-global-stock-hint">Stock inscrit dans la source : <strong data-transfer-source-stock>Choisis l’emplacement et le produit</strong></small></label>
                                <label class="stock-control-field"><span>Quantité à déplacer</span><input type="number" name="lines[{{ $index }}][quantity_requested]" value="{{ $line['quantity_requested'] ?? '' }}" min="1" step="1" placeholder="0" required></label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <template id="transfer-line-template"><div class="receipt-product-line" data-transfer-line-index="__INDEX__"><div class="receipt-line-top"><span class="receipt-line-number">Nouveau produit</span><button type="button" class="receipt-remove-line" aria-label="Supprimer cette ligne"><span class="material-icons">delete_outline</span></button></div><div class="transfer-create-line-grid"><label class="stock-control-field"><span>Produit</span><select name="lines[__INDEX__][product_id]" required><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select><small class="transfer-global-stock-hint">Stock inscrit dans la source : <strong data-transfer-source-stock>Choisis l’emplacement et le produit</strong></small></label><label class="stock-control-field"><span>Quantité à déplacer</span><input type="number" name="lines[__INDEX__][quantity_requested]" min="1" step="1" placeholder="0" required></label></div></div></template>
            </section>

            <div class="receipt-form-actions">
                <a href="{{ route('stock-control.transfers.index') }}" class="stock-control-button">Annuler</a>
                <button type="submit" class="stock-control-button stock-control-button-primary">
                    <span class="material-icons">{{ auth()->user()->user_type === 'ADMINUSER' ? 'local_shipping' : 'send' }}</span>
                    @if(auth()->user()->user_type === 'ADMINUSER')
                        Créer et envoyer en transit
                    @else
                        Soumettre à l’Admin
                    @endif
                </button>
            </div>
        </form>
    </div>
    <script>
        (function () {
            const list = document.getElementById('transfer-lines');
            const template = document.getElementById('transfer-line-template');
            const addButton = document.getElementById('add-transfer-line');
            const sourceSelect = document.querySelector('select[name="source_location_id"]');
            const locationQuantities = @json($locationQuantities);
            if (!list || !template || !addButton) return;
            const formatQuantity = (value) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 3 }).format(Number(value || 0)) + ' unité(s)';
            const updateSourceQuantity = (row) => {
                const select = row.querySelector('select[name$="[product_id]"]');
                const output = row.querySelector('[data-transfer-source-stock]');
                if (!select || !output) return;
                if (!sourceSelect || !sourceSelect.value || !select.value) {
                    output.textContent = 'Choisis l’emplacement et le produit';
                    return;
                }
                const quantity = locationQuantities[select.value]?.[sourceSelect.value] ?? 0;
                output.textContent = formatQuantity(quantity);
            };
            let nextIndex = Math.max(-1, ...Array.from(list.querySelectorAll('[data-transfer-line-index]')).map((row) => Number(row.dataset.transferLineIndex))) + 1;
            const updateRows = () => {
                const rows = Array.from(list.querySelectorAll('.receipt-product-line'));
                rows.forEach((row, index) => {
                    row.querySelector('.receipt-line-number').textContent = 'Produit ' + String(index + 1).padStart(2, '0');
                    row.querySelector('.receipt-remove-line').disabled = rows.length === 1;
                    updateSourceQuantity(row);
                });
            };
            list.addEventListener('change', (event) => {
                if (event.target.matches('select[name$="[product_id]"]')) updateSourceQuantity(event.target.closest('.receipt-product-line'));
            });
            sourceSelect?.addEventListener('change', updateRows);
            addButton.addEventListener('click', () => {
                const wrapper = document.createElement('div');
                wrapper.appendChild(template.content.cloneNode(true));
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
