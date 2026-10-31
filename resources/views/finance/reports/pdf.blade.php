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
        .meta { color: #647084; margin-bottom: 18px; }
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
    </div>
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
