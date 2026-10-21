@extends('layouts.admin')

@section('content')
<div>
    @if (session()->has('messages'))
        <div class="alert alert-success">{{ session('messages') }}</div>
    @endif

    <div class="row d-flex justify-content-center">
        <div class="col-12 col-sm-7 ">
            <div class="card shadow">
                <div class="card-header" style="background-color: var(--app-primary, #7e1615)">
                    <h4 style="text-align: center;font-weight: 900 ;color: white">

                    Ajouter un membre
                    </h4>
                </div>
                <div class="card-body">
                <form method="POST" action="{{ route('livreurs.store') }}" enctype="multipart/form-data">
                    @csrf
                        <div class="form-group">
                            <label for="name">Nom:</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Nom" value="{{ old('name') }}" required>
                            @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Email:</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone:</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Téléphone" value="{{ old('phone') }}" required>
                            @error('phone') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Adresse:</label>
                            <input type="text" class="form-control" id="adresse" name="adresse" placeholder="Adresse" value="{{ old('adresse') }}" required>
                            @error('adresse') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password">Mot de passe:</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="password-confirm">Confirmez le mot de passe:</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                        </div>

                        <div class="form-group">
                        <label for="password-confirm">Type:</label>

                        <select name="user_type" id="user_type" class="form-control" required>
                             <option value="LVS" @selected(old('user_type', 'LVS') === 'LVS')>Livreur</option>
                             <option value="VDS" @selected(old('user_type') === 'VDS')>Vendeur</option>
                             <option value="CSA" @selected(old('user_type') === 'CSA')>Caissier</option>
                             <option value="MNG" @selected(old('user_type') === 'MNG')>Manager</option>
                             <option value="SCR" @selected(old('user_type') === 'SCR')>Secrétaire</option>
                             <option value="CALLCENTER" @selected(old('user_type') === 'CALLCENTER')>SAV</option>
                             <option value="COMPTABLE" @selected(old('user_type') === 'COMPTABLE')>Comptable</option>
                             <option value="ADMINUSER" @selected(old('user_type') === 'ADMINUSER')>Administrateur</option>
                         </select>
                         @error('user_type') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group" id="country-code-group">
                            <label for="country_code">Pays du partenaire ou du livreur <span class="text-muted">(facultatif)</span></label>
                            <select name="country_code" id="country_code" class="form-control js-country-search">
                                <option value="">Choisir un pays</option>
                                @foreach ($countries as $code => $country)
                                    <option value="{{ $code }}" @selected(old('country_code') === $code)>{{ $country['label'] }}</option>
                                @endforeach
                            </select>
                            @error('country_code') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group" id="identity-card-group">
                            <label for="identity_card_photo">Photo de la carte d’identité du livreur <span class="text-muted">(facultatif)</span></label>
                            <input type="file" name="identity_card_photo" id="identity_card_photo" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                            <small class="form-text text-muted">Facultative. Si elle est ajoutée, la photo est conservée dans un espace privé.</small>
                            @error('identity_card_photo') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div id="emergency-contact-group" class="border rounded p-3 mb-3">
                            <strong class="d-block mb-2">Personne à prévenir <span class="text-muted">(facultatif)</span></strong>
                            <div class="form-group"><label for="emergency_contact_name">Nom complet</label><input class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" maxlength="160">@error('emergency_contact_name')<span class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="form-group mb-0"><label for="emergency_contact_phone">Téléphone</label><input class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" type="tel" value="{{ old('emergency_contact_phone') }}" maxlength="32">@error('emergency_contact_phone')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        </div>

                        <a  href="{{ route('useradminlist') }}" class="btn btn-danger">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
            <div class="card-footer" style="background-color: var(--app-primary, #7e1615)" >

            </div>
        </div>
    </div>

   </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const role = document.getElementById('user_type');
        const countryGroup = document.getElementById('country-code-group');
        const country = document.getElementById('country_code');
        const documentGroup = document.getElementById('identity-card-group');
        const documentInput = document.getElementById('identity_card_photo');
        const emergencyGroup = document.getElementById('emergency-contact-group');
        const emergencyName = document.getElementById('emergency_contact_name');
        const emergencyPhone = document.getElementById('emergency_contact_phone');
        const updateFields = () => {
            const countryApplicable = ['LVS', 'PT'].includes(role.value);
            const courier = role.value === 'LVS';
            countryGroup.hidden = !countryApplicable;
            country.required = false;
            documentGroup.hidden = !courier;
            documentInput.required = false;
            emergencyGroup.hidden = !courier;
            emergencyName.required = false;
            emergencyPhone.required = false;
        };
        role.addEventListener('change', updateFields);
        updateFields();
    });
</script>

@endsection
