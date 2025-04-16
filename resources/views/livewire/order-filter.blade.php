<div class="p-4">

    {{--  <div class="mb-4">
        <label for="phone">Téléphone du client:</label>
        <input type="text" wire:model="phone" id="phone" class="border p-2 w-full" placeholder="Ex: +2250700000000">
    </div>
    <div class="mb-4">
        <label for="month">Mois:</label>
        <input type="month" wire:model="month" id="month" class="border p-2 w-full">
    </div>  --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <label for="phone" class="form-label">Téléphone du client:</label>
            <input type="text" wire:model="phone" id="phone" class="form-control" placeholder="Ex: +2250700000000">
        </div>

        <div class="col-md-6">
            <label for="month" class="form-label">Mois:</label>
            <input type="month" wire:model="month" id="month" class="form-control">
        </div>
    </div>



    @if ($orders && count($orders))
        <h3 class="text-lg font-bold mt-4">Commandes trouvées :</h3>
        <div class="row mt-4">
            @foreach ($orders as $order)
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Commande #{{ $order->id }}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">Client : {{ $order->costumer->name ?? '' }}</h6>
                            <p class="card-text"><strong>Date :</strong> {{ $order->created_at->format('d/m/Y') }}</p>
                            <p class="card-text"><strong>Total :</strong> {{ $order->total }} XOF</p>

                            <div class="mt-3">
                                <p class="fw-semibold mb-1">Produits :</p>
                                <ul class="ps-3">
                                    @foreach ($order->orderItems as $item)
                                        <li>{{ $item->product->name ?? '' }} x ({{ $item->quantity }})</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($phone && $month)
        <p class="text-gray-500">Aucune commande trouvée pour ce client ce mois-ci.</p>
    @endif
</div>
