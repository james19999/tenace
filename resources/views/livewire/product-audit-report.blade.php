<div class="container-fluid py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="h3 fw-bold mb-1">
                Audit des ventes produits
            </h1>

            <p class="text-muted mb-0">
                Rapport journalier, hebdomadaire, mensuel et annuel
            </p>
        </div>

        <button onclick="window.print()" class="btn btn-success no-print">
            🖨 Imprimer
        </button>



    </div>

    {{-- KPI --}}
    {{--
    <div class="row g-3 mb-4">

        <div class="col-md-2">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Aujourd'hui</small>
                    <h4 class="fw-bold mb-0">
                        {{ number_format($this->totalDaily) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Cette semaine</small>
                    <h4 class="fw-bold mb-0">
                        {{ number_format($this->totalWeekly) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Ce mois</small>
                    <h4 class="fw-bold mb-0">
                        {{ number_format($this->totalMonthly) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Cette année</small>
                    <h4 class="fw-bold text-success mb-0">
                        {{ number_format($this->totalYearly) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">CA annuel</small>
                    <h4 class="fw-bold text-primary mb-0">
                        {{ number_format($this->totalAmount) }} XOF
                    </h4>
                </div>
            </div>
        </div>

    </div>
    --}}

    {{-- TABLEAU --}}
    <div id="printArea">

        <div class="card shadow-sm border-0">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover table-striped align-middle mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Produit</th>
                                <th class="text-center">Jour</th>
                                <th class="text-center">Semaine</th>
                                <th class="text-center">Mois</th>
                                <th class="text-center">Année</th>

                                {{--
                                <th class="text-end">
                                    CA Annuel
                                </th>
                                --}}
                            </tr>

                        </thead>

                        <tbody>

                            @forelse($reports as $report)
                                <tr>

                                    <td class="fw-semibold">
                                        {{ $report->name }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($report->daily_qty) }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($report->weekly_qty) }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($report->monthly_qty) }}
                                    </td>

                                    <td class="text-center fw-bold">
                                        {{ number_format($report->yearly_qty) }}
                                    </td>

                                    {{--
                                    <td class="text-end text-success fw-bold">
                                        {{ number_format($report->yearly_amount) }} XOF
                                    </td>
                                    --}}

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Aucun mouvement trouvé
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
