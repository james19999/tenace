<div>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                <i class="bi bi-box-seam"></i>
                Commandes
            </h4>

            <small class="text-muted">
                Commandes prévues pour livraison
            </small>
        </div>

        <button wire:click="today" class="btn btn-outline-primary">
            <i class="bi bi-calendar-day"></i>
            Aujourd'hui
        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- NAVIGATION DES JOURS --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex align-items-center gap-2">

                {{-- Jour précédent --}}
                <button wire:click="previousDay" class="btn btn-light border" title="Jour précédent">
                    <i class="bi bi-chevron-left">
                        Jour précédent
                    </i>
                </button>


                {{-- DATES --}}
                <div class="d-flex gap-2 flex-grow-1 overflow-auto" style="scrollbar-width: thin;">

                    @foreach ($dates as $date)
                        @php
                            $dateValue = $date->date;
                        @endphp

                        <button wire:click="selectDate('{{ $dateValue }}')"
                            class="btn
                            {{ $selectedDate === $dateValue ? 'btn-primary' : 'btn-outline-secondary' }}"
                            style="min-width: 120px;">

                            <div class="small">
                                {{ \Carbon\Carbon::parse($dateValue)->translatedFormat('D') }}
                            </div>

                            <strong>
                                {{ \Carbon\Carbon::parse($dateValue)->format('d/m') }}
                            </strong>

                            <span
                                class="badge
                                {{ $selectedDate === $dateValue ? 'bg-white text-primary' : 'bg-secondary' }}">
                                {{ $date->total_orders }}
                            </span>

                        </button>
                    @endforeach

                </div>


                {{-- Jour suivant --}}
                <button wire:click="nextDay" class="btn btn-light border" title="Jour suivant">
                    <i class="bi bi-chevron-right">
                        Jour suivant
                    </i>
                </button>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- DATE SELECTIONNÉE --}}
    {{-- ========================================================= --}}

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h5 class="mb-0">

                <i class="bi bi-calendar3 text-primary"></i>

                {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l d F Y') }}

            </h5>

        </div>

        <div>

            <span class="badge bg-primary fs-6">

                {{ $selectedDateCount }}

                {{ $selectedDateCount > 1 ? 'commandes' : 'commande' }}

            </span>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- LOADING --}}
    {{-- ========================================================= --}}

    <div wire:loading class="text-center py-3">

        <div class="spinner-border text-primary" role="status">

            <span class="visually-hidden">
                Chargement...
            </span>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- LISTE DES COMMANDES --}}
    {{-- ========================================================= --}}

    <div wire:loading.remove>

        @forelse($orders as $order)
            <div class="card shadow-sm border-0 mb-3">

                <div class="card-body">

                    <div class="row align-items-center">


                        {{-- ========================================= --}}
                        {{-- INFORMATIONS COMMANDE --}}
                        {{-- ========================================= --}}

                        <div class="col-md-4">

                            <div class="d-flex align-items-center">


                                <div>

                                    <h5 class="mb-1">

                                        Commande
                                        #{{ $order->id }}

                                    </h5>

                                    <small class="text-muted">

                                        Créée le

                                        {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}

                                    </small>

                                </div>

                            </div>

                        </div>


                        {{-- ========================================= --}}
                        {{-- CLIENT --}}
                        {{-- ========================================= --}}

                        <div class="col-md-3">

                            <small class="text-muted d-block">
                                Client
                            </small>

                            <strong>

                                @if ($order->costumer)
                                    {{ $order->costumer->name }}
                                    {{ $order->costumer->phone }}
                                @else
                                    Client #{{ $order->costumer_id }}
                                @endif

                            </strong>

                        </div>


                        {{-- ========================================= --}}
                        {{-- DATE / HEURE LIVRAISON --}}
                        {{-- ========================================= --}}

                        <div class="col-md-2">

                            <small class="text-muted d-block">
                                Livraison prévue
                            </small>

                            <strong class="text-primary">

                                <i class="bi bi-clock"></i>

                                {{ \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') }}

                            </strong>

                        </div>


                        {{-- ========================================= --}}
                        {{-- TOTAL --}}
                        {{-- ========================================= --}}

                        <div class="col-md-2">

                            <small class="text-muted d-block">
                                Total
                            </small>

                            <strong class="fs-5">

                                {{ number_format($order->total ?? 0, 2, ',', ' ') }}

                            </strong>

                        </div>


                        {{-- ========================================= --}}
                        {{-- ACTION --}}
                        {{-- ========================================= --}}

                        <div class="col-md-1 text-end">
                            @if ($order->type === 'PR')
                                <button wire:click="changeType({{ $order->id }})" class="btn btn-sm btn-warning">
                                    PR → PU
                                </button>
                            @else
                                <span class="badge bg-success">
                                    {{ $order->type }}
                                </span>
                            @endif

                        </div>

                    </div>


                    {{-- ============================================= --}}
                    {{-- DEUXIÈME LIGNE --}}
                    {{-- ============================================= --}}

                    <hr>


                    <div class="row align-items-center">


                        {{-- STATUT --}}
                        <div class="col-md-3">

                            <small class="text-muted me-2">
                                Statut :
                            </small>

                            @php

                                $statusClass = match ($order->status) {
                                    'pending' => 'bg-warning text-dark',

                                    'confirmed' => 'bg-info',

                                    'ready' => 'bg-primary',

                                    'delivered' => 'bg-success',

                                    'cancelled' => 'bg-danger',

                                    default => 'bg-secondary',
                                };

                            @endphp

                            <span class="badge {{ $statusClass }}">

                                {{ ucfirst($order->status) }}

                            </span>

                        </div>


                        {{-- SUBTOTAL --}}
                        <div class="col-md-3">

                            <small class="text-muted">
                                Sous-total :
                            </small>

                            <strong>

                                {{ number_format($order->subtotal ?? 0, 2, ',', ' ') }}

                            </strong>

                        </div>


                        {{-- MONTANT --}}
                        <div class="col-md-3">

                            <small class="text-muted">
                                Montant :
                            </small>

                            <strong>

                                {{ number_format($order->montant ?? 0, 2, ',', ' ') }}

                            </strong>

                        </div>


                        {{-- CRÉÉ PAR --}}
                        <div class="col-md-3 text-md-end">

                            <small class="text-muted">
                                Créée par :
                            </small>

                            <strong>

                                @if ($order->createduser)
                                    {{ $order->createduser->name }}
                                @else
                                    -
                                @endif

                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            {{-- ============================================= --}}
            {{-- AUCUNE COMMANDE --}}
            {{-- ============================================= --}}

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center py-5">

                    <i class="bi bi-calendar-x
                               display-4 text-muted"></i>

                    <h5 class="mt-3">
                        Aucune commande
                    </h5>

                    <p class="text-muted mb-0">

                        Aucune commande n'est prévue pour cette date.

                    </p>

                </div>

            </div>
        @endforelse


        {{-- ========================================================= --}}
        {{-- PAGINATION --}}
        {{-- ========================================================= --}}

        @if ($orders->hasPages())
            <div class="mt-4">

                {{ $orders->links() }}

            </div>
        @endif

    </div>

</div>
