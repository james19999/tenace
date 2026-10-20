@extends('layouts.admin')


@section('content')
    <div class="col-12 ">
        <div style="padding-top: 10px">
            <h3>Classement/ <a href="{{ route('ranking') }}">Rang</a> </h4>
            @if ($canSelectDeliveryCountry)
                <form method="GET" action="{{ route('show-deliveries') }}" class="d-flex align-items-center flex-wrap" style="gap:10px;margin:14px 0;">
                    <label for="delivery-ranking-country" class="mb-0">Pays du classement</label>
                    <select id="delivery-ranking-country" name="country_code" class="form-control" style="max-width:260px" onchange="this.form.submit()">
                        <option value="ALL" @selected($selectedCountry === null)>Tous les pays</option>
                        @foreach ($countryOptions as $code => $country)
                            <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $country['label'] }}</option>
                        @endforeach
                    </select>
                </form>
            @else
                <p class="text-muted mb-2">Classement des livreurs · {{ $countryOptions[$selectedCountry]['label'] ?? 'Pays non configuré' }}</p>
            @endif
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
