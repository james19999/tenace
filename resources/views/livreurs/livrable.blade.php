@extends('layouts.admin')


@section('content')


<div class="col-12 ">
    <div  style="padding-top: 10px">
        <h3>Liste des livraisons</h4>
    </div>
      @if (Auth::user()->active==true)

      <div class="card shadow">
          <div class="card-body ">
               @if (Session::has('messages'))
                <div class="alert alert-success">
                   <strong>{{ session('messages') }}</strong>
                </div>
               @endif
               @if (Session::has('error'))
                <div class="alert alert-danger">
                   <strong>{{ session('error') }}</strong>
                </div>
               @endif
               @if (Session::has('waring'))
                <div class="alert alert-warning">
                   <strong>{{ session('waring') }}</strong>
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
                              <th style="width: 20%">Adresse</th>
                              <th style="width: 20%">Total</th>
                              <th style="width: 20%">Heure</th>
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
                            <td style="color: black ">{{ $order->code}}</td>
                            <td style="color: black ">{{ $order->costumer->name ?? '-' }}| {{ $order->costumer->phone ?? '-' }}</td>

                            <td style="color: black ">{{ $order->subtotal }} F</td>
                            <td style="color: black ">{{ $order->costumer->adresse ?? '-' }}  </td>
                            <td style="color: black ">{{ $order->total }} F </td>
                            <td style="color: black ">{{ $order->time }}  </td>
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

                                       <form   method="POST" action="{{ route('checklivraison',$order->id) }}">
                                            @csrf
                                           <button  style="padding-bottom: 12%" class="btn btn-sm btn-success"
                                            ><i class="material-icons">shopping_cart</i></button>
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
                           <th style="width: 20%">Sous total</th>
                           <th style="width: 20%">Adresse</th>
                           <th style="width: 20%">Total</th>
                           <th style="width: 20%">Heure</th>
                           <th style="width: 20%">Etat</th>
                           <th style="width: 20%">Actions</th>
                          </tr>
                      </tfoot>
                  </table>
              </div>
          </div>
      </div>
      @else
           <p>Votre compte a été bloqué. Veuillez contacter notre service client</p>
      @endif
</div>


@endsection
