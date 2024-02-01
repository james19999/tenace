@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div class="row"  style="padding-top: 10px">
         <div class="col-md-4">

             <a href="{{ route('livreurs.create') }}" class="btn btn-primary" > Ajouter un membre</a>
         </div>

        <div class="col-md-4">
            <form action="{{ route('useradminlist') }}"  method="GET" >
                <select name="user_type" id="" class="form-control">

                    <option value="LVS">livreur</option>
                    <option value="PT">Partenaire</option>
                    <option value="VDS">Vendeur</option>
                    <option value="CSA">Caissier</option>
                    <option value="MNG">Manageur & Secrétaire</option>
                    <option value="ADMINUSER">Administrateur</option>
                </select>

            </div>
            <div class="col-md-4">
                <button type="submit"  class="btn btn-success" >Valider</button>
            </div>
        </form>
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




                                     <form   method="POST" action="{{ route('livreurs.destroy', $user) }}"
                                     onclick="return confirm('supprimer') "
                                    >
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


@endsection
