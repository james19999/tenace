<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bon {{ $remittance->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f4f6; color: #202a36; font: 12px/1.35 Arial, sans-serif; }
        .copies { display: flex; width: min(1180px, calc(100% - 32px)); margin: 24px auto; background: #fff; border: 1px solid #cbd2da; }
        .copy { flex: 1 1 50%; min-width: 0; padding: 20px; }
        .copy + .copy { border-left: 1px dashed #788392; }
        .copy-label { display: inline-block; margin-bottom: 10px; padding: 4px 8px; background: #edf5ee; color: #176b2a; font-size: 9px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .brand { display: flex; justify-content: space-between; gap: 8px; align-items: flex-start; padding-bottom: 9px; border-bottom: 2px solid #187b2a; }
        .brand h1 { margin: 0 0 4px; font-size: 15px; line-height: 1.15; }
        .brand p { margin: 0; color: #5b6675; }
        .ref { text-align: right; font-weight: 700; white-space: nowrap; }
        .ref small { display: block; margin-top: 3px; color: #5b6675; font-size: 9px; font-weight: 400; }
        .intro { margin: 10px 0; padding: 8px; background: #f4f8f4; }
        .facts { width: 100%; border-collapse: collapse; }
        .facts td { padding: 5px 3px; border-bottom: 1px solid #e4e8ec; vertical-align: top; }
        .facts td:first-child { color: #536071; }
        .facts td:last-child { text-align: right; font-weight: 700; white-space: nowrap; }
        .note { margin: 9px 0; padding: 7px 8px; background: #f6f7f8; overflow-wrap: anywhere; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; }
        .sign { min-height: 100px; padding: 8px; border: 1px solid #d5dae0; }
        .sign strong, .sign small { display: block; }
        .sign small { margin-top: 4px; color: #566273; overflow-wrap: anywhere; }
        .blank { margin-top: 29px; padding-top: 4px; border-top: 1px solid #677384; color: #6b7480; font-size: 9px; }
        .stamp { min-height: 34px; margin-top: 8px; padding-top: 6px; border-top: 1px solid #d5dae0; color: #586475; font-size: 10px; }
        .actions { margin: 16px auto; text-align: center; }
        .actions button { padding: 11px 20px; border: 0; background: #187b2a; color: #fff; font-weight: 700; cursor: pointer; }
        @media screen and (max-width: 760px) {
            .copies { display: block; }
            .copy + .copy { border-top: 1px dashed #788392; border-left: 0; }
        }
        @page { size: A4 landscape; margin: 7mm; }
        @media print {
            body { background: #fff; font-size: 9px; line-height: 1.25; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .copies { display: flex; flex-direction: row; width: 100%; height: 195mm; margin: 0; border: 1px solid #aeb6c0; }
            .copy { flex: 0 0 50%; width: 50%; padding: 5mm; }
            .copy + .copy { border-top: 0; border-left: 1px dashed #788392; }
            .copy-label { margin-bottom: 6px; padding: 3px 6px; font-size: 8px; }
            .brand { padding-bottom: 6px; }
            .brand h1 { font-size: 12px; }
            .brand p, .ref small { font-size: 8px; }
            .intro { margin: 6px 0; padding: 5px; }
            .facts td { padding: 3px 2px; }
            .note { margin: 5px 0; padding: 5px; }
            .signatures { gap: 5px; margin-top: 7px; }
            .sign { min-height: 82px; padding: 5px; }
            .blank { margin-top: 20px; }
            .stamp { min-height: 27px; margin-top: 5px; padding-top: 4px; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    @php($directorCollectsDirectly = $remittance->collector?->isAdmin() ?? false)
    <main class="copies">
        @foreach (['Remettant · responsable boutique', 'Receveur · collecteur / caisse générale'] as $copyLabel)
            <article class="copy">
                <div class="copy-label">{{ $copyLabel }}</div>
                <header class="brand">
                    <div>
                        <h1>TENACOS · Bon de remise d’espèces</h1>
                        <p>{{ $isWholesaleRemittance ? 'Grossiste' : 'Boutique' }} : {{ $remittance->location?->name }}</p>
                    </div>
                    <div class="ref">{{ $remittance->reference }}<small>Créé le {{ $remittance->created_at?->format('d/m/Y à H:i') }}</small></div>
                </header>

                <p class="intro">Période : <strong>{{ $remittance->period_start?->format('d/m/Y') }} au {{ $remittance->period_end?->format('d/m/Y') }}</strong><br>Référence papier : <strong>{{ $remittance->paper_voucher_reference ?? 'à renseigner' }}</strong></p>

                <table class="facts">
                    <tr><td>Solde théorique en fin de période</td><td>{{ number_format($remittance->expected_amount, 0, ',', ' ') }} F</td></tr>
                    <tr><td>Montant compté ensemble</td><td>{{ number_format($remittance->counted_amount, 0, ',', ' ') }} F</td></tr>
                    <tr><td>Montant confié</td><td>{{ number_format($remittance->handed_over_amount, 0, ',', ' ') }} F</td></tr>
                    <tr><td>Destination</td><td>{{ $remittance->destination === 'bank_deposit' ? 'Dépôt bancaire' : 'Caisse générale' }}</td></tr>
                    <tr><td>Montant reçu par le DG</td><td>{{ $remittance->received_amount === null ? 'En attente' : number_format($remittance->received_amount, 0, ',', ' ').' F' }}</td></tr>
                    @if ($remittance->bank_slip_reference)
                        <tr><td>Référence du bordereau</td><td>{{ $remittance->bank_slip_reference }}</td></tr>
                    @endif
                </table>

                <p class="note">Ventes encaissées pendant la période : <strong>{{ $cashOperations->where('movement_type', 'sale_cash_received')->count() }}</strong>. Elles sont comprises dans le montant attendu.</p>
                @if ($remittance->discrepancy_note)
                    <p class="note"><strong>Écart / remarque :</strong> {{ $remittance->discrepancy_note }}</p>
                @endif
                @if ($remittance->resolution_note)
                    <p class="note"><strong>Décision du DG :</strong> {{ $remittance->resolution_note }}</p>
                @endif

                <section class="signatures">
                    <div class="sign">
                        <strong>Responsable {{ $isWholesaleRemittance ? 'du grossiste' : 'de boutique' }}</strong>
                        <small>{{ $remittance->shopManager?->name ?? 'Non renseigné' }}</small>
                        <small>Confirmation : {{ $remittance->shop_manager_signed_at?->format('d/m/Y à H:i') ?? 'en attente' }}</small>
                        <div class="blank">Signature du responsable</div>
                    </div>
                    <div class="sign">
                        <strong>{{ $directorCollectsDirectly ? 'DG · réception directe' : 'Collecteur' }}</strong>
                        <small>{{ $remittance->collector?->name ?? 'Non renseigné' }}</small>
                        <small>{{ $directorCollectsDirectly ? 'Réception : '.($remittance->received_at?->format('d/m/Y à H:i') ?? 'en attente') : 'Prise en charge : '.($remittance->collector_signed_at?->format('d/m/Y à H:i') ?? 'en attente') }}</small>
                        @unless ($directorCollectsDirectly)
                            <div class="blank">Signature du collecteur</div>
                        @endunless
                    </div>
                </section>
                <div class="stamp"><strong>Réception confirmée par le DG :</strong> {{ $remittance->receiver?->name ?? 'En attente' }} · {{ $remittance->received_at?->format('d/m/Y à H:i') ?? 'En attente' }}</div>
            </article>
        @endforeach
    </main>
    <div class="actions"><button onclick="window.print()">Relancer l’impression des deux exemplaires</button></div>
    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => window.print(), 350);
        });
    </script>
</body>
</html>
