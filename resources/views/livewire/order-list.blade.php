<div>

    <style>
        .date-scroll-container {
            display: flex;
            overflow-x: auto;
            padding: 4px 2px 8px 2px;
            scrollbar-width: thin;
        }
        .date-scroll-container::-webkit-scrollbar {
            height: 6px;
        }
        .date-scroll-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
        .date-chip {
            min-width: 110px;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #4a5568;
            text-align: center;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            flex-shrink: 0;
            margin-right: 8px;
            outline: none !important;
        }
        .date-chip:hover {
            border-color: #cbd5e0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }
        .date-chip.active {
            background: #7e1615;
            border-color: #7e1615;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(126, 22, 21, 0.35);
        }
        .date-chip.active .date-chip-day,
        .date-chip.active .date-chip-date {
            color: #ffffff !important;
        }
        .date-chip-day {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            color: #a0aec0;
            letter-spacing: 0.5px;
        }
        .date-chip-date {
            font-size: 15px;
            font-weight: 700;
            color: #2d3748;
            margin: 2px 0;
        }
        .date-chip-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .order-card {
            border-radius: 12px;
            border: 1px solid #edf2f7;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 22px;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }
        .order-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }
        .order-card-header {
            background: #fcfcfc;
            border-bottom: 1px solid #f0f0f0;
            padding: 14px 20px;
        }
        .order-card-body {
            padding: 18px 20px;
        }
        .order-card-footer {
            background: #fdfdfd;
            border-top: 1px solid #f0f0f0;
            padding: 12px 20px;
        }
        .pill-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .pill-time {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .pill-driver-assigned {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .pill-driver-none {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .finance-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
        }
    </style>

    {{-- ========================================================= --}}
    {{-- EN-TÊTE PRINCIPAL --}}
    {{-- ========================================================= --}}
    <div class="row align-items-center mb-3">
        <div class="col-md-6 mb-2 mb-md-0">
            <h4 class="mb-1 text-dark font-weight-bold">
                <i class="material-icons text-primary" style="vertical-align: middle;">local_shipping</i>
                Gestion des livraisons
            </h4>
            <small class="text-muted">
                Commandes prévues pour la livraison organisées par date
            </small>
        </div>

        <div class="col-md-6 text-md-right d-flex justify-content-md-end align-items-center flex-wrap">
            {{-- Sélection de date directe --}}
            <div class="d-inline-flex align-items-center mr-2 mb-1">
                <span class="text-muted small mr-1 font-weight-bold">Date :</span>
                <input type="date" wire:model="selectedDate" class="form-control form-control-sm"
                    style="width: 150px; border-radius: 6px;">
            </div>

            <button wire:click="today" class="btn btn-sm btn-outline-primary mb-1">
                <i class="material-icons f-16" style="vertical-align: middle;">today</i>
                Aujourd'hui
            </button>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- BANDEAU DE NAVIGATION PAR DATE --}}
    {{-- ========================================================= --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body py-3 px-3">
            <div class="d-flex align-items-center">

                {{-- Bouton Jour Précédent --}}
                <button type="button" wire:click="previousDay" class="btn btn-primary d-flex align-items-center justify-content-center mr-2 px-3 shadow-sm font-weight-bold"
                    style="border-radius: 10px; height: 58px; min-width: 140px; font-size: 13px;" title="Jour précédent">
                    <i class="material-icons mr-1" style="font-size: 20px;">arrow_back</i>
                    <span>Jour précédent</span>
                </button>

                {{-- Liste défilable des dates --}}
                <div class="date-scroll-container flex-grow-1">
                    @forelse ($dates as $date)
                        @php
                            $dateValue = $date->date;
                            $carbonDate = \Carbon\Carbon::parse($dateValue);
                            $isActive = ($selectedDate === $dateValue);
                        @endphp

                        <button type="button" wire:click="selectDate('{{ $dateValue }}')"
                            class="date-chip {{ $isActive ? 'active' : '' }}">
                            <div class="date-chip-day">
                                {{ $carbonDate->translatedFormat('D') }}
                            </div>
                            <div class="date-chip-date">
                                {{ $carbonDate->format('d/m') }}
                            </div>
                            <span class="date-chip-badge badge {{ $isActive ? 'badge-light text-primary' : 'badge-secondary' }}">
                                {{ $date->total_orders }}
                            </span>
                        </button>
                    @empty
                        <div class="text-muted small py-2 px-3">
                            <i class="fa fa-info-circle mr-1"></i> Aucune date planifiée pour le moment.
                        </div>
                    @endforelse
                </div>

                {{-- Bouton Jour Suivant --}}
                <button type="button" wire:click="nextDay" class="btn btn-primary d-flex align-items-center justify-content-center ml-2 px-3 shadow-sm font-weight-bold"
                    style="border-radius: 10px; height: 58px; min-width: 140px; font-size: 13px;" title="Jour suivant">
                    <span>Jour suivant</span>
                    <i class="material-icons ml-1" style="font-size: 20px;">arrow_forward</i>
                </button>

            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- TITRE DE LA DATE SÉLECTIONNÉE ET COMPTEUR --}}
    {{-- ========================================================= --}}
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <div>
            <h5 class="mb-0 text-dark font-weight-bold">
                <i class="material-icons text-primary" style="vertical-align: middle;">event_note</i>
                {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l d F Y') }}
                @if ($selectedDate === now()->toDateString())
                    <span class="badge badge-success ml-2 font-weight-normal" style="font-size: 11px; vertical-align: middle;">
                        Aujourd'hui
                    </span>
                @endif
            </h5>
        </div>

        <div>
            <span class="badge badge-primary px-3 py-2 font-weight-bold" style="font-size: 13px; border-radius: 20px;">
                {{ $selectedDateCount }} {{ $selectedDateCount > 1 ? 'commandes' : 'commande' }}
            </span>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- INDICATEUR DE CHARGEMENT LIVEWIRE --}}
    {{-- ========================================================= --}}
    <div wire:loading class="text-center py-4 w-100">
        <div class="spinner-border text-primary" role="status" style="width: 2.2rem; height: 2.2rem;">
            <span class="sr-only">Chargement...</span>
        </div>
        <div class="text-muted small mt-2">Chargement des commandes...</div>
    </div>


    {{-- ========================================================= --}}
    {{-- LISTE DES COMMANDES --}}
    {{-- ========================================================= --}}
    <div wire:loading.remove>

        @forelse($orders as $order)

            <div class="order-card">

                {{-- ENTÊTE DE LA COMMANDE --}}
                <div class="order-card-header">
                    <div class="row align-items-center">

                        {{-- Réf / ID & Date de création --}}
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-dark mr-2" style="font-size: 13px; border-radius: 6px;">
                                    #{{ $order->id }}
                                </span>
                                <div>
                                    <strong class="text-dark d-block" style="font-size: 15px;">
                                        {{ $order->code ? $order->code : 'Commande #' . $order->id }}
                                    </strong>
                                    <small class="text-muted">
                                        <i class="fa fa-clock mr-1"></i>
                                        {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Client --}}
                        <div class="col-md-3 mb-2 mb-md-0">
                            <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                Client
                            </small>
                            @if ($order->costumer)
                                <div class="font-weight-bold text-dark text-truncate" title="{{ $order->costumer->name }}">
                                    <i class="material-icons f-16 text-muted" style="vertical-align: middle;">person</i>
                                    {{ $order->costumer->name }}
                                </div>
                                @if ($order->costumer->phone)
                                    <a href="tel:{{ $order->costumer->phone }}" class="text-primary small font-weight-bold">
                                        <i class="fa fa-phone-alt fa-xs mr-1"></i>{{ $order->costumer->phone }}
                                    </a>
                                @endif
                            @else
                                <span class="text-muted">Client #{{ $order->costumer_id }}</span>
                            @endif
                        </div>

                        {{-- Date & Heure de livraison prévues --}}
                        <div class="col-md-3 mb-2 mb-md-0">
                            <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                Date & Heure de livraison
                            </small>
                            <div class="pill-badge pill-time font-weight-bold mt-1" style="font-size: 13px;">
                                <i class="material-icons f-16 mr-1" style="vertical-align: middle;">calendar_today</i>
                                {{ \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') }}
                                <span class="mx-1 text-muted">à</span>
                                <i class="material-icons f-16 mr-1" style="vertical-align: middle;">schedule</i>
                                {{ \Carbon\Carbon::parse($order->time)->format('H:i') }}
                            </div>
                        </div>

                        {{-- Livreur & Bouton Affecter un livreur --}}
                        <div class="col-md-3 text-md-right">
                            <small class="text-muted d-block text-uppercase font-weight-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                                Livreur
                            </small>
                            @if ($order->user)
                                <button type="button" wire:click="openAssignModal({{ $order->id }})"
                                    class="btn btn-sm btn-outline-success font-weight-bold shadow-sm"
                                    style="border-radius: 20px; padding: 4px 12px;"
                                    title="Cliquez pour changer de livreur">
                                    <i class="material-icons f-16 mr-1" style="vertical-align: middle;">delivery_dining</i>
                                    {{ $order->user->name }}
                                    <span class="badge badge-success ml-1" style="font-size: 10px;">Modifier</span>
                                </button>
                            @else
                                <button type="button" wire:click="openAssignModal({{ $order->id }})"
                                    class="btn btn-sm btn-warning font-weight-bold text-dark shadow-sm"
                                    style="border-radius: 20px; padding: 4px 14px;"
                                    title="Affecter un livreur à cette commande">
                                    <i class="material-icons f-16 mr-1" style="vertical-align: middle;">person_add</i>
                                    Affecter un livreur
                                </button>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- CORPS DE LA COMMANDE : ARTICLES & FINANCES --}}
                <div class="order-card-body">
                    <div class="row">

                        {{-- TABLEAU DES ARTICLES (COLONNE GAUCHE) --}}
                        <div class="col-lg-8 mb-3 mb-lg-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-dark font-weight-bold" style="font-size: 13px;">
                                    <i class="material-icons text-primary f-16" style="vertical-align: middle;">shopping_bag</i>
                                    Articles commandés
                                </h6>
                                <span class="badge badge-light border text-muted">
                                    {{ $order->orderItems->count() }} {{ $order->orderItems->count() > 1 ? 'articles' : 'article' }}
                                </span>
                            </div>

                            <div class="table-responsive border rounded" style="background: #ffffff;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr style="font-size: 12px;">
                                            <th style="color: #4a5568;">Article</th>
                                            <th class="text-center" style="color: #4a5568; width: 90px;">Quantité</th>
                                            <th class="text-right" style="color: #4a5568; width: 120px;">Prix unitaire</th>
                                            <th class="text-right" style="color: #4a5568; width: 130px;">Sous-total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($order->orderItems as $item)
                                            <tr style="font-size: 13px;">
                                                <td class="text-dark align-middle">
                                                    @if ($item->product)
                                                        <strong>{{ $item->product->name }}</strong>
                                                    @else
                                                        <span class="text-muted">Article #{{ $item->product_id }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge badge-light border px-2 py-1 font-weight-bold">
                                                        {{ $item->quantity }}
                                                    </span>
                                                </td>
                                                <td class="text-right align-middle text-muted">
                                                    {{ number_format($item->price ?? 0, 0, ',', ' ') }} F
                                                </td>
                                                <td class="text-right align-middle font-weight-bold text-dark">
                                                    {{ number_format(($item->quantity ?? 0) * ($item->price ?? 0), 0, ',', ' ') }} F
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3 small">
                                                    Aucun article enregistré pour cette commande.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- RÉCAPITULATIF FINANCIER & STATUT (COLONNE DROITE) --}}
                        <div class="col-lg-4">
                            <div class="finance-box h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Sous-total :</span>
                                        <span class="font-weight-bold text-dark">
                                            {{ number_format($order->subtotal ?? 0, 0, ',', ' ') }} F
                                        </span>
                                    </div>

                                    @if ($order->remis > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Remise :</span>
                                            <span class="badge badge-warning">
                                                -{{ $order->remis }}%
                                            </span>
                                        </div>
                                    @endif

                                    @if ($order->montant > 0 && $order->montant != $order->subtotal)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Montant payé :</span>
                                            <span class="text-dark font-weight-bold">
                                                {{ number_format($order->montant, 0, ',', ' ') }} F
                                            </span>
                                        </div>
                                    @endif

                                    <hr class="my-2">

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-dark" style="font-size: 14px;">Total à régler :</span>
                                        <span class="text-primary font-weight-bold" style="font-size: 18px;">
                                            {{ number_format($order->total ?? 0, 0, ',', ' ') }} F
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Statut actuel :</span>
                                    @if ($order->status == 'ordered')
                                        <span class="badge badge-warning px-2 py-1 font-weight-bold">En cours</span>
                                    @elseif ($order->status == 'delivered')
                                        <span class="badge badge-success px-2 py-1 font-weight-bold">Terminé</span>
                                    @elseif ($order->status == 'canceled')
                                        <span class="badge badge-danger px-2 py-1 font-weight-bold">Annulé</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1 font-weight-bold">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- PIED DE PAGE : ACTIONS --}}
                <div class="order-card-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">

                        <div class="small text-muted mb-2 mb-md-0">
                            Créée par : <strong class="text-dark">{{ $order->createduser?->name ?? '-' }}</strong>
                            <span class="mx-2">|</span>
                            Livreur :
                            @if ($order->user)
                                <strong class="text-success">{{ $order->user->name }}</strong>
                            @else
                                <span class="badge badge-warning text-dark font-weight-bold">Non affecté</span>
                            @endif
                        </div>

                        <div class="d-flex flex-wrap align-items-center">
                            {{-- Bouton Affecter Livreur --}}
                            <button type="button" wire:click="openAssignModal({{ $order->id }})"
                                class="btn btn-sm {{ $order->user_id ? 'btn-outline-dark' : 'btn-warning text-dark font-weight-bold shadow-sm' }} mr-2 mb-1"
                                style="border-radius: 6px;">
                                <i class="material-icons f-16 mr-1" style="vertical-align: middle;">delivery_dining</i>
                                {{ $order->user_id ? 'Modifier le livreur' : 'Affecter un livreur' }}
                            </button>

                            {{-- Bouton Modifier Date Livraison --}}
                            <button type="button" wire:click="openEditModal({{ $order->id }})"
                                class="btn btn-sm btn-outline-primary mr-2 mb-1" style="border-radius: 6px;">
                                <i class="material-icons f-16" style="vertical-align: middle;">event</i> Date & Heure
                            </button>

                            {{-- Bouton Facture --}}
                            <a href="{{ route('orders.invoice', $order->id) }}" target="_blank"
                                class="btn btn-sm btn-outline-success mr-2 mb-1" style="border-radius: 6px;">
                                <i class="fa fa-print mr-1"></i> Facture
                            </a>

                            {{-- Bouton Modifier Commande --}}
                            <a href="{{ route('edit-order', $order->id) }}"
                                class="btn btn-sm btn-info mr-2 mb-1" style="border-radius: 6px;">
                                <i class="fa fa-edit mr-1"></i> Modifier commande
                            </a>

                            {{-- Bouton Supprimer --}}
                            <button type="button" wire:click="openDeleteModal({{ $order->id }})"
                                class="btn btn-sm btn-outline-danger mb-1" style="border-radius: 6px;">
                                <i class="material-icons f-16" style="vertical-align: middle;">delete</i> Supprimer
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        @empty

            <div class="card shadow-sm border-0 my-4" style="border-radius: 12px;">
                <div class="card-body text-center py-5">
                    <i class="material-icons text-muted" style="font-size: 64px;">event_busy</i>
                    <h5 class="mt-3 font-weight-bold text-dark">Aucune commande pour cette date</h5>
                    <p class="text-muted mb-3">
                        Aucune commande n'est prévue pour livraison le {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l d F Y') }}.
                    </p>
                    <button wire:click="today" class="btn btn-sm btn-primary px-3 py-2" style="border-radius: 6px;">
                        <i class="material-icons f-16" style="vertical-align: middle;">today</i> Revenir à aujourd'hui
                    </button>
                </div>
            </div>

        @endforelse

        {{-- PAGINATION --}}
        @if ($orders->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $orders->links() }}
            </div>
        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- MODAL : MODIFIER DATE ET HEURE DE LIVRAISON --}}
    {{-- ========================================================= --}}
    @if ($showEditModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">

                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title font-weight-bold text-dark">
                            <i class="material-icons text-primary" style="vertical-align: middle;">event</i>
                            Modifier la date de livraison
                        </h5>
                        <button type="button" class="close" wire:click="closeEditModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body py-3">
                        <div class="alert alert-info py-2 px-3 small mb-3" style="border-radius: 6px;">
                            <i class="fa fa-info-circle mr-1"></i>
                            Vous pouvez ajuster la date et l'heure prévues pour cette livraison.
                        </div>

                        {{-- Date --}}
                        <div class="form-group mb-3">
                            <label class="font-weight-bold small text-muted">Date de livraison</label>
                            <input type="date" wire:model="editDate"
                                class="form-control @error('editDate') is-invalid @enderror" style="border-radius: 6px;">
                            @error('editDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Heure --}}
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small text-muted">Heure de livraison</label>
                            <input type="time" wire:model="editTime"
                                class="form-control @error('editTime') is-invalid @enderror" style="border-radius: 6px;">
                            @error('editTime')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" wire:click="closeEditModal" style="border-radius: 6px;">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="updateDeliveryDate"
                            wire:loading.attr="disabled" style="border-radius: 6px;">
                            <span wire:loading.remove wire:target="updateDeliveryDate">
                                <i class="material-icons f-16" style="vertical-align: middle;">check_circle</i> Enregistrer
                            </span>
                            <span wire:loading wire:target="updateDeliveryDate">
                                <span class="spinner-border spinner-border-sm mr-1"></span> Enregistrement...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif


    {{-- ========================================================= --}}
    {{-- MODAL : ATTRIBUER UN LIVREUR --}}
    {{-- ========================================================= --}}
    @if ($showAssignModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">

                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title font-weight-bold text-dark">
                            <i class="material-icons text-primary" style="vertical-align: middle;">delivery_dining</i>
                            Affecter un livreur
                        </h5>
                        <button type="button" class="close" wire:click="closeAssignModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body py-3">
                        <div class="alert alert-info py-2 px-3 small mb-3" style="border-radius: 6px;">
                            <i class="fa fa-info-circle mr-1"></i>
                            Sélectionnez le livreur à affecter pour la livraison de cette commande.
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold small text-muted">Choisir le livreur</label>
                            <select wire:model="selectedLivreur"
                                class="form-control @error('selectedLivreur') is-invalid @enderror" style="border-radius: 6px;">
                                <option value="">-- Choisir un livreur --</option>
                                @foreach ($livreurs as $livreur)
                                    <option value="{{ $livreur->id }}">
                                        {{ $livreur->name }} @if ($livreur->phone) ({{ $livreur->phone }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedLivreur')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" wire:click="closeAssignModal" style="border-radius: 6px;">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="assignOrder"
                            wire:loading.attr="disabled" style="border-radius: 6px;">
                            <span wire:loading.remove wire:target="assignOrder">
                                <i class="material-icons f-16" style="vertical-align: middle;">check_circle</i> Affecter le livreur
                            </span>
                            <span wire:loading wire:target="assignOrder">
                                <span class="spinner-border spinner-border-sm mr-1"></span> Affectation...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif


    {{-- ========================================================= --}}
    {{-- MODAL : CONFIRMATION DE SUPPRESSION --}}
    {{-- ========================================================= --}}
    @if ($showDeleteModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">

                    <div class="modal-header bg-danger text-white py-3">
                        <h5 class="modal-title font-weight-bold">
                            <i class="material-icons mr-1" style="vertical-align: middle;">warning</i>
                            Confirmation de suppression
                        </h5>
                        <button type="button" class="close text-white" wire:click="closeDeleteModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body text-center py-4">
                        <div class="mb-3">
                            <i class="material-icons text-danger" style="font-size: 56px;">delete_forever</i>
                        </div>
                        <h5 class="font-weight-bold text-dark">Supprimer cette commande ?</h5>
                        <p class="text-muted mb-0">
                            Êtes-vous sûr de vouloir supprimer définitivement la commande <strong>#{{ $deleteOrderId }}</strong> ?
                        </p>
                        <div class="alert alert-warning py-2 px-3 small mt-3 mb-0" style="border-radius: 6px;">
                            <i class="fa fa-exclamation-triangle mr-1"></i> Cette action est irréversible.
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" wire:click="closeDeleteModal" style="border-radius: 6px;">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="deleteOrder"
                            wire:loading.attr="disabled" style="border-radius: 6px;">
                            <span wire:loading.remove wire:target="deleteOrder">
                                <i class="material-icons f-16" style="vertical-align: middle;">delete</i> Oui, supprimer
                            </span>
                            <span wire:loading wire:target="deleteOrder">
                                <span class="spinner-border spinner-border-sm mr-1"></span> Suppression...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif

</div>
