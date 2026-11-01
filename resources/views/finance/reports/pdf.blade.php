<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>TENACOS · {{ $title }}</title>
    <style>
        @page { margin: 28px 30px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color: #202733; font-size: 9px; }
        .brand { color: #187b2a; font-size: 9px; font-weight: bold; letter-spacing: 1px; }
        h1 { margin: 7px 0 5px; font-size: 19px; }
        .meta { color: #647084; margin-bottom: 14px; line-height: 1.6; }
        .summary { margin: 0 0 12px; padding: 9px 11px; border: 1px solid #dce2e8; background: #f6f8f9; font-size: 10px; }
        .summary strong { color: #187b2a; font-size: 12px; }
        .by-location { margin: 0 0 17px; }
        .by-location th { background: #e9eef2; color: #364152; }
        .by-location th, .by-location td { padding: 5px 7px; }
        .overview { margin: 0 0 17px; }
        .overview th { background: #e9eef2; color: #364152; }
        .overview th, .overview td { padding: 5px 7px; }
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        th { background: #187b2a; color: #fff; text-align: left; font-size: 8px; }
        th, td { border: 1px solid #dce2e8; padding: 6px 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f6f8f9; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; color: #748092; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="brand">TENACOS · COMPTABILITÉ</div>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Période du {{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}
        · Généré le {{ $generatedAt->format('d/m/Y à H:i') }}
        · {{ count($rows) }} opération(s)
        · Boutique : {{ $filters['location_name'] ?? 'Toutes les boutiques' }}
    </div>
    <div class="summary">
        Total du rapport : <strong>{{ number_format($total, 0, ',', ' ') }} F CFA</strong>
        @if($filters['type'] === 'expenses')
            · Somme payée sur les dépenses recensées
        @endif
    </div>
    <table class="overview">
        <thead><tr><th>Synthèse des opérations sur la période</th><th>Total</th><th>Autre montant suivi</th></tr></thead>
        <tbody>
            <tr><td>Journal de caisse (entrées et sorties additionnées)</td><td>{{ number_format($overviewTotals['cash']['amount'], 0, ',', ' ') }} F</td><td>—</td></tr>
            <tr><td>Remises reçues par la caisse générale</td><td>{{ number_format($overviewTotals['remittances']['amount'], 0, ',', ' ') }} F</td><td>—</td></tr>
            <tr><td>Dépenses</td><td>{{ number_format($overviewTotals['expenses']['paid'], 0, ',', ' ') }} F payés</td><td>{{ number_format($overviewTotals['expenses']['amount'], 0, ',', ' ') }} F demandés</td></tr>
            <tr><td>Factures fournisseurs</td><td>{{ number_format($overviewTotals['suppliers']['amount'], 0, ',', ' ') }} F</td><td>{{ number_format($overviewTotals['suppliers']['paid'], 0, ',', ' ') }} F réglés</td></tr>
        </tbody>
    </table>
    @if(!$filters['stock_location_id'] && $locationTotals->isNotEmpty())
        <table class="by-location">
            <thead>
                <tr>
                    <th>Total par boutique</th>
                    @if($filters['type'] === 'expenses')
                        <th>Demandé</th>
                    @endif
                    <th>{{ $filters['type'] === 'expenses' ? 'Payé' : ($filters['type'] === 'remittances' ? 'Reçu' : 'Total') }}</th>
                    <th>Nombre</th>
                </tr>
            </thead>
            <tbody>
                @foreach($locationTotals as $locationTotal)
                    <tr>
                        <td>{{ $locationTotal['name'] }}</td>
                        @if($filters['type'] === 'expenses')
                            <td>{{ number_format($locationTotal['requested'], 0, ',', ' ') }} F</td>
                        @endif
                        <td>{{ number_format($locationTotal['total'], 0, ',', ' ') }} F</td>
                        <td>{{ $locationTotal['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <table>
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>@foreach($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($headers) }}">Aucune opération pour cette période.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="footer">Document généré par TENACOS</div>
</body>
</html>
