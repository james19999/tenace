@extends('layouts.admin')


@section('content')

    <div class="dashboard-page">
        <div class="dashboard-welcome">
            <div class="dashboard-welcome-copy">
                <span class="dashboard-welcome-eyebrow">TENACOS · ESPACE DE PILOTAGE</span>
                <h1>Bonjour {{ Auth::user()->name }}</h1>
                <p>Voici un aperçu de l’activité et des commandes à suivre.</p>
            </div>
            <div class="dashboard-datetime" aria-label="Date et heure locales">
                <div class="dashboard-date-icon"><span class="material-icons" aria-hidden="true">calendar_month</span></div>
                <div class="dashboard-date-copy">
                    <span id="dashboard-current-date">{{ now()->locale('fr')->translatedFormat('l d F Y') }}</span>
                    <strong id="dashboard-current-time">{{ now()->format('H:i:s') }}</strong>
                    <small>Heure locale · Lomé</small>
                </div>
                <span class="dashboard-live-dot" aria-hidden="true"></span>
            </div>
        </div>

    <div class="row dashboard-content">
        @if (Auth::user()->user_type == 'ADMINUSER')
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Chiffre d’affaires du jour </h5><span
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

            @include('dashboard.partials.orders-tabs')
        @elseif (Auth::user()->user_type == 'MNG')
            {{-- DASHBOARD MANAGER --}}
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Chiffre d’affaires du jour</h5>
                                <span style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ number_format((float) $OrderdeAmount, 0, ',', ' ') }} XOF</span>
                            </div>
                            <div class="col-auto col">
                                <div class="btn btn-transparent-primary btn-lg btn-circle">
                                    <img src="{{ asset('assets/images/money.png') }}" width="40" height="40" alt="">
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
                                <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Orderall }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/ord.png') }}" width="40" height="40" alt="">
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
                                <h5 class="text-uppercase text-muted mb-0 card-title">Commades en cours</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Ordered }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/ords.png') }}" width="40" height="40" alt="">
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
                                <h5 class="text-uppercase text-muted mb-0 card-title">Commades livrées</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Orderdelivered }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/reciv.png') }}" width="40" height="40" alt="">
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('rupture') }}">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Produits en rupture </h5><span
                                        style="font-size: 130%; color: black"
                                        class="h1 font-weight-bold mb-0">{{ $rupture }}</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/rup.png') }}" width="40" height="40" alt="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('expensives') }}">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Total des dépenses</h5><span
                                        style="font-size: 130%; color: black"
                                        class="h1 font-weight-bold mb-0">{{ $expensive }} XOF</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/dep.png') }}" width="40" height="40" alt="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            @include('dashboard.partials.orders-tabs')
        @elseif (Auth::user()->user_type == 'SCR')
            {{-- DASHBOARD SECRETAIRE (Commandes en cours, livrées, annulées, rupture) --}}
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
                                        <img src="{{ asset('assets/images/ords.png') }}" width="40" height="40" alt="">
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
                                <h5 class="text-uppercase text-muted mb-0 card-title">Commades livrées</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $Orderdelivered }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/reciv.png') }}" width="40" height="40" alt="">
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
                                <h5 class="text-uppercase text-muted mb-0 card-title">Commandes annulées</h5><span
                                    style="font-size: 130%" class="h1 font-weight-bold mb-0 text-danger">{{ $Ordercanceled }}</span>
                            </div>
                            <div class="col-auto col">
                                <div>
                                    <button class="btn btn-transparent-primary btn-lg btn-circle">
                                        <img src="{{ asset('assets/images/out.png') }}" width="40" height="40" alt="">
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="{{ route('rupture') }}">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">Produits en rupture </h5><span
                                        style="font-size: 130%; color: black"
                                        class="h1 font-weight-bold mb-0">{{ $rupture }}</span>
                                </div>
                                <div class="col-auto col">
                                    <div>
                                        <button class="btn btn-transparent-primary btn-lg btn-circle">
                                            <img src="{{ asset('assets/images/rup.png') }}" width="40" height="40" alt="">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            @include('dashboard.partials.orders-tabs')
        @elseif (Auth::user()->user_type == 'LVS')
            @livewire('card-livreur')
        @endif

        @if (Auth::user()->user_type == 'ADMINUSER')
            {!! $chart1->renderChartJsLibrary() !!}
            {!! $chart1->renderJs() !!}
            {!! $chart2->renderJs() !!}
            {!! $chart3->renderJs() !!}
            {!! $chart4->renderJs() !!}
        @endif

    </div>

    <style>
        .dashboard-page{--dash-red:#861b1b;--dash-ink:#282b35;--dash-muted:#777d89;padding:6px 0 28px;color:var(--dash-ink)}
        .dashboard-welcome{position:relative;display:flex;align-items:center;justify-content:space-between;gap:22px;overflow:hidden;margin:6px 0 22px;padding:23px 25px;border:1px solid #eadfdd;border-radius:17px;background:linear-gradient(110deg,#fff 0%,#fff 62%,#fbf0ee 100%);box-shadow:0 8px 25px rgba(71,32,30,.045)}
        .dashboard-welcome:after{position:absolute;right:270px;bottom:-100px;width:230px;height:230px;border:1px solid rgba(134,27,27,.06);border-radius:50%;content:"";pointer-events:none}
        .dashboard-welcome-copy,.dashboard-datetime{position:relative;z-index:1}.dashboard-welcome-eyebrow{display:block;margin-bottom:7px;color:var(--dash-red);font-size:10px;font-weight:850;letter-spacing:.15em}
        .dashboard-welcome h1{margin:0;color:var(--dash-ink);font-size:clamp(25px,3vw,34px);font-weight:850;letter-spacing:-.7px;line-height:1.15}
        .dashboard-welcome-copy p{margin:7px 0 0;color:var(--dash-muted);font-size:13px}
        .dashboard-datetime{display:flex;align-items:center;gap:12px;min-width:226px;padding:12px 15px;border:1px solid rgba(134,27,27,.1);border-radius:13px;background:rgba(255,255,255,.88);box-shadow:0 5px 16px rgba(50,30,30,.04)}
        .dashboard-date-icon{display:grid;place-items:center;width:42px;height:42px;flex:0 0 42px;border-radius:12px;background:#f8eaea;color:var(--dash-red)}
        .dashboard-date-copy{display:flex;min-width:0;flex-direction:column;gap:2px}.dashboard-date-copy span{color:#686e7a;font-size:11px;font-weight:700;text-transform:capitalize}
        .dashboard-date-copy strong{color:var(--dash-ink);font-size:23px;font-variant-numeric:tabular-nums;letter-spacing:.02em;line-height:1.15}
        .dashboard-date-copy small{color:#9297a1;font-size:10px}.dashboard-live-dot{align-self:flex-start;width:7px;height:7px;flex:0 0 7px;margin:2px 0 0 auto;border-radius:50%;background:#48a36b;box-shadow:0 0 0 4px rgba(72,163,107,.12)}
        .dashboard-content{row-gap:4px}.dashboard-content>.col-12>.card,.dashboard-content>.col-12>a>.card{height:calc(100% - 12px);margin-top:12px;border:1px solid #e8e9ed;border-radius:12px;box-shadow:0 5px 18px rgba(33,38,48,.04)!important;transition:transform .18s ease,box-shadow .18s ease}
        .dashboard-content>.col-12>.card:hover,.dashboard-content>.col-12>a>.card:hover{transform:translateY(-2px);box-shadow:0 9px 22px rgba(33,38,48,.08)!important}
        .dashboard-content>.col-12>.card .card-body,.dashboard-content>.col-12>a>.card .card-body{padding:14px 15px}
        .dashboard-content .card-title{font-size:10px;letter-spacing:.04em;line-height:1.35}.dashboard-content .h1{font-size:clamp(19px,1.8vw,24px)!important;letter-spacing:-.45px}
        .dashboard-content>.col-12>a{display:block;height:100%;text-decoration:none}
        @media(max-width:767.98px){.dashboard-page{padding-top:0}.dashboard-welcome{align-items:stretch;flex-direction:column;margin:5px 0 15px;padding:18px;border-radius:13px;gap:15px}.dashboard-welcome:after{right:-110px;bottom:-150px}.dashboard-welcome h1{font-size:25px}.dashboard-welcome-copy p{font-size:12px;line-height:1.5}.dashboard-datetime{min-width:0;width:100%;padding:10px 12px}.dashboard-date-icon{width:38px;height:38px;flex-basis:38px}.dashboard-date-copy strong{font-size:21px}.dashboard-content>.col-12>.card .card-body,.dashboard-content>.col-12>a>.card .card-body{padding:12px}.dashboard-content .card-title{font-size:10px}.dashboard-content .h1{font-size:19px!important}}

        /* Flat, square dashboard styling. */
        .dashboard-welcome{overflow:visible;margin-top:4px;padding:20px 22px;border-radius:0;background:#fff;box-shadow:none}
        .dashboard-welcome:after{display:none}
        .dashboard-welcome h1{line-height:1.25}
        .dashboard-welcome-copy p{line-height:1.6}
        .dashboard-datetime{border-radius:0;background:#fff;box-shadow:none}
        .dashboard-date-icon{border-radius:0;background:#f7eeee}
        .dashboard-live-dot{border-radius:0;box-shadow:none}
        .dashboard-content>.col-12>.card,.dashboard-content>.col-12>a>.card{border-radius:0;box-shadow:none!important;transition:border-color .15s ease}
        .dashboard-content>.col-12>.card:hover,.dashboard-content>.col-12>a>.card:hover{transform:none;border-color:#c7b7b6;box-shadow:none!important}
        .dashboard-content>.col-12>.card .card-body,.dashboard-content>.col-12>a>.card .card-body{padding:16px}
        .dashboard-content .card-title{margin-bottom:9px!important;color:#6c7380;font-size:10px;font-weight:800;letter-spacing:.07em;line-height:1.5}
        .dashboard-content .h1{display:block;line-height:1.25;overflow-wrap:anywhere}
        .dashboard-content .btn-circle{display:grid;width:46px;height:46px;place-items:center;padding:8px;border-radius:0;background:#f7eeee}
        .dashboard-content .btn-circle img{width:28px!important;height:28px!important;object-fit:contain}
        .dashboard-content>.col-12>a{color:inherit}
        .dashboard-order-tabs .nav-link{border-radius:0!important}
        .dashboard-order-tabs .badge,.dashboard-orders-table .badge,.dashboard-orders-table .btn,.dashboard-content .pagination .page-link{border-radius:0!important}
        .dashboard-orders-table{border-collapse:collapse}
        .dashboard-orders-table th,.dashboard-orders-table td{padding:12px 13px;vertical-align:middle}
        @media(max-width:767.98px){
            .dashboard-welcome{margin:4px 0 14px;padding:16px}
            .dashboard-datetime{width:100%;min-width:0}
            .dashboard-content>.col-12>.card .card-body,.dashboard-content>.col-12>a>.card .card-body{padding:13px}
            .dashboard-order-tabs{gap:0}
        }
        @media(max-width:575.98px){
            .dashboard-content>.col-12.col-sm-6.col-md-6.col-lg-3{flex:0 0 50%;max-width:50%;padding-right:6px;padding-left:6px}
            .dashboard-content .btn-circle{width:36px;height:36px;padding:5px}
            .dashboard-content .btn-circle img{width:24px!important;height:24px!important}
            .dashboard-content .card-title{font-size:9px}
            .dashboard-content .h1{font-size:17px!important}
        }
    </style>
    <script>
        (function () {
            var dateNode = document.getElementById('dashboard-current-date');
            var timeNode = document.getElementById('dashboard-current-time');
            if (!dateNode || !timeNode) return;
            var dateFormatter = new Intl.DateTimeFormat('fr-FR', { timeZone: 'Africa/Lome', weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
            var timeFormatter = new Intl.DateTimeFormat('fr-FR', { timeZone: 'Africa/Lome', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
            function updateDashboardClock() {
                var now = new Date();
                dateNode.textContent = dateFormatter.format(now);
                timeNode.textContent = timeFormatter.format(now);
            }
            updateDashboardClock();
            window.setInterval(updateDashboardClock, 1000);
        })();
    </script>
    </div>

@endsection
