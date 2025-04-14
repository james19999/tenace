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
        <ul class="mt-2 space-y-4">
            @foreach ($orders as $order)
                <li class="p-4 border rounded">
                    <p><strong>Commande #{{ $order->id }}</strong></p>
                    <p>Date: {{ $order->created_at->format('d/m/Y') }}</p>
                    <p>Commande:</p>
                    <ul class="ml-4 list-disc">
                        @foreach ($order->orderItems as $item)
                            <li>{{ $item->name }} - {{ $item->quantity }}x</li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    @elseif($phone && $month)
        <p class="text-gray-500">Aucune commande trouvée pour ce client ce mois-ci.</p>
    @endif
</div>
