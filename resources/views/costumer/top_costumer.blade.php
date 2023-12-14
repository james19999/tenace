@extends('layouts.admin')

@section('content')
<a href="{{ route('costumer.index') }}">Retour</a>
<div class="col-12 ">

    <div  style="padding-top: 10px">
         <strong>
            Classement des meilleurs clients du mois.
         </strong>
      <div class="col-md-7 mt-4">
             <form action="{{ route('top-costumers') }}">
                 <div class="row">
                    <div class="col-md-4">
                         <label for="">Nombre de page

                             <input type="number" name="limit" value="{{ old('limit',10) }}" id="" class="form-control" placeholder="Entrer">
                         </label>

                    </div>
                    <div class="col-md-4">
                        <label for="">Sélectionnez le mois

                            <select name="month" id="" class="form-control">
                                @foreach($montharray as $key => $value)
                                    <option value="{{ $key }}" class="form-control"

                                    >{{ $value }}</option>
                                @endforeach
                            </select>

                        </label>

                    </div>
                    <div class="col-md-4 mt-4">

                        <input type="submit" style="color: white" class="btn btn-success" >
                    </div>
                 </div>
             </form>

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
                           <th style="width: 20%">N°</th>
                           <th style="width: 20%">Nom</th>
                           <th style="width: 20%">Téléphone</th>
                           <th style="width: 20%">Email</th>
                           <th style="width: 20%">Adresse</th>
                           <th style="width: 20%">Nombre /montant</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($costumers as $costumer )
                         @if ($loop->first)
                         @endif
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $costumer->name }}</td>
                         <td style="color: black ">{{ $costumer->phone }} </td>
                         <td style="color: black ">{{ $costumer->email ?? '@' }} </td>
                         <td style="color: black ">{{ $costumer->adresse ?? '-' }} </td>
                         <td style="color: black ">
                            @if ($loop->first)
                            <span @style([
                                'color: #7e1615',
                                'font-weight: bold',
                            ])>
                            {{ $costumer->orders_count }} Commandes /  Montant total : {{ $costumer->orders_sum_total }} </td>

                        </span>
                        @else
                        {{ $costumer->orders_count }} Commandes / Montant total : {{ $costumer->orders_sum_total }} </td>

                        @endif



                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">
                                 <a  href="{{ route('view-costumers',$costumer) }}" style="color: white" type="button" class="btn btn-success">
                                     <i class="material-icons">visibility</i>
                                     Voir</a>

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
                        <th style="width: 20%">Nombre /montant</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection
