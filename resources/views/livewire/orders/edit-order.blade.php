<div>



    <div class="card shadow">

        <div class="card-header">
            <h4>Modification de la commande #{{ $order->code }}: {{ $order->costumer->name }} </h4>
        </div>

        <div class="card-body">

            <div class="row mb-4">

                <div class="col-md-4">
                    <label>Frais de livraison</label>

                    <input type="number" class="form-control" wire:model.live="tax">
                </div>

                <div class="col-md-4">
                    <label>Remise (%)</label>

                    <input type="number" class="form-control" wire:model.live="remis">
                </div>

            </div>
            <div class="row mb-3">

                <div class="col-md-6">

                    <label>Rechercher un produit</label>

                    <input type="text" class="form-control" placeholder="Nom ..." wire:model.debounce.300ms="search">

                </div>

            </div>
            @if (count($products))

                <div class="card mb-3">

                    <div class="card-header">

                        Produits trouvés

                    </div>

                    <div class="card-body p-0">

                        <table class="table table-hover mb-0">

                            <thead>

                                <tr>

                                    <th>Nom</th>

                                    <th>Prix</th>

                                    <th>Stock</th>

                                    <th></th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($products as $product)
                                    <tr>

                                        <td>{{ $product->name }}</td>

                                        <td>{{ number_format($product->price) }} FCFA</td>

                                        <td>{{ $product->qt_initial }}</td>

                                        <td>

                                            <button class="btn btn-success btn-sm"
                                                wire:click="addProduct({{ $product->id }})">

                                                Ajouter

                                            </button>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif
            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Produit</th>

                            <th width="140">Prix</th>

                            <th width="180">Quantité</th>

                            <th>Total</th>

                            <th width="100">Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($items as $index=>$item)
                            <tr>

                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $item['name'] }}</td>

                                <td>

                                    <input type="number" class="form-control"
                                        wire:model.live="items.{{ $index }}.price">

                                </td>

                                <td>

                                    <div class="btn-group">

                                        <button class="btn btn-danger" wire:click="decrement({{ $index }})">

                                            -

                                        </button>

                                        <input type="number" class="form-control text-center" style="width:70px"
                                            min="1" wire:model.live="items.{{ $index }}.quantity">

                                        <button class="btn btn-success" wire:click="increment({{ $index }})">

                                            +

                                        </button>

                                    </div>

                                </td>

                                <td>

                                    {{ number_format($item['price'] * $item['quantity']) }} FCFA

                                </td>

                                <td>

                                    <button class="btn btn-danger btn-sm" wire:click="remove({{ $index }})">

                                        <i class="fa fa-trash">Supprimer</i>

                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center">

                                    Aucun produit

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

            <hr>

            <div class="row">

                <div class="col-md-4 offset-md-8">

                    <table class="table">

                        <tr>

                            <th>Sous-total</th>

                            <td>{{ number_format($subtotal) }} FCFA</td>

                        </tr>

                        <tr>

                            <th>Remise</th>

                            <td>{{ $remis }} %</td>

                        </tr>

                        <tr>

                            <th>Livraison</th>

                            <td>{{ number_format($tax) }} FCFA</td>

                        </tr>

                        <tr>

                            <th>Total</th>

                            <td>

                                <strong>

                                    {{ number_format($total) }} FCFA

                                </strong>

                            </td>

                        </tr>

                        <tr>

                            <th>Montant à payer</th>

                            <td>

                                <strong class="text-success">

                                    {{ number_format($montant) }} FCFA

                                </strong>

                            </td>

                        </tr>

                    </table>

                </div>

            </div>

        </div>
        <div class="m-5">
            @if (session()->has('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
        </div>
        <div class="card-footer text-right">

            <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">

                <span wire:loading.remove>
                    Enregistrer les modifications
                </span>

                <span wire:loading>
                    Sauvegarde...
                </span>

            </button>

        </div>

    </div>

</div>
