@extends('layouts.admin')
@section('content')
<main class="account-settings-page">
    <header class="account-settings-heading">
        <span class="material-icons">manage_accounts</span>
        <div><span class="account-settings-kicker">MON COMPTE</span><h1>Paramètres du compte</h1><p>Consulte et mets à jour les informations de ton propre compte.</p></div>
    </header>

    @if(session('profile_saved'))<div class="alert alert-success">{{ session('profile_saved') }}</div>@endif
    @if(session('password_saved'))<div class="alert alert-success">{{ session('password_saved') }}</div>@endif

    <section class="account-settings-summary">
        <span class="material-icons">badge</span>
        <div><strong>{{ $user->name }}</strong><span>{{ $roleLabel }}</span></div>
        @if($user->primaryStockLocation)<small>Boutique : {{ $user->primaryStockLocation->name }}</small>@endif
    </section>

    <div class="account-settings-grid">
        <section class="account-settings-card">
            <div class="account-settings-card-heading"><span class="material-icons">person</span><div><h2>Mes informations</h2><p>Ces changements concernent uniquement ton compte.</p></div></div>
            @if($errors->any() && old('_form') === 'profile')<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('account.settings.profile.update') }}" class="account-settings-form">
                @csrf @method('PUT')<input type="hidden" name="_form" value="profile">
                <label>Nom complet<input class="form-control" name="name" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required></label>
                <label>Adresse e-mail<input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required></label>
                <label>Téléphone<input class="form-control" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="50" autocomplete="tel"></label>
                <label>Adresse personnelle<input class="form-control" name="adresse" value="{{ old('adresse', $user->adresse) }}" maxlength="255" autocomplete="street-address"></label>
                <label>Nom du contact d’urgence<input class="form-control" name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}" maxlength="255"></label>
                <label>Téléphone du contact d’urgence<input class="form-control" type="tel" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}" maxlength="32"></label>
                <button class="btn btn-primary" type="submit">Enregistrer mes informations</button>
            </form>
        </section>

        <section class="account-settings-card">
            <div class="account-settings-card-heading"><span class="material-icons">lock</span><div><h2>Changer mon mot de passe</h2><p>Confirme ton mot de passe actuel pour en choisir un nouveau.</p></div></div>
            @if($errors->any() && old('_form') === 'password')<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('account.settings.password.update') }}" class="account-settings-form">
                @csrf @method('PUT')<input type="hidden" name="_form" value="password">
                <label>Mot de passe actuel<input class="form-control" type="password" name="current_password" autocomplete="current-password" required></label>
                <label>Nouveau mot de passe<input class="form-control" type="password" name="password" autocomplete="new-password" minlength="8" required><small>8 caractères minimum.</small></label>
                <label>Confirmer le nouveau mot de passe<input class="form-control" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
                <button class="btn btn-primary" type="submit">Modifier mon mot de passe</button>
            </form>
        </section>
    </div>
</main>
<style>
.account-settings-page{width:100%;max-width:1250px;margin:0 auto;padding:22px 24px 45px;color:var(--app-text,#273142)}
.account-settings-heading{display:flex;align-items:center;gap:14px;padding:22px 24px;margin-bottom:14px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}
.account-settings-heading>.material-icons{display:grid;place-items:center;flex:none;width:48px;height:48px;background:var(--app-primary-soft,#f7eeee);color:var(--app-primary,#811515);font-size:27px}
.account-settings-kicker{color:var(--app-primary,#811515);font-size:10px;font-weight:850;letter-spacing:.1em}.account-settings-heading h1{margin:3px 0;font-size:clamp(25px,2.5vw,33px);font-weight:850}.account-settings-heading p{margin:0;color:var(--app-muted,#778397)}
.account-settings-summary{display:flex;align-items:center;gap:12px;padding:14px 18px;margin-bottom:14px;background:var(--app-primary-soft,#f8f5f5);border:1px solid var(--app-border,#e2e7eb)}
.account-settings-summary>.material-icons{color:var(--app-primary,#811515);font-size:22px}.account-settings-summary>div{display:grid;gap:3px;min-width:0}.account-settings-summary strong{font-size:14px}.account-settings-summary>div span,.account-settings-summary>small{color:var(--app-muted,#778397);font-size:12px}.account-settings-summary>small{margin-left:auto;text-align:right}
.account-settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:start}.account-settings-card{min-width:0;padding:20px 22px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}
.account-settings-card-heading{display:flex;gap:10px;align-items:flex-start;margin-bottom:17px}.account-settings-card-heading>.material-icons{flex:none;color:var(--app-primary,#811515);font-size:22px}.account-settings-card-heading h2{margin:0 0 3px;font-size:18px;font-weight:820}.account-settings-card-heading p{margin:0;color:var(--app-muted,#778397);font-size:12px;line-height:1.5}
.account-settings-form{display:grid;gap:13px}.account-settings-form label{display:grid;gap:6px;margin:0;font-size:12px;font-weight:750}.account-settings-form .form-control{height:43px;min-width:0}.account-settings-form label small{color:var(--app-muted,#778397);font-weight:450}.account-settings-form button{justify-self:start;min-height:43px;margin-top:3px;padding:10px 15px;font-size:12px;font-weight:750}
@media(max-width:850px){.account-settings-grid{grid-template-columns:1fr}}@media(max-width:600px){.account-settings-page{padding:12px}.account-settings-heading,.account-settings-card{padding:16px}.account-settings-summary{align-items:flex-start;padding:13px}.account-settings-summary>small{max-width:42%;overflow-wrap:anywhere}.account-settings-form button{width:100%}}
</style>
@endsection
