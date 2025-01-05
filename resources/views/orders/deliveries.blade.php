@extends('layouts.admin')


@section('content')
    <div class="col-12 ">
        <div style="padding-top: 10px">
            <h3>Classement</h4>
        </div>
        <div class="card shadow">
            <div class="card-body ">



                <div class="table-responsive">
                    <table id="example" class="table table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 20%">N°</th>
                                <th style="width: 20%">Livreur</th>
                                <th style="width: 20%">Lundi</th>
                                <th style="width: 20%">Mardi</th>
                                <th style="width: 20%">Mercredi</th>
                                <th style="width: 20%">Jeudi</th>
                                <th style="width: 20%">Vendredi</th>
                                <th style="width: 20%">Samedi</th>
                                <th style="width: 20%">Dimanche</th>
                                <th style="width: 20%">Total </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = 1;
                            @endphp
                            @foreach ($formatted as $user)
                                <tr>
                                    <td style="color: black ">{{ $i++ }}</td>

                                    <td style="color: black ">{{ $user['name'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Monday'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Tuesday'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Wednesday'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Thursday'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Friday'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Saturday'] }}</td>
                                    <td style="color: black ">{{ $user['days']['Sunday'] }}</td>
                                    <td style="color: black ">{{ $user['total_deliveries'] }}</td>
                                </tr>
                            @endforeach


                        </tbody>
                        <tfoot class="thead-light">
                            <tr>
                                <th style="width: 20%">N°</th>
                                <th style="width: 20%">Livreur</th>
                                <th style="width: 20%">Lundi</th>
                                <th style="width: 20%">Mardi</th>
                                <th style="width: 20%">Mercredi</th>
                                <th style="width: 20%">Jeudi</th>
                                <th style="width: 20%">Vendredi</th>
                                <th style="width: 20%">Samedi</th>
                                <th style="width: 20%">Dimanche</th>
                                <th style="width: 20%">Total </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
