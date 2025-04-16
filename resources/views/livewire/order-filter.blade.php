<div class="p-4">

    <div class="mb-4">
        <label for="phone">Téléphone du client:</label>
        <input type="text" wire:model="phone" id="phone" class="border p-2 w-full" placeholder="Ex: +2250700000000">
    </div>
    <div class="mb-4">
        <label for="month">Mois:</label>
        <input type="month" wire:model="month" id="month" class="border p-2 w-full">
    </div>


    @if ($orders && count($orders))
        <h3 class="text-lg font-bold mt-4">Commandes trouvées :</h3>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($orders as $order)
                <div class="p-4 border rounded shadow bg-white">
                    <p class="font-semibold text-lg">Commande #{{ $order->id }}</p>
                    <p class="text-sm text-gray-600">Date : {{ $order->created_at->format('d/m/Y') }}</p>

                    <div class="mt-2">
                        <p class="font-medium">Produits :</p>
                        <ul class="list-disc ml-5 mt-1 text-sm text-gray-700">
                            @foreach ($order->orderItems as $item)
                                <li>{{ $item->product->name ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($phone && $month)
        <p class="text-gray-500">Aucune commande trouvée pour ce client ce mois-ci.</p>
    @endif
</div>
