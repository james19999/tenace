@extends('layouts.admin')

@section('content')
    @php
        $monthLabel = now()->locale('fr')->translatedFormat('F');
        $totalOrderLines = $productOrders->sum('order_count');
    @endphp

    <div class="product-orders-report">
        <div class="product-orders-shell">
            <div class="product-orders-heading">
                <div>
                    <p class="product-orders-eyebrow">Analyse du catalogue</p>
                    <h1>Rapport des ventes produits</h1>
                    <p>Lignes d’articles enregistrées au mois de {{ $monthLabel }}, toutes années confondues.</p>
                </div>
                <a href="{{ route('product') }}" class="product-orders-back"><span class="material-icons" aria-hidden="true">arrow_back</span>Retour au catalogue</a>
            </div>

            <div class="product-orders-summary">
                <article class="product-orders-stat">
                    <span class="product-orders-stat-icon"><span class="material-icons" aria-hidden="true">inventory_2</span></span>
                    <div><small>Produits concernés</small><strong>{{ number_format($productOrders->count(), 0, ',', ' ') }}</strong></div>
                </article>
                <article class="product-orders-stat product-orders-stat-accent">
                    <span class="product-orders-stat-icon"><span class="material-icons" aria-hidden="true">receipt_long</span></span>
                    <div><small>Lignes de commande</small><strong>{{ number_format($totalOrderLines, 0, ',', ' ') }}</strong></div>
                </article>
                <article class="product-orders-stat product-orders-period">
                    <span class="product-orders-stat-icon"><span class="material-icons" aria-hidden="true">calendar_month</span></span>
                    <div><small>Période affichée · toutes années</small><strong>{{ ucfirst($monthLabel) }}</strong></div>
                </article>
            </div>

            @if ($productOrders->isNotEmpty())
                <section class="product-orders-card" aria-labelledby="product-orders-chart-title">
                    <div class="product-orders-card-heading">
                        <div><h2 id="product-orders-chart-title">Top 10 des produits</h2><p>Les dix produits avec le plus de lignes de commande sur la période.</p></div>
                        <span class="product-orders-count">10 produits maximum</span>
                    </div>
                    <div class="product-orders-chart-scroll">
                        <canvas id="productOrdersChart" aria-label="Graphique du nombre de lignes de commande par produit"></canvas>
                    </div>
                </section>

                <section class="product-orders-card product-orders-table-card" aria-labelledby="product-orders-table-title">
                    <div class="product-orders-card-heading">
                        <div><h2 id="product-orders-table-title">Détail par produit</h2><p>Valeurs utilisées dans le graphique.</p></div>
                    </div>
                    <div class="product-orders-table-wrap">
                        <table class="product-orders-table">
                            <thead><tr><th>Rang</th><th>Produit</th><th class="text-right">Lignes de commande</th></tr></thead>
                            <tbody>
                                @foreach ($productOrders as $productOrder)
                                    <tr><td><span class="product-orders-rank">{{ $loop->iteration }}</span></td><td class="product-orders-name">{{ $productOrder->name }}</td><td class="text-right"><strong>{{ number_format($productOrder->order_count, 0, ',', ' ') }}</strong></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @else
                <section class="product-orders-card product-orders-empty">
                    <span class="material-icons" aria-hidden="true">query_stats</span>
                    <h2>Aucune vente produit à afficher</h2>
                    <p>Aucune ligne de commande n’a été trouvée pour le mois de {{ $monthLabel }}.</p>
                </section>
            @endif
        </div>
    </div>

    <style>
        .product-orders-report{--po-red:#861b1b;--po-ink:#292c36;--po-muted:#777d89;--po-line:#e7e9ee;min-width:0;padding:8px 0 32px;color:var(--po-ink)}
        .product-orders-report *{box-sizing:border-box}.product-orders-shell{max-width:1420px;margin:0 auto;padding:0 18px}
        .product-orders-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:8px 0 22px}
        .product-orders-eyebrow{margin:0 0 6px;color:var(--po-red);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .product-orders-heading h1{margin:0;color:var(--po-ink);font-size:clamp(27px,3vw,35px);font-weight:850;letter-spacing:-.6px;line-height:1.15}
        .product-orders-heading p:not(.product-orders-eyebrow){margin:8px 0 0;color:var(--po-muted);font-size:14px}
        .product-orders-back{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:9px 13px;border:1px solid var(--po-line);border-radius:9px;background:#fff;color:#505663!important;font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap}
        .product-orders-back:hover{border-color:#d5b8b6;background:#fcf7f6;color:var(--po-red)!important}
        .product-orders-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
        .product-orders-stat{display:flex;align-items:center;gap:12px;min-width:0;padding:14px 16px;border:1px solid var(--po-line);border-radius:12px;background:#fff;box-shadow:0 4px 15px rgba(33,38,48,.035)}
        .product-orders-stat-icon{display:grid;place-items:center;width:40px;height:40px;flex:0 0 40px;border-radius:11px;background:#f8eded;color:var(--po-red)}
        .product-orders-stat-accent .product-orders-stat-icon{background:#edf5ef;color:#45805a}.product-orders-period .product-orders-stat-icon{background:#eff3fa;color:#5b78a3}
        .product-orders-stat small{display:block;color:var(--po-muted);font-size:11px}.product-orders-stat strong{display:block;margin-top:2px;color:var(--po-ink);font-size:19px;font-weight:850;line-height:1.25}
        .product-orders-card{overflow:hidden;margin-bottom:15px;border:1px solid var(--po-line);border-radius:14px;background:#fff;box-shadow:0 6px 20px rgba(33,38,48,.04)}
        .product-orders-card-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:17px 19px;border-bottom:1px solid var(--po-line)}
        .product-orders-card-heading h2{margin:0;color:var(--po-ink);font-size:16px;font-weight:800}.product-orders-card-heading p{margin:4px 0 0;color:var(--po-muted);font-size:12px}
        .product-orders-count{padding:6px 9px;border-radius:18px;background:#f6f1f1;color:var(--po-red);font-size:10px;font-weight:800;white-space:nowrap}
        .product-orders-chart-scroll{max-height:610px;overflow-y:auto;overflow-x:hidden;padding:16px 18px}.product-orders-chart-scroll canvas{display:block;width:100%!important;max-width:100%;height:auto}
        .product-orders-table-wrap{width:100%;overflow-x:auto}.product-orders-table{width:100%;border-collapse:collapse;min-width:440px}
        .product-orders-table th{padding:11px 15px;background:#f7f8fa;color:#737985;text-align:left;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
        .product-orders-table td{padding:11px 15px;border-top:1px solid #eff0f3;color:#505560;font-size:12px}.product-orders-table tbody tr:hover{background:#fcfaf9}
        .product-orders-rank{display:inline-grid;place-items:center;width:25px;height:25px;border-radius:8px;background:#f2f3f5;color:#666c77;font-size:10px;font-weight:800}
        .product-orders-name{font-weight:700;color:#333741!important}.product-orders-table td strong{color:var(--po-red)}
        .product-orders-empty{display:flex;flex-direction:column;align-items:center;padding:48px 20px;text-align:center}.product-orders-empty>.material-icons{color:#aa7775;font-size:38px}
        .product-orders-empty h2{margin:10px 0 4px;font-size:17px;font-weight:800}.product-orders-empty p{margin:0;color:var(--po-muted);font-size:13px}
        @media(max-width:767.98px){.product-orders-report{padding-top:0}.product-orders-shell{padding:0 11px}.product-orders-heading{display:block;margin:8px 0 16px}.product-orders-heading h1{font-size:26px}.product-orders-heading p:not(.product-orders-eyebrow){font-size:13px;line-height:1.45}.product-orders-back{width:100%;margin-top:13px}.product-orders-summary{grid-template-columns:1fr;gap:8px}.product-orders-stat{padding:11px 13px}.product-orders-stat-icon{width:36px;height:36px;flex-basis:36px}.product-orders-card{border-radius:12px}.product-orders-card-heading{padding:14px}.product-orders-card-heading h2{font-size:15px}.product-orders-chart-scroll{padding:10px;max-height:520px}.product-orders-table th,.product-orders-table td{padding:10px 11px}}
    </style>

    @if ($productOrders->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                var rows = @json($productOrders->values());
                var chartRows = rows.slice().sort(function (a, b) {
                    return Number(b.order_count) - Number(a.order_count);
                }).slice(0, 10);
                var canvas = document.getElementById('productOrdersChart');
                if (!canvas || !rows.length || typeof Chart === 'undefined') return;
                canvas.style.height = Math.max(300, chartRows.length * 38) + 'px';
                var context = canvas.getContext('2d');
                var gradient = context.createLinearGradient(0, 0, Math.max(canvas.parentElement.clientWidth || 700, 1), 0);
                gradient.addColorStop(0, '#8b2524');
                gradient.addColorStop(1, '#d58b75');
                new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: chartRows.map(function (row) { return row.name; }),
                        datasets: [{ label: 'Lignes de commande', data: chartRows.map(function (row) { return Number(row.order_count); }), backgroundColor: gradient, borderRadius: 0, maxBarThickness: 21 }]
                    },
                    options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (context) { return ' ' + context.formattedValue + ' ligne(s)'; } } } },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f3' }, title: { display: true, text: 'Nombre de lignes de commande' } },
                            y: { grid: { display: false }, ticks: { color: '#4d5360', autoSkip: false, font: { size: 11 }, callback: function (value) { var label = this.getLabelForValue(value); return label.length > 28 ? label.slice(0, 26) + '…' : label; } } }
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
