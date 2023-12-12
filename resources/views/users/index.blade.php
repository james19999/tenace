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
                                 <a  href="{{ route('livreurs.edit', $user) }}" style="color: white" type="button" class="btn btn-warning">
                                     <i class="material-icons">edit</i>
                                     Modifier</a>
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
