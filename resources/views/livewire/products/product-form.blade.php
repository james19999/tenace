<div class="col-12 product-form-page">
    <div class="product-form-heading">
        <a href="{{ route('product') }}" class="product-form-back"><span class="material-icons">arrow_back</span> Retour au catalogue</a>
        <span class="product-form-eyebrow">GESTION DU CATALOGUE</span>
        <h1>Ajouter des produits</h1>
        <p>Renseigne les informations de vente et le stock initial pour chaque produit.</p>
    </div>

    <form wire:submit.prevent="saveProducts" class="product-form-list" novalidate>
        @foreach ($products as $index => $product)
            <section class="product-entry-card" wire:key="new-product-{{ $index }}">
                <div class="product-entry-heading">
                    <div class="product-entry-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                    <div>
                        <h2>Nouveau produit</h2>
                        <p>Informations produit et prix</p>
                    </div>
                    @if (count($products) > 1)
                        <button type="button" class="product-remove-button" wire:click="removeProduct({{ $index }})" aria-label="Retirer ce produit">
                            <span class="material-icons">delete_outline</span><span>Retirer</span>
                        </button>
                    @endif
                </div>

                <div class="product-form-fields">
                    <label class="product-field product-field-wide">
                        <span>Nom du produit <b>*</b></span>
                        <input type="text" wire:model.defer="products.{{ $index }}.name" placeholder="Ex. Crème hydratante" autocomplete="off">
                        @error('products.'.$index.'.name')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="product-field">
                        <span>Prix de vente unitaire <b>*</b></span>
                        <div class="product-input-unit"><input type="number" min="0" step="1" wire:model.defer="products.{{ $index }}.price" placeholder="0"><span>F CFA</span></div>
                        @error('products.'.$index.'.price')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>
                    <div class="product-form-section product-field-wide">
                        <span class="product-section-label"><span class="material-icons">inventory_2</span> Stock initial</span>
                    </div>
                    <label class="product-field">
                        <span>Quantité à enregistrer <b>*</b></span>
                        <input type="number" min="0" step="1" wire:model.defer="products.{{ $index }}.qt_initial" placeholder="Ex. 25">
                        @error('products.'.$index.'.qt_initial')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="product-field">
                        <span>Seuil d’alerte de stock <b>*</b></span>
                        <input type="number" min="0" step="1" wire:model.defer="products.{{ $index }}.qts_seuil" placeholder="Ex. 5">
                        @error('products.'.$index.'.qts_seuil')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="product-field">
                        <span>Montant total d’achat du lot <b>*</b></span>
                        <div class="product-input-unit"><input type="number" min="0" step="1" wire:model.defer="products.{{ $index }}.price_by" placeholder="0"><span>F CFA</span></div>
                        @error('products.'.$index.'.price_by')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="product-field">
                        <span>Coût d’achat par unité <b>*</b></span>
                        <div class="product-input-unit"><input type="number" min="0" step="1" wire:model.defer="products.{{ $index }}.price_market" placeholder="0"><span>F CFA</span></div>
                        @error('products.'.$index.'.price_market')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="product-field product-field-wide">
                        <span>Description <small>Facultatif</small></span>
                        <textarea rows="3" wire:model.defer="products.{{ $index }}.description" placeholder="Précise les informations utiles sur le produit"></textarea>
                        @error('products.'.$index.'.description')<small class="product-field-error">{{ $message }}</small>@enderror
                    </label>

                    <div class="product-field product-field-wide">
                        <label for="product-image-{{ $index }}">Image du produit <small>Facultatif · JPG, PNG ou WEBP · 5 Mo max.</small></label>
                        <input id="product-image-{{ $index }}" type="file" accept="image/jpeg,image/png,image/webp" wire:model="products.{{ $index }}.img">
                        @error('products.'.$index.'.img')<small class="product-field-error">{{ $message }}</small>@enderror
                        @if (!empty($product['img']))
                            <div class="product-image-preview"><img src="{{ $product['img']->temporaryUrl() }}" alt="Aperçu de {{ $product['name'] ?: 'ce produit' }}"></div>
                        @endif
                        <span class="product-uploading" wire:loading wire:target="products.{{ $index }}.img">Chargement de l’image…</span>
                    </div>
                </div>
            </section>
        @endforeach

        @error('products')<div class="product-form-error-summary">{{ $message }}</div>@enderror

        <div class="product-form-actions">
            <button type="button" class="product-add-row" wire:click="addProduct">
                <span class="material-icons">add_circle_outline</span> Ajouter un autre produit
            </button>
            <div class="product-form-submit-actions">
                <a href="{{ route('product') }}" class="product-cancel-button">Annuler</a>
                <button type="submit" class="product-save-button" wire:loading.attr="disabled" wire:target="saveProducts">
                    <span wire:loading.remove wire:target="saveProducts" class="material-icons">save</span>
                    <span wire:loading wire:target="saveProducts" class="spinner-border spinner-border-sm" role="status"></span>
                    Enregistrer {{ count($products) > 1 ? 'les produits' : 'le produit' }}
                </button>
            </div>
        </div>
    </form>

    <style>
        .product-form-page{--form-burgundy:var(--app-primary, #7e1615);--form-ink:#292d37;--form-muted:#7a8290;--form-line:#e4e6eb;max-width:1040px;margin:0 auto;padding:18px 12px 36px;color:var(--form-ink)}
        .product-form-heading{margin-bottom:20px}
        .product-form-back{display:inline-flex;align-items:center;gap:5px;margin-bottom:19px;color:#687080;font-size:13px;font-weight:650;text-decoration:none}
        .product-form-back:hover{color:var(--form-burgundy)}
        .product-form-back .material-icons{font-size:18px}
        .product-form-eyebrow{display:block;margin-bottom:6px;color:var(--form-burgundy);font-size:10px;font-weight:800;letter-spacing:1.4px}
        .product-form-heading h1{margin:0 0 5px;font-size:28px;font-weight:800;letter-spacing:-.5px}
        .product-form-heading p{margin:0;color:var(--form-muted);font-size:13px}
        .product-form-list{display:grid;gap:14px}
        .product-entry-card{padding:20px;border:1px solid var(--form-line);border-radius:14px;background:#fff;box-shadow:0 5px 18px rgba(29,34,44,.045)}
        .product-entry-heading{display:flex;align-items:center;gap:12px;margin-bottom:19px;padding-bottom:14px;border-bottom:1px solid #f0f1f3}
        .product-entry-number{display:grid;place-items:center;width:40px;height:40px;flex:0 0 40px;border-radius:11px;background:#f7e9e8;color:var(--form-burgundy);font-size:13px;font-weight:800}
        .product-entry-heading h2{margin:0 0 3px;font-size:16px;font-weight:800}
        .product-entry-heading p{margin:0;color:var(--form-muted);font-size:11px}
        .product-remove-button{display:inline-flex;align-items:center;gap:4px;margin-left:auto;padding:7px 9px;border:1px solid #f0d7d6;border-radius:8px;background:#fff;color:#a33430;font-size:11px;font-weight:650}
        .product-remove-button .material-icons{font-size:17px}
        .product-form-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px 18px}
        .product-field{display:block;min-width:0;margin:0;color:#444a56;font-size:12px;font-weight:700}
        .product-field>span{display:block;margin-bottom:6px}
        .product-field b{color:var(--form-burgundy)}
        .product-field>span small{margin-left:4px;color:#989eaa;font-size:10px;font-weight:500}
        .product-field input,.product-field textarea{display:block;width:100%;min-width:0;border:1px solid #dfe2e8;border-radius:9px;background:#fff;color:var(--form-ink);font-size:13px;font-weight:500;outline:none;transition:border-color .15s,box-shadow .15s}
        .product-field input{height:43px;padding:0 12px}
        .product-field textarea{padding:10px 12px;resize:vertical}
        .product-field input:focus,.product-field textarea:focus{border-color:#a96360;box-shadow:0 0 0 3px rgba(126,22,21,.08)}
        .product-field input::placeholder,.product-field textarea::placeholder{color:#aab0ba;font-weight:400}
        .product-field input[type=file]{height:auto;min-height:43px;padding:9px 10px;background:#fafafb}
        .product-image-preview{width:88px;height:88px;margin-top:10px;overflow:hidden;border:1px solid var(--form-line);border-radius:11px;background:#f7f7f8}
        .product-image-preview img{width:100%;height:100%;object-fit:cover}
        .product-uploading{display:block;margin-top:5px;color:var(--form-muted);font-size:11px}
        .product-field-wide{grid-column:1/-1}
        .product-input-unit{display:flex;align-items:center;min-width:0;border:1px solid #dfe2e8;border-radius:9px;overflow:hidden}
        .product-input-unit:focus-within{border-color:#a96360;box-shadow:0 0 0 3px rgba(126,22,21,.08)}
        .product-input-unit input{border:0;border-radius:0;box-shadow:none!important}
        .product-input-unit>span{padding:0 10px;color:#7d8491;font-size:10px;font-weight:650;white-space:nowrap}
        .product-form-section{margin-top:4px;padding-top:12px;border-top:1px solid #eff0f2}
        .product-section-label{display:inline-flex!important;align-items:center;gap:6px;margin:0!important;color:#555c69;font-size:11px;font-weight:800;letter-spacing:.4px;text-transform:uppercase}
        .product-section-label .material-icons{font-size:17px;color:var(--form-burgundy)}
        .product-field-error,.product-form-error-summary{display:block;margin-top:5px;color:#b42318;font-size:11px;font-weight:600}
        .product-form-error-summary{padding:10px 12px;border-radius:8px;background:#fff0ef}
        .product-form-actions{position:sticky;bottom:10px;z-index:5;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--form-line);border-radius:12px;background:rgba(255,255,255,.97);box-shadow:0 6px 24px rgba(31,36,46,.1);backdrop-filter:blur(8px)}
        .product-add-row,.product-cancel-button,.product-save-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 13px;border-radius:9px;font-size:12px;font-weight:750;text-decoration:none}
        .product-add-row{border:1px solid #e2c9c8;background:#fff;color:var(--form-burgundy)}
        .product-add-row .material-icons{font-size:19px}
        .product-cancel-button{border:1px solid #dfe2e8;background:#fff;color:#596170}
        .product-save-button{border:1px solid var(--form-burgundy);background:var(--form-burgundy);color:#fff}
        .product-save-button:disabled{opacity:.65}
        .product-save-button .material-icons{font-size:17px}
        .product-form-submit-actions{display:flex;align-items:center;gap:8px}
        @media(max-width:767.98px){
            .product-form-page{padding:10px 3px 24px}
            .product-form-heading{margin-bottom:15px}
            .product-form-back{margin-bottom:14px;font-size:12px}
            .product-form-heading h1{font-size:23px}
            .product-form-heading p{font-size:11px;line-height:1.5}
            .product-entry-card{padding:14px 12px;border-radius:12px}
            .product-entry-heading{margin-bottom:14px;padding-bottom:12px}
            .product-form-fields{grid-template-columns:minmax(0,1fr);gap:13px}
            .product-field-wide{grid-column:auto}
            .product-form-section{grid-column:auto}
            .product-field{font-size:11px}
            .product-field input{height:42px}
            .product-form-actions{position:static;display:grid;grid-template-columns:1fr;gap:9px;padding:10px}
            .product-add-row{width:100%}
            .product-form-submit-actions{display:grid;grid-template-columns:minmax(85px,.7fr) minmax(0,1.5fr);width:100%}
            .product-cancel-button,.product-save-button{min-width:0;padding:0 8px;font-size:11px}
        }
        @media(max-width:359.98px){.product-entry-card{padding:12px 9px}.product-form-submit-actions{grid-template-columns:1fr 1.6fr}.product-save-button{font-size:10px}}
    </style>
</div>
