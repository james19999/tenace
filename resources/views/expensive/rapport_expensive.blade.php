@extends('layouts.admin')


@section('content')

<div class="row">
   <a href="{{ route('expensives') }}">Retour</a>
    {{--  <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="card shadow">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes </h5><span style="font-size: 130%" class="h1 font-weight-bold mb-0"> XOF</span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">

                                <img src="{{ asset('assets/images/money.png') }}" width="40" height="40" alt="" srcset="">

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
                        <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes</h5><span style="font-size: 130%" class="h1 font-weight-bold mb-0"></span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">

                                <img src="{{ asset('assets/images/ord.png') }}" width="40" height="40" alt="" srcset="">

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
                        <h5 class="text-uppercase text-muted mb-0 card-title">Commades en cours</h5><span style="font-size: 130%" class="h1 font-weight-bold mb-0"> </span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">
                                <img src="{{ asset('assets/images/ords.png') }}" width="40" height="40" alt="" srcset="">

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
                        <h5 class="text-uppercase text-muted mb-0 card-title">Commades livrées</h5><span  style="font-size: 130%" class="h1 font-weight-bold mb-0"> </span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">
                                <img src="{{ asset('assets/images/reciv.png') }}" width="40" height="40" alt="" srcset="">
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>  --}}
    <div class="col-12 col-sm-12 col-md-12 col-lg-12">
        <div class="card shadow">
            <div class="card-body">

                <h3 class="card-title mb-4 float-sm-left">{{ $chart1->options['chart_title'] }}</h3>


                <div >
                    {!! $chart1->renderHtml() !!}
                </div>
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




    {!! $chart1->renderChartJsLibrary() !!}

    {!! $chart1->renderJs() !!}

    {{--  {!! $chart2->renderChartJsLibrary() !!}
    {!! $chart2->renderJs() !!}  --}}

    {!! $chart3->renderChartJsLibrary() !!}
    {!! $chart3->renderJs() !!}

    {!! $chart4->renderChartJsLibrary() !!}
    {!! $chart4->renderJs() !!}

</div>

@endsection
