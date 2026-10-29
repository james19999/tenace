<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bon de règlement fournisseur</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:24px;background:#f3f5f7;color:#202936;font:15px Arial,sans-serif}.voucher{max-width:800px;margin:0 auto;padding:36px;background:white;border:1px solid #d9dee5}.top{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #16801b;padding-bottom:18px}.top h1{margin:0 0 8px;font-size:25px}.muted{color:#687386}.reference{text-align:right;font-weight:bold}.notice{margin:22px 0;padding:14px;background:#f4f8f4;border:1px solid #d9e8da;line-height:1.5}.details{width:100%;border-collapse:collapse}.details td{padding:12px 8px;border-bottom:1px solid #e5e9ee}.details td:first-child{color:#687386;width:42%}.amount{font-size:23px;font-weight:bold}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:54px}.sign{min-height:135px;border-top:1px solid #7a8492;padding-top:10px;line-height:1.6}.actions{max-width:800px;margin:0 auto 14px;display:flex;gap:10px}.actions button,.actions a{padding:10px 14px;border:0;background:#16801b;color:#fff;text-decoration:none;cursor:pointer;font-size:14px}@media print{body{padding:0;background:#fff}.voucher{border:0;margin:0 auto}.actions{display:none}}@media(max-width:600px){body{padding:10px}.voucher{padding:20px}.top{flex-direction:column}.reference{text-align:left}.signatures{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Imprimer le bon</button><a href="{{ route('finance.suppliers.show', $payment->invoice) }}">Retour à la facture</a></div>
    <main class="voucher">
        <header class="top">
            <div><h1>Bon de règlement fournisseur</h1><div class="muted">TENACOS · Caisse générale</div></div>
            <div class="reference">N° REG-{{ str_pad((string)$payment->id, 6, '0', STR_PAD_LEFT) }}<br><span class="muted">{{ $payment->paid_at->format('d/m/Y à H:i') }}</span></div>
        </header>
        <p class="notice">Ce bon est généré par le système. Il atteste la remise des espèces uniquement après le versement effectif. Le fournisseur le signe au moment où il reçoit l’argent.</p>
        <table class="details">
            <tr><td>Fournisseur</td><td><strong>{{ $payment->invoice->supplier_name }}</strong></td></tr>
            <tr><td>Référence TENACOS</td><td>{{ $payment->invoice->invoice_number }}</td></tr>
            @if($payment->invoice->supplier_invoice_number)<tr><td>N° facture fournisseur</td><td>{{ $payment->invoice->supplier_invoice_number }}</td></tr>@endif
            <tr><td>Montant remis</td><td class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} F CFA</td></tr>
            <tr><td>Référence du paiement</td><td>{{ $payment->payment_reference ?: '—' }}</td></tr>
            <tr><td>Remis par</td><td>{{ $payment->payer?->name ?? '—' }}</td></tr>
        </table>
        <section class="signatures">
            <div class="sign"><strong>Signature du fournisseur</strong><br>Je reconnais avoir reçu le montant indiqué ci-dessus.<br>Nom : ______________________________</div>
            <div class="sign"><strong>Signature du caissier</strong><br>{{ $payment->payer?->name ?? '—' }}</div>
        </section>
    </main>
</body>
</html>
