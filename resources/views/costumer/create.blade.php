@extends('layouts.admin')

@section('content')
<div class="customer-create-page">
    <div class="customer-create-heading">
        <div><span class="customer-create-eyebrow">GESTION CLIENTÈLE</span><h1>Ajouter un client</h1><p>Renseigne les coordonnées pour créer une nouvelle fiche client.</p></div>
        <a href="{{ route('costumer.index') }}" class="customer-create-back"><span class="material-icons" aria-hidden="true">arrow_back</span>Retour aux clients</a>
    </div>

    @if (session()->has('messages'))
        <div class="alert alert-success">{{ session('messages') }}</div>
    @endif

    <section class="customer-create-card">
        <div class="customer-create-card-heading"><span class="material-icons" aria-hidden="true">person_add</span><div><h2>Informations du client</h2><p>Les champs marqués d’un astérisque sont nécessaires à la création.</p></div></div>
        <form method="POST" action="{{ route('costumer.store') }}">
            @csrf
            <div class="customer-create-fields">
                <div class="customer-create-field">
                    <label for="customer-name">Nom complet <span>*</span></label>
                    <input type="text" id="customer-name" name="name" value="{{ old('name') }}" placeholder="Ex. Ama Kossi" autocomplete="name" required>
                    @error('name')<small class="customer-create-error">{{ $message }}</small>@enderror
                </div>
                <div class="customer-create-field">
                    <label for="customer-phone">Téléphone <span>*</span></label>
                    <input type="tel" id="customer-phone" name="phone" value="{{ old('phone') }}" placeholder="Ex. 90 00 00 00" autocomplete="tel" required>
                    @error('phone')<small class="customer-create-error">{{ $message }}</small>@enderror
                </div>
                <div class="customer-create-field customer-create-field-wide">
                    <label for="customer-address">Adresse de livraison <span>*</span></label>
                    <textarea id="customer-address" name="adresse" rows="3" placeholder="Quartier, rue, repère…" autocomplete="street-address" required>{{ old('adresse') }}</textarea>
                    @error('adresse')<small class="customer-create-error">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="customer-create-form-footer"><p><span>*</span> Champs requis</p><div><a href="{{ route('costumer.index') }}" class="customer-create-cancel">Annuler</a><button type="submit" class="customer-create-submit"><span class="material-icons" aria-hidden="true">save</span>Enregistrer le client</button></div></div>
        </form>
    </section>
</div>
@endsection
