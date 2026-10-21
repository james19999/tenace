@extends('layouts.admin')

@section('content')

<div class="settings-page">
    <div class="settings-page-heading">
        <span class="material-icons" aria-hidden="true">tune</span>
        <div><h1>Paramètres</h1><p>Gère les informations de l’entreprise, l’apparence et les accès.</p></div>
    </div>
    <nav class="settings-tabs" role="tablist" aria-label="Sections des paramètres">
        <button type="button" class="settings-tab is-active" role="tab" aria-selected="true" aria-controls="settings-company" id="tab-company" data-settings-tab="company"><span class="material-icons">business</span>Entreprise</button>
        <button type="button" class="settings-tab" role="tab" aria-selected="false" aria-controls="settings-theme" id="tab-theme" data-settings-tab="theme"><span class="material-icons">palette</span>Apparence</button>
        <button type="button" class="settings-tab" role="tab" aria-selected="false" aria-controls="settings-users" id="tab-users" data-settings-tab="users"><span class="material-icons">manage_accounts</span>Utilisateurs et rôles</button>
        <button type="button" class="settings-tab" role="tab" aria-selected="false" aria-controls="settings-appointments" id="tab-appointments" data-settings-tab="appointments"><span class="material-icons">event_available</span>Rendez-vous du DG <span id="dg-appointments-badge" class="badge badge-danger ml-1" data-appointment-counter="upcoming" data-count-url="{{ route('dg-appointments.settings.count') }}" style="{{ ($dgUpcomingAppointmentCount ?? 0) > 0 ? '' : 'display:none' }}">{{ $dgUpcomingAppointmentCount > 99 ? '99+' : $dgUpcomingAppointmentCount }}</span></button>
    </nav>

    <section class="config-tab-panel is-active" id="settings-company" role="tabpanel" aria-labelledby="tab-company" data-settings-panel="company">
<div class="col-12 ">
    <div  style="padding-top: 10px">
        <div class="card-body">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#defaultModal">
                Paramètre
            </button>

            <!-- Modal -->
            <div class="modal fade" id="defaultModal" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="defaultModalLabel">Information sur entreprise</h4>
                            <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true" class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('setting-info') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                          <input type="text" name="name" class="form-control col-12 mb-4" id="" placeholder="Nom de l'entreprise" required>
                          <input type="text" name="phone" class="form-control col-12 mb-4" id="" placeholder="Téléphone" required>
                          <input type="text" name="address" class="form-control col-12 mb-4" id="" placeholder="Adresse" >
                          <input type="text" name="email" class="form-control col-12 mb-4" id="" placeholder="E-mail" required>
                          <input type="file" name="img" class="form-control col-12 mb-4" id="" placeholder="" >
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">Valider</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>




   <div class="card shadow">
       <div class="card-body ">
            @if (Session::has('messages'))
            <div class="alert alert-success">
               <strong>{{ session('messages') }}</strong>
               </div>
            @endif
           <div class="table-responsive">
               <table id="example" class="table table-hover w-100">
                   <thead class="thead-light">
                       <tr>
                           <th style="width: 20%">Logo</th>
                           <th style="width: 20%">Nom</th>
                           <th style="width: 20%">Téléphone</th>
                           <th style="width: 20%">Adresse</th>
                           <th style="width: 20%">Email</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>

                         @foreach ($settings as $setting )
                      <tr>

                         <td style="color: black "><img src="{{ url('image/',$setting->img) }}" width="50" height="50" alt="" srcset="">  </td>
                         <td style="color: black ">{{ $setting->name }}</td>
                         <td style="color: black ">{{ $setting->phone }}</td>
                         <td style="color: black ">{{ $setting->address }}</td>
                         <td style="color: black ">{{ $setting->email }}</td>

                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">

                                 <a  href="" style="color: white" type="button" class="btn btn-warning"

                                 data-hover="tooltip" data-placement="top"
                                 data-target="#modal-edit-customers{{$setting->id }}" data-toggle="modal"
                                  id="modal-edit"
                                 >
                                     <i class="material-icons">edit</i>
                                     Modifier</a>


                                     {{--  <form   method="POST" action="{{ route('destroy-expensives',$setting) }}"
                                     onclick="return confirm('supprimer') "
                                    >
                                         @csrf
                                          @method("DELETE")
                                        <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                         ><i class="material-icons">delete</i>Supprimer</button>
                                    </form>  --}}
                             </div>
                         </td>
                      </tr>
                      <div class="modal fade" id="modal-edit-customers{{$setting->id}}" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="defaultModalLabel">Modifier les informations</h4>
                                    <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="material-icons">close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('setting-update',$setting) }}" method="POST" enctype="multipart/form-data">
                                        @method('PUT')
                                        @csrf
                                         <input type="text" name="name" value="{{ $setting->name }}" class="form-control col-12 mb-4" id="" placeholder="libelle" required>
                                         <input type="text" name="phone" value="{{ $setting->phone }}" class="form-control col-12 mb-4" id="" placeholder="Téléphone" required>
                                         <input type="text" name="address" value="{{ $setting->address }}"  class="form-control col-12 mb-4" id="" placeholder="Adresse" >
                                         <input type="text" name="email"   value="{{ $setting->email }}" class="form-control col-12 mb-4" id="" placeholder="E-mail" required>
                                         <input type="file" name="img"   value="{{ $setting->img }}" class="form-control col-12 mb-4" id="" placeholder="" >
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    <button type="submit" class="btn btn-primary">Modifier</button>
                                </div>
                            </form>
                            </div>
                        </div>
                     </div>
                         @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                    <tr>
                        <th style="width: 20%">Logo</th>
                        <th style="width: 20%">Nom</th>
                        <th style="width: 20%">Téléphone</th>
                        <th style="width: 20%">Adresse</th>
                        <th style="width: 20%">Email</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
    </section>
<section class="config-tab-panel" id="settings-theme" role="tabpanel" aria-labelledby="tab-theme" data-settings-panel="theme" hidden>
<section class="card shadow mt-4 theme-settings-card">
    <div class="card-header bg-white d-flex align-items-center">
        <span class="material-icons mr-2" style="color:{{ $appTheme['primary_color'] }};">palette</span>
        <div><h5 class="mb-0 font-weight-bold">Thème de l’application</h5><small class="text-muted">Personnalise les couleurs et l’arrondi des éléments dans les espaces connectés.</small></div>
    </div>
    <div class="card-body">
        @if(session('theme_success'))<div class="alert alert-success">{{ session('theme_success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('setting-theme') }}" id="app-theme-form">
            @csrf
            @method('PUT')
            <div class="theme-presets mb-4">
                <span class="font-weight-bold">Palettes de départ</span>
                <button type="button" class="theme-preset" data-theme-preset='{"primary_color":"#7e1615","secondary_color":"#343a40","accent_color":"#5fb49c"}'><i style="--swatch:#7e1615"></i><span>TENACOS</span></button>
                <button type="button" class="theme-preset" data-theme-preset='{"primary_color":"#1d4ed8","secondary_color":"#172554","accent_color":"#38bdf8"}'><i style="--swatch:#1d4ed8"></i><span>Bleu profond</span></button>
                <button type="button" class="theme-preset" data-theme-preset='{"primary_color":"#166534","secondary_color":"#183b2b","accent_color":"#84cc16"}'><i style="--swatch:#166534"></i><span>Vert forêt</span></button>
                <small>Choisis une palette comme point de départ, puis ajuste chaque couleur librement.</small>
            </div>
            <div class="row">
                @php
                    $themeFields = [
                        'primary_color' => ['Couleur principale', 'Boutons principaux, navigation et identité visuelle.'],
                        'secondary_color' => ['Couleur secondaire', 'Barres et éléments secondaires de l’interface.'],
                        'accent_color' => ['Couleur de validation', 'Actions positives et confirmation.'],
                        'background_color' => ['Fond de page', 'Arrière-plan général des espaces connectés.'],
                        'surface_color' => ['Fond des cartes', 'Cartes, fenêtres et panneaux.'],
                        'text_color' => ['Couleur du texte', 'Texte principal de l’application.'],
                    ];
                @endphp
                @foreach($themeFields as $field => [$label, $help])
                    <div class="col-12 col-sm-6 col-lg-4 mb-3">
                        <label class="theme-color-field" for="{{ $field }}"><span>{{ $label }}</span><small>{{ $help }}</small>
                            <span class="theme-color-input"><input type="color" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $appTheme[$field]) }}" data-theme-color="{{ str_replace('_color', '', $field) }}"><code>{{ old($field, $appTheme[$field]) }}</code></span>
                        </label>
                    </div>
                @endforeach
                <div class="col-12 col-sm-6 col-lg-4 mb-3">
                    <label for="corner-style">Style des angles</label>
                    <select class="form-control" id="corner-style" name="corner_style" data-theme-corners>
                        <option value="square" @selected(old('corner_style', $appTheme['corner_style']) === 'square')>Carré</option>
                        <option value="soft" @selected(old('corner_style', $appTheme['corner_style']) === 'soft')>Légèrement arrondi</option>
                        <option value="rounded" @selected(old('corner_style', $appTheme['corner_style']) === 'rounded')>Arrondi</option>
                    </select>
                    <small class="form-text text-muted">S’applique aux cartes, boutons et champs.</small>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 mb-3">
                    <span class="d-block font-weight-bold mb-2">Mode d’affichage</span>
                    <input type="hidden" name="appearance_mode" id="appearance-mode" value="{{ old('appearance_mode', $appTheme['appearance_mode'] ?? 'light') }}">
                    <div class="appearance-switch" role="group" aria-label="Choisir le mode d’affichage">
                        <button type="button" data-appearance-choice="light" aria-pressed="{{ old('appearance_mode', $appTheme['appearance_mode'] ?? 'light') === 'light' ? 'true' : 'false' }}"><span class="material-icons">light_mode</span>Mode clair</button>
                        <button type="button" data-appearance-choice="dark" aria-pressed="{{ old('appearance_mode', $appTheme['appearance_mode'] ?? 'light') === 'dark' ? 'true' : 'false' }}"><span class="material-icons">dark_mode</span>Mode sombre</button>
                    </div>
                    <small class="form-text text-muted">Les cartes, tableaux, menus et champs gardent un contraste lisible.</small>
                </div>
            </div>

            <div class="theme-preview" id="theme-preview" data-preview-mode="{{ old('appearance_mode', $appTheme['appearance_mode'] ?? 'light') }}" style="--preview-primary:{{ $appTheme['primary_color'] }};--preview-primary-contrast:{{ $appTheme['primary_contrast_color'] }};--preview-secondary:{{ $appTheme['secondary_color'] }};--preview-accent:{{ $appTheme['accent_color'] }};--preview-background:{{ $appTheme['background_color'] }};--preview-surface:{{ $appTheme['surface_color'] }};--preview-text:{{ $appTheme['text_color'] }};--preview-radius:{{ ['square' => '0px', 'soft' => '8px', 'rounded' => '16px'][$appTheme['corner_style']] ?? '8px' }}">
                <div class="theme-preview-sidebar"><strong>TENACOS</strong><span>Accueil</span><span class="active">Tableau de bord</span></div>
                <div class="theme-preview-content"><small>Aperçu</small><strong>Votre espace de gestion</strong><div class="theme-preview-card"><span>Chiffre d’affaires du jour</span><b>125 000 XOF</b><button type="button">Action principale</button><i>Validation réussie</i></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-3" style="gap:10px">
                <button type="button" class="btn btn-outline-secondary" id="theme-reset-defaults">Rétablir le thème TENACOS</button>
                <button type="submit" class="btn btn-primary"><span class="material-icons align-middle mr-1">save</span>Enregistrer le thème</button>
            </div>
        </form>
    </div>
</section>
    </section>
<section class="config-tab-panel" id="settings-appointments" role="tabpanel" aria-labelledby="tab-appointments" data-settings-panel="appointments" hidden>
    <nav class="nav nav-pills mt-4 mb-3" aria-label="Calendrier du DG">
        <button type="button" class="nav-link active" data-appointment-settings-tab="availability">Disponibilités</button>
        <button type="button" class="nav-link" data-appointment-settings-tab="list">Rendez-vous planifiés <span class="badge badge-light ml-1">{{ $dgUpcomingAppointmentCount }}</span></button>
    </nav>
    <div data-appointment-settings-panel="availability">
    <div class="card shadow mt-4">
        <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold"><span class="material-icons align-middle mr-2" style="color:var(--app-primary)">event_available</span>Disponibilités du DG</h5></div>
        <div class="card-body">
            @if(session('appointment_settings_success'))<div class="alert alert-success">{{ session('appointment_settings_success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            @if($dgCalendar)<p><strong>Agenda configuré pour :</strong> {{ $dgCalendar->dg->name }}</p>@else<p class="alert alert-info">En enregistrant ces horaires, ton compte administrateur sera défini comme le DG de l’agenda.</p>@endif
            <p class="text-muted">Ajoute une ou plusieurs plages par jour. Les équipes choisiront librement le début et la fin du rendez-vous dans les périodes encore disponibles.</p>
            <form method="POST" action="{{ route('dg-appointments.settings.update') }}">
                @csrf
                @php
                    $weekdays = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                @endphp
                @foreach($weekdays as $weekday => $label)
                    @php
                        $dayRanges = old("days.$weekday.ranges");
                        if ($dayRanges === null) {
                            $dayRanges = $dgAvailabilities->get($weekday, collect())->map(function ($range) {
                                return ['starts_at' => substr($range->starts_at, 0, 5), 'ends_at' => substr($range->ends_at, 0, 5)];
                            })->values()->all();
                        }
                    @endphp
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ $label }}</strong><button type="button" class="btn btn-sm btn-outline-primary" data-add-availability-range="{{ $weekday }}"><span class="material-icons align-middle" style="font-size:16px">add</span> Ajouter une plage</button></div>
                        <div class="dg-availability-ranges" data-availability-day="{{ $weekday }}">
                            @foreach($dayRanges as $rangeIndex => $range)
                                <div class="row align-items-center mb-2 dg-availability-range">
                                    <div class="col-5 col-md-4"><label class="sr-only">Début {{ $label }}</label><input class="form-control" type="time" name="days[{{ $weekday }}][ranges][{{ $rangeIndex }}][starts_at]" value="{{ $range['starts_at'] ?? '' }}"></div>
                                    <div class="col-5 col-md-4"><label class="sr-only">Fin {{ $label }}</label><input class="form-control" type="time" name="days[{{ $weekday }}][ranges][{{ $rangeIndex }}][ends_at]" value="{{ $range['ends_at'] ?? '' }}"></div>
                                    <div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-availability-range aria-label="Supprimer cette plage"><span class="material-icons" style="font-size:18px">delete</span></button></div>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Sans plage horaire, le DG est considéré indisponible ce jour.</small>
                    </div>
                @endforeach
                <template id="dg-availability-range-template"><div class="row align-items-center mb-2 dg-availability-range"><div class="col-5 col-md-4"><label class="sr-only">Début</label><input class="form-control" type="time" name="days[__DAY__][ranges][__INDEX__][starts_at]"></div><div class="col-5 col-md-4"><label class="sr-only">Fin</label><input class="form-control" type="time" name="days[__DAY__][ranges][__INDEX__][ends_at]"></div><div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-availability-range aria-label="Supprimer cette plage"><span class="material-icons" style="font-size:18px">delete</span></button></div></div></template>
                <div class="d-flex justify-content-end mt-3"><button type="submit" class="btn btn-primary"><span class="material-icons align-middle mr-1">save</span>Enregistrer les disponibilités</button></div>
            </form>
        </div>
    </div>
    </div>
    <div data-appointment-settings-panel="list" hidden>
        <nav class="nav nav-pills mb-3" aria-label="État des rendez-vous du DG">
            <button type="button" class="nav-link active" data-dg-appointment-list-tab="upcoming">À venir <span class="badge badge-light ml-1" data-appointment-counter="upcoming">{{ $dgUpcomingAppointmentCount }}</span></button>
            <button type="button" class="nav-link" data-dg-appointment-list-tab="past">Passés <span class="badge badge-light ml-1" data-appointment-counter="past">{{ $dgPastAppointmentCount }}</span></button>
        </nav>
        <div data-dg-appointment-list-panel="upcoming">
        <div class="card shadow mt-4">
            <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold">Rendez-vous à venir</h5></div>
            <div class="table-responsive"><table class="table mb-0 dg-appointment-table"><thead><tr><th>Date</th><th>Heure</th><th>Personne</th><th>Téléphone</th><th>Objet</th><th>Planifié par</th><th>État</th></tr></thead><tbody>
                @forelse($dgUpcomingAppointments as $appointment)
                    <tr data-appointment-expires-at="{{ \Carbon\Carbon::parse($appointment->appointment_date->format('Y-m-d').' '.substr($appointment->ends_at, 0, 8))->timestamp * 1000 }}"><td>{{ $appointment->appointment_date->format('d/m/Y') }}</td><td>{{ substr($appointment->starts_at, 0, 5) }}–{{ substr($appointment->ends_at, 0, 5) }}</td><td>{{ $appointment->visitor_name }}</td><td>{{ $appointment->visitor_phone }}</td><td>{{ $appointment->subject }}</td><td>{{ $appointment->requester?->name ?? '—' }}</td><td><span class="badge badge-primary" data-appointment-status>À venir</span></td></tr>
                @empty<tr data-appointment-empty><td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous planifié pour le moment.</td></tr>@endforelse
            </tbody></table></div>
        </div>
        </div>
        <div data-dg-appointment-list-panel="past" hidden>
            <div class="card shadow mt-4">
                <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold">Rendez-vous terminés</h5></div>
                <div class="table-responsive"><table class="table mb-0 dg-appointment-table"><thead><tr><th>Date</th><th>Heure</th><th>Personne</th><th>Téléphone</th><th>Objet</th><th>Planifié par</th><th>État</th></tr></thead><tbody data-appointment-history-body>
                    @forelse($dgPastAppointments as $appointment)
                        <tr><td>{{ $appointment->appointment_date->format('d/m/Y') }}</td><td>{{ substr($appointment->starts_at, 0, 5) }}–{{ substr($appointment->ends_at, 0, 5) }}</td><td>{{ $appointment->visitor_name }}</td><td>{{ $appointment->visitor_phone }}</td><td>{{ $appointment->subject }}</td><td>{{ $appointment->requester?->name ?? '—' }}</td><td><span class="badge badge-secondary">Passé</span></td></tr>
                    @empty<tr data-appointment-empty><td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous terminé.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
</section>
{{-- ============================================================ --}}
{{-- Section : Gestion des rôles utilisateurs (Admin uniquement) --}}
{{-- ============================================================ --}}
<section class="config-tab-panel" id="settings-users" role="tabpanel" aria-labelledby="tab-users" data-settings-panel="users" hidden>
<div class="card shadow mt-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 font-weight-bold text-dark">
            <span class="material-icons align-middle mr-2" style="color:var(--app-primary);">manage_accounts</span>
            Gestion des rôles utilisateurs
        </h5>
    </div>
    <div class="card-body">

        {{-- Alertes --}}
        @if(session('role_success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="material-icons align-middle mr-1">check_circle</span>
                {{ session('role_success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if(session('role_error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="material-icons align-middle mr-1">error</span>
                {{ session('role_error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        {{-- Barre de recherche instantanée (au fur et à mesure de la frappe) --}}
        <form method="GET" action="{{ route('setting') }}" id="user-search-form" class="mb-4" onsubmit="return false;">
            <div class="input-group" style="max-width:420px;">
                <input type="text"
                       id="user-search-input"
                       name="user_search"
                       value="{{ $search ?? '' }}"
                       class="form-control"
                       placeholder="Rechercher par nom ou e-mail…"
                       autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" id="user-search-btn" tabindex="-1">
                        <span class="material-icons align-middle" style="font-size:18px;">search</span>
                    </button>
                    <button type="button" id="user-search-clear" class="btn btn-outline-secondary" style="{{ $search ? 'display:inline-flex;' : 'display:none;' }}">
                        <span class="material-icons align-middle" style="font-size:18px;">close</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Conteneur dynamique AJAX du Tableau et de la Pagination --}}
        <div id="users-table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Rôle actuel</th>
                            <th style="width:220px;">Changer le rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="text-muted small">{{ $u->id }}</td>
                                <td class="font-weight-bold">{{ $u->name }}</td>
                                <td class="text-muted small">{{ $u->email }}</td>
                                <td>
                                    @php
                                        $roleLabels = [
                                            'ADMINUSER'   => ['label' => 'Admin',          'class' => 'badge-danger'],
                                            'MNG'         => ['label' => 'Manager',        'class' => 'badge-warning text-dark'],
                                            'SCR'         => ['label' => 'Secrétaire',     'class' => 'badge-info'],
                                            'CALLCENTER'  => ['label' => 'SAV',            'class' => 'badge-primary'],
                                            'VDS'         => ['label' => 'Vendeur',        'class' => 'badge-success'],
                                            'LVS'         => ['label' => 'Livreur',        'class' => 'badge-info'],
                                            'PT'          => ['label' => 'Partenaire',     'class' => 'badge-secondary'],
                                            'CSA'         => ['label' => 'Caissier',       'class' => 'badge-dark'],
                                            'COMPTABLE'   => ['label' => 'Comptable',      'class' => 'badge-primary'],
                                            'User'        => ['label' => 'Utilisateur',    'class' => 'badge-light border'],
                                        ];
                                        $badge = $roleLabels[$u->user_type] ?? ['label' => $u->user_type, 'class' => 'badge-secondary'];
                                    @endphp
                                    <span class="badge {{ $badge['class'] }} px-2 py-1">{{ $badge['label'] }}</span>
                                </td>
                                <td>
                                    @if($u->id !== Auth::id())
                                        <button type="button" class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#modal-role-{{ $u->id }}">
                                            <i class="material-icons mr-1" style="font-size:16px;vertical-align:middle;">manage_accounts</i>
                                            Changer
                                        </button>

                                        {{-- Modal Confirmation Changement de Rôle --}}
                                        <div class="modal fade" id="modal-role-{{ $u->id }}" tabindex="-1" aria-labelledby="modalRoleLabel{{ $u->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title" id="modalRoleLabel{{ $u->id }}">Modifier le rôle</h4>
                                                        <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true" class="material-icons">close</span>
                                                        </button>
                                                    </div>
                                                    <form action="{{ route('user.update-role', $u->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body text-left">
                                                            <div class="mb-3 p-3 bg-light rounded">
                                                                <div class="font-weight-bold text-dark">{{ $u->name }}</div>
                                                                <div class="text-muted small">{{ $u->email }}</div>
                                                                <div class="mt-2">
                                                                    <span class="text-muted small mr-1">Rôle actuel :</span>
                                                                    <span class="badge {{ $badge['class'] }} px-2 py-1">{{ $badge['label'] }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="form-group mb-0">
                                                                <label for="select-role-{{ $u->id }}" class="font-weight-bold text-dark">Nouveau rôle :</label>
                                                                <select name="user_type" id="select-role-{{ $u->id }}" class="form-control" required>
                                                                    @foreach($roleLabels as $val => $info)
                                                                        <option value="{{ $val }}" {{ $u->user_type === $val ? 'selected' : '' }}>
                                                                            {{ $info['label'] }} ({{ $val }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-primary">Valider la modification</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small font-italic">Votre compte</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <span class="material-icons d-block mb-2" style="font-size:2rem;">person_search</span>
                                    Aucun utilisateur trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination propre avec style Bootstrap et taille contrôlée --}}
            <style>
                #users-table-container .pagination {
                    margin-bottom: 0;
                    flex-wrap: wrap;
                }
                #users-table-container .pagination .page-item .page-link {
                    color: var(--app-primary);
                    border-color: #dee2e6;
                    padding: 6px 12px;
                    font-size: 0.9rem;
                }
                #users-table-container .pagination .page-item.active .page-link {
                    background-color: var(--app-primary);
                    border-color: var(--app-primary);
                    color: #ffffff;
                }
                #users-table-container .pagination .page-item.disabled .page-link {
                    color: #6c757d;
                }
                #users-table-container .pagination svg {
                    width: 14px !important;
                    height: 14px !important;
                    max-width: 14px !important;
                    max-height: 14px !important;
                    vertical-align: middle;
                }
            </style>

            <div class="d-flex justify-content-between align-items-center flex-wrap mt-3 pt-2 border-top">
                <div class="text-muted small mb-2 mb-md-0">
                    Affichage de <span class="font-weight-bold">{{ $users->firstItem() ?? 0 }}</span> à <span class="font-weight-bold">{{ $users->lastItem() ?? 0 }}</span> sur <span class="font-weight-bold">{{ $users->total() }}</span> utilisateur(s)
                </div>
                @if($users->hasPages())
                    <div>
                        {{ $users->appends(['user_search' => $search])->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
    </section>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('user-search-input');
        const clearBtn = document.getElementById('user-search-clear');
        const container = document.getElementById('users-table-container');
        let searchTimer = null;

        if (searchInput && container) {
            function performSearch(query) {
                if (clearBtn) {
                    clearBtn.style.display = query.trim() ? 'inline-flex' : 'none';
                }

                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    const url = new URL('{{ route("setting") }}', window.location.origin);
                    if (query.trim()) {
                        url.searchParams.set('user_search', query.trim());
                    }

                    fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('users-table-container');
                        if (newContainer) {
                            container.innerHTML = newContainer.innerHTML;
                        }
                    })
                    .catch(err => console.error('Erreur recherche utilisateurs:', err));
                }, 250);
            }

            // Recherche automatique instantanée lors de la frappe
            searchInput.addEventListener('input', function () {
                performSearch(this.value);
            });

            // Bouton vider la recherche
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    performSearch('');
                    searchInput.focus();
                });
            }

            // Navigation pagination via AJAX fluide sans rechargement de page
            container.addEventListener('click', function (e) {
                const link = e.target.closest('.pagination a');
                if (link && link.href) {
                    e.preventDefault();
                    fetch(link.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('users-table-container');
                        if (newContainer) {
                            container.innerHTML = newContainer.innerHTML;
                        }
                    })
                    .catch(err => console.error('Erreur pagination:', err));
                }
            });
        }
    });
</script>
<style>
    .theme-settings-card .theme-color-field { display:block; margin-bottom:0; }
    .theme-color-field > span:first-child { display:block; margin-bottom:3px; color:#292d38; font-weight:750; }
    .theme-color-field > small { display:block; min-height:34px; margin-bottom:8px; color:#778091; font-weight:400; }
    .theme-color-input { display:flex; align-items:center; gap:10px; min-height:44px; padding:5px 9px; border:1px solid #dce1e8; border-radius:7px; background:#fff; }
    .theme-color-input input { width:38px; height:32px; padding:0; border:0; background:transparent; cursor:pointer; }
    .theme-color-input code { color:#4d5868; font-size:13px; }
    .theme-preview { display:grid; grid-template-columns:190px minmax(0,1fr); min-height:205px; overflow:hidden; border:1px solid #dfe3e9; border-radius:var(--preview-radius); background:var(--preview-background); color:var(--preview-text); }
    .theme-preview-sidebar { display:flex; flex-direction:column; gap:8px; padding:18px 12px; background:var(--preview-secondary); color:#fff; }
    .theme-preview-sidebar strong { margin:0 0 10px 6px; letter-spacing:.08em; }
    .theme-preview-sidebar span { padding:8px; border-radius:var(--preview-radius); font-size:12px; }
    .theme-preview-sidebar .active { background:var(--preview-primary); }
    .theme-preview-content { display:flex; flex-direction:column; gap:8px; padding:18px; }
    .theme-preview-content > small { color:#778091; font-weight:700; text-transform:uppercase; letter-spacing:.1em; }
    .theme-preview-content > strong { font-size:17px; }
    .theme-preview-card { display:grid; grid-template-columns:1fr auto; gap:9px; align-items:center; max-width:520px; padding:13px; border:1px solid rgba(80,90,110,.13); border-radius:var(--preview-radius); background:var(--preview-surface); }
    .theme-preview-card span { color:#778091; font-size:12px; }
    .theme-preview-card b { color:var(--preview-text); }
    .theme-preview-card button { grid-column:1; width:max-content; padding:7px 11px; border:0; border-radius:var(--preview-radius); background:var(--preview-primary); color:var(--preview-primary-contrast); font-weight:700; }
    .theme-preview-card i { grid-column:2; grid-row:2; color:var(--preview-accent); font-size:12px; font-style:normal; font-weight:700; }
    @media(max-width:575.98px){.theme-preview{grid-template-columns:105px minmax(0,1fr)}.theme-preview-content{padding:12px}.theme-preview-card{grid-template-columns:1fr}.theme-preview-card button,.theme-preview-card i{grid-column:1;grid-row:auto}.theme-preview-sidebar{padding:14px 8px}}
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const preview = document.getElementById('theme-preview');
        const colorInputs = document.querySelectorAll('[data-theme-color]');
        const cornerSelect = document.querySelector('[data-theme-corners]');
        const appearanceInput = document.getElementById('appearance-mode');
        const radiusByStyle = { square: '0px', soft: '8px', rounded: '16px' };
        const contrastColor = (hex) => {
            const channels = hex.match(/[0-9a-f]{2}/gi)?.map(value => parseInt(value, 16) / 255) || [0.5, 0.5, 0.5];
            const linear = channels.map(value => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
            return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2] > 0.42 ? '#20242c' : '#ffffff';
        };
        colorInputs.forEach((input) => input.addEventListener('input', function () {
            const variable = input.dataset.themeColor;
            preview.style.setProperty('--preview-' + variable, input.value);
            document.documentElement.style.setProperty('--app-' + variable, input.value);
            if (variable === 'primary') {
                const contrast = contrastColor(input.value);
                preview.style.setProperty('--preview-primary-contrast', contrast);
                document.documentElement.style.setProperty('--app-primary-contrast', contrast);
                document.querySelector('meta[name="theme-color"]')?.setAttribute('content', input.value);
            }
            if (variable === 'accent') document.documentElement.style.setProperty('--app-accent-contrast', contrastColor(input.value));
            const value = input.parentElement.querySelector('code');
            if (value) value.textContent = input.value;
        }));
        cornerSelect?.addEventListener('change', () => {
            const radius = radiusByStyle[cornerSelect.value] || '8px';
            preview.style.setProperty('--preview-radius', radius);
            document.documentElement.style.setProperty('--app-radius', radius);
        });
        document.querySelectorAll('[data-appearance-choice]').forEach((button) => button.addEventListener('click', function () {
            const mode = button.dataset.appearanceChoice;
            appearanceInput.value = mode;
            preview.dataset.previewMode = mode;
            document.documentElement.dataset.appTheme = mode;
            document.querySelectorAll('[data-appearance-choice]').forEach(choice => choice.setAttribute('aria-pressed', choice === button ? 'true' : 'false'));
        }));
        document.getElementById('theme-reset-defaults')?.addEventListener('click', function () {
            const defaults = { primary_color:'#7e1615', secondary_color:'#343a40', accent_color:'#5fb49c', background_color:'#f8f9fa', surface_color:'#ffffff', text_color:'#292d38' };
            Object.entries(defaults).forEach(([name, value]) => {
                const input = document.getElementById(name);
                if (!input) return;
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            if (cornerSelect) { cornerSelect.value = 'soft'; cornerSelect.dispatchEvent(new Event('change', { bubbles: true })); }
        });
        document.querySelectorAll('[data-theme-preset]').forEach((button) => button.addEventListener('click', function () {
            const palette = JSON.parse(button.dataset.themePreset);
            Object.entries(palette).forEach(([name, value]) => {
                const input = document.getElementById(name);
                if (!input) return;
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }));
    });
</script>
<style>
    .settings-page { max-width: 1500px; margin: 0 auto; padding: 8px 14px 30px; }
    .settings-page-heading { display:flex; align-items:center; gap:14px; margin: 8px 0 22px; }
    .settings-page-heading > .material-icons { display:grid; place-items:center; width:48px; height:48px; border-radius:12px; background:var(--app-primary-soft); color:var(--app-primary); font-size:27px; }
    .settings-page-heading h1 { margin:0; color:#292d38; font-size:26px; font-weight:800; }
    .settings-page-heading p { margin:4px 0 0; color:#778091; }
    .settings-tabs { display:flex; gap:8px; overflow-x:auto; margin-bottom:20px; padding:5px; border:1px solid #e3e6eb; border-radius:12px; background:#fff; }
    .theme-presets { display:flex; align-items:center; flex-wrap:wrap; gap:8px; padding:12px; border:1px solid #e5e8ed; border-radius:9px; background:#fafbfc; }
    .theme-presets > span { margin-right:4px; color:#343a40; }
    .theme-presets > small { flex-basis:100%; color:#778091; }
    .appearance-switch { display:flex; gap:7px; padding:4px; border:1px solid #dfe3e9; border-radius:8px; background:#f6f7f9; }
    .appearance-switch button { display:flex; flex:1; align-items:center; justify-content:center; gap:6px; min-height:39px; padding:7px 9px; border:1px solid transparent; border-radius:6px; background:transparent; color:#555e6c; font-size:12px; font-weight:700; cursor:pointer; }
    .appearance-switch button[aria-pressed="true"] { border-color:var(--app-primary); background:var(--app-primary); color:var(--app-primary-contrast); }
    .appearance-switch .material-icons { font-size:17px; }
    .theme-preview[data-preview-mode="dark"] { --preview-background:#14171c !important; --preview-surface:#1e232b !important; --preview-text:#edf1f5 !important; border-color:#373f4b; }
    .theme-preview[data-preview-mode="dark"] .theme-preview-content > small,
    .theme-preview[data-preview-mode="dark"] .theme-preview-card span { color:#a8b1bd; }
    .theme-preview[data-preview-mode="dark"] .theme-preview-card { border-color:#373f4b; background:#171b21; }
    .theme-preview[data-preview-mode="dark"] .theme-preview-card b { color:#edf1f5; }
    .theme-preset { display:inline-flex; align-items:center; gap:7px; padding:7px 10px; border:1px solid #dfe3e9; border-radius:7px; background:#fff; color:#343a40; font-weight:650; cursor:pointer; }
    .theme-preset:hover { border-color:var(--app-primary-border); background:var(--app-primary-soft); }
    .theme-preset i { width:15px; height:15px; border-radius:50%; background:var(--swatch); }
    .settings-tab { display:inline-flex; align-items:center; justify-content:center; gap:8px; flex:0 0 auto; padding:11px 16px; border:0; border-radius:8px; background:transparent; color:#606a7a; font-weight:700; cursor:pointer; transition:background .18s,color .18s; }
    .settings-tab .material-icons { font-size:19px; }
    .settings-tab:hover { background:var(--app-primary-soft); color:var(--app-primary); }
    .settings-tab.is-active { background:var(--app-primary); color:var(--app-primary-contrast); }
    .config-tab-panel[hidden] { display:none !important; }
    .config-tab-panel > .col-12 { padding:0; }
    #settings-appointments .nav-pills { gap:8px; flex-wrap:wrap; }
    #settings-appointments .nav-pills .nav-link { display:inline-flex; align-items:center; gap:5px; }
    #settings-appointments .table-responsive { -webkit-overflow-scrolling:touch; }
    #settings-appointments .dg-appointment-table { min-width:760px; }
    #settings-appointments .dg-availability-range { margin-left:0; margin-right:0; }
    html[data-app-theme="dark"] .settings-page-heading h1 { color:var(--app-text) !important; }
    html[data-app-theme="dark"] .settings-page-heading p { color:var(--app-muted) !important; }
    html[data-app-theme="dark"] .settings-tabs { background:var(--app-surface) !important; border-color:var(--app-border) !important; }
    html[data-app-theme="dark"] .settings-tab { color:var(--app-muted) !important; }
    html[data-app-theme="dark"] .settings-tab:hover { background:var(--app-primary-soft) !important; color:var(--app-text) !important; }
    html[data-app-theme="dark"] .settings-tab.is-active { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
    html[data-app-theme="dark"] .settings-page .card,
    html[data-app-theme="dark"] .settings-page .card-body { color:var(--app-text); }
    html[data-app-theme="dark"] .settings-page .theme-presets,
    html[data-app-theme="dark"] .settings-page .appearance-switch { background:#171b21 !important; border-color:var(--app-border) !important; }
    html[data-app-theme="dark"] .settings-page .theme-presets > span,
    html[data-app-theme="dark"] .settings-page .theme-preset { color:var(--app-text) !important; }
    html[data-app-theme="dark"] .settings-page .theme-presets > small { color:var(--app-muted) !important; }
    html[data-app-theme="dark"] .settings-page .theme-preset { background:#252b34 !important; border-color:var(--app-border) !important; }
    html[data-app-theme="dark"] .settings-page .appearance-switch button:not([aria-pressed="true"]) { color:var(--app-muted) !important; }
    html[data-app-theme="dark"] .settings-page .dataTables_wrapper .dt-buttons .dt-button,
    html[data-app-theme="dark"] .settings-page .dataTables_wrapper button.dt-button,
    html[data-app-theme="dark"] .settings-page .dataTables_wrapper a.dt-button { background:#252b34 !important; background-image:none !important; color:var(--app-text) !important; border:1px solid var(--app-border) !important; box-shadow:none !important; }
    html[data-app-theme="dark"] .settings-page .dataTables_wrapper .dt-buttons .dt-button:hover,
    html[data-app-theme="dark"] .settings-page .dataTables_wrapper button.dt-button:hover,
    html[data-app-theme="dark"] .settings-page .dataTables_wrapper a.dt-button:hover { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; }
    @media(max-width:575.98px) { .settings-page { padding:6px 8px 22px; } .settings-page-heading { margin-bottom:15px; } .settings-page-heading h1 { font-size:22px; } .settings-tabs { gap:4px; } .settings-tab { padding:10px 12px; font-size:13px; } }
    @media(max-width:575.98px) {
        #settings-appointments .nav-pills { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; }
        #settings-appointments .nav-pills .nav-link { justify-content:center; min-width:0; padding:9px 7px; font-size:12px; line-height:1.25; text-align:center; white-space:normal; }
        #settings-appointments .card-header { padding:12px; }
        #settings-appointments .card-body { padding:13px; }
        #settings-appointments .dg-availability-range { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr) 38px; gap:7px; }
        #settings-appointments .dg-availability-range>[class*="col-"] { width:auto; max-width:none; padding:0; }
        #settings-appointments .dg-availability-range .form-control { min-width:0; padding:6px 5px; font-size:13px; }
        #settings-appointments [data-add-availability-range] { padding:5px 7px; font-size:11px; }
        #settings-appointments .dg-appointment-table { min-width:720px; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const expireAppointmentRows = () => {
            document.querySelectorAll('[data-appointment-expires-at]').forEach(row => {
                if (Number(row.dataset.appointmentExpiresAt) > Date.now()) return;
                const historyBody = document.querySelector('[data-appointment-history-body]');
                if (!historyBody) { row.remove(); return; }
                const upcomingBody = row.closest('tbody');
                upcomingBody.querySelector('[data-appointment-empty]')?.remove();
                historyBody.querySelector('[data-appointment-empty]')?.remove();
                const status = row.querySelector('[data-appointment-status]');
                if (status) { status.textContent = 'Passé'; status.classList.remove('badge-primary'); status.classList.add('badge-secondary'); }
                row.removeAttribute('data-appointment-expires-at');
                historyBody.prepend(row);
                if (!upcomingBody.querySelector('tr:not([data-appointment-empty])')) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.dataset.appointmentEmpty = '';
                    emptyRow.innerHTML = '<td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous à venir.</td>';
                    upcomingBody.appendChild(emptyRow);
                }
                if (!historyBody.querySelector('tr:not([data-appointment-empty])')) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.dataset.appointmentEmpty = '';
                    emptyRow.innerHTML = '<td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous terminé.</td>';
                    historyBody.appendChild(emptyRow);
                }
                document.querySelectorAll('[data-appointment-counter="upcoming"]').forEach(counter => {
                    const count = Math.max(0, parseInt(counter.textContent, 10) || 0) - 1;
                    counter.textContent = count > 99 ? '99+' : count;
                    if (counter.id === 'dg-appointments-badge') counter.style.display = count ? '' : 'none';
                });
                document.querySelectorAll('[data-appointment-counter="past"]').forEach(counter => {
                    const count = (parseInt(counter.textContent, 10) || 0) + 1;
                    counter.textContent = count > 99 ? '99+' : count;
                });
            });
        };
        expireAppointmentRows();
        window.setInterval(expireAppointmentRows, 15000);
        const appointmentSubtabs = [...document.querySelectorAll('[data-appointment-settings-tab]')];
        appointmentSubtabs.forEach(tab => tab.addEventListener('click', () => {
            appointmentSubtabs.forEach(item => item.classList.toggle('active', item === tab));
            document.querySelectorAll('[data-appointment-settings-panel]').forEach(panel => {
                panel.hidden = panel.dataset.appointmentSettingsPanel !== tab.dataset.appointmentSettingsTab;
            });
        }));
        const appointmentListTabs = [...document.querySelectorAll('[data-dg-appointment-list-tab]')];
        appointmentListTabs.forEach(tab => tab.addEventListener('click', () => {
            appointmentListTabs.forEach(item => item.classList.toggle('active', item === tab));
            document.querySelectorAll('[data-dg-appointment-list-panel]').forEach(panel => {
                panel.hidden = panel.dataset.dgAppointmentListPanel !== tab.dataset.dgAppointmentListTab;
            });
        }));
        const appointmentBadge = document.getElementById('dg-appointments-badge');
        if (appointmentBadge) {
            const refreshAppointmentCount = () => fetch(appointmentBadge.dataset.countUrl, {headers: {'Accept': 'application/json'}})
                .then(response => response.ok ? response.json() : null)
                .then(result => {
                    if (!result) return;
                    appointmentBadge.textContent = result.count > 99 ? '99+' : result.count;
                    appointmentBadge.style.display = result.count > 0 ? '' : 'none';
                }).catch(() => {});
            refreshAppointmentCount();
            window.setInterval(refreshAppointmentCount, 30000);
        }
        const rangeTemplate = document.getElementById('dg-availability-range-template');
        document.querySelectorAll('[data-add-availability-range]').forEach(button => {
            button.addEventListener('click', () => {
                const day = button.dataset.addAvailabilityRange;
                const container = document.querySelector(`[data-availability-day="${day}"]`);
                const index = container.querySelectorAll('.dg-availability-range').length;
                container.insertAdjacentHTML('beforeend', rangeTemplate.innerHTML.replaceAll('__DAY__', day).replaceAll('__INDEX__', index));
            });
        });
        function reindexAvailabilityRanges(container) {
            const day = container.dataset.availabilityDay;
            container.querySelectorAll('.dg-availability-range').forEach((row, index) => {
                row.querySelectorAll('input[name]').forEach(input => {
                    input.name = input.name.replace(/days\[\d+\]\[ranges\]\[\d+\]/, `days[${day}][ranges][${index}]`);
                });
            });
        }
        document.addEventListener('click', event => {
            const removeButton = event.target.closest('[data-remove-availability-range]');
            if (removeButton) {
                const container = removeButton.closest('.dg-availability-ranges');
                removeButton.closest('.dg-availability-range').remove();
                reindexAvailabilityRanges(container);
            }
        });
        const tabs = [...document.querySelectorAll('[data-settings-tab]')];
        const panels = [...document.querySelectorAll('[data-settings-panel]')];
        function activate(name, updateHash = true) {
            tabs.forEach(tab => {
                const active = tab.dataset.settingsTab === name;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
            });
            panels.forEach(panel => {
                const active = panel.dataset.settingsPanel === name;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });
            if (updateHash) history.replaceState(null, '', '#' + name);
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab.dataset.settingsTab));
            tab.addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                const step = event.key === 'ArrowRight' ? 1 : -1;
                const next = tabs[(index + step + tabs.length) % tabs.length];
                activate(next.dataset.settingsTab);
                next.focus();
            });
        });
        const initial = @json(session('settings_tab', '')) || window.location.hash.replace('#', '');
        activate(panels.some(panel => panel.dataset.settingsPanel === initial) ? initial : 'company', false);
    });
</script>
@endsection
