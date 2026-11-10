@extends('layouts.admin')
@section('content')
<div class="container-fluid finance-page"><header class="finance-page-heading"><div><a href="{{ route('finance.expenses.index') }}">← Dépenses</a><h1>Nouvelle demande de dépense</h1><p>{{ auth()->user()->isAccountant() ? 'Ta demande sera envoyée directement au DG pour décision. Si elle est autorisée, le caissier général te remettra les fonds.' : 'La demande sera contrôlée, puis les fonds seront remis par le responsable unique de la caisse générale.' }}</p></div>@if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isAccountant())<a class="btn btn-outline-primary" href="{{ route('finance.expenses.categories.index') }}">Gérer les catégories</a>@endif</header>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="finance-panel"><form method="POST" enctype="multipart/form-data" action="{{ route('finance.expenses.store') }}">@csrf<div class="finance-form-grid">
        <label>Catégorie<div class="category-search"><input class="form-control" id="expense-category-search" type="search" placeholder="Rechercher une catégorie…" autocomplete="off"><select class="form-control" id="expense-category-select" name="category_id" required><option value="">Choisir une catégorie</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select></div><small>La recherche filtre la liste ci-dessous.</small></label>
        @if(auth()->user()->isAdmin())
            <label>Compartiment à utiliser<select class="form-control" name="compartment_id" required><option value="">Choisir le compartiment</option>@foreach($compartments as $compartment)<option value="{{ $compartment->id }}" @selected(old('compartment_id')==$compartment->id)>{{ $compartment->name }} · {{ $compartment->percentage }} %</option>@endforeach</select><small>Réservé à l’administration. Les achats de marchandises et le réapprovisionnement du stock sont toujours imputés à Approvisionnement.</small></label>
        @endif
        <label>{{ $isWholesaleManager ? 'Espace grossiste concerné' : ($isShopManager ? 'Ta boutique concernée' : 'Boutique concernée') }}<select class="form-control" name="stock_location_id" {{ $isShopManager ? 'required' : '' }}>
            @if($isShopManager)
                @if($locations->count() !== 1)<option value="" disabled @selected(!old('stock_location_id'))>Choisir ta boutique</option>@endif
                @foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('stock_location_id', $locations->count() === 1 ? $locations->first()->id : '')==$location->id)>{{ $location->name }}</option>@endforeach
            @else
                <option value="">Dépense centrale (sans boutique)</option>
                @foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('stock_location_id')==$location->id)>{{ $location->name }}</option>@endforeach
            @endif
        </select><small>Ce choix sert à attribuer la dépense à une activité. Il ne choisit pas la caisse : tout paiement autorisé sortira de la caisse générale.</small></label>
        <label>Montant demandé (F CFA)<input class="form-control" type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required><small>Au-delà de 50 000 F, contrôle financier puis décision obligatoire du DG.</small></label>
        <label>Bénéficiaire<input class="form-control" name="beneficiary" value="{{ old('beneficiary') }}" maxlength="160" required></label>
        <label>Date de paiement souhaitée<input class="form-control" type="date" name="requested_payment_date" value="{{ old('requested_payment_date') }}"></label>
        <label>Budget / projet<input class="form-control" name="budget_reference" value="{{ old('budget_reference') }}" maxlength="120"></label>
        <label>Justificatif disponible<input class="form-control-file" type="file" name="justification" accept=".pdf,.jpg,.jpeg,.png"><small>PDF ou image · 10 Mo maximum</small></label>
        <label class="finance-form-wide">Si aucun justificatif n’est disponible, explique pourquoi<input class="form-control" name="justification_exception" value="{{ old('justification_exception') }}" maxlength="2000"></label>
        <label class="finance-form-wide">Motif et détails<textarea class="form-control" name="description" rows="4" maxlength="5000" required>{{ old('description') }}</textarea></label>
    </div><div class="finance-signature-note"><span class="material-icons">lock</span><span>@if(auth()->user()->isAccountant())Chaque demande créée par le comptable, quel que soit son montant, doit être validée par le DG. Après son accord, seul le responsable désigné remettra l’argent depuis la caisse générale. Tu déposeras les reçus après l’achat.@elseAprès validation, seul le responsable désigné remettra l’argent depuis la caisse générale. La personne qui a créé la demande déposera les reçus après l’achat. Une dépense supérieure à 50 000 F attendra la décision du DG après le contrôle financier.@endif</span></div><button class="btn btn-primary" type="submit">Transmettre pour validation</button></form></section>
</div><style>.finance-page{max-width:1100px;padding:18px 22px 40px}.finance-page-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:18px}.finance-page-heading h1{font-weight:800;margin:8px 0}.finance-page-heading p{color:#758194}.finance-panel{padding:24px;background:#fff;border:1px solid #e1e5eb}.finance-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.finance-form-grid label{font-weight:700;color:#465164}.finance-form-grid small{display:block;color:#758194;font-weight:400;margin-top:5px}.category-search{display:grid;gap:7px}.finance-form-wide{grid-column:1/-1}.finance-auto-compartment{display:flex;flex-direction:column;gap:5px;padding:12px 14px;border:1px solid #dfe8e1;background:#f6faf6;color:#465164}.finance-auto-compartment span{font-weight:700;color:#187b2a}.finance-auto-compartment small{color:#758194}.finance-signature-note{display:flex;gap:12px;background:#f5f8f5;padding:14px;margin:18px 0;color:#4f5b6a}.finance-signature-note .material-icons{color:#187b2a}@media(max-width:700px){.finance-page{padding:10px}.finance-page-heading{align-items:stretch;flex-direction:column}.finance-panel{padding:15px}.finance-form-grid{grid-template-columns:1fr}.finance-form-wide{grid-column:auto}}</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('expense-category-search');
    const select = document.getElementById('expense-category-select');
    if (!search || !select) return;
    const options = Array.from(select.options).map(option => ({ value: option.value, text: option.text, selected: option.selected }));
    search.addEventListener('input', function () {
        const query = search.value.trim().toLocaleLowerCase();
        const current = select.value;
        select.replaceChildren();
        options.filter(option => option.value === '' || option.text.toLocaleLowerCase().includes(query)).forEach(option => {
            const node = new Option(option.text, option.value);
            node.selected = option.value === current || (!current && option.selected);
            select.add(node);
        });
    });
});
</script>
@endsection
