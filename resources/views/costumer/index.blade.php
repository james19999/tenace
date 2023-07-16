@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div  style="padding-top: 10px">
       <a href="{{ route('costumer.create') }}" class="btn btn-primary  pull-right">Ajouter un client</a>
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
                         @foreach ($costumers as $costumer )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $costumer->name }}</td>
                         <td style="color: black ">{{ $costumer->phone }} </td>
                         <td style="color: black ">{{ $costumer->email ?? '@' }} </td>
                         <td style="color: black ">{{ $costumer->adresse ?? '-' }} </td>
                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">
                                 <a  href="{{ route('costumer.edit',$costumer) }}" style="color: white" type="button" class="btn btn-warning">
                                     <i class="material-icons">edit</i>
                                     Modifier</a>
                                     <form   method="POST" action="{{ route('costumer.destroy',$costumer) }}"
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
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection
