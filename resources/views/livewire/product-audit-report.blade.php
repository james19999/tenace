<div class="product-audit-page">
    @php
        $salesChannelLabel = $saleChannel === 'wholesale' ? 'Ventes grossistes' : 'Ventes boutiques';
        $periodLabel = match ($filterType) {
            'day' => $selectedDay ? \Illuminate\Support\Carbon::parse($selectedDay)->format('d/m/Y') : 'Jour sélectionné',
            'week' => $selectedWeek ? 'Semaine ' . substr($selectedWeek, -2) . ' · ' . substr($selectedWeek, 0, 4) : 'Semaine sélectionnée',
            'month' => $selectedMonth ? \Illuminate\Support\Carbon::parse($selectedMonth . '-01')->translatedFormat('F Y') : 'Mois sélectionné',
            'year' => (string) $selectedYear,
            default => 'Période sélectionnée',
        };
    @endphp

    <style>
        .product-audit-page{--audit-red:#861b1b;--audit-ink:#282b35;--audit-muted:#777d89;--audit-border:#e7e9ee;color:var(--audit-ink);padding:8px 0 34px}
        .product-audit-page *{box-sizing:border-box}
        .audit-shell{max-width:1460px;margin:0 auto;padding:0 20px}
        .audit-hero{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin:8px 0 22px}
        .audit-eyebrow{margin:0 0 7px;color:var(--audit-red);font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .audit-hero h1{margin:0;color:var(--audit-ink);font-size:clamp(27px,3vw,36px);font-weight:800;line-height:1.12}
        .audit-hero p{margin:8px 0 0;color:var(--audit-muted);font-size:14px}
        .audit-print{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:43px;padding:9px 15px;border:1px solid #d9e4dd;border-radius:10px;background:#f1f8f3;color:#287046;font-weight:750;white-space:nowrap}
        .audit-card{overflow:hidden;margin-bottom:17px;border:1px solid var(--audit-border);border-radius:15px;background:#fff;box-shadow:0 6px 22px rgba(32,36,46,.04)}
        .audit-card-heading{display:flex;align-items:center;gap:11px;padding:16px 19px;border-bottom:1px solid var(--audit-border)}
        .audit-heading-icon{display:grid;place-items:center;width:36px;height:36px;flex:0 0 36px;border-radius:11px;background:#f8eded;color:var(--audit-red)}
        .audit-card-heading h2{margin:0;font-size:16px;font-weight:800}.audit-card-heading p{margin:3px 0 0;color:var(--audit-muted);font-size:12px}
        .audit-filter-body{padding:17px 19px}
        .audit-filters{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));align-items:end;gap:14px}
        .audit-field{min-width:0}.audit-field label{display:block;margin:0 0 7px;color:#4d5260;font-size:12px;font-weight:750}
        .audit-field .form-control{height:43px;border:1px solid #dfe2e8;border-radius:9px;box-shadow:none;font-size:14px}
        .audit-field .form-control:focus{border-color:#bd7777;box-shadow:0 0 0 3px rgba(134,27,27,.09)}
        .audit-period-chip{display:inline-flex;align-items:center;gap:7px;margin-top:13px;padding:6px 10px;border-radius:20px;background:#faf1f0;color:#81312d;font-size:12px;font-weight:700}
        .audit-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:17px}
        .audit-kpi{position:relative;min-width:0;min-height:126px;padding:17px 18px;border:1px solid var(--audit-border);border-radius:14px;background:#fff;box-shadow:0 5px 18px rgba(32,36,46,.035)}
        .audit-kpi-top{display:flex;align-items:center;gap:9px;color:var(--audit-muted);font-size:12px;font-weight:700}
        .audit-kpi-icon{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border-radius:10px;background:#f8eded;color:var(--audit-red)}
        .audit-kpi strong{display:block;overflow-wrap:anywhere;margin-top:12px;color:var(--audit-ink);font-size:clamp(21px,2vw,28px);font-weight:850;letter-spacing:-.5px;line-height:1.15}
        .audit-kpi small{display:block;margin-top:5px;color:#9297a1;font-size:11px}
        .audit-kpi.kpi-green .audit-kpi-icon{background:#edf7f0;color:#3c8159}.audit-kpi.kpi-blue .audit-kpi-icon{background:#eef3fb;color:#5077a7}.audit-kpi.kpi-amber .audit-kpi-icon{background:#fff5e7;color:#a87424}
        .audit-results-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 19px;border-bottom:1px solid var(--audit-border)}
        .audit-results-heading h2{margin:0;font-size:16px;font-weight:800}.audit-results-heading p{margin:4px 0 0;color:var(--audit-muted);font-size:12px}
        .audit-results-count{padding:6px 10px;border-radius:20px;background:#f5f6f8;color:#626875;font-size:12px;font-weight:750;white-space:nowrap}
        .audit-table-wrap{width:100%;overflow-x:auto}.audit-table{width:100%;border-collapse:collapse;min-width:620px}
        .audit-table th{padding:12px 16px;background:#f8f9fb;color:#6e7480;text-align:left;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase}
        .audit-table th.numeric,.audit-table td.numeric{text-align:right}.audit-table td{padding:13px 16px;border-top:1px solid #eff0f3;font-size:13px;vertical-align:middle}
        .audit-table tbody tr:hover{background:#fcfaf9}.audit-product-name{font-weight:750;color:#343842}.audit-qty{display:inline-flex;min-width:40px;justify-content:center;padding:5px 9px;border-radius:8px;background:#f2f4f7;color:#454b57;font-weight:800}
        .audit-amount{color:#34724b;font-weight:800;white-space:nowrap}.audit-empty{text-align:center!important;padding:38px 15px!important;color:var(--audit-muted)}
        .audit-table tfoot td{background:#fbfbfc;font-weight:800;border-top:1px solid var(--audit-border)}
        .audit-pagination{display:flex;justify-content:flex-end;padding:14px 18px;border-top:1px solid var(--audit-border)}
        .audit-pagination nav{margin:0}.audit-pagination p{margin:0;color:var(--audit-muted);font-size:12px}
        .audit-pagination .pagination{margin:0;flex-wrap:wrap;gap:4px}.audit-pagination .page-link{border-radius:7px!important;color:var(--audit-red);border-color:#e6e7eb}
        .audit-pagination .page-item.active .page-link{background:var(--audit-red);border-color:var(--audit-red);color:#fff}
        .audit-print-only{display:none}
        .audit-print-header{display:none}
        .audit-print-summary{display:none}
        @media(max-width:991px){.audit-shell{padding:0 14px}.audit-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.audit-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:575px){.product-audit-page{padding-top:0}.audit-shell{padding:0 11px}.audit-hero{display:block;margin:8px 0 17px}.audit-hero h1{font-size:26px}.audit-hero p{font-size:13px;line-height:1.5}.audit-print{margin-top:13px;width:100%}.audit-card{border-radius:12px}.audit-card-heading,.audit-results-heading{padding:14px}.audit-filter-body{padding:14px}.audit-filters{grid-template-columns:1fr;gap:12px}.audit-kpis{gap:9px}.audit-kpi{min-height:112px;padding:13px 12px}.audit-kpi-top{align-items:flex-start;font-size:11px;line-height:1.35}.audit-kpi-icon{width:30px;height:30px;flex-basis:30px}.audit-kpi strong{font-size:19px;margin-top:10px}.audit-kpi small{font-size:10px}.audit-table th,.audit-table td{padding:11px 12px}.audit-results-heading h2{font-size:14px}}
        @media(max-width:360px){.audit-kpis{grid-template-columns:1fr}.audit-kpi{min-height:95px}}
        .product-audit-page .audit-print,.product-audit-page .audit-card,
        .product-audit-page .audit-heading-icon,.product-audit-page .audit-field .form-control,
        .product-audit-page .audit-period-chip,.product-audit-page .audit-kpi,
        .product-audit-page .audit-kpi-icon,.product-audit-page .audit-results-count,
        .product-audit-page .audit-qty,.product-audit-page .audit-pagination .page-link{border-radius:0!important}
        .product-audit-page .audit-card,.product-audit-page .audit-kpi{box-shadow:none}
        .product-audit-page .audit-card,.product-audit-page .audit-kpi{border-color:#d9dde5}
        .product-audit-page .audit-card-heading,.product-audit-page .audit-results-heading{background:#fff}
        @page{size:A4 portrait;margin:13mm 14mm 16mm}
        @media print{
            body *{visibility:hidden!important}.product-audit-page,.product-audit-page *{visibility:visible!important}
            .product-audit-page{position:static;width:auto;padding:0}.audit-shell{max-width:none;padding:0}
            .no-print,.audit-print,.audit-hero{display:none!important}
            .audit-print-header{display:block;width:100%;margin:0 0 12px;padding:0 0 9px;border-bottom:2px solid #861b1b}
            .audit-print-brand{margin:0 0 5px;color:#861b1b;font-size:9pt;font-weight:800;letter-spacing:.16em}
            .audit-print-header h1{margin:0;color:#282b35;font-size:19pt;font-weight:800;line-height:1.2;white-space:nowrap}
            .audit-print-description{margin:4px 0 6px;color:#686e78;font-size:8.5pt}
            .audit-print-meta{display:block;width:100%;padding-top:6px;border-top:1px solid #e5e5e8;color:#555b66;font-size:8.5pt;line-height:1.45;text-align:left}
            .audit-print-meta span{display:inline-block;margin-right:20px}
            .audit-print-meta strong{display:inline;color:#282b35;font-size:8.5pt}
            .audit-card,.audit-kpi{box-shadow:none;break-inside:avoid}
            .audit-filters-card{display:none!important}.audit-table{min-width:0}.audit-table-wrap{overflow:visible}
            .audit-results-card{break-inside:auto!important;overflow:visible;border-color:#d7d9de}
            .audit-screen-only,.audit-pagination{display:none!important}.audit-print-only{display:block!important}
            .audit-kpis{display:none!important}
            .audit-print-summary{display:table;width:100%;table-layout:fixed;margin:0 0 12px;border-collapse:separate;border-spacing:5px}
            .audit-print-summary td{width:50%;padding:8px 10px;border:1px solid #dfe1e5;border-radius:5px;vertical-align:top}
            .audit-print-summary span,.audit-print-summary strong,.audit-print-summary small{display:block}
            .audit-print-summary span{color:#535a65;font-size:8pt;font-weight:700;line-height:1.3}
            .audit-print-summary strong{margin-top:5px;color:#282b35;font-size:14pt;line-height:1.15}
            .audit-print-summary small{margin-top:3px;color:#737985;font-size:7.5pt;line-height:1.25}
            .audit-results-heading{padding:10px 12px;border-bottom:1px solid #d7d9de}
            .audit-results-heading h2{font-size:11pt;text-transform:uppercase;letter-spacing:.04em}
            .audit-results-heading p{font-size:8pt}
            .audit-results-count{padding:4px 8px;background:#f1f2f4;font-size:8pt}
            .audit-table{font-size:9pt}
            .audit-table th{padding:8px 10px;background:#861b1b!important;color:#fff!important;font-size:8pt;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .audit-table td{padding:7px 10px;border-color:#e4e5e8;font-size:9pt}
            .audit-print-only .audit-table tbody tr:nth-child(even){background:#f7f7f8!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .audit-qty{min-width:0;padding:2px 6px;background:transparent;color:#282b35}
            .audit-amount{color:#282b35}
            .audit-table tfoot td{padding:8px 10px;background:#eceef1!important;font-weight:800;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .audit-table thead{display:table-header-group}
            .audit-print-only .audit-table thead{display:table-header-group}
            .audit-print-only .audit-table tfoot{display:table-row-group}
            .audit-print-only .audit-table tr{break-inside:avoid;page-break-inside:avoid}
        }
    </style>

    <div class="audit-shell">
        <div class="audit-hero">
            <div>
                <p class="audit-eyebrow">Pilotage commercial</p>
                <h1>Audit des ventes produits</h1>
                <p>Analyse séparée des ventes boutiques ou grossistes sur la période choisie.</p>
            </div>
            <button type="button" onclick="window.print()" class="audit-print no-print"><span class="material-icons" aria-hidden="true">print</span>Imprimer le rapport</button>
        </div>

        <section class="audit-card audit-filters-card no-print" aria-labelledby="audit-filters-title">
            <div class="audit-card-heading">
                <span class="audit-heading-icon material-icons" aria-hidden="true">tune</span>
                <div><h2 id="audit-filters-title">Période d’analyse</h2><p>Choisis une période pour actualiser les résultats.</p></div>
            </div>
            <div class="audit-filter-body">
                <div class="audit-filters">
                    <div class="audit-field">
                        <label for="audit-sales-channel">Canal de vente</label>
                        <select id="audit-sales-channel" wire:model="saleChannel" class="form-control">
                            <option value="shop">Ventes boutiques</option>
                            <option value="wholesale">Ventes grossistes</option>
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-filter-type">Type de période</label>
                        <select id="audit-filter-type" wire:model="filterType" class="form-control">
                            <option value="day">Jour</option><option value="week">Semaine</option><option value="month">Mois</option><option value="year">Année</option>
                        </select>
                    </div>
                    @if ($filterType == 'day')
                        <div class="audit-field"><label for="audit-day">Date</label><input id="audit-day" type="date" class="form-control" wire:model="selectedDay"></div>
                    @endif
                    @if ($filterType == 'week')
                        <div class="audit-field"><label for="audit-week">Semaine</label><input id="audit-week" type="week" class="form-control" wire:model="selectedWeek"></div>
                    @endif
                    @if ($filterType == 'month')
                        <div class="audit-field"><label for="audit-month">Mois</label><input id="audit-month" type="month" class="form-control" wire:model="selectedMonth"></div>
                    @endif
                    @if ($filterType == 'year')
                        <div class="audit-field"><label for="audit-year">Année</label><input id="audit-year" type="number" min="2020" max="2100" class="form-control" wire:model="selectedYear"></div>
                    @endif
                </div>
                <span class="audit-period-chip"><span class="material-icons" aria-hidden="true" style="font-size:16px">calendar_month</span>{{ $periodLabel }}</span>
            </div>
        </section>

        <div id="printArea" class="audit-report-content">
            <div class="audit-print-header">
                <div>
                    <p class="audit-print-brand">TENACOS · GESTION COMMERCIALE</p>
                    <h1>Rapport d’audit des ventes</h1>
                    <p class="audit-print-description">{{ $salesChannelLabel }} · commandes livrées, regroupées par produit.</p>
                </div>
                <div class="audit-print-meta">
                    <span><strong>Canal :</strong> {{ $salesChannelLabel }}</span>
                    <span><strong>Période :</strong> {{ $periodLabel }}</span>
                    <span>Édité le {{ now()->format('d/m/Y à H:i') }} · {{ auth()->user()?->name ?? 'Équipe TENACOS' }}</span>
                </div>
            </div>
            <table class="audit-print-summary">
                <tbody>
                    <tr>
                        <td><span>Quantité vendue</span><strong>{{ number_format($this->totalQty, 0, ',', ' ') }}</strong><small>Unités sur la période</small></td>
                        <td><span>Chiffre d’affaires produits</span><strong>{{ number_format($this->totalAmount, 0, ',', ' ') }} F</strong><small>Montant total des lignes vendues</small></td>
                    </tr>
                    <tr>
                        <td><span>Écart avec le total des commandes</span><strong>{{ number_format($this->tax, 0, ',', ' ') }} F</strong><small>Ventes produits moins total enregistré</small></td>
                        <td><span>Montant net calculé</span><strong>{{ number_format($this->netAmount, 0, ',', ' ') }} F</strong><small>Selon les montants de commande enregistrés</small></td>
                    </tr>
                </tbody>
            </table>
            <div class="audit-kpis">
                <article class="audit-kpi">
                    <div class="audit-kpi-top"><span class="audit-kpi-icon material-icons" aria-hidden="true">inventory_2</span>Quantité vendue</div>
                    <strong>{{ number_format($this->totalQty, 0, ',', ' ') }}</strong><small>Unités sur la période</small>
                </article>
                <article class="audit-kpi kpi-blue">
                    <div class="audit-kpi-top"><span class="audit-kpi-icon material-icons" aria-hidden="true">payments</span>Chiffre d’affaires produits</div>
                    <strong>{{ number_format($this->totalAmount, 0, ',', ' ') }} F</strong><small>Montant des lignes de commande</small>
                </article>
                <article class="audit-kpi kpi-amber">
                    <div class="audit-kpi-top"><span class="audit-kpi-icon material-icons" aria-hidden="true">discount</span>Écart avec le total commandes</div>
                    <strong>{{ number_format($this->tax, 0, ',', ' ') }} F</strong><small>Ventes produits moins total enregistré</small>
                </article>
                <article class="audit-kpi kpi-green">
                    <div class="audit-kpi-top"><span class="audit-kpi-icon material-icons" aria-hidden="true">account_balance_wallet</span>Montant net calculé</div>
                    <strong>{{ number_format($this->netAmount, 0, ',', ' ') }} F</strong><small>Selon les montants de commande enregistrés</small>
                </article>
            </div>

            <section class="audit-card audit-results-card" aria-labelledby="audit-results-title">
                <div class="audit-results-heading">
                    <div><h2 id="audit-results-title">{{ $salesChannelLabel }}</h2><p>Commandes livrées · {{ $periodLabel }}</p></div>
                    <span class="audit-results-count">{{ $paginatedReports->total() }} produit(s)</span>
                </div>
                <div class="audit-table-wrap audit-screen-only">
                    <table class="audit-table">
                        <thead><tr><th>Produit</th><th class="numeric">Quantité vendue</th><th class="numeric">Montant</th></tr></thead>
                        <tbody>
                            @forelse($paginatedReports as $report)
                                <tr><td class="audit-product-name">{{ $report->name }}</td><td class="numeric"><span class="audit-qty">{{ number_format($report->qty, 0, ',', ' ') }}</span></td><td class="numeric audit-amount">{{ number_format($report->amount, 0, ',', ' ') }} F</td></tr>
                            @empty
                                <tr><td colspan="3" class="audit-empty">Aucune vente livrée trouvée pour cette période.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($paginatedReports->total() > 0)
                            <tfoot><tr><td>Total de la période</td><td class="numeric">{{ number_format($this->totalQty, 0, ',', ' ') }}</td><td class="numeric">{{ number_format($this->totalAmount, 0, ',', ' ') }} F</td></tr></tfoot>
                        @endif
                    </table>
                </div>
                <div class="audit-table-wrap audit-print-only">
                    <table class="audit-table">
                        <thead><tr><th>Produit</th><th class="numeric">Quantité vendue</th><th class="numeric">Montant</th></tr></thead>
                        <tbody>
                            @forelse($allReports as $report)
                                <tr><td class="audit-product-name">{{ $report->name }}</td><td class="numeric"><span class="audit-qty">{{ number_format($report->qty, 0, ',', ' ') }}</span></td><td class="numeric audit-amount">{{ number_format($report->amount, 0, ',', ' ') }} F</td></tr>
                            @empty
                                <tr><td colspan="3" class="audit-empty">Aucune vente livrée trouvée pour cette période.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($allReports->isNotEmpty())
                            <tfoot><tr><td>Total de la période</td><td class="numeric">{{ number_format($this->totalQty, 0, ',', ' ') }}</td><td class="numeric">{{ number_format($this->totalAmount, 0, ',', ' ') }} F</td></tr></tfoot>
                        @endif
                    </table>
                </div>
                @if ($paginatedReports->hasPages())
                    <div class="audit-pagination">{{ $paginatedReports->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</div>
