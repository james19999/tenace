@extends('layouts.admin')


@section('content')

<div class="row">
    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="card shadow">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes </h5><span class="h1 font-weight-bold mb-0">{{ $OrderdeAmount }} XOF</span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">

                                <i class="material-icons">monetization_on</i>
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
                        <h5 class="text-uppercase text-muted mb-0 card-title">Total des commandes</h5><span class="h1 font-weight-bold mb-0">{{ $Orderall }}</span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">

                                <i class="material-icons">trending_up</i>
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
                        <h5 class="text-uppercase text-muted mb-0 card-title">Commades en cours</h5><span class="h1 font-weight-bold mb-0">{{ $Ordered }}</span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">
                                <i class="material-icons">language</i>
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
                        <h5 class="text-uppercase text-muted mb-0 card-title">Commades livrées</h5><span class="h1 font-weight-bold mb-0">{{ $Orderdelivered }}</span>
                    </div>
                    <div class="col-auto col">
                        <div>
                            <button class="btn btn-transparent-primary btn-lg btn-circle">
                                <i class="material-icons">receipt</i>

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
    <div class="col-12 col-sm-12 col-md-12 col-lg-8">
        <div class="card shadow">
            <div class="card-body">

                <h3 class="card-title mb-4 float-sm-left">{{ $chart1->options['chart_title'] }}</h3>


                <div >
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

    {{-- <div class="col-12 col-md-12 col-lg-4">
        <div class="card shadow">

            <div class="card-body p-0">
                <div class="card-header bg-white">
                    Product Demand
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead class="thead-light">
                            <tr>
                                <th>Brand</th>
                                <th>Popularity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    Samsung
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>

                            </tr>
                            <tr>
                                <td>
                                    Apple
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>

                            </tr>
                            <tr>
                                <td>
                                    Oppo
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>

                            </tr>
                            <tr>
                                <td>
                                    Vivo
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 57%" aria-valuenow="57" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>

                            </tr>
                            <tr>
                                <td>
                                    OnePlus
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> --}}
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

    {{-- <div class="col-12 col-md-12 col-lg-6">
        <div class="card shadow">
            <div class="card-body">
                <h4 class="card-title mb-4">Product Stock</h4>
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <thead class="thead-light rounded">
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">In Stock</th>
                                <th scope="col">Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <img src="assets/images/product/a.png" class="avatar avatar-sm mr-2" alt="Samsung Galaxy S20"> Samsung Galaxy S20

                                </td>

                                <td class="align-middle">$32</td>
                                <td class="align-middle">24</td>
                                <td class="align-middle">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customSwitch1" checked>
                                        <label class="custom-control-label" for="customSwitch1">
                                        </label>
                                    </div>

                                </td>
                                <td class="align-middle">Mark</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="assets/images/product/b.png" class="avatar avatar-sm mr-2" alt="iPhone 12 Pro"> iPhone 12 Pro
                                </td>

                                <td class="align-middle">$52</td>
                                <td class="align-middle">58</td>
                                <td class="align-middle">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customSwitch2">
                                        <label class="custom-control-label" for="customSwitch2">
                                        </label>
                                    </div>
                                </td>
                                <td class="align-middle">John</td>

                            </tr>
                            <tr>
                                <td>
                                    <img src="assets/images/product/c.png" class="avatar avatar-sm mr-2" alt="OnePlus 8"> OnePlus 8
                                </td>

                                <td class="align-middle">$79</td>
                                <td class="align-middle">5</td>
                                <td class="align-middle">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customSwitch3" checked>
                                        <label class="custom-control-label" for="customSwitch3">
                                        </label>
                                    </div>
                                </td>
                                <td class="align-middle">John</td>

                            </tr>
                            <tr>
                                <td>
                                    <img src="assets/images/product/d.png" class="avatar avatar-sm mr-2" alt="Vivo F17 Pro"> Vivo F17 Pro
                                </td>

                                <td class="align-middle">$52</td>
                                <td class="align-middle">58</td>
                                <td class="align-middle">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customSwitch4">
                                        <label class="custom-control-label" for="customSwitch4">
                                        </label>
                                    </div>
                                </td>
                                <td class="align-middle">John</td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> --}}


    {{-- <div class="col-sm-12 col-md-6">
        <div class="card shadow">
            <div class="card-header bg-white">
                Track Order
            </div>
            <div class="card-body p-4 ml-3">
                <div class="timeline timeline-one-side" data-timeline-axis-style="dashed" data-timeline-content="axis">
                    <div class="timeline-block">
                        <span class="timeline-step badge-success">
                            <i class="material-icons f-12">today</i>
                        </span>
                        <div class="timeline-content">

                            <h5 class="mb-0 font-weight-bold">Order has Dispatched</h5>
                            <small class="text-muted ">10:30
                                AM</small>
                            <p class="text-sm mt-1 mb-0">Product has dispatched from vendor.
                            </p>

                        </div>
                    </div>
                    <div class="timeline-block"><span class="timeline-step badge-danger"><i class="material-icons f-12">alarm</i></span>
                        <div class="timeline-content">
                            <h5 class="mb-0 font-weight-bold">Order Picked Up</h5>
                            <small class="text-muted ">14:30
                                AM</small>
                            <p class="text-sm mt-1 mb-0">Order picked up from delivery partner
                            </p>

                        </div>
                    </div>
                    <div class="timeline-block">
                        <span class="timeline-step badge-info"><i class="material-icons f-12">thumb_up</i></span>
                        <div class="timeline-content">
                            <h5 class="mb-0 font-weight-bold">Order received at nearest hub</h5>
                            <small class="text-muted">10:30
                                AM</small>
                            <p class="text-sm mt-1 mb-0">Order received at nearest hub in your city
                            </p>

                        </div>
                    </div>
                    <div class="timeline-block"><span class="timeline-step badge-success"><i class="material-icons f-12">bookmarks</i></span>
                        <div class="timeline-content">
                            <h5 class="mb-0 font-weight-bold">Out for delivery</h5>
                            <small class="text-muted">15:30
                                AM</small>
                            <p class="text-sm mt-1 mb-0">Order will be delivered around 5 PM.
                            </p>

                        </div>
                    </div>
                    <div class="timeline-block"><span class="timeline-step badge-danger"><i class="material-icons f-12">grade</i></span>
                        <div class="timeline-content">
                            <h5 class="mb-0 font-weight-bold">Order delivered</h5>
                            <small class="text-muted">16:55
                                AM</small>
                            <p class="text-sm mt-1 mb-0">Order delivered at 4:55 PM.
                            </p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6">
        <div class="card shadow">
            <div class="card-header bg-white">
                Google Maps
                <div class="tools">

                    <button class="btn btn-square toggle-fullscreen">
                        <i class="material-icons">fullscreen</i>
                        <i class="material-icons">fullscreen_exit</i>
                    </button>

                </div>
            </div>
            <div class="card-body">
                <div class="m-0 p-0 overflow-hidden">
                    <iframe class="border-none" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241317.1160991881!2d72.74109908908066!3d19.082197838926515!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7c9409a609d75%3A0xd0a71c45e7557bfa!2sBasilica%20Of%20Our%20Lady%20of%20The%20Mount!5e0!3m2!1sen!2sin!4v1607597877463!5m2!1sen!2sin" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="col-lg-12">
        <div class="card shadow">
            <div class="card-body">
                <h4 class="card-title mb-4">Commandes</h4>
                <div class="table-responsive">
                    <table id="example" class="table table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 20%">N°</th>
                                <th style="width: 20%">Nom/Téléphone (Client)</th>
                                <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                                <th style="width: 20%">Sous total</th>
                                <th style="width: 20%">Frais</th>
                                <th style="width: 20%">Total</th>
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
                              <td style="color: black ">{{ $order->costumer->name ?? '-' }}| {{ $order->costumer->phone ?? '-' }}</td>
                              <td style="color: black ">{{ $order->user->name ?? 'Non' }}| {{ $order->user->phone ?? '' }}</td>

                              <td style="color: black ">{{ $order->subtotal }} F</td>
                              <td style="color: black ">{{ $order->tax }} F </td>
                              <td style="color: black ">{{ $order->total }} F </td>
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
                                      <a href="{{ route('ordershow',$order) }}" style="color: white" type="button" class="btn btn-success"
                                      >
                                      <i class="material-icons f-16">visibility</i>Details</a>


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
                             <th style="width: 20%">Frais</th>
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
