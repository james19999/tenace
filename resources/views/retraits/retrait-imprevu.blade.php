@extends('layouts.admin')


@section('content')


<div class="col-12 ">

    <div class="row">


        <div class="col-md-3" style="padding-top: 1%">
           <button type="button" style="color: white" class="btn btn-success btn-block">
            Total  {{  $retraits->sum('amount') }}  XOF </button>
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
                           <th style="width: 20%">Montant</th>
                           <th style="width: 20%">Raison</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                        @foreach ($retraits as $commis )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ \Carbon\Carbon::parse($commis->created_at)->translatedFormat('l j F Y \à H:i:s') }}</td>
                         <td style="color: black ">{{ $commis->amount }} XOF</td>
                         <td style="color: black ">{{ $commis->raison }} </td>
                        </tr>
                        @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                     <tr>
                            <th style="width: 20%">N°</th>
                            <th style="width: 20%">Date</th>
                            <th style="width: 20%">Montant</th>
                            <th style="width: 20%">Raison</th>


                    </tr>


                </tfoot>
               </table>
           </div>
       </div>
   </div>


</div>


@endsection
