<div>
    <div class=" mt-3 ">
        <strong>
            Bienvenue {{ Auth::user()->name }}, ravi de vous revoir parmi nous !
        </strong>
    </div>
    <div class="row">
        {{-- Nombre de livraisons effectuées --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card shadow bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Livraisons effectuées</h6>
                    <span style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $totalLivraisons }}</span>
                </div>
            </div>
        </div>

        {{-- Livrées avec succès --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card shadow bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Livrées avec succès</h6>
                    <span style="font-size: 130%" class="h1 font-weight-bold mb-0">
                        {{ $livrees }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Commandes échouées ou en attente --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card shadow bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Échouées / en attente</h6>
                    <span style="font-size: 130%" class="h1 font-weight-bold mb-0">
                        {{ $echouees }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Montant total encaissé --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card shadow bg-dark text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Montant encaissé</h6>
                    <span style="font-size: 130%" class="h1 font-weight-bold mb-0"> {{ $montantTotal }} F
                        CFA</span>
                </div>
            </div>
        </div>

        {{-- Frais de livraison total encaissé --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3 mt-4">
            <div class="card shadow bg-info text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1">Frais de livraison encaissés</h6>
                    <span style="font-size: 130%" class="h1 font-weight-bold mb-0"> {{ $fraisLivraison }}F
                        CFA</span>
                </div>

            </div>

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
                            <th style="width: 20%">Code</th>
                            <th style="width: 20%">Nom/Téléphone (Client)</th>
                            <th style="width: 20%">Adresse</th>
                            <th style="width: 20%">Sous total</th>
                            <th style="width: 20%">Frais</th>
                            <th style="width: 20%">Total</th>
                            <th style="width: 20%">Etat</th>
                            <th style="width: 20%">Produits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $i = 1;
                        @endphp
                        @foreach ($orders as $order)
                            <tr>

                                <td style="color: black ">{{ $i++ }}</td>
                                <td style="color: black ">{{ $order->code }}</td>
                                <td style="color: black ">{{ $order->costumer->name ?? '-' }}|
                                    {{ $order->costumer->phone ?? '-' }}</td>

                                <td style="color: black ">{{ $order->costumer->adresse }} F</td>
                                <td style="color: black ">{{ $order->subtotal }} F</td>
                                <td style="color: black ">{{ $order->tax }} F </td>
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

                                    @foreach ($order->orderItems as $items)
                                        <h5>({{ $items->quantity }}) {{ $items->product->name }} ,</h5>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                    <tfoot class="thead-light">
                        <tr>
                            <th style="width: 20%">N°</th>
                            <th style="width: 20%">Code</th>
                            <th style="width: 20%">Nom/Téléphone (Client)</th>
                            <th style="width: 20%">Adresse </th>
                            <th style="width: 20% ;color: black">Sous total</th>
                            <th style="width: 20% ;color: black">Total des frais</th>
                            <th style="width: 20%;color: black">Total</th>
                            <th style="width: 20%">Etat</th>
                            <th style="width: 20%">Produit</th>
                        </tr>
                        <th style="width: 20%"></th>
                        <th style="width: 20%"></th>
                        <th style="width: 20%"></th>

                        <th style="width: 20%"></th>
                        <th style="width: 20% ; color:red ;font-weight: 900">{{ $orders->sum('subtotal') }} F</th>
                        <th style="width: 20% ; color:red ;font-weight: 900">{{ $orders->sum('tax') }} F</th>
                        <th style="width: 20% ; color:red ;font-weight: 900">{{ $orders->sum('total') }} F</th>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
