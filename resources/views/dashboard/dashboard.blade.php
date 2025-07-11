@extends('layouts.admin')


@section('content')

    <div class="row">
        @if (Auth::user()->user_type == 'ADMINUSER')
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes </h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $OrderdeAmount }} XOF</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">

                                        <img src="{{ asset('assets/images/money.png') }}" width="40" height="40"
                                            alt="" srcset="">

                                    </button>


                                </div>
                            </div>
                        </div>
                        {{-- <p class="mt-3 mb-0 text-sm">

                    <span class="text-nowrap">this month</span>
                    <span class="text-success float-right ml-2"> <i class="fa fa-arrow-up"></i>
                        8%</span>
                </p> --}}
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Orderall }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">

                                        <img src="{{ asset('assets/images/ord.png') }}" width="40" height="40"
                                            alt="" srcset="">

                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- <p class="mt-3 mb-0 text-sm">

                    <span class="text-nowrap ">since last month</span>
                    <span class="text-success float-right ml-2">
                        <i class="fa fa-arrow-up"></i>
                        3.48%</span>
                </p> --}}
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Commades en cours</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Ordered }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/ords.png') }}" width="40" height="40"
                                            alt="" srcset="">

                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- <p class="mt-3 mb-0 text-sm">
                    <span class="text-nowrap">since last month</span>
                    <span class="text-danger float-right ml-2"> <i class="fa fa-arrow-down"></i>
                        3.48%</span>
                </p> --}}
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Commades livrées</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Orderdelivered }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/reciv.png') }}" width="40" height="40"
                                            alt="" srcset="">
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- <p class="mt-3 mb-0 text-sm">
                    <span class="text-nowrap">since last month</span>
                    <span class="text-danger float-right ml-2"> <i class="fa fa-arrow-down"></i>
                        3.48%</span>
                </p> --}}
                    </div>
                </div>
            </div>


            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Chiffre d'affaire annuel</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $totalCommandes }}
                                    XOF</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">

                                        <img src="{{ asset('assets/images/chi.png') }}" width="40" height="40"
                                            alt="" srcset="">

                                    </button>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Total des dépenses</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $expensive }} XOF</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">

                                        <img src="{{ asset('assets/images/dep.png') }}" width="40" height="40"
                                            alt="" srcset="">

                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- <p class="mt-3 mb-0 text-sm">

                    <span class="text-nowrap ">since last month</span>
                    <span class="text-success float-right ml-2">
                        <i class="fa fa-arrow-up"></i>
                        3.48%</span>
                </p> --}}
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Chiffre d'affaire hebdomadaire</h5>
                                <span style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $totalOrdersThisWeek }}
                                    XOF</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/hb.png') }}" width="40" height="40"
                                            alt="" srcset="">

                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- <p class="mt-3 mb-0 text-sm">
                    <span class="text-nowrap">since last month</span>
                    <span class="text-danger float-right ml-2"> <i class="fa fa-arrow-down"></i>
                        3.48%</span>
                </p> --}}
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('rupture') }}">

                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Produits en rupture</h5><span
                                        style="font-size: 130%"
                                        class="h1 font-weight-bold mb-0">{{ $rupture }}</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/rup.png') }}" width="40"
                                                height="40" alt="" srcset="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('pub-list') }}">

                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Fonds publicitaire</h5><span
                                        style="font-size: 130%;color: black"
                                        class="h1 font-weight-bold mb-0">{{ $totalpubs }} XOF</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/money.png') }}" width="40"
                                                height="40" alt="" srcset="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('imprevu-list') }}">

                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Fonds des imprévus</h5><span
                                        style="font-size: 130% ;color: black"
                                        class="h1 font-weight-bold mb-0">{{ $totalimpre }} XOF</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/money.png') }}" width="40"
                                                height="40" alt="" srcset="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('fond-list') }}">

                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Fonds de roulement</h5><span
                                        style="font-size: 130% ;color: black"
                                        class="h1 font-weight-bold mb-0">{{ $totalfond }} XOF</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/money.png') }}" width="40"
                                                height="40" alt="" srcset="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('epargne-list') }}">

                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Epargne</h5><span
                                        style="font-size: 130% ;color: black"
                                        class="h1 font-weight-bold mb-0">{{ $totalepargn }} XOF</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/money.png') }}" width="40"
                                                height="40" alt="" srcset="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>


            <div class="col-12 col-sm-12 col-md-12 col-lg-8">
                <div class="card shadow">
                    <div class="card-body">

                        <h3 class="card-title mb-4 float-sm-left">{{ $chart1->options['chart_title'] }}</h3>


                        <div>
                            {!! $chart1->renderHtml() !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-12 col-md-12 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4">{{ $chart2->options['chart_title'] }}</h3>


                        {!! $chart2->renderHtml() !!}

                    </div>
                </div>
            </div>
            <div class="col-12 col-md-12 col-lg-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4">{{ $chart3->options['chart_title'] }}</h3>


                        {!! $chart3->renderHtml() !!}
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4">{{ $chart4->options['chart_title'] }}</h3>
                        {!! $chart4->renderHtml() !!}
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Commandes</h4>
                        <div class="table-responsive">
                            <table id="example" class="table table-hover w-130">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 20%">N°</th>
                                        <th style="width: 20%">Nom/Téléphone (Client)</th>
                                        <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                                        <th style="width: 20%">Sous total</th>
                                        <th style="width: 20%">Remise</th>
                                        <th style="width: 20%">Total</th>
                                        <th style="width: 20%">Etat</th>
                                        <th style="width: 20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 1;
                                    @endphp
                                    @foreach ($orders as $order)
                                        <tr>

                                            <td style="color: black ">{{ $i++ }}</td>
                                            <td style="color: black ">{{ $order->costumer->name ?? '-' }}|
                                                {{ $order->costumer->phone ?? '-' }}</td>
                                            <td style="color: black ">{{ $order->user->name ?? 'Non' }}|
                                                {{ $order->user->phone ?? '' }}</td>

                                            <td style="color: black ">{{ $order->subtotal }} F</td>
                                            <td style="color: black ">{{ $order->remis }} % </td>
                                            <td style="color: black ">{{ $order->total }} F </td>
                                            <td style="color: black ">
                                                @if ($order->status == 'ordered')
                                                    <span class="badge badge-warning"> En cours</span>
                                                @elseif($order->status == 'delivered')
                                                    <span class="badge badge-success"> Terminé</span>

                                                    {{-- @endif --}}
                                                @elseif ($order->status == 'canceled')
                                                    <span class="badge badge-danger">Annuler</span>

                                                    {{-- @endif --}}
                                                @endif
                                            </td>
                                            <td style="color: black " class=" pull-right">

                                                <div class="btn-group btn-group-justified">
                                                    @if (Auth::user()->user_type == 'ADMINUSER')
                                                        <a href="{{ route('ordershow', $order) }}" style="color: white"
                                                            type="button" class="btn btn-success">
                                                            <i class="material-icons f-16">visibility</i>Details</a>
                                                    @endif

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                                <tfoot class="thead-light">
                                    <tr>
                                        <th style="width: 20%">N°</th>
                                        <th style="width: 20%">Nom/Téléphone (Client)</th>
                                        <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                                        <th style="width: 20%">Sous total</th>
                                        <th style="width: 20%">Remise</th>
                                        <th style="width: 20%">Total</th>
                                        <th style="width: 20%">Etat</th>
                                        <th style="width: 20%">Actions</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- end table-responsive -->
                    </div>
                </div>
            </div>
        @elseif (Auth::user()->user_type == 'LVS')
            @livewire('card-livreur')
        @endif


        {!! $chart1->renderChartJsLibrary() !!}

        {!! $chart1->renderJs() !!}

        {!! $chart2->renderChartJsLibrary() !!}
        {!! $chart2->renderJs() !!}

        {!! $chart3->renderChartJsLibrary() !!}
        {!! $chart3->renderJs() !!}

        {!! $chart4->renderChartJsLibrary() !!}
        {!! $chart4->renderJs() !!}

    </div>

@endsection
