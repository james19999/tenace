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

                    Modifier un membre
                    </h4>
                </div>
                <div class="card-body">
                <form method="POST" action="{{ route('livreurs.update',$users) }}" enctype="multipart/form-data">
                    @csrf

                    @method("PUT")
                        <div class="form-group">
                            <label for="name">Nom:</label>
                            <input type="text" class="form-control" id="name"   name="name"  placeholder="Nom"  value="{{$users->name }}" >
                            @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="name">Email:</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{$users->email }}" required autocomplete="email">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone:</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Téléphone"  value="{{ $users->phone }}">
                            @error('phone') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Adresse:</label>
                            <input type="text" class="form-control" id="adresse" name="adresse" placeholder="Adresse"  value="{{$users->adresse }}">
                            @error('adresse') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        {{-- <div class="form-group">
                            <label for="password">Mot de passe:</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div> --}}
                        {{-- <div class="form-group">
                            <label for="password-confirm">Confirmez le mot de passe:</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                        </div> --}}

                        <div class="form-group">
                        <label for="password-confirm">Type:</label>

                         <select name="user_type" id="" class="form-control" required>
                             <option value="LVS" {{ $users->user_type == 'LVS' ? 'selected' : '' }}>Livreur</option>
                             <option value="PT" {{ $users->user_type == 'PT' ? 'selected' : '' }}>Partenaire</option>
                             <option value="VDS" {{ $users->user_type == 'VDS' ? 'selected' : '' }}>Vendeur</option>
                             <option value="CSA" {{ $users->user_type == 'CSA' ? 'selected' : '' }}>Caissier</option>
                             <option value="MNG" {{ $users->user_type == 'MNG' ? 'selected' : '' }}>Manager</option>
                             <option value="SCR" {{ $users->user_type == 'SCR' ? 'selected' : '' }}>Secrétaire</option>
                             <option value="CALLCENTER" {{ $users->user_type == 'CALLCENTER' ? 'selected' : '' }}>SAV</option>
                             <option value="COMPTABLE" {{ $users->user_type == 'COMPTABLE' ? 'selected' : '' }}>Comptable</option>
                             <option value="ADMINUSER" {{ $users->user_type == 'ADMINUSER' ? 'selected' : '' }}>Administrateur</option>
                         </select>
                        </div>

                        <div class="form-group" id="country-code-group">
                            <label for="country_code">Pays du partenaire ou du livreur</label>
                            <select name="country_code" id="country_code" class="form-control">
                                <option value="">Choisir un pays</option>
                                @foreach ($countries as $code => $country)
                                    <option value="{{ $code }}" @selected(old('country_code', $users->country_code) === $code)>{{ $country['label'] }}</option>
                                @endforeach
                            </select>
                            @error('country_code') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group" id="identity-card-group">
                            <label for="identity_card_photo">Ajouter une photo de la carte d’identité</label>
                            <input type="file" name="identity_card_photo" id="identity_card_photo" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                            @if ($users->courierDocuments->isNotEmpty())
                                <small class="form-text text-muted">Une pièce d’identité est déjà enregistrée. Tu peux en ajouter une nouvelle.</small>
                                <ul class="mt-2">
                                    @foreach ($users->courierDocuments as $document)
                                        <li><a href="{{ route('livreurs.documents.show', [$users, $document]) }}" target="_blank" rel="noopener">Voir la pièce enregistrée</a></li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('identity_card_photo') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div id="emergency-contact-group" class="border rounded p-3 mb-3">
                            <strong class="d-block mb-2">Personne à prévenir</strong>
                            <div class="form-group"><label for="emergency_contact_name">Nom complet</label><input class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $users->emergency_contact_name) }}" maxlength="160">@error('emergency_contact_name')<span class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="form-group mb-0"><label for="emergency_contact_phone">Téléphone</label><input class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" type="tel" value="{{ old('emergency_contact_phone', $users->emergency_contact_phone) }}" maxlength="32">@error('emergency_contact_phone')<span class="text-danger">{{ $message }}</span>@enderror</div>
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
        const role = document.querySelector('select[name="user_type"]');
        const countryGroup = document.getElementById('country-code-group');
        const country = document.getElementById('country_code');
        const documentGroup = document.getElementById('identity-card-group');
        const emergencyGroup = document.getElementById('emergency-contact-group');
        const emergencyName = document.getElementById('emergency_contact_name');
        const emergencyPhone = document.getElementById('emergency_contact_phone');
        const updateFields = () => {
            const countryRequired = ['LVS', 'PT'].includes(role.value);
            countryGroup.hidden = !countryRequired;
            country.required = countryRequired;
            documentGroup.hidden = role.value !== 'LVS';
            emergencyGroup.hidden = role.value !== 'LVS';
            emergencyName.required = role.value === 'LVS';
            emergencyPhone.required = role.value === 'LVS';
        };
        role.addEventListener('change', updateFields);
        updateFields();
    });
</script>

@endsection
