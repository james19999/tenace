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
                            {{ $selectedDate === $dateValue ? 'btn-primary' : 'bg-success' }}"
                            style="min-width: 120px; margin: 1%;">

                            <div class="small">
                                {{ \Carbon\Carbon::parse($dateValue)->translatedFormat('D') }}
                            </div>

                            <strong>
                                {{ \Carbon\Carbon::parse($dateValue)->format('d/m') }}
                            </strong>

                            <span
                                class="badge
                                {{ $selectedDate === $dateValue ? 'bg-danger text-primary' : 'bg-success' }}">
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

            <div class="card shadow-sm border-0 mb-4">

                {{-- ================================================= --}}
                {{-- HEADER COMMANDE --}}
                {{-- ================================================= --}}

                <div class="card-header bg-white border-0 py-3">

                    <div class="row align-items-center">

                        {{-- Numéro commande --}}
                        <div class="col-md-4">

                            <div class="d-flex align-items-center">



                                <div>

                                    <h5 class="mb-1">
                                        Commande #{{ $order->id }}
                                    </h5>

                                    <small class="text-muted">

                                        Créée le

                                        {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}

                                    </small>

                                </div>

                            </div>

                        </div>


                        {{-- CLIENT --}}
                        <div class="col-md-3">

                            <small class="text-muted d-block">
                                <i class="bi bi-person"></i>
                                Client
                            </small>

                            @if ($order->costumer)
                                <strong class="d-block">
                                    {{ $order->costumer->name }}
                                </strong>

                                @if ($order->costumer->phone)
                                    <a href="tel:{{ $order->costumer->phone }}" class="text-decoration-none">
                                        <i class="bi bi-telephone"></i>
                                        {{ $order->costumer->phone }}
                                    </a>
                                @endif
                            @else
                                <strong>
                                    Client #{{ $order->costumer_id }}
                                </strong>
                            @endif

                        </div>


                        {{-- DATE LIVRAISON --}}
                        <div class="col-md-3">

                            <small class="text-muted d-block">
                                <i class="bi bi-calendar-event"></i>
                                Livraison prévue
                            </small>

                            <strong class="text-primary">

                                {{ \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') }}

                                à

                                {{ \Carbon\Carbon::parse($order->time)->format('H:i') }}

                            </strong>

                        </div>


                        {{-- TYPE --}}
                        {{-- <div class="col-md-2 text-md-end">

                            @if ($order->type === 'PR')
                                <button type="button" wire:click="openChangeTypeModal({{ $order->id }})"
                    class="btn btn-sm btn-warning">
                    PR → PU
                    </button>
                    @else
                    <span class="badge bg-success">
                        {{ $order->type }}
                    </span>
                    @endif

                </div> --}}

                        <div class="col-md-2 text-end">

                            <button type="button" wire:click="openAssignModal({{ $order->id }})"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-badge"></i>

                                @if ($order->user_id)
                                    Modifier livreur
                                @else
                                    Attribuer livreur
                                @endif
                            </button>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- PRODUITS --}}
                {{-- ================================================= --}}

                <div class="card-body pt-0">

                    <hr>

                    <div wire:click="toggleText" class="d-flex justify-content-between align-items-center mb-3">

                        <h6 class="mb-0">

                            <i class="bi bi-cart3 text-primary"></i>

                            Produits

                        </h6>

                        <span class="badge bg-light text-dark">

                            {{ $order->orderItems->count() }}

                            {{ $order->orderItems->count() > 1 ? 'produits' : 'produit' }}

                        </span>

                    </div>


                    {{-- TABLE PRODUITS --}}
                    @if ($showText)
                        <div class="table-responsive">

                            <table class="table table-sm align-middle mb-0">

                                <thead class="table-light">

                                    <tr>

                                        <th>
                                            Produit
                                        </th>

                                        <th class="text-center">
                                            Quantité
                                        </th>

                                        <th class="text-end">
                                            Prix
                                        </th>

                                        <th class="text-end">
                                            Total
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($order->orderItems as $item)
                                        <tr>

                                            <td>

                                                @if ($item->product)
                                                    <strong>
                                                        {{ $item->product->name }}
                                                    </strong>
                                                @else
                                                    Produit #{{ $item->product_id }}
                                                @endif

                                            </td>


                                            <td class="text-center">

                                                <span class="badge bg-secondary">

                                                    {{ $item->quantity }}

                                                </span>

                                            </td>


                                            <td class="text-end">

                                                {{ number_format($item->price ?? 0, 2, ',', ' ') }} XOF

                                            </td>


                                            <td class="text-end fw-bold">

                                                {{ number_format(($item->quantity ?? 0) * ($item->price ?? 0), 2, ',', ' ') }}
                                                XOF

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td colspan="4" class="text-center text-muted py-3">

                                                Aucun produit dans cette commande.

                                            </td>

                                        </tr>
                                    @endforelse

                                </tbody>

                            </table>

                        </div>



                        {{-- ================================================= --}}
                        {{-- INFORMATIONS FINANCIÈRES --}}
                        {{-- ================================================= --}}

                        <div class="row mt-4">

                            <div class="col-md-3">

                                <small class="text-muted d-block">
                                    Sous-total
                                </small>

                                <strong>

                                    {{ number_format($order->subtotal ?? 0, 2, ',', ' ') }} XOF

                                </strong>

                            </div>


                            <div class="col-md-3">

                                <small class="text-muted d-block">
                                    Montant
                                </small>

                                <strong>

                                    {{ number_format($order->montant ?? 0, 2, ',', ' ') }} XOF

                                </strong>

                            </div>


                            <div class="col-md-3">

                                <small class="text-muted d-block">
                                    Total
                                </small>

                                <strong class="fs-5 text-primary">

                                    {{ number_format($order->total ?? 0, 2, ',', ' ') }} XOF

                                </strong>

                            </div>


                            <div class="col-md-3">

                                <small class="text-muted d-block">
                                    Statut
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

                        </div>
                    @endif

                </div>


                {{-- ================================================= --}}
                {{-- FOOTER ACTIONS --}}
                {{-- ================================================= --}}

                <div class="card-footer bg-light border-0">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">

                                Créée par :

                                <strong>

                                    {{ $order->createduser?->name ?? '-' }}

                                </strong>

                            </small>

                        </div>
                        <div>

                            <small class="text-muted">

                                Livreur :

                                <strong>

                                    @if ($order->user)
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-person"></i>
                                            {{ $order->user->name }}
                                        </div>
                                    @endif

                                </strong>

                            </small>

                        </div>


                        <div class="d-flex gap-2">


                            {{-- DETAILS --}}

                            <a href="{{ route('edit-order', $order->id) }}" type="button"
                                class="btn btn-sm btn-outline-primary">

                                <i class="bi bi-eye"></i>

                                Modifier la commande
                            </a>


                            {{-- MODIFIER DATE --}}

                            <button type="button" wire:click="openEditModal({{ $order->id }})"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar-event"></i>
                                Modifier la livraison
                            </button>


                            {{-- SUPPRIMER --}}

                            <button type="button" wire:click="openDeleteModal({{ $order->id }})"
                                class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                                Supprimer
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center py-5">

                    <i class="bi bi-calendar-x display-4 text-muted"></i>

                    <h5 class="mt-3">
                        Aucune commande
                    </h5>

                    <p class="text-muted mb-0">

                        Aucune commande n'est prévue pour cette date.

                    </p>

                </div>

            </div>

        @endforelse


        {{-- PAGINATION --}}

        @if ($orders->hasPages())
            <div class="mt-4">

                {{ $orders->links() }}

            </div>
        @endif
        @if ($showEditModal)
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true">

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content">

                        {{-- HEADER --}}

                        <div class="modal-header">

                            <h5 class="modal-title">

                                <i class="bi bi-calendar-event text-primary"></i>

                                Modifier la livraison

                            </h5>

                            <button type="button" class="btn-close" wire:click="closeEditModal"></button>

                        </div>


                        {{-- BODY --}}

                        <div class="modal-body">

                            <div class="alert alert-info">

                                <i class="bi bi-info-circle"></i>

                                Vous pouvez uniquement modifier
                                la date et l'heure de livraison.

                            </div>


                            {{-- DATE --}}

                            <div class="mb-3">

                                <label class="form-label">
                                    Date de livraison
                                </label>

                                <input type="date" wire:model="editDate"
                                    class="form-control @error('editDate') is-invalid @enderror">

                                @error('editDate')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- HEURE --}}

                            <div class="mb-3">

                                <label class="form-label">
                                    Heure de livraison
                                </label>

                                <input type="time" wire:model="editTime"
                                    class="form-control @error('editTime') is-invalid @enderror">

                                @error('editTime')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- FOOTER --}}

                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" wire:click="closeEditModal">
                                Annuler
                            </button>

                            <button type="button" class="btn btn-primary" wire:click="updateDeliveryDate"
                                wire:loading.attr="disabled">

                                <span wire:loading.remove wire:target="updateDeliveryDate">

                                    <i class="bi bi-check-circle"></i>

                                    Enregistrer

                                </span>

                                <span wire:loading wire:target="updateDeliveryDate">

                                    <span class="spinner-border spinner-border-sm"></span>

                                    Enregistrement...

                                </span>

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            {{-- BACKDROP --}}

            <div class="modal-backdrop fade show"></div>
        @endif
    </div>
    @if ($showTypeModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <div class="modal-header bg-warning">

                        <h5 class="modal-title">

                            <i class="bi bi-exclamation-triangle"></i>

                            Confirmation

                        </h5>

                        <button type="button" class="btn-close" wire:click="$set('showTypeModal', false)"></button>

                    </div>


                    <div class="modal-body text-center">

                        <i class="bi bi-arrow-repeat text-warning" style="font-size:50px;"></i>

                        <h5 class="mt-3">
                            Changer le type de la commande ?
                        </h5>

                        <p class="text-muted">

                            Voulez-vous vraiment changer

                            <strong>PR</strong>

                            en

                            <strong>PU</strong> ?

                        </p>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" wire:click="$set('showTypeModal', false)">
                            Annuler
                        </button>

                        <button type="button" wire:click="confirmChangeType" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i>
                            Oui, changer en PU
                        </button>

                    </div>

                </div>

            </div>

        </div>

        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showDeleteModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    {{-- HEADER --}}

                    <div class="modal-header bg-danger text-white">

                        <h5 class="modal-title">

                            <i class="bi bi-exclamation-triangle"></i>

                            Confirmation de suppression

                        </h5>

                        <button type="button" class="btn-close btn-close-white"
                            wire:click="closeDeleteModal"></button>

                    </div>


                    {{-- BODY --}}

                    <div class="modal-body text-center">

                        <div class="mb-3">

                            <i class="bi bi-trash3 text-danger" style="font-size: 60px;"></i>

                        </div>


                        <h5>
                            Supprimer cette commande ?
                        </h5>


                        <p class="text-muted mb-0">

                            Êtes-vous sûr de vouloir supprimer

                            <strong>
                                la commande #{{ $deleteOrderId }}
                            </strong>

                            ?

                        </p>


                        <div class="alert alert-warning mt-3 mb-0">

                            <i class="bi bi-info-circle"></i>

                            Cette action est irréversible.

                        </div>

                    </div>


                    {{-- FOOTER --}}

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">
                            <i class="bi bi-x-circle"></i>
                            Annuler
                        </button>


                        <button type="button" class="btn btn-danger" wire:click="deleteOrder"
                            wire:loading.attr="disabled">

                            <span wire:loading.remove wire:target="deleteOrder">

                                <i class="bi bi-trash"></i>

                                Oui, supprimer

                            </span>


                            <span wire:loading wire:target="deleteOrder">

                                <span class="spinner-border spinner-border-sm"></span>

                                Suppression...

                            </span>

                        </button>

                    </div>

                </div>

            </div>

        </div>


        {{-- BACKDROP --}}

        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showAssignModal)

        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    {{-- HEADER --}}

                    <div class="modal-header">

                        <h5 class="modal-title">

                            <i class="bi bi-person-badge text-primary"></i>

                            Attribution du livreur

                        </h5>

                        <button type="button" class="btn-close" wire:click="closeAssignModal"></button>

                    </div>


                    {{-- BODY --}}

                    <div class="modal-body">

                        <div class="alert alert-info">

                            <i class="bi bi-info-circle"></i>

                            Sélectionnez le livreur auquel vous souhaitez
                            attribuer cette commande.

                        </div>


                        <div class="mb-3">

                            <label class="form-label fw-bold">

                                Livreur

                            </label>


                            <select wire:model="selectedLivreur"
                                class="form-select  form-control @error('selectedLivreur') is-invalid @enderror">

                                <option value="">
                                    -- Sélectionner un livreur --
                                </option>

                                @foreach ($livreurs as $livreur)
                                    <option value="{{ $livreur->id }}">

                                        {{ $livreur->name }}

                                        @if ($livreur->phone)
                                            - {{ $livreur->phone }}
                                        @endif

                                    </option>
                                @endforeach

                            </select>


                            @error('selectedLivreur')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- FOOTER --}}

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" wire:click="closeAssignModal">

                            <i class="bi bi-x-circle"></i>

                            Annuler

                        </button>


                        <button type="button" class="btn btn-primary" wire:click="assignOrder"
                            wire:loading.attr="disabled">

                            <span wire:loading.remove wire:target="assignOrder">

                                <i class="bi bi-check-circle"></i>

                                Valider l'attribution

                            </span>


                            <span wire:loading wire:target="assignOrder">

                                <span class="spinner-border spinner-border-sm"></span>

                                Attribution...

                            </span>

                        </button>

                    </div>

                </div>

            </div>

        </div>


        {{-- BACKDROP --}}

        <div class="modal-backdrop fade show">

        </div>

    @endif
</div>
