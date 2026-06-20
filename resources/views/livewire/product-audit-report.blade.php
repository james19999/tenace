<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Audit des ventes produits
            </h2>


        </div>

        <button onclick="window.print()" class="btn btn-success no-print">
            🖨 Imprimer
        </button>

    </div>

    {{-- FILTRES --}}

    <div class="card shadow-sm mb-4 no-print">

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">

                    <label class="form-label">
                        Type de période
                    </label>

                    <select wire:model="filterType" class="form-select form-control">

                        <option value="day">
                            Jour
                        </option>

                        <option value="week">
                            Semaine
                        </option>

                        <option value="month">
                            Mois
                        </option>

                        <option value="year">
                            Année
                        </option>

                    </select>

                </div>

                @if ($filterType == 'day')
                    <div class="col-md-3">

                        <label class="form-label">
                            Date
                        </label>

                        <input type="date" class="form-control" wire:model="selectedDay">

                    </div>
                @endif

                @if ($filterType == 'week')
                    <div class="col-md-3">

                        <label class="form-label">
                            Semaine
                        </label>

                        <input type="week" class="form-control" wire:model="selectedWeek">

                    </div>
                @endif

                @if ($filterType == 'month')
                    <div class="col-md-3">

                        <label class="form-label">
                            Mois
                        </label>

                        <input type="month" class="form-control" wire:model="selectedMonth">

                    </div>
                @endif

                @if ($filterType == 'year')
                    <div class="col-md-3">

                        <label class="form-label">
                            Année
                        </label>

                        <input type="number" min="2020" class="form-control" wire:model="selectedYear">

                    </div>
                @endif

            </div>

        </div>

    </div>

    {{-- KPI --}}
    <div id="printArea">


        <div class="row g-3 mb-4">

            {{-- Quantité vendue --}}
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted">
                            Quantité vendue
                        </small>

                        <h3 class="fw-bold text-dark mb-0">
                            {{ number_format($this->totalQty) }}
                        </h3>
                    </div>
                </div>
            </div>

            {{-- Chiffre d'affaires --}}
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted">
                            Chiffre d'affaires
                        </small>

                        <h3 class="fw-bold text-primary mb-0">
                            {{ number_format($this->totalAmount) }} XOF
                        </h3>
                    </div>
                </div>
            </div>

            {{-- Taxes --}}
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted">
                            Total remise
                        </small>

                        <h3 class="fw-bold text-danger mb-0">
                            {{ number_format($this->tax) }} XOF
                        </h3>
                    </div>
                </div>
            </div>

            {{-- CA Net --}}
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted">
                            CA net après taxes
                        </small>

                        <h3 class="fw-bold text-success mb-0">
                            {{ number_format($this->netAmount) }} XOF
                        </h3>
                    </div>
                </div>
            </div>

        </div>

        {{-- TABLEAU --}}

        <div>

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Détail des ventes du {{ now() }}
                    </h5>

                </div>

                <div class="table-responsive">

                    <table class="table table-striped table-hover mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>Produit</th>

                                <th class="text-center">
                                    Quantité
                                </th>

                                <th class="text-end">
                                    Montant
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($reports as $report)
                                <tr>

                                    <td>
                                        {{ $report->name }}
                                    </td>

                                    <td class="text-center fw-bold">

                                        {{ number_format($report->qty) }}

                                    </td>

                                    <td class="text-end text-success fw-bold">

                                        {{ number_format($report->amount) }}
                                        XOF

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="3" class="text-center py-4">

                                        Aucun résultat trouvé

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>
    </div>

    <style>
        @media print {

            body * {
                visibility: hidden;
            }

            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

</div>
