@extends('layouts.admin')


@section('content')
<style>
    @media print {
      .no-print {
        display: none;
      }
    }
  </style>

    <div class="card shadow">

        <div class="card-body ">
            @if (Session::has('success'))
            <div class="alert alert-success">
               <strong>{{ session('success') }}</strong>
               </div>
            @endif
            <div class="container table-responsive">
              <div class="row">
                <div class="col-12" id="printableArea">
                   <div class="row">
                        <div class="col-md-3">
                            <img src="{{ asset('assets/images/tena.jpg') }}" class="img-thumbnail" height="100" width="100" alt="" srcset="">
                        </div>
                        <div class="col-md-3">
                           <h2>Livreur</h2>
                           <p>Nom : {{ $Orders->user->name ?? 'TENA-COS ' }}</p>
                           <p>Téléphone :  {{ $Orders->user->phone ?? '92521341' }}</p>
                           <p>Adresse :  {{ $Orders->user->adresse ?? ' Adidogomé yokoè' }}</p>
                        </div>
                        <div class="col-md-3">
                           <h2>Client</h2>
                           <p>Nom : {{ $Orders->costumer->name ?? '' }}</p>
                           <p>Téléphone : {{ $Orders->costumer->phone ?? '' }}</p>
                           <p>Adresse   : {{ $Orders->costumer->adresse ?? ''}}</p>
                        </div>
                        <div class="col-md-3">
                            <h2>Commande</h2>
                            <p>Numéro : {{ $Orders->code }}  </p>
                            <p>Date  : {{ $Orders->created_at }}  </p>
                            <p>Heure de livraison  : {{ $Orders->time }}  </p>
                            <p>Etat  :
                              @if ($Orders->status=="ordered")
                              <span class="badge badge-warning"   > En cours</span>

                              @elseif($Orders->status=="delivered")
                              <span class="badge badge-success"> Terminé</span>

                              {{-- @endif --}}

                              @elseif ($Orders->status=="canceled")

                              <span class="badge badge-danger">Annuler</span>
                               <p>Motif : {{  $Orders->motif }}</p>

                              {{-- @endif --}}

                              @endif
                            </p>
                        </div>
                   </div>
                  <table class="table table-hover w-100 ">
                    <thead>
                      <tr>
                        <th>Désignation</th>
                        <th>Prix</th>
                        <th>Qauntité</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                       @foreach ($Orders->orderItems as $items )

                       <tr>
                         <td>{{ $items->product->name ?? ''}}</td>
                         <td>{{ $items->price ?? ''  }} F </td>
                         <td>{{ $items->quantity }}</td>
                         <td>{{ $items->quantity * $items->product->price }} F</td>
                       </tr>
                       @endforeach

                    </tbody>
                  </table>
                  <p>Sous total: {{ $Orders->subtotal }} F</p>
                  <p>Frais de livraison:  {{ $Orders->tax }} F</p>
                  <p>Total:  {{ $Orders->total }} F</p>
                </div>
                 <div style="padding-right:5px">

                     <a  href="{{ route('brouillons') }}"  class="btn btn-warning"  >Retour</a>
                 </div>
                <button   class="btn btn-primary no-print" onclick="printDiv('printableArea')">Print</button>

                  <div class="row" style="padding-left:5px">
                      <div class="col-md-6">

                          <form action="{{ route('refreshorder',$Orders->id) }}"   method="POST">
                              @csrf
                             @method("PUT")
                                 <div class="row">

                                     <div class="col-md-4">
                                         <input type="text"  class="form-control @error('adresse') is-invalid @enderror" name="adresse" value="{{ $Orders->costumer->adresse ?? '' }}" placeholder="adresse">
                                          @error('adresse')
                                           <div class="alert alert-danger">{{ $message }}</div>
                                           @enderror
                                      </div>
                                      <div class="col-md-4">
                                         <input type="time"  class="form-control @error('time') is-invalid @enderror" name="time" value="{{  $Orders->time }}" placeholder="adresse">
                                          @error('time')
                                           <div class="alert alert-danger">{{ $message }}</div>
                                           @enderror
                                      </div>
                                      <div class="col-md-4">
                                          <button  type="submit"   class="btn btn-info" >Relancer</button>
                                      </div>
                                 </div>

                          </form>


                      </div>

                      <div class="col-md-6">

                        <form action="{{route('changestatus',$Orders->id)}}"  method="POST" style="padding-left: 10px">
                            @csrf
                            @method("PUT")
                             <div class="row">
                                <div class="col-md-4">
                                    <select  name="status" id="" class="form-control" >
                                        <option value="ordered">En cours</option>
                                        <option value="delivered">Valider</option>
                                        <option value="canceled">Annuler</option>
                                    </select>

                                </div>
                                <div class="col-md-4">
                                   <input type="text"  class="form-control @error('motif') is-invalid @enderror" name="motif" value="{{ old('motif') }}" placeholder="motif d' annulation">
                                    @error('motif')
                                     <div class="alert alert-danger">{{ $message }}</div>
                                     @enderror
                                </div>

                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-success" >Enregister</button>
                                </div>
                             </div>
                      </form>


                      </div>
                  </div>



              </div>
            </div>

        <div>
    <div>


@endsection
