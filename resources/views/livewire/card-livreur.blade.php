<div class="col-12">
    <div class="mt-3">
        <strong>
            Bienvenue {{ Auth::user()->name }}, ravi de vous revoir parmi nous !
        </strong>
    </div>

    <div class="row">
        <!-- Total livraisons -->
        <div class="col-12 col-sm-6 col-lg-3 mt-3">
            <div class="card shadow bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Livraisons totales</h6>
                    <span class="h1 font-weight-bold mb-0" style="font-size: 130%;">{{ $totalLivraisons }}</span>
                </div>
            </div>
        </div>

        <!-- Livrées avec succès -->
        <div class="col-12 col-sm-6 col-lg-3 mt-3">
            <div class="card shadow bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Livrées avec succès</h6>
                    <span class="h1 font-weight-bold mb-0" style="font-size: 130%;">{{ $livrees }}</span>
                </div>
            </div>
        </div>

        <!-- Échouées / en attente -->
        <div class="col-12 col-sm-6 col-lg-3 mt-3">
            <div class="card shadow bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Échouées / en attente</h6>
                    <span class="h1 font-weight-bold mb-0" style="font-size: 130%;">{{ $echouees }}</span>
                </div>
            </div>
        </div>

        <!-- Montant total encaissé -->
        <div class="col-12 col-sm-6 col-lg-3 mt-3">
            <div class="card shadow bg-dark text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Montant encaissé</h6>
                    <span class="h1 font-weight-bold mb-0" style="font-size: 130%;">{{ $montantTotal }} F CFA</span>
                </div>
            </div>
        </div>

        <!-- Frais de livraison -->
        <div class="col-12 col-sm-6 col-lg-3 mt-4">
            <div class="card shadow bg-info text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Frais de livraison encaissés</h6>
                    <span class="h1 font-weight-bold mb-0" style="font-size: 130%;">{{ $fraisLivraison }} F CFA</span>
                </div>
            </div>
        </div>
        <div class="card shadow mt-4">
            <div class="card-body">
                @if (Session::has('messages'))
                    <div class="alert alert-success">
                        <strong>{{ session('messages') }}</strong>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover w-100" id="example">
                        <thead class="thead-light">
                            <tr>
                                <th>N°</th>
                                <th>Code</th>
                                <th>Nom/Téléphone (Client)</th>
                                <th>Adresse</th>
                                <th>Sous total</th>
                                <th>Frais</th>
                                <th>Total</th>
                                <th>État</th>
                                <th>Produits</th>
                                <th> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="text-dark">{{ $i++ }}</td>
                                    <td class="text-dark">{{ $order->code }}</td>
                                    <td class="text-dark">
                                        {{ $order->costumer->name ?? '-' }} | {{ $order->costumer->phone ?? '-' }}
                                    </td>
                                    <td class="text-dark">{{ $order->costumer->adresse }} F</td>
                                    <td class="text-dark">{{ $order->subtotal }} F</td>
                                    <td class="text-dark">{{ $order->tax }} F</td>
                                    <td class="text-dark">{{ $order->total }} F</td>
                                    <td>
                                        @if ($order->status == 'ordered')
                                            <span class="badge badge-warning">En cours</span>
                                        @elseif($order->status == 'delivered')
                                            <span class="badge badge-success">Terminé</span>
                                        @elseif ($order->status == 'canceled')
                                            <span class="badge badge-danger">Annulé</span>
                                        @endif
                                    </td>
                                    <td class="text-dark">
                                        @foreach ($order->orderItems as $items)
                                            <h6>({{ $items->quantity }}) {{ $items->product->name }}</h6>
                                        @endforeach
                                    </td>
                                    <td class="text-dark">
                                        <a href="{{ route('livrableshow', $order) }}" style="color: white"
                                            type="button" class="btn btn-success">
                                            <i class="material-icons f-16">visibility</i>Details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="thead-light">
                            <tr>
                                <th colspan="4" class="text-right">Totaux :</th>
                                <th class="text-danger font-weight-bold">{{ $orders->sum('subtotal') }} F</th>
                                <th class="text-danger font-weight-bold">{{ $orders->sum('tax') }} F</th>
                                <th class="text-danger font-weight-bold">{{ $orders->sum('total') }} F</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
