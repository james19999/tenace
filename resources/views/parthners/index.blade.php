@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <style>
        .partner-create-disclosure > summary { display:flex; align-items:center; justify-content:flex-start; gap:10px; list-style:none; cursor:pointer; }
        .partner-create-disclosure > summary::-webkit-details-marker { display:none; }
        .partner-create-disclosure > summary::before { content:'+'; display:inline-grid; place-items:center; width:30px; height:30px; flex:0 0 30px; border-radius:50%; background:var(--app-primary,#7e1615); color:#fff; font-size:21px; line-height:1; }
        .partner-create-disclosure[open] > summary::before { content:'−'; }
        .partner-create-disclosure .partner-create-summary { font-weight:700; }
        .partner-delete-dialog { width:min(440px,calc(100vw - 28px)); padding:24px; border:1px solid #e3e8ef; background:#fff; color:#252a34; box-shadow:0 24px 80px rgba(0,0,0,.28); }
        .partner-delete-dialog::backdrop { background:rgba(15,23,42,.62); backdrop-filter:blur(3px); }
        .partner-delete-dialog h4 { margin:0 0 8px; font-weight:800; }
        .partner-delete-dialog p { margin:0 0 22px; color:#687386; }
        .partner-delete-dialog-actions { display:flex; justify-content:flex-end; gap:10px; }
        .partner-delete-dialog-actions button { min-height:42px; padding:8px 15px; border:1px solid #d8dce5; background:#fff; color:#252a34; font-weight:700; cursor:pointer; }
        .partner-delete-dialog-actions .confirm { border-color:#b93636; background:#b93636; color:#fff; }
        @media(max-width:480px) { .partner-delete-dialog { padding:20px 16px; } .partner-delete-dialog-actions { flex-direction:column-reverse; } .partner-delete-dialog-actions button { width:100%; } }
    </style>
    <div  style="padding-top: 10px">
       <h3>Liste des Partenaires</h3>
    </div>
   @if (auth()->user()?->user_type === \App\Models\User::ROLE_ADMIN)
   <details class="card shadow mb-4 partner-create-disclosure" @if ($errors->any()) open @endif>
       <summary class="card-header"><span class="partner-create-summary">Créer un nouveau partenaire</span></summary>
       <div class="card-body">
           @if ($errors->any())
               <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
           @endif
           <form method="POST" action="{{ route('parthners.store', [], false) }}">
               @csrf
               <div class="form-row">
                   <div class="form-group col-md-6"><label for="partner-name">Nom complet</label><input id="partner-name" class="form-control" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></div>
                   <div class="form-group col-md-6"><label for="partner-email">E-mail</label><input id="partner-email" class="form-control" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></div>
                   <div class="form-group col-md-6"><label for="partner-phone">Téléphone</label><input id="partner-phone" class="form-control" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="32" autocomplete="tel"></div>
                   <div class="form-group col-md-6"><label for="partner-country">Pays du partenaire</label><select id="partner-country" class="form-control js-country-search" name="country_code" required><option value="">Choisir un pays</option>@foreach ($countries as $code => $country)<option value="{{ $code }}" @selected(old('country_code') === $code)>{{ $country['label'] }}</option>@endforeach</select></div>
                   <div class="form-group col-12"><label for="partner-address">Adresse</label><input id="partner-address" class="form-control" name="adresse" value="{{ old('adresse') }}" required maxlength="255" autocomplete="street-address"></div>
                   <div class="form-group col-md-6"><label for="partner-password">Mot de passe</label><input id="partner-password" class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
                   <div class="form-group col-md-6"><label for="partner-password-confirmation">Confirmer le mot de passe</label><input id="partner-password-confirmation" class="form-control" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
               </div>
               <button class="btn btn-danger" type="submit">Créer le compte partenaire</button>
           </form>
       </div>
   </details>
   @endif
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
                           <th style="width: 20%">N°</th>
                           <th style="width: 20%">Nom</th>
                           <th style="width: 20%">Téléphone</th>
                           <th style="width: 20%">Email</th>
                           <th style="width: 20%">Adresse</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($users as $user )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $user->name }}</td>
                         <td style="color: black ">{{ $user->phone }} </td>
                         <td style="color: black ">{{ $user->email ?? '@' }} </td>
                         <td style="color: black ">{{ $user->adresse ?? '-' }} </td>
                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">
                                 <a  href="{{ route('livreurs.edit',$user) }}" style="color: white" type="button" class="btn btn-warning">
                                     <i class="material-icons">edit</i>
                                     Modifier</a>
                                     <form method="POST" action="{{ route('livreurs.destroy',$user) }}" data-partner-delete-form data-partner-name="{{ $user->name }}">
                                         @csrf
                                          @method("DELETE")
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="material-icons">delete</i>Supprimer</button>
                                    </form>
                             </div>
                         </td>
                      </tr>
                         @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                    <tr>
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Nom</th>
                        <th style="width: 20%">Téléphone</th>
                        <th style="width: 20%">Email</th>
                        <th style="width: 20%">Adresse</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>

<dialog class="partner-delete-dialog" id="partnerDeleteDialog" aria-labelledby="partnerDeleteTitle" aria-describedby="partnerDeleteMessage">
    <h4 id="partnerDeleteTitle">Supprimer ce partenaire ?</h4>
    <p id="partnerDeleteMessage"></p>
    <div class="partner-delete-dialog-actions">
        <button type="button" data-partner-delete-cancel>Annuler</button>
        <button type="button" class="confirm" data-partner-delete-confirm>Supprimer le partenaire</button>
    </div>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dialog = document.getElementById('partnerDeleteDialog');
        const message = document.getElementById('partnerDeleteMessage');
        const cancelButton = dialog?.querySelector('[data-partner-delete-cancel]');
        const confirmButton = dialog?.querySelector('[data-partner-delete-confirm]');
        let pendingForm = null;

        document.querySelectorAll('[data-partner-delete-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === 'true') return;
                event.preventDefault();
                pendingForm = form;
                message.textContent = `Le compte de ${form.dataset.partnerName} sera supprimé.`;
                dialog.showModal();
            });
        });

        cancelButton?.addEventListener('click', () => dialog.close());
        dialog?.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
        dialog?.addEventListener('close', () => { pendingForm = null; });
        confirmButton?.addEventListener('click', () => {
            const form = pendingForm;
            if (!form) return;
            form.dataset.confirmed = 'true';
            dialog.close();
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        });
    });
</script>


@endsection
