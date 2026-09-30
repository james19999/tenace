@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div class="d-flex justify-content-end mb-3">
       <a href="{{ route('costumer.create') }}" class="btn btn-sm btn-primary mr-2">Ajouter un client</a>
       <a href="{{ route('top-costumers') }}" class="btn btn-sm btn-success text-white">Top clients</a>
    </div>
   <div class="card shadow">
       <div class="card-body ">
            @if (Session::has('messages'))
            <div class="alert alert-success">
               <strong>{{ session('messages') }}</strong>
               </div>
            @endif
           <div class="table-responsive">
               <table id="customer-table" class="table table-hover w-100" data-source="{{ route('costumer.data') }}">
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
