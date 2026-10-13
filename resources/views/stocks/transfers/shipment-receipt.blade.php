@extends('layouts.admin')

@section('content')
    <div class="stock-control-page shipment-receipt-page">
        <div class="shipment-receipt-toolbar"><a class="stock-control-button" href="{{ route('stock-control.transfers.show', $transfer) }}"><span class="material-icons">arrow_back</span>Retour au transfert</a><button class="stock-control-button stock-control-button-primary" id="shipmentReceiptPrint" type="button"><span class="material-icons">print</span>Imprimer le reçu</button></div>
        <main class="shipment-receipt-paper">
            <div class="shipment-receipt-header">
                <div><div class="shipment-receipt-brandline"><img src="{{ asset('assets/images/tenaSS.png') }}" alt="Tenace Cosmetique" class="shipment-receipt-logo"><span class="shipment-receipt-brand">TENACOS · CONTRÔLE DES STOCKS</span></div><h1>Bon d’expédition</h1><p>Document de transfert entre emplacements</p></div>
                <div class="shipment-receipt-reference"><span>Référence du transfert</span><strong>{{ $transfer->transfer_number }}</strong><small>{{ $shipment->event_number }}</small></div>
            </div>

            <section class="shipment-receipt-parties">
                <article><span>Emplacement source</span><strong>{{ $transfer->source->name }}</strong><small>{{ $transfer->source->code }} · Responsable : {{ $transfer->source->responsible?->name ?? 'Non désigné' }}</small></article>
                <div class="shipment-receipt-arrow material-icons" aria-hidden="true">east</div>
                <article><span>Destination</span><strong>{{ $transfer->destination->name }}</strong><small>{{ $transfer->destination->code }} · Responsable : {{ $transfer->destination->responsible?->name ?? 'Non désigné' }}</small></article>
            </section>

            <section class="shipment-receipt-meta">
                <div><span>Date et heure de départ</span><strong>{{ $shipment->occurred_at?->format('d/m/Y à H:i') ?? '—' }}</strong></div>
                <div><span>Expédié par</span><strong>{{ $shipment->user?->name ?? $transfer->shipper?->name ?? '—' }}</strong></div>
                <div><span>Demandeur</span><strong>{{ $transfer->requester?->name ?? '—' }}</strong></div>
                <div><span>Autorisé par</span><strong>{{ $transfer->authorizer?->name ?? '—' }}</strong></div>
                <div><span>Motif</span><strong>{{ $transfer->reason ?: '—' }}</strong></div>
                <div><span>Référence mouvement</span><strong>{{ $shipment->movement?->movement_number ?? '—' }}</strong></div>
            </section>

            <section class="shipment-receipt-products">
                <div class="shipment-receipt-section-heading"><h2>Articles expédiés</h2><span>{{ $shipment->lines->count() }} ligne(s)</span></div>
                <table><thead><tr><th>Produit</th><th>Lot</th><th>Quantité expédiée</th><th>Unité</th></tr></thead><tbody>
                    @foreach($shipment->lines as $line)
                        <tr><td><strong>{{ $line->product->name }}</strong><small>Réf. ligne {{ $line->transfer_line_id }}</small></td><td>{{ $line->lot?->lot_code ?: 'Sans lot' }}</td><td class="shipment-receipt-quantity">{{ number_format((float) $line->quantity, 0, ',', ' ') }}</td><td>unité(s)</td></tr>
                    @endforeach
                </tbody></table>
            </section>

            @if($shipment->notes || $transfer->notes)
                <section class="shipment-receipt-notes"><h2>Notes</h2>@if($shipment->notes)<p><strong>Expédition :</strong> {{ $shipment->notes }}</p>@endif @if($transfer->notes)<p><strong>Demande :</strong> {{ $transfer->notes }}</p>@endif</section>
            @endif

            <footer class="shipment-receipt-signatures"><div><strong>Départ confirmé par</strong><span>{{ $shipment->user?->name ?? $transfer->shipper?->name ?? ' ' }}</span><small>Nom, date et signature</small></div><div><strong>Réception confirmée par</strong><span></span><small>Nom, date et signature à l’arrivée</small></div></footer>
            <div class="shipment-receipt-footnote">Ce bon atteste la sortie des articles de l’emplacement source et leur mise en transit. La réception à destination sera enregistrée séparément.</div>
        </main>
    </div>

    <style>
        .shipment-receipt-toolbar{display:flex;justify-content:space-between;gap:12px;margin:14px 0}
        .shipment-receipt-paper{max-width:980px;margin:0 auto;padding:36px;border:1px solid #dfe2e8;background:#fff;color:#292d38}
        .shipment-receipt-header{position:static;display:flex;width:100%;height:auto;min-height:0;align-items:flex-start;justify-content:space-between;gap:24px;margin:0;padding:0 0 22px;border:0;border-bottom:2px solid #851b1b;border-radius:0;background:#fff;color:#292d38;box-shadow:none}
        .shipment-receipt-brand{font-size:10px;font-weight:850;letter-spacing:.14em;color:#851b1b}
        .shipment-receipt-header h1{margin:8px 0 4px;font-size:30px;line-height:1.15}
        .shipment-receipt-header p{margin:0;color:#747b89;font-size:13px}
        .shipment-receipt-brandline{display:flex;align-items:center;gap:12px;margin-bottom:8px}
        .shipment-receipt-logo{display:block;width:118px;height:auto;object-fit:contain}
        .shipment-receipt-reference{display:flex;min-width:210px;flex-direction:column;gap:5px;text-align:right}
        .shipment-receipt-reference span,.shipment-receipt-parties article>span,.shipment-receipt-meta span{color:#747b89;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
        .shipment-receipt-reference strong{color:#851b1b;font-size:18px}
        .shipment-receipt-reference small{color:#747b89}
        .shipment-receipt-parties{display:grid;grid-template-columns:1fr 44px 1fr;align-items:center;gap:15px;margin:24px 0}
        .shipment-receipt-parties article{display:grid;gap:7px;min-height:105px;padding:16px;border:1px solid #dfe2e8}
        .shipment-receipt-parties article strong{font-size:16px}
        .shipment-receipt-parties article small{color:#747b89}
        .shipment-receipt-arrow{color:#851b1b;text-align:center}
        .shipment-receipt-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;border-top:1px solid #dfe2e8;border-left:1px solid #dfe2e8}
        .shipment-receipt-meta>div{display:grid;align-content:start;gap:7px;min-height:70px;padding:12px;border-right:1px solid #dfe2e8;border-bottom:1px solid #dfe2e8}
        .shipment-receipt-meta strong{font-size:12px;overflow-wrap:anywhere}
        .shipment-receipt-products{margin-top:27px}
        .shipment-receipt-section-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .shipment-receipt-section-heading h2,.shipment-receipt-notes h2{margin:0;font-size:17px}
        .shipment-receipt-section-heading span{color:#747b89;font-size:11px}
        .shipment-receipt-products table{width:100%;border-collapse:collapse}
        .shipment-receipt-products th,.shipment-receipt-products td{padding:12px;border:1px solid #dfe2e8;text-align:left;font-size:12px}
        .shipment-receipt-products th{background:#f5f6f8;color:#626a78;font-size:10px;text-transform:uppercase}
        .shipment-receipt-products td:first-child small{display:block;margin-top:4px;color:#747b89;font-size:10px}
        .shipment-receipt-quantity{font-size:15px!important;font-weight:850}
        .shipment-receipt-notes{margin-top:22px;padding:14px;border:1px solid #dfe2e8}
        .shipment-receipt-notes p{margin:8px 0 0;font-size:12px;line-height:1.5}
        .shipment-receipt-signatures{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:42px}
        .shipment-receipt-signatures>div{display:flex;min-height:105px;flex-direction:column;gap:9px;padding:12px;border:1px solid #dfe2e8}
        .shipment-receipt-signatures strong{font-size:11px}
        .shipment-receipt-signatures span{min-height:25px;border-bottom:1px solid #aeb4bf;font-size:12px}
        .shipment-receipt-signatures small,.shipment-receipt-footnote{color:#747b89;font-size:10px}
        .shipment-receipt-footnote{margin-top:18px;padding-top:11px;border-top:1px solid #dfe2e8;line-height:1.5}
        @media(max-width:650px){.shipment-receipt-paper{padding:18px}.shipment-receipt-header{flex-direction:column}.shipment-receipt-reference{text-align:left}.shipment-receipt-parties{grid-template-columns:1fr}.shipment-receipt-arrow{transform:rotate(90deg)}.shipment-receipt-meta{grid-template-columns:repeat(2,minmax(0,1fr))}.shipment-receipt-products{overflow-x:auto}.shipment-receipt-products table{min-width:580px}}
        @media print{@page{size:A4;margin:12mm}html,body{width:100%!important;min-height:0!important;margin:0!important;padding:0!important;background:#fff!important}.main-wrapper{display:block!important;width:100%!important;min-height:0!important;margin:0!important;padding:0!important}.main-wrapper .sidebar,.main-wrapper aside,.overlay-mask,.right-area>header,.navbar,.header,.shipment-receipt-toolbar{display:none!important}.right-area,.main-content,.content-wrapper,.shipment-receipt-page{display:block!important;width:100%!important;max-width:none!important;min-height:0!important;margin:0!important;padding:0!important;overflow:visible!important}.shipment-receipt-paper{display:block!important;max-width:none;border:0;padding:0}.shipment-receipt-header{display:flex!important;background:#fff!important;color:#292d38!important}.shipment-receipt-parties article,.shipment-receipt-meta>div,.shipment-receipt-products th,.shipment-receipt-products td,.shipment-receipt-signatures>div{-webkit-print-color-adjust:exact;print-color-adjust:exact}.shipment-receipt-products tr,.shipment-receipt-signatures{break-inside:avoid}}
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('shipmentReceiptPrint');
            if (!printButton) return;
            printButton.addEventListener('click', function (event) {
                event.preventDefault();
                window.focus();
                window.setTimeout(function () { window.print(); }, 100);
            });
        });
    </script>
@endsection
