<div wire:poll.1m>

    @if ($lateOrdersCount > 0 || $todayOrdersCount > 0)
        <a href="{{ route('order-liste-order') }}">

            <div class="m-2">

                {{-- COMMANDES EN RETARD --}}

                @if ($lateOrdersCount > 0)
                    <div class="alert alert-danger d-flex align-items-center shadow-sm mb-2" role="alert">

                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>

                        <div>

                            <strong>Attention !</strong>

                            {{ $lateOrdersCount }}

                            {{ $lateOrdersCount > 1 ? 'commandes sont en retard.' : 'commande est en retard.' }}

                        </div>

                    </div>
                @endif


                {{-- COMMANDES PRÉVUES AUJOURD'HUI --}}

                @if ($todayOrdersCount > 0)
                    <div class="alert alert-warning d-flex align-items-center shadow-sm mb-2" role="alert">

                        <i class="bi bi-calendar-event fs-4 me-3"></i>

                        <div>

                            <strong>Livraisons aujourd'hui :</strong>

                            {{ $todayOrdersCount }}

                            {{ $todayOrdersCount > 1 ? 'commandes sont prévues aujourd’hui.' : 'commande est prévue aujourd’hui.' }}

                        </div>

                    </div>
                @endif

            </div>
        </a>

    @endif

</div>
