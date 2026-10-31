<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bon de décaissement {{ $payment->id }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f5f6;color:#202936;font:15px Arial,sans-serif}.voucher{max-width:850px;margin:24px auto;padding:36px;background:#fff;border:1px solid #d8dee5}.head{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #187b2a;padding-bottom:18px}.head h1{font-size:23px;margin:0 0 7px}.head p{margin:0;color:#667386}.ref{text-align:right;font-weight:bold}.intro{margin:22px 0;padding:14px;background:#f3f8f4}.facts{width:100%;border-collapse:collapse}.facts th,.facts td{padding:13px 10px;border-bottom:1px solid #e1e6eb;text-align:left}.facts th{width:38%;color:#586579;font-weight:600}.facts td{font-weight:700}.amount{font-size:24px;color:#187b2a}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:42px}.sign{min-height:145px;border:1px solid #d8dee5;padding:16px}.sign strong,.sign small{display:block}.sign small{margin-top:6px;color:#667386}.line{margin-top:62px;border-top:1px solid #667386;padding-top:8px;color:#667386}.actions{text-align:center;margin:18px}.actions button{padding:12px 22px;border:0;background:#187b2a;color:white;font-weight:bold;cursor:pointer}@media print{body{background:#fff}.voucher{margin:0 auto;border:0;padding:12mm}.actions{display:none}}@media(max-width:600px){.voucher{margin:0;padding:20px}.head{flex-direction:column}.ref{text-align:left}.signatures{grid-template-columns:1fr}}
    </style>
</head>
<body>
<main class="voucher">
    <header class="head">
        <div><h1>TENACOS · Bon de décaissement</h1><p>Justificatif généré par le système après l’enregistrement du paiement.</p></div>
        <div class="ref">DEC-{{ $payment->paid_at?->format('Y') ?? now()->format('Y') }}-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}<br><small>{{ $payment->paid_at?->format('d/m/Y à H:i') }}</small></div>
    </header>
    <p class="intro">Ce bon confirme l’enregistrement du décaissement ci-dessous. La signature manuscrite du bénéficiaire peut être recueillie sur cette copie lors de la remise des fonds.</p>
    <table class="facts">
        <tr><th>Référence de la dépense</th><td>{{ $payment->expense?->reference }}</td></tr>
        <tr><th>Motif</th><td>{{ $payment->expense?->description }}</td></tr>
        <tr><th>Catégorie</th><td>{{ $payment->expense?->category_name }}</td></tr>
        <tr><th>Bénéficiaire</th><td>{{ $payment->expense?->beneficiary }}</td></tr>
        <tr><th>Montant remis</th><td class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} F CFA</td></tr>
        <tr><th>Caisse utilisée</th><td>{{ \App\Support\FinanceDisplayLabels::custody($payment->custody_account) }}</td></tr>
        <tr><th>Boutique</th><td>{{ $payment->location?->name ?? 'Caisse centrale' }}</td></tr>
        <tr><th>Enregistré par</th><td>{{ $payment->payer?->name ?? '—' }}</td></tr>
        <tr><th>Référence externe</th><td>{{ $payment->payment_reference ?: 'Aucune' }}</td></tr>
    </table>
    <section class="signatures">
        <div class="sign"><strong>Personne ayant remis les fonds</strong><small>{{ $payment->payer?->name ?? '—' }}</small><small>Enregistrement numérique · {{ $payment->paid_at?->format('d/m/Y à H:i') }}</small><div class="line">Signature</div></div>
        <div class="sign"><strong>Bénéficiaire</strong><small>{{ $payment->expense?->beneficiary }}</small><small>Reconnais avoir reçu le montant indiqué ci-dessus.</small><div class="line">Nom et signature</div></div>
    </section>
</main>
<div class="actions"><button onclick="window.print()">Imprimer le bon</button></div>
</body>
</html>
