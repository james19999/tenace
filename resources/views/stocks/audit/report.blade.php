<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport d’activité des stocks</title>
    <style>
        @page{margin:28px 30px 40px}*{box-sizing:border-box}body{font-family:DejaVu Sans,sans-serif;color:#292e3a;font-size:10px}.top{border-bottom:3px solid #8d1b1b;padding-bottom:14px}.eyebrow{color:#8d1b1b;font-size:9px;font-weight:bold;letter-spacing:1.5px}.top h1{font-size:23px;margin:7px 0 4px}.top p{margin:0;color:#697386;font-size:11px}.meta{margin:15px 0;padding:10px 12px;background:#f5f6f8;color:#4d5666}.meta strong{color:#292e3a}.summary{margin:12px 0;color:#697386}.summary strong{color:#292e3a}table{width:100%;border-collapse:collapse;table-layout:fixed}thead{display:table-header-group}th{padding:9px 7px;background:#8d1b1b;color:#fff;text-align:left;font-size:9px;text-transform:uppercase}td{padding:8px 7px;border-bottom:1px solid #e3e6ec;vertical-align:top;overflow-wrap:anywhere}tr{page-break-inside:avoid}.date{width:13%}.operation{width:20%}.reference{width:17%}.person{width:17%}.status{width:10%}.reason{width:23%}.muted{color:#7c8492}.footer{position:fixed;bottom:-24px;left:0;right:0;border-top:1px solid #dfe3e9;padding-top:7px;color:#7b8492;font-size:8px}.empty{text-align:center;padding:35px;color:#697386}
    </style>
</head>
<body>
    <header class="top">
        <div class="eyebrow">CONTRÔLE DES STOCKS</div>
        <h1>Rapport d’activité des stocks</h1>
        <p>Historique des opérations réalisées et de leurs validations.</p>
    </header>
    <div class="meta">
        <strong>Période :</strong>
        {{ !empty($filters['from']) ? \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') : 'Depuis le début' }}
        <strong>au</strong>
        {{ !empty($filters['to']) ? \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') : 'Aujourd’hui' }}
        @if ($filterActionLabel)
            &nbsp;·&nbsp; <strong>Opération :</strong> {{ $filterActionLabel }}
        @endif
        @if (!empty($filters['search']))
            &nbsp;·&nbsp; <strong>Recherche :</strong> {{ $filters['search'] }}
        @endif
    </div>
    <div class="summary"><strong>{{ number_format($rows->count(), 0, ',', ' ') }}</strong> opération(s) &nbsp;·&nbsp; Rapport généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
    @if($rows->isEmpty())
        <div class="empty">Aucune opération ne correspond aux filtres sélectionnés.</div>
    @else
        <table>
            <thead><tr><th class="date">Date et heure</th><th class="operation">Opération</th><th class="reference">Référence</th><th class="person">Responsable</th><th class="status">Résultat</th><th class="reason">Motif</th></tr></thead>
            <tbody>@foreach($rows as $row)<tr><td>{{ $row['date']?->format('d/m/Y') }}<br><span class="muted">{{ $row['date']?->format('H:i') }}</span></td><td>{{ $row['action'] }}</td><td>{{ $row['reference'] }}</td><td>{{ $row['actor_label'] }}<br><strong>{{ $row['actor'] }}</strong></td><td>{{ $row['result'] }}</td><td>{{ $row['reason'] ?: '—' }}</td></tr>@endforeach</tbody>
        </table>
    @endif
    <footer class="footer">Rapport interne · Historique du contrôle des stocks · Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</footer>
</body>
</html>
