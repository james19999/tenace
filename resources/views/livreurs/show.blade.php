@extends('layouts.admin')


@section('content')


<div class="col-12 ">

    <div class="row">
        <div class="col-md-3">

            <div  style="padding-top: 10px">
                <h3>{{ $user->name }}</h3>
            </div>
        </div>

        <div class="col-md-3" style="padding-top: 1%">
           <h3>{{ $user->phone ?? '' }}</h3>
        </div>

        <div class="col-md-3" style="padding-top: 1%">
           <h3>{{ $user->adresse ?? '' }}</h3>
        </div>

        <div class="col-md-3" style="padding-top: 1%">
           <button type="button" style="color: white" class="btn btn-success btn-block">
           {{ $orde->sum('total') }}  F </button>
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
                           <th style="width: 20%">Code</th>
                           <th style="width: 20%">Nom/Téléphone (Client)</th>
                           <th style="width: 20%">Sous total</th>
                           <th style="width: 20%">Frais</th>
                           <th style="width: 20%">Total</th>
                           <th style="width: 20%">Etat</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($orde as $order )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $order->code}}</td>
                         <td style="color: black ">{{ $order->costumer->name ?? '-' }}| {{ $order->costumer->phone ?? '-' }}</td>

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
                      </tr>
                         @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                       <tr>
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Code</th>
                        <th style="width: 20%">Nom/Téléphone (Client)</th>
                        <th style="width: 20% ;color: black" >Sous total</th>
                        <th style="width: 20% ;color: black">Total des frais</th>
                        <th style="width: 20%;color: black">Total</th>
                        <th style="width: 20%">Etat</th>
                    </tr>
                    <th style="width: 20%"></th>
                    <th style="width: 20%"></th>
                    <th style="width: 20%"></th>
                    <th style="width: 20% ; color:red ;font-weight: 900">{{$orde->sum('subtotal')}} F</th>
                    <th style="width: 20% ; color:red ;font-weight: 900">{{$orde->sum('tax')}} F</th>
                    <th style="width: 20% ; color:red ;font-weight: 900">{{$orde->sum('total')}} F</th>
                </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection
