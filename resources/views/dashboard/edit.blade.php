@extends('layouts.admin')

@section('content')
    <div class="product-edit-page">
        <div class="product-edit-heading">
            <a href="{{ route('product') }}" class="product-edit-back"><span class="material-icons">arrow_back</span> Retour au catalogue</a>
            <span class="product-edit-eyebrow">GESTION DU CATALOGUE</span>
            <h1>Modifier le produit</h1>
            <p>Met à jour les informations de <strong>{{ $product->name }}</strong>.</p>
        </div>

        <form method="POST" action="{{ route('updateproduct', $product) }}" class="product-edit-form">
            @csrf
            @method('PUT')

            <section class="product-edit-card">
                <div class="product-edit-section-heading">
                    <span class="material-icons">inventory_2</span>
                    <div><h2>Informations du produit</h2><p>Nom, description et prix de vente</p></div>
                </div>
                <div class="product-edit-fields">
                    <label class="edit-field edit-field-wide">
                        <span>Nom du produit <b>*</b></span>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="255" autocomplete="off">
                        @error('name')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="edit-field">
                        <span>Prix de vente unitaire <b>*</b></span>
                        <div class="edit-input-unit"><input type="number" name="price" min="0" step="1" value="{{ old('price', $product->price) }}" required><span>F CFA</span></div>
                        @error('price')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="edit-field">
                        <span>Prix de vente en gros</span>
                        <div class="edit-input-unit"><input type="number" name="high_price" min="0" step="1" value="{{ old('high_price', $product->high_price) }}"><span>F CFA</span></div>
                        @error('high_price')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="edit-field edit-field-wide">
                        <span>Description <small>Facultatif</small></span>
                        <textarea name="description" rows="3" maxlength="2000" placeholder="Informations utiles sur le produit">{{ old('description', $product->description) }}</textarea>
                        @error('description')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <section class="product-edit-card">
                <div class="product-edit-section-heading">
                    <span class="material-icons">payments</span>
                    <div><h2>Approvisionnement et stock</h2><p>Coûts de revient et seuil d’alerte</p></div>
                </div>
                <div class="product-edit-fields">
                    <label class="edit-field">
                        <span>Montant d’achat du lot</span>
                        <div class="edit-input-unit"><input type="number" name="price_by" min="0" step="1" value="{{ old('price_by', $product->price_by) }}"><span>F CFA</span></div>
                        @error('price_by')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="edit-field">
                        <span>Coût d’achat par unité</span>
                        <div class="edit-input-unit"><input type="number" name="price_market" min="0" step="1" value="{{ old('price_market', $product->price_market) }}"><span>F CFA</span></div>
                        @error('price_market')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="edit-field">
                        <span>Seuil d’alerte de stock</span>
                        <input type="number" name="qts_seuil" min="0" step="1" value="{{ old('qts_seuil', $product->qts_seuil) }}" placeholder="Ex. 5">
                        @error('qts_seuil')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                    <div class="edit-stock-readonly">
                        <span>Stock actuel</span>
                        <strong>{{ number_format((int) ($product->qt_initial ?? 0), 0, ',', ' ') }} unité(s)</strong>
                        <small>Pour modifier la quantité, utilise les entrées ou sorties de stock.</small>
                    </div>
                    <label class="edit-field">
                        <span>Commission</span>
                        <div class="edit-input-unit"><input type="number" name="commission_amount" min="0" step="1" value="{{ old('commission_amount', $product->commission_amount) }}"><span>F CFA</span></div>
                        @error('commission_amount')<small class="edit-field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <div class="product-edit-actions">
                <a href="{{ route('product') }}" class="edit-cancel-button">Annuler</a>
                <button type="submit" class="edit-save-button"><span class="material-icons">save</span> Enregistrer les modifications</button>
            </div>
        </form>
    </div>

    <style>
        .product-edit-page{--edit-burgundy:#7e1615;--edit-ink:#292d37;--edit-muted:#7a8290;--edit-line:#e4e6eb;max-width:1040px;margin:0 auto;padding:18px 12px 36px;color:var(--edit-ink)}
        .product-edit-heading{margin-bottom:20px}
        .product-edit-back{display:inline-flex;align-items:center;gap:5px;margin-bottom:19px;color:#687080;font-size:13px;font-weight:650;text-decoration:none}
        .product-edit-back:hover{color:var(--edit-burgundy)}
        .product-edit-back .material-icons{font-size:18px}
        .product-edit-eyebrow{display:block;margin-bottom:6px;color:var(--edit-burgundy);font-size:10px;font-weight:800;letter-spacing:1.4px}
        .product-edit-heading h1{margin:0 0 5px;font-size:28px;font-weight:800;letter-spacing:-.5px}
        .product-edit-heading p{margin:0;color:var(--edit-muted);font-size:13px}
        .product-edit-form{display:grid;gap:14px}
        .product-edit-card{padding:20px;border:1px solid var(--edit-line);border-radius:14px;background:#fff;box-shadow:0 5px 18px rgba(29,34,44,.045)}
        .product-edit-section-heading{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:13px;border-bottom:1px solid #f0f1f3}
        .product-edit-section-heading>.material-icons{display:grid;place-items:center;width:38px;height:38px;border-radius:10px;background:#f7e9e8;color:var(--edit-burgundy);font-size:20px}
        .product-edit-section-heading h2{margin:0 0 3px;font-size:15px;font-weight:800}
        .product-edit-section-heading p{margin:0;color:var(--edit-muted);font-size:11px}
        .product-edit-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px 18px}
        .edit-field{display:block;min-width:0;margin:0;color:#444a56;font-size:12px;font-weight:700}
        .edit-field>span{display:block;margin-bottom:6px}
        .edit-field b{color:var(--edit-burgundy)}
        .edit-field>span small{margin-left:4px;color:#989eaa;font-size:10px;font-weight:500}
        .edit-field input,.edit-field textarea{display:block;width:100%;min-width:0;border:1px solid #dfe2e8;border-radius:9px;background:#fff;color:var(--edit-ink);font-size:13px;font-weight:500;outline:none;transition:border-color .15s,box-shadow .15s}
        .edit-field input{height:43px;padding:0 12px}
        .edit-field textarea{padding:10px 12px;resize:vertical}
        .edit-field input:focus,.edit-field textarea:focus{border-color:#a96360;box-shadow:0 0 0 3px rgba(126,22,21,.08)}
        .edit-field-wide{grid-column:1/-1}
        .edit-input-unit{display:flex;align-items:center;min-width:0;border:1px solid #dfe2e8;border-radius:9px;overflow:hidden}
        .edit-input-unit:focus-within{border-color:#a96360;box-shadow:0 0 0 3px rgba(126,22,21,.08)}
        .edit-input-unit input{border:0;border-radius:0;box-shadow:none!important}
        .edit-input-unit>span{padding:0 10px;color:#7d8491;font-size:10px;font-weight:650;white-space:nowrap}
        .edit-field-error{display:block;margin-top:5px;color:#b42318;font-size:11px;font-weight:600}
        .edit-stock-readonly{display:flex;flex-direction:column;justify-content:center;gap:3px;min-height:66px;padding:9px 12px;border:1px solid #e8e9ed;border-radius:9px;background:#f8f8fa}
        .edit-stock-readonly span{color:var(--edit-muted);font-size:10px}
        .edit-stock-readonly strong{color:var(--edit-ink);font-size:13px}
        .edit-stock-readonly small{color:var(--edit-muted);font-size:9px;line-height:1.4}
        .product-edit-actions{position:sticky;bottom:10px;z-index:5;display:flex;justify-content:flex-end;gap:8px;padding:12px 14px;border:1px solid var(--edit-line);border-radius:12px;background:rgba(255,255,255,.97);box-shadow:0 6px 24px rgba(31,36,46,.1);backdrop-filter:blur(8px)}
        .edit-cancel-button,.edit-save-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 14px;border:1px solid #dfe2e8;border-radius:9px;background:#fff;color:#596170;font-size:12px;font-weight:750;text-decoration:none}
        .edit-save-button{border-color:var(--edit-burgundy);background:var(--edit-burgundy);color:#fff}
        .edit-save-button .material-icons{font-size:17px}
        @media(max-width:767.98px){
            .product-edit-page{padding:10px 3px 24px}
            .product-edit-heading{margin-bottom:15px}
            .product-edit-back{margin-bottom:14px;font-size:12px}
            .product-edit-heading h1{font-size:23px}
            .product-edit-heading p{font-size:11px;line-height:1.5}
            .product-edit-card{padding:14px 12px;border-radius:12px}
            .product-edit-fields{grid-template-columns:minmax(0,1fr);gap:13px}
            .edit-field-wide{grid-column:auto}
            .edit-field{font-size:11px}
            .edit-field input{height:42px}
            .product-edit-actions{position:static;display:grid;grid-template-columns:minmax(85px,.7fr) minmax(0,1.5fr);padding:10px}
            .edit-cancel-button,.edit-save-button{min-width:0;padding:0 8px;font-size:11px}
        }
        @media(max-width:359.98px){.product-edit-card{padding:12px 9px}.product-edit-actions{grid-template-columns:1fr 1.6fr}.edit-save-button{font-size:10px}}
    </style>
@endsection
