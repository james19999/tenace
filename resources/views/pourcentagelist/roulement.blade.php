@extends('layouts.admin')


@section('content')


<div class="col-12 ">
    <div class="row">


        <div class="col-md-6" style="padding-top: 2%">
           <button type="button" style="color: white" class="btn btn-success btn-block">
            Total  {{  $fonds->sum('amount') }}  XOF  | Restant {{ $totalfons }} XOF</button>
        </div>


        <div  style="padding-top: 1px">
            <div class="card-body">
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#defaultModal">
                    Effectuer un retrait
                </button>

                <!-- Modal -->
                <div class="modal fade" id="defaultModal" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="defaultModalLabel">Retrait</h4>
                                <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true" class="material-icons">close</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('fond-retrait') }}" method="POST">
                                    @csrf
                                     <input type="number" name="amount" class="form-control col-12" id="" placeholder="montant" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                <button type="submit" class="btn btn-primary">Valider</button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3" style="padding-top: 2%">
          <a href="{{ route('retrait-list') }}" class="btn btn-info" >Liste des retraits  | {{ $totalretrait }} XOF</a>
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
                           <th style="width: 20%">Date</th>
                           <th style="width: 20%">Montant de la commande</th>
                           <th style="width: 20%">Pourcentage</th>
                           <th style="width: 20%">Montant de la commission</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                        @foreach ($fonds as $commis )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ \Carbon\Carbon::parse($commis->created_at)->translatedFormat('l j F Y \à H:i:s') }}</td>
                         <td style="color: black ">{{ $commis->total }} XOF</td>
                         <td style="color: black ">{{ $commis->fixed }} %</td>
                         <td style="color: black ">{{ $commis->amount }} XOF</td>

                        </tr>
                        @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                     <tr>
                            <th style="width: 20%">N°</th>
                            <th style="width: 20%">Date</th>
                            <th style="width: 20%">Montant de la commande</th>
                            <th style="width: 20%">Pourcentage</th>
                            <th style="width: 20%">Montant de la commission</th>


                    </tr>


                </tfoot>
               </table>
           </div>
       </div>
   </div>


</div>


@endsection
