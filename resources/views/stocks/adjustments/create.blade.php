@extends('layouts.admin')

@section('content')
<div class="stock-control-page stock-adjustments-page">
    <header class="stock-control-hero"><div class="stock-control-hero-copy"><span class="stock-control-kicker">TENACOS · CORRECTION PONCTUELLE</span><h1>Nouvel ajustement</h1><p>Déclare une quantité ajoutée ou retirée. Selon les règles, la correction sera appliquée ou soumise à approbation.</p></div><a href="{{ route('stock-control.adjustments.index') }}" class="stock-control-hero-action"><span class="material-icons">arrow_back</span>Retour</a></header>
    @if($errors->any())<div class="stock-control-alert stock-control-alert-error"><span class="material-icons">error_outline</span><div><strong>Vérifie les champs indiqués.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <form method="POST" action="{{ route('stock-control.adjustments.store') }}" class="stock-control-card adjustment-form">@csrf
        <div class="stock-control-section-head"><div><span class="stock-control-kicker">DÉTAILS DE LA CORRECTION</span><h2>Stock concerné</h2></div></div>
        <div class="stock-control-form-grid">
            <label class="stock-control-field"><span>Emplacement</span><select name="location_id" required><option value="">Choisir un emplacement</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id')==$location->id)>{{ $location->name }} · {{ $location->code }}</option>@endforeach</select></label>
            <label class="stock-control-field"><span>Produit</span><select name="product_id" required><option value="">Choisir un produit</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id')==$product->id)>{{ $product->name }} · Stock global {{ $product->qt_initial }}</option>@endforeach</select></label>
            <label class="stock-control-field"><span>Variation de quantité</span><input type="number" name="quantity_delta" step="1" min="-999999999" max="999999999" value="{{ old('quantity_delta') }}" placeholder="Ex. 5 ou -2" required><small>Positif pour ajouter, négatif pour retirer.</small></label>
            <label class="stock-control-field"><span>Motif</span><select name="reason_code" required>@foreach(['found'=>'Stock retrouvé','loss'=>'Perte','damage'=>'Produit endommagé','correction'=>'Correction de saisie','other'=>'Autre'] as $value=>$label)<option value="{{ $value }}" @selected(old('reason_code')===$value)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <label class="stock-control-field stock-control-field-notes adjustment-reason"><span>Explication détaillée</span><textarea name="reason" rows="4" minlength="5" maxlength="3000" required placeholder="Explique la cause et le constat qui justifient cette correction.">{{ old('reason') }}</textarea></label>
        <div class="stock-control-form-footer"><small>Une demande en attente ne modifie pas le stock avant approbation.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">send</span>Soumettre l’ajustement</button></div>
    </form>
</div>
@endsection
