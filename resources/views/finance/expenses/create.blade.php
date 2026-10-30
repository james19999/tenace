@extends('layouts.admin')
@section('content')
<div class="container-fluid finance-page"><header class="finance-page-heading"><div><a href="{{ route('finance.expenses.index') }}">← Dépenses</a><h1>Nouvelle demande de dépense</h1><p>La demande devra être autorisée avant tout décaissement.</p></div></header>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="finance-panel"><form method="POST" enctype="multipart/form-data" action="{{ route('finance.expenses.store') }}">@csrf<div class="finance-form-grid">
        <label>Catégorie<select class="form-control" name="category_id"><option value="">Choisir ou saisir une catégorie</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label>Nouvelle catégorie, si nécessaire<input class="form-control" name="category_name" value="{{ old('category_name') }}" maxlength="120"><small>Elle sera enregistrée et proposée pour les prochaines demandes.</small></label>
        <label>{{ $isShopManager ? 'Ta boutique concernée' : 'Boutique concernée' }}<select class="form-control" name="stock_location_id" {{ $isShopManager ? 'required' : '' }}>
            @if($isShopManager)
                @if($locations->count() !== 1)<option value="" disabled @selected(!old('stock_location_id'))>Choisir ta boutique</option>@endif
                @foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('stock_location_id', $locations->count() === 1 ? $locations->first()->id : '')==$location->id)>{{ $location->name }}</option>@endforeach
            @else
                <option value="">Caisse générale / dépense centrale</option>
                @foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('stock_location_id')==$location->id)>{{ $location->name }}</option>@endforeach
            @endif
        </select>@if($isShopManager)<small>Cette demande sera imputée à la caisse de ta boutique.</small>@endif</label>
        <label>Montant demandé (F CFA)<input class="form-control" type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required><small>Au-delà de 50 000 F, contrôle financier puis décision obligatoire du DG.</small></label>
        <label>Bénéficiaire<input class="form-control" name="beneficiary" value="{{ old('beneficiary') }}" maxlength="160" required></label>
        <label>Date de paiement souhaitée<input class="form-control" type="date" name="requested_payment_date" value="{{ old('requested_payment_date') }}"></label>
        <label>Budget / projet<input class="form-control" name="budget_reference" value="{{ old('budget_reference') }}" maxlength="120"></label>
        <label>Justificatif disponible<input class="form-control-file" type="file" name="justification" accept=".pdf,.jpg,.jpeg,.png"><small>PDF ou image · 10 Mo maximum</small></label>
        <label class="finance-form-wide">Si aucun justificatif n’est disponible, explique pourquoi<input class="form-control" name="justification_exception" value="{{ old('justification_exception') }}" maxlength="2000"></label>
        <label class="finance-form-wide">Motif et détails<textarea class="form-control" name="description" rows="4" maxlength="5000" required>{{ old('description') }}</textarea></label>
    </div><div class="finance-signature-note"><span class="material-icons">lock</span><span>Le demandeur ne pourra pas approuver sa propre demande. Une dépense supérieure à 50 000 F attendra la décision du DG après le contrôle financier.</span></div><button class="btn btn-primary" type="submit">Transmettre pour validation</button></form></section>
</div><style>.finance-page{max-width:1100px;padding:18px 22px 40px}.finance-page-heading{margin-bottom:18px}.finance-page-heading h1{font-weight:800;margin:8px 0}.finance-page-heading p{color:#758194}.finance-panel{padding:24px;background:#fff;border:1px solid #e1e5eb}.finance-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.finance-form-grid label{font-weight:700;color:#465164}.finance-form-grid small{display:block;color:#758194;font-weight:400;margin-top:5px}.finance-form-wide{grid-column:1/-1}.finance-signature-note{display:flex;gap:12px;background:#f5f8f5;padding:14px;margin:18px 0;color:#4f5b6a}.finance-signature-note .material-icons{color:#187b2a}@media(max-width:700px){.finance-page{padding:10px}.finance-panel{padding:15px}.finance-form-grid{grid-template-columns:1fr}.finance-form-wide{grid-column:auto}}</style>
@endsection
