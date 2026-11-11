@php
    $setting = \App\Models\Setting::query()->first();
    $subtotal = (int) round((float) $order->subtotal);
    $itemsTotal = (int) round((float) $order->total);
    $discountAmount = max(0, $subtotal - $itemsTotal);
    $amountDue = (int) round((float) ($order->montant ?? ($itemsTotal + (float) $order->tax)));
    $paidAmount = (int) ($order->paid_amount ?? 0);
    $remainingAmount = max(0, $amountDue - $paidAmount);
    $customerPhone = $order->delivery_phone ?: ($order->costumer->phone ?? '');
    $customerAddress = $order->delivery_address ?: ($order->costumer->adresse ?? '');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu {{ $order->code }}</title>
    <style>
        :root{--red:#811919;--ink:#232a35;--muted:#687589;--line:#dce2e9;--soft:#f4f6f8}
        *{box-sizing:border-box}
        body{margin:0;background:#edf0f4;color:var(--ink);font:14px/1.4 Arial,Helvetica,sans-serif}
        .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;max-width:1200px;margin:20px auto 12px;padding:0 16px}
        .toolbar a,.toolbar button{min-height:40px;padding:9px 15px;border:1px solid var(--line);background:#fff;color:var(--ink);font-weight:700;text-decoration:none;cursor:pointer}
        .toolbar button{border-color:var(--red);background:var(--red);color:#fff}
        .receipt-sheet{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;max-width:1200px;margin:0 auto 24px;padding:16px}
        .receipt-copy{position:relative;min-width:0;padding:20px;background:#fff;border:1px solid var(--line);border-top:4px solid var(--red);box-shadow:0 8px 26px #17203312}
        .receipt-copy-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin:0 0 12px}
        .receipt-logo{display:block;width:54px;height:54px;object-fit:contain;object-position:left top}
        .copy-kind{display:table;margin:0 0 0 auto;padding:5px 9px;background:#f8eded;color:var(--red);font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .receipt-head{display:flex;justify-content:space-between;gap:12px;padding:0 0 12px;border-bottom:1px solid var(--line)}
        .brand{display:flex;align-items:center;gap:10px;min-width:0}
        .brand img{width:46px;height:46px;object-fit:contain}
        .brand-name{font-size:17px;font-weight:850;letter-spacing:.01em}
        .brand-contact{margin-top:3px;color:var(--muted);font-size:10px}
        .receipt-title{text-align:right}
        .receipt-title h1{margin:0;color:var(--red);font-size:20px;line-height:1.1}
        .receipt-title strong{display:block;margin-top:5px;font-size:12px}
        .receipt-title small{display:block;margin-top:3px;color:var(--muted);font-size:10px}
        .meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:12px 0}
        .meta>div{min-width:0;padding:8px 9px;background:var(--soft)}
        .meta span,.meta strong{display:block;overflow-wrap:anywhere}
        .meta span{margin-bottom:3px;color:var(--muted);font-size:9px;font-weight:750;letter-spacing:.06em;text-transform:uppercase}
        .meta strong{font-size:11px}
        table{width:100%;border-collapse:collapse}
        th{padding:7px 5px;background:#f1f3f6;color:#647084;font-size:9px;letter-spacing:.04em;text-align:left;text-transform:uppercase}
        td{padding:7px 5px;border-bottom:1px solid #e9edf1;font-size:10px;vertical-align:top}
        th:last-child,td:last-child{text-align:right}
        .num{text-align:right;white-space:nowrap}
        .product-name{font-weight:700;overflow-wrap:anywhere}
        .totals{width:min(100%,330px);margin:10px 0 0 auto}
        .total-row{display:flex;justify-content:space-between;gap:10px;padding:4px 0;border-bottom:1px solid #edf0f3;font-size:10px}
        .total-row span{color:var(--muted)}.total-row strong{text-align:right;white-space:nowrap}
        .total-row.discount strong{color:#237447}
        .total-row.grand{margin-top:3px;padding:8px 0;border-bottom:2px solid var(--red);font-size:13px;font-weight:800}
        .total-row.grand span,.total-row.grand strong{color:var(--red)}
        .balance{display:flex;justify-content:space-between;gap:8px;margin-top:7px;padding:7px 9px;background:#f8eded;color:var(--red);font-size:10px;font-weight:750}
        .thanks{margin:10px 0 0;padding-top:8px;border-top:1px dashed var(--line);color:var(--muted);font-size:9px}
        .signatures{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:17px}
        .signatures div{min-height:35px;padding-top:5px;border-top:1px solid #8b93a0;color:var(--muted);font-size:9px}
        @media(max-width:760px){.receipt-sheet{grid-template-columns:1fr}.receipt-copy{padding:16px}.toolbar{margin-top:12px}}
        @media print{
            @page{size:A4 landscape;margin:7mm}
            body{background:#fff;font-size:10px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .toolbar{display:none!important}
            .receipt-sheet{width:100%;max-width:none;grid-template-columns:repeat(2,minmax(0,1fr));gap:6mm;margin:0;padding:0}
            .receipt-copy{min-height:190mm;padding:5mm;box-shadow:none;border:1px solid #bfc6d0;border-top:3px solid var(--red);break-inside:avoid}
            .receipt-copy-top{margin-bottom:3mm}
            .receipt-logo{width:14mm;height:14mm}
            .copy-kind{margin:0 0 0 auto;padding:1.2mm 2mm}
            .receipt-head{padding-bottom:3mm}
            .brand img{width:12mm;height:12mm}
            .brand-name{font-size:13px}
            .meta{gap:2mm;margin:3mm 0}
            .meta>div{padding:2mm}
            th{padding:1.7mm 1.2mm;font-size:7px}
            td{padding:1.6mm 1.2mm;font-size:8px}
            .totals{margin-top:2mm}
            .total-row{padding:1.2mm 0;font-size:8px}
            .total-row.grand{padding:2mm 0;font-size:11px}
            .balance{margin-top:2mm;padding:1.5mm 2mm;font-size:8px}
            .thanks{margin-top:2mm;padding-top:2mm}
            .signatures{margin-top:5mm;gap:5mm}
            .signatures div{min-height:12mm}
        }
    </style>
</head>
<body>
    <nav class="toolbar"><a href="{{ url()->previous() }}">← Retour</a><button type="button" onclick="window.print()">Imprimer les deux exemplaires</button></nav>
    <main class="receipt-sheet">
        @foreach ([['Client', 'CLIENT'], ['TENACOS', 'ARCHIVE TENACOS']] as [$recipient, $copyLabel])
            <article class="receipt-copy">
                <div class="receipt-copy-top">
                    <img class="receipt-logo" src="{{ $setting?->img ? url('image/', $setting->img) : asset('assets/images/tena.png') }}" alt="Logo TENACOS" onerror="this.onerror=null;this.src='{{ asset('assets/images/tena.png') }}'">
                    <div class="copy-kind">Exemplaire {{ $copyLabel }}</div>
                </div>
                <header class="receipt-head">
                    <div class="brand">
                        <div><div class="brand-name">{{ $setting->name ?? 'TENACOS' }}</div><div class="brand-contact">{{ $setting->phone ?? 'Togo' }}</div></div>
                    </div>
                    <div class="receipt-title"><h1>REÇU DE COMMANDE</h1><strong>{{ $order->code }}</strong><small>{{ $order->created_at?->format('d/m/Y à H:i') ?? '—' }}</small></div>
                </header>
                @php($copyRecipient = $recipient === 'Client' ? 'Remis au client' : 'Conservé par TENACOS')
                <section class="meta">
                    <div><span>Client</span><strong>{{ $order->costumer->name ?? 'Client comptoir' }}</strong></div>
                    <div><span>Téléphone</span><strong>{{ $customerPhone ?: '—' }}</strong></div>
                    @if ($customerAddress)<div><span>Adresse de livraison</span><strong>{{ $customerAddress }}</strong></div>@endif
                    <div><span>Destination de l’exemplaire</span><strong>{{ $copyRecipient }}</strong></div>
                </section>
                <table>
                    <thead><tr><th style="width:48%">Désignation</th><th class="num">Prix unit.</th><th class="num">Qté</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach ($order->orderItems as $item)
                            <tr><td class="product-name">{{ $item->product->name ?? 'Produit' }}</td><td class="num">{{ number_format((float) $item->price, 0, ',', ' ') }} F</td><td class="num">{{ $item->quantity }}</td><td class="num">{{ number_format((float) $item->price * (int) $item->quantity, 0, ',', ' ') }} F</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <section class="totals">
                    <div class="total-row"><span>Sous-total articles</span><strong>{{ number_format($subtotal, 0, ',', ' ') }} F</strong></div>
                    <div class="total-row discount"><span>Remise ({{ number_format((float) $order->remis, 0, ',', ' ') }} %)</span><strong>− {{ number_format($discountAmount, 0, ',', ' ') }} F</strong></div>
                    <div class="total-row"><span>Articles après remise</span><strong>{{ number_format($itemsTotal, 0, ',', ' ') }} F</strong></div>
                    <div class="total-row"><span>Frais de livraison</span><strong>{{ number_format((float) $order->tax, 0, ',', ' ') }} F</strong></div>
                    <div class="total-row grand"><span>Total à payer</span><strong>{{ number_format($amountDue, 0, ',', ' ') }} F CFA</strong></div>
                    <div class="total-row"><span>Déjà payé</span><strong>{{ number_format($paidAmount, 0, ',', ' ') }} F</strong></div>
                    <div class="total-row"><span>Reste à payer</span><strong>{{ number_format($remainingAmount, 0, ',', ' ') }} F</strong></div>
                    <div class="balance"><span>{{ $remainingAmount > 0 ? 'À encaisser' : 'Commande réglée' }}</span><strong>{{ number_format($remainingAmount, 0, ',', ' ') }} F</strong></div>
                </section>
                <p class="thanks">Merci pour votre confiance. Ce reçu confirme les articles et les montants de la commande.</p>
                <footer class="signatures"><div>Signature du client</div><div>Signature TENACOS</div></footer>
            </article>
        @endforeach
    </main>
</body>
</html>
