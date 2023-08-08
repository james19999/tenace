@extends('layouts.admin')


@section('content')


<div class="col-12 ">
    <div  style="padding-top: 10px">
        <h3>Brouillons/Commandes</h4>
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
                           <th style="width: 20%">Code</th>
                           <th style="width: 20%">Nom/Téléphone (Client)</th>
                           <th style="width: 20%">Nom/Téléphone (Partenaire)</th>
                           <th style="width: 20%">Sous total</th>
                           <th style="width: 20%">Frais</th>
                           <th style="width: 20%">Total</th>
                           <th style="width: 20%">Etat</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($orders as $order )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $order->code }}</td>
                         <td style="color: black ">{{ $order->costumer->name ?? '-' }}| {{ $order->costumer->phone ?? '-' }}</td>
                         <td style="color: black ">{{ $order->createduser->name ?? '' }}| {{ $order->createduser->phone ?? '' }}</td>
     
                         <td style="color: black ">{{ $order->subtotal }} F</td>
                         <td style="color: black ">{{ $order->tax }} F </td>
                         <td style="color: black ">{{ $order->total }} F </td>
                         <td style="color: black ">
                                @if ($order->status=="ordered")
                                <span class="badge badge-warning"> En cours</span>

                                @elseif($order->status=="delivered")
                                <span class="badge badge-success"> Terminé</span>

                                {{-- @endif --}}

                                @elseif ($order->status=="canceled")

                                <span class="badge badge-danger">Annuler</span>

                                {{-- @endif --}}

                                @endif
                          </td>
                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">
                                 <a href="{{route('unlockbrouillon',$order)}}" style="color: white" type="button" class="btn btn-primary"
                                 >
                                 <i class="material-icons f-16">check_circle</i>Valider</a>

                                 <a href="{{ route('brouillonshow',$order) }}" style="color: white" type="button" class="btn btn-success"
                                 >
                                 <i class="material-icons f-16">visibility</i>Details</a>

                                 <form   method="POST" action="{{ route('deleteorder',$order) }}"
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
                        <th style="width: 20%">Code</th>
                        <th style="width: 20%">Nom/Téléphone (Client)</th>
                        <th style="width: 20%">Nom/Téléphone (Partenaire)</th>
                        <th style="width: 20%">Sous total</th>
                        <th style="width: 20%">Frais</th>
                        <th style="width: 20%">Total</th>
                        <th style="width: 20%">Etat</th>
                        <th style="width: 20%">Actions</th>
                       </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection
