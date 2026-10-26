@extends('layouts.admin')

@section('content')

<div class="col-12 legacy-users-page">
    <div class="users-page-toolbar">
        <div><span class="users-page-eyebrow">ÉQUIPE</span><h1>Utilisateurs</h1><p>Choisis un rôle pour afficher immédiatement les comptes correspondants.</p></div>
        <a href="{{ route('livreurs.create') }}" class="btn btn-primary"><span class="material-icons" aria-hidden="true">person_add</span>Ajouter un membre</a>
    </div>
    <form action="{{ route('useradminlist') }}" method="GET" class="users-role-filter">
        <label for="user-type-filter">Afficher le rôle</label>
        <select name="user_type" id="user-type-filter" class="form-control" onchange="this.form.submit()">
            <option value="LVS" @selected($userType === 'LVS')>Livreur</option>
            <option value="PT" @selected($userType === 'PT')>Partenaire</option>
            <option value="VDS" @selected($userType === 'VDS')>Vendeur</option>
            <option value="CSA" @selected($userType === 'CSA')>Caissier</option>
            <option value="MNG" @selected($userType === 'MNG')>Manager</option>
            <option value="SCR" @selected($userType === 'SCR')>Secrétaire</option>
            <option value="CALLCENTER" @selected($userType === 'CALLCENTER')>SAV</option>
            <option value="RCM" @selected($userType === 'RCM')>Responsable commercial</option>
            <option value="ADMINUSER" @selected($userType === 'ADMINUSER')>Administrateur</option>
        </select>
        <noscript><button type="submit" class="btn btn-secondary">Afficher</button></noscript>
    </form>
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
                           <th style="width: 20%">Type</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ( $users as  $user )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{  $user->name }}</td>
                         <td style="color: black ">{{  $user->phone }} </td>
                         <td style="color: black ">{{  $user->email ?? '@' }} </td>
                         <td style="color: black ">{{  $user->adresse ?? '-' }} </td>
                         <td style="color: black ">{{  $user->user_type ?? '-' }} </td>
                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">
                                <a href="{{ route('user-show',$user) }}" style="color: white" type="button" class="btn btn-success"
                                >
                                <i class="material-icons f-16">visibility</i>Details</a>

                                 <a  href="{{ route('livreurs.edit', $user) }}" style="color: white" type="button" class="btn btn-warning">
                                     <i class="material-icons">edit</i>
                                     Modifier</a>


                                     <a  href="{{ route('set-password',$user) }}" style="color: white" type="button" class="btn btn-dark"

                                     data-hover="tooltip" data-placement="top"
                                     data-target="#modal-edit-customers{{$user->id }}" data-toggle="modal"
                                      id="modal-edit"
                                     >
                                         <i class="material-icons">password</i>
                                         Mot de passe</a>




                                     <form method="POST" action="{{ route('livreurs.destroy', $user) }}" data-user-delete-confirm data-user-name="{{ $user->name }}">
                                         @csrf
                                          @method("DELETE")
                                        <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                         ><i class="material-icons">delete</i>Supprimer</button>
                                    </form>
                             </div>
                         </td>
                      </tr>
                      <div class="modal fade" id="modal-edit-customers{{$user->id}}" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="defaultModalLabel">Modifier le mot de passe</h4>
                                    <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="material-icons">close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('set-password',$user) }}" method="POST">
                                        @method('PUT')
                                        @csrf
                                        <div class="form-group">
                                             <label for="">Nouveau mot de passe</label>
                                            <input type="text" name="password" class="form-control col-12" value="{{ old('password') }}" id="">
                                        </div>
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
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Nom</th>
                        <th style="width: 20%">Téléphone</th>
                        <th style="width: 20%">Email</th>
                        <th style="width: 20%">Adresse</th>
                        <th style="width: 20%">Type</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>

<div class="user-delete-modal" id="user-delete-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="user-delete-modal-title">
    <div class="user-delete-backdrop" data-user-delete-close></div>
    <section class="user-delete-panel" tabindex="-1">
        <span class="material-icons user-delete-icon" aria-hidden="true">person_remove</span>
        <h2 id="user-delete-modal-title">Supprimer ce compte ?</h2>
        <p id="user-delete-modal-message">Cette action supprimera le compte utilisateur.</p>
        <div class="user-delete-actions"><button type="button" class="user-delete-cancel" data-user-delete-close>Annuler</button><button type="button" class="user-delete-confirm" id="user-delete-confirm">Supprimer le compte</button></div>
    </section>
</div>
<style>
.user-delete-modal{--ud-ink:var(--app-text,#202938);--ud-muted:var(--app-muted,#748093);--ud-line:var(--app-border,#e3e8ef);position:fixed;z-index:12000;inset:0;display:none;align-items:center;justify-content:center;padding:18px}.user-delete-modal.is-open{display:flex}.user-delete-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(3px)}.user-delete-panel{position:relative;width:min(100%,430px);padding:28px;background:var(--app-surface,#fff);border:1px solid var(--ud-line);box-shadow:0 24px 80px rgba(0,0,0,.25);text-align:center}.user-delete-icon{display:grid;place-items:center;width:50px;height:50px;margin:0 auto 14px;background:#fae9ea;color:#c93642;font-size:25px}.user-delete-panel h2{margin:0;color:var(--ud-ink);font-size:20px;font-weight:800}.user-delete-panel p{margin:8px 0 22px;color:var(--ud-muted);font-size:13px;line-height:1.55}.user-delete-actions{display:flex;justify-content:center;gap:9px}.user-delete-actions button{min-height:41px;padding:9px 15px;border:1px solid var(--ud-line);font-size:12px;font-weight:750;cursor:pointer}.user-delete-cancel{background:transparent;color:var(--ud-ink)}.user-delete-confirm{display:inline-flex!important;align-items:center;justify-content:center;border-color:#c93642!important;background:#c93642!important;color:#fff!important}.user-delete-actions button:focus-visible{outline:3px solid rgba(201,54,66,.25);outline-offset:2px}html[data-app-theme="dark"] .user-delete-panel{background:var(--app-surface)}
</style>
<script>
(() => {
    const modal = document.getElementById('user-delete-modal');
    if (!modal) return;
    let pendingForm = null;
    const close = () => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); pendingForm = null; };
    document.querySelectorAll('form[data-user-delete-confirm]').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault(); pendingForm = form;
        document.getElementById('user-delete-modal-message').textContent = `Le compte de ${form.dataset.userName} sera supprimé. Son historique lié à ce compte peut être affecté.`;
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); modal.querySelector('.user-delete-cancel').focus();
    }));
    modal.querySelectorAll('[data-user-delete-close]').forEach(button => button.addEventListener('click', close));
    document.getElementById('user-delete-confirm').addEventListener('click', () => { if (!pendingForm) return; const form = pendingForm; pendingForm = null; form.submit(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal.classList.contains('is-open')) close(); });
})();
</script>

@endsection
