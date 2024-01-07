@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div  style="padding-top: 10px">
       <h3>Liste des livreurs</h3>
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
                                  @if (Auth::user()->user_type=="ADMINUSER" || Auth::user()->user_type=="MNG")

                                  <a href="{{ route('active-user',$user) }}" style="color: white" type="button" class="btn  {{ $user->active==true ? 'btn-info' : 'btn-danger'}} "
                                  >
                                  <i class="material-icons f-16"></i>
                                      {{ $user->active==true? "Actif": "Inactif" }}
                                   </a>
                                  <a href="{{ route('livreurs.show',$user) }}" style="color: white" type="button" class="btn btn-success"
                                  >
                                  <i class="material-icons f-16">visibility</i>Details</a>
                                   <a  href="{{ route('livreurs.edit',$user) }}" style="color: white" type="button" class="btn btn-warning">
                                       <i class="material-icons">edit</i>
                                       Modifier</a>
                                       <form   method="POST" action="{{ route('livreurs.destroy',$user) }}"
                                       onclick="return confirm('supprimer') "
                                      >
                                           @csrf
                                            @method("DELETE")
                                          <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                           ><i class="material-icons">delete</i>Supprimer</button>
                                      </form>

                                  @else
                                  <a href="{{ route('livreurs.show',$user) }}" style="color: white" type="button" class="btn btn-success"
                                  >
                                  <i class="material-icons f-16">visibility</i>Details</a>
                                  @endif
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


@endsection
