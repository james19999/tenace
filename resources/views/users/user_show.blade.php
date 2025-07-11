@extends('layouts.admin')


@section('content')
    <div class="col-12 ">

        <div class="row">
            <div class="col-md-3">

                <div style="padding-top: 10px">
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
                    Total {{ $user->wallets->sum('balance') }} XOF </button>
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
                                $i = 1;
                            @endphp
                            @foreach ($commiss as $commis)
                                <tr>

                                    <td style="color: black ">{{ $i++ }}</td>
                                    <td style="color: black ">
                                        {{ \Carbon\Carbon::parse($commis->created_at)->translatedFormat('l j F Y \à H:i:s') }}
                                    </td>
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
