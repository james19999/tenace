@extends('layouts.admin')
@section('content')
<div class="container-fluid expense-categories-page">
    <header class="page-head">
        <div><a href="{{ route('finance.expenses.index') }}">← Demandes de dépenses</a><h1>Catégories de dépenses</h1><p>Gère ici les catégories séparément du formulaire de demande. Une catégorie créée sera disponible immédiatement.</p></div>
    </header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="categories-layout">
        @if(auth()->user()->isAdmin() || auth()->user()->isManager())
            <section class="category-panel create-panel">
                <h2>Ajouter une catégorie</h2>
                <form method="POST" action="{{ route('finance.expenses.categories.store') }}">@csrf
                    <label for="category-name">Nom de la catégorie</label>
                    <input class="form-control" id="category-name" name="name" maxlength="120" value="{{ old('name') }}" placeholder="Ex. Transport, entretien, fournitures" required>
                    <button class="btn btn-primary" type="submit">Créer la catégorie</button>
                </form>
            </section>
        @endif
        <section class="category-panel category-list">
            <form method="GET" class="search-form"><label for="category-search">Rechercher une catégorie</label><div><input class="form-control" id="category-search" type="search" name="search" value="{{ $search }}" placeholder="Saisis un nom…"><button class="btn btn-outline-primary" type="submit">Rechercher</button></div></form>
            <div class="category-count">{{ $categories->total() }} catégorie(s)</div>
            <ul>@forelse($categories as $category)<li><span class="material-icons">sell</span><span>{{ $category->name }}</span></li>@empty<li class="empty">Aucune catégorie trouvée.</li>@endforelse</ul>
            {{ $categories->links() }}
        </section>
    </div>
</div>
<style>
.expense-categories-page{max-width:1200px;padding:22px 24px 44px;color:#293342}.page-head{margin-bottom:20px}.page-head a{color:#187b2a;font-weight:750;text-decoration:none}.page-head h1{font-size:clamp(25px,2.5vw,34px);font-weight:850;margin:9px 0}.page-head p{color:#778397;margin:0;max-width:720px;line-height:1.55}.categories-layout{display:grid;grid-template-columns:minmax(260px,.75fr) minmax(0,1.5fr);gap:16px}.category-panel{background:#fff;border:1px solid #e1e5eb;padding:22px;box-shadow:0 5px 20px rgba(25,38,55,.035)}.category-panel h2{font-size:19px;font-weight:800;margin:0 0 17px}.category-panel label{display:block;font-weight:750;margin-bottom:7px}.create-panel form{display:grid;gap:12px}.create-panel .btn{justify-self:start;margin-top:4px}.search-form>div{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:9px}.category-count{font-size:12px;color:#758194;margin:17px 0 9px}.category-list ul{list-style:none;margin:0;padding:0;border-top:1px solid #edf0f3}.category-list li{min-height:48px;display:flex;align-items:center;gap:10px;padding:10px 4px;border-bottom:1px solid #edf0f3;font-weight:650}.category-list li .material-icons{color:#187b2a;font-size:19px}.category-list li.empty{color:#758194;font-weight:500}.category-list .pagination{justify-content:flex-end;margin:16px 0 0}.category-list .page-link{color:#187b2a}.category-list .page-item.active .page-link{background:#187b2a;border-color:#187b2a;color:white}@media(max-width:760px){.expense-categories-page{padding:14px 12px 30px}.categories-layout{grid-template-columns:1fr}.category-panel{padding:16px}.search-form>div{grid-template-columns:1fr}.search-form .btn{width:100%}.category-list .pagination{justify-content:center}.category-list .page-item:not(:first-child):not(:last-child):not(.active){display:none}}
</style>
@endsection
