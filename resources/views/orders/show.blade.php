@extends('layouts.admin')

@section('content')
    @php
        $settings = App\Models\Setting::all();
        $statusLabels = [
            'ordered' => ['En cours', 'warning'],
            'delivered' => ['Livrée', 'success'],
            'canceled' => ['Annulée', 'danger'],
        ];
        [$statusLabel, $statusTone] = $statusLabels[$Orders->status] ?? ['Statut inconnu', 'secondary'];
        $orderDate = $Orders->created_at ? $Orders->created_at->format('d/m/Y à H:i') : '—';
        $amountDue = (float) ($Orders->montant ?? ((float) $Orders->total + (float) $Orders->tax));
        $paidAmount = $Orders->paid_amount;
        $remainingAmount = $paidAmount === null ? null : max(0, $amountDue - (float) $paidAmount);
    @endphp

    <style>
        .order-detail { --od-red:#851b1b; --od-ink:#292d38; --od-muted:#747b89; --od-line:#e8eaf0; max-width:1540px; margin:0 auto; padding:24px; color:var(--od-ink); background:linear-gradient(180deg,#f4f6f9 0,#fafbfd 320px); border-radius:24px; }
        .order-detail * { box-sizing:border-box; }
        .od-heading { position:relative; display:flex; align-items:center; justify-content:space-between; gap:18px; width:100%; min-height:132px; margin:0 0 22px; padding:26px 30px; clear:both; color:#fff; background:radial-gradient(circle at 86% 15%,rgba(255,255,255,.16),transparent 28%),linear-gradient(120deg,#5e1115,#8e1e20 62%,#a1352c); border-radius:20px; box-shadow:0 16px 34px rgba(93,18,21,.16); overflow:hidden; }
        .od-heading > div:first-child { min-width:0; flex:1 1 auto; }
        .od-eyebrow { margin:0 0 6px; color:var(--od-red); font-size:11px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .od-heading .od-eyebrow { color:#ffd9c9; }
        .od-title { margin:0; color:inherit; font-size:clamp(28px,3vw,40px); line-height:1.1; font-weight:850; letter-spacing:-.025em; }
        .od-subtitle { display:block; position:static; max-width:680px; margin:9px 0 0; color:rgba(255,255,255,.78); line-height:1.5; overflow-wrap:anywhere; }
        .od-heading-actions { display:flex; gap:9px; flex-wrap:wrap; }
        .od-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:42px; padding:9px 15px; border:1px solid var(--od-line); border-radius:10px; background:#fff; color:var(--od-ink); font-weight:700; text-decoration:none; cursor:pointer; }
        .od-btn:hover { text-decoration:none; filter:brightness(.97); transform:translateY(-1px); }
        .od-btn-primary { color:#fff; background:var(--od-red); border-color:var(--od-red); }
        .od-heading-actions .od-btn { min-height:46px; padding:10px 16px; border-color:rgba(255,255,255,.38); background:rgba(255,255,255,.97); color:#641518; box-shadow:0 6px 16px rgba(30,0,0,.12); }
        .od-heading-actions .od-btn-primary { background:#fff; color:#641518; }
        .od-heading-facts { display:flex; flex-wrap:wrap; gap:9px; margin-top:17px; }
        .od-heading-facts span { display:flex; align-items:center; gap:7px; padding:6px 10px; border:1px solid rgba(255,255,255,.2); border-radius:9px; background:rgba(255,255,255,.09); }
        .od-heading-facts small { color:rgba(255,255,255,.7); font-size:10px; }
        .od-heading-facts strong { color:#fff; font-size:11px; }
        .od-hero-status { display:inline-flex; align-items:center; min-height:34px; padding:7px 12px; border-radius:999px; font-size:12px; font-weight:800; }
        .od-hero-status.badge-warning { color:#604100; background:#ffe29a; }
        .od-hero-status.badge-success { color:#145a39; background:#b8f0d0; }
        .od-hero-status.badge-danger { color:#851b1b; background:#ffd2d2; }
        .od-hero-status.badge-secondary { color:#424956; background:#e8ebf0; }
        .od-alert { margin-bottom:15px; border-radius:12px; }
        .od-paper, .od-panel { background:#fff; border:1px solid rgba(223,227,234,.9); border-radius:18px; box-shadow:0 12px 32px rgba(27,34,49,.055); }
        .od-paper { position:relative; clear:both; margin-top:0; padding:30px; border-color:#e3e7ed; box-shadow:0 16px 42px rgba(29,38,52,.075); }
        .od-receipt-head { display:grid; grid-template-columns:1.1fr 1fr 1fr 1fr; gap:14px; padding-bottom:24px; border-bottom:1px solid var(--od-line); }
        .od-brand { display:flex; align-items:center; gap:12px; padding:15px; border:1px solid #efdede; border-radius:14px; background:linear-gradient(145deg,#fffafa,#fff 80%); }
        .od-logo { width:58px; height:58px; flex:none; object-fit:contain; padding:5px; border:1px solid var(--od-line); border-radius:12px; background:#fff; }
        .od-brand h2, .od-info h2 { margin:0 0 10px; font-size:13px; font-weight:800; }
        .od-info { min-width:0; padding:15px; border:1px solid #e9edf2; border-radius:14px; background:linear-gradient(150deg,#fbfcfd,#f7f8fa); }
        .od-brand-name { margin:2px 0 5px; font-size:16px; font-weight:800; }
        .od-brand p, .od-info p { margin:4px 0; color:var(--od-muted); font-size:13px; line-height:1.45; overflow-wrap:anywhere; }
        .od-info strong { color:var(--od-ink); font-weight:700; }
        .od-detail-label { display:block; margin-bottom:2px; color:#8992a0; font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
        .od-delivery-phone { padding:7px 8px; border-radius:8px; background:#edf7f0; color:#176b43 !important; font-weight:800; }
        .od-status { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; }
        .od-status.badge-warning { color:#8b5b00; background:#fff2cc; }
        .od-status.badge-success { color:#176b43; background:#dff5e9; }
        .od-status.badge-danger { color:#a62828; background:#fde5e5; }
        .od-status.badge-secondary { color:#4f5663; background:#eceef2; }
        .od-items-heading { display:flex; align-items:end; justify-content:space-between; gap:12px; padding:26px 0 14px; }
        .od-items-heading h2 { margin:0; font-size:18px; font-weight:800; }
        .od-items-heading span { color:var(--od-muted); font-size:13px; }
        .od-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; overscroll-behavior-inline:contain; border:1px solid var(--od-line); border-radius:12px; }
        .od-table { width:100%; border-collapse:collapse; min-width:560px; }
        .od-table th { padding:12px; color:#687487; background:#f4f6f9; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
        .od-table td { padding:14px 12px; border-bottom:1px solid var(--od-line); vertical-align:middle; }
        .od-table tbody tr:nth-child(even) { background:#fcfcfd; }
        .od-table tbody tr:last-child td { border-bottom:0; }
        .od-table .number { text-align:right; white-space:nowrap; font-weight:700; }
        .od-product { font-weight:750; }
        .od-product small { display:block; margin-top:3px; color:var(--od-muted); font-weight:400; }
        .od-totals { display:block; margin-top:20px; overflow:hidden; border:1px solid var(--od-line); border-radius:14px; background:#fff; }
        .od-note { padding:13px 21px; border-bottom:1px solid var(--od-line); background:#f8f9fb; color:var(--od-muted); line-height:1.55; overflow-wrap:anywhere; }
        .od-total-list { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr); gap:14px 24px; margin:0; padding:14px 18px 16px; }
        .od-breakdown-title,.od-payment-summary-title { margin:0 0 5px; color:var(--od-ink); font-size:13px; font-weight:800; }
        .od-breakdown { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); align-content:start; column-gap:20px; }
        .od-breakdown-title { grid-column:1/-1; }
        .od-total-row { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:7px 0; color:var(--od-muted); border-bottom:1px solid #edf0f3; font-size:13px; }
        .od-total-row strong { color:var(--od-ink); white-space:nowrap; }
        .od-payment-summary { align-self:start; padding:12px 14px; border:1px solid #ead3d3; border-radius:10px; background:#fff8f8; }
        .od-total-final { display:flex; flex-direction:row; align-items:center; justify-content:space-between; gap:10px; margin:0; padding:3px 0 8px; border-bottom:1px solid #ead3d3; color:var(--od-muted); font-size:13px; }
        .od-total-final strong { color:var(--od-red); font-size:22px; line-height:1.1; }
        .od-payment-summary .od-total-row { padding:5px 0; border-bottom:0; }
        .od-payment-status{margin:5px 0 0;padding:6px 9px;border-left:3px solid var(--od-red);background:#fff0f0;color:#681616;font-size:12px;line-height:1.35}
        .od-payment-status strong{color:#451111}
        .od-actions { margin-top:26px; padding:24px; border:1px solid #e5e9ef; border-radius:20px; background:linear-gradient(135deg,#f8f9fb,#fff 55%); box-shadow:0 10px 28px rgba(27,34,49,.045); }
        .od-actions-title { margin:0 0 13px; font-size:19px; font-weight:800; }
        .od-action-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .od-panel { padding:20px; }
        .od-panel h3 { margin:0 0 15px; font-size:15px; font-weight:800; }
        .od-field { margin-bottom:12px; }
        .od-field label { display:block; margin-bottom:6px; color:#4d5360; font-size:12px; font-weight:750; }
        .od-field .form-control { min-height:42px; border-color:#dfe2e9; border-radius:9px; }
        .od-field .invalid-feedback { display:block; }
        .od-panel .od-btn { width:100%; }
        .od-delivery-editor { border-left:4px solid var(--od-red); }
        .od-editor-intro { margin:-6px 0 16px; color:var(--od-muted); font-size:13px; line-height:1.5; }
        .od-delivery-fields { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(250px,.85fr); gap:15px; }
        .od-optional { color:#8a919e; font-size:11px; font-weight:600; }
        .od-payment-form{grid-column:1/-1;display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:12px;margin-top:0;padding:10px 12px;border:1px solid #efc2c2;border-left:4px solid var(--od-red);background:#fff8f8}
        .od-payment-form .od-field{margin:0}
        .od-payment-form .od-btn{min-width:235px}
        .od-shipping-card,.od-package-photos{margin:18px 0;padding:16px;border:1px solid var(--od-line);background:#fbfcfd}
        .od-shipping-card h2,.od-package-photos h2{margin:0 0 10px;font-size:15px;font-weight:800}
        .od-shipping-card p{margin:4px 0;color:var(--od-muted)}
        .od-photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
        .od-photo-card{min-width:0;border:1px solid var(--od-line);background:#fff}
        .od-photo-card img{display:block;width:100%;height:140px;object-fit:cover;background:#f3f4f6}
        .od-photo-card a{display:block;padding:8px;color:var(--od-red);font-size:12px;font-weight:750;overflow-wrap:anywhere}
        .od-back { margin-top:18px; }
        @media (max-width: 1050px) {
            .od-receipt-head { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .od-action-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .od-panel:last-child { grid-column:1/-1; }
        }
        @media (max-width: 850px) {
            .order-detail { padding:18px; }
            .od-heading { align-items:flex-start; flex-direction:column; min-height:0; padding:22px; }
            .od-heading-actions { width:100%; align-items:center; }
            .od-total-list { grid-template-columns:1fr; gap:12px; }
            .od-payment-summary { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); column-gap:18px; }
            .od-payment-summary-title,.od-total-final,.od-payment-status { grid-column:1/-1; }
            .od-payment-summary .od-total-row:not(.od-total-final) { border-bottom:1px solid #ead3d3; }
            .od-payment-form { grid-template-columns:minmax(0,1fr) auto; }
        }
        @media (max-width: 650px) {
            .order-detail { width:100%; padding:10px; border-radius:12px; }
            .od-heading { display:block; min-height:0; margin-bottom:14px; padding:17px; border-radius:14px; }
            .od-title { font-size:clamp(23px,7vw,32px); overflow-wrap:anywhere; }
            .od-subtitle { font-size:13px; }
            .od-heading-facts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; margin-top:12px; }
            .od-heading-facts span { min-width:0; flex-wrap:wrap; }
            .od-heading-facts span:last-child { grid-column:1/-1; }
            .od-heading-actions { position:static; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); width:100%; margin-top:13px; gap:8px; }
            .od-heading-actions .od-hero-status { grid-column:1/-1; justify-content:flex-start; }
            .od-heading-actions .od-btn { width:100%; min-width:0; min-height:42px; padding:8px 6px; font-size:12px; white-space:normal; }
            .od-paper { padding:13px 10px; border-radius:12px; }
            .od-receipt-head { grid-template-columns:1fr 1fr; gap:16px 12px; }
            .od-brand { grid-column:1/-1; }
            .od-info { padding:12px; }
            .od-info h2 { margin-bottom:7px; }
            .od-info p, .od-brand p { font-size:12px; }
            .od-items-heading { align-items:flex-start; padding:18px 0 10px; }
            .od-items-heading h2 { font-size:16px; }
            .od-table { min-width:520px; }
            .od-table th,.od-table td { padding:10px 8px; }
            .od-total-list { gap:10px; padding:10px; }
            .od-breakdown { column-gap:10px; }
            .od-payment-summary { grid-template-columns:minmax(0,1fr) minmax(0,1fr); padding:10px; }
            .od-total-final { align-items:flex-start; flex-direction:column; gap:4px; }
            .od-total-final strong { font-size:21px; }
            .od-payment-form { grid-template-columns:1fr; gap:8px; padding:10px; }
            .od-payment-form .od-btn { width:100%; min-width:0; }
            .od-action-grid { grid-template-columns:1fr; }
            .od-delivery-fields { grid-template-columns:1fr; gap:0; }
            .od-actions { margin-top:16px; padding:13px; border-radius:14px; }
            .od-panel { padding:14px; }
            .od-panel:last-child { grid-column:auto; }
        }
        @media (max-width: 420px) {
            .order-detail { padding:7px; }
            .od-heading { padding:14px; }
            .od-heading-actions { grid-template-columns:1fr; }
            .od-heading-actions .od-hero-status,.od-heading-actions .od-btn { grid-column:1; }
            .od-receipt-head { grid-template-columns:minmax(0,1fr); gap:10px; }
            .od-brand { grid-column:auto; }
            .od-breakdown { grid-template-columns:minmax(0,1fr); }
            .od-breakdown-title { grid-column:auto; }
            .od-payment-summary { grid-template-columns:minmax(0,1fr); }
            .od-payment-summary-title,.od-total-final,.od-payment-status { grid-column:auto; }
            .od-payment-summary .od-total-row:not(.od-total-final) { border-bottom:1px solid #ead3d3; }
            .od-note { padding:10px 12px; font-size:12px; }
        }
        @media print {
            @page { size:A4; margin:14mm; }
            body { background:#fff !important; }
            body * { visibility:hidden !important; }
            #printableArea, #printableArea * { visibility:visible !important; }
            #printableArea { position:absolute; inset:0 auto auto 0; width:100%; padding:0; border:0; box-shadow:none; }
            .od-receipt-head { grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
            .od-table { min-width:0; }
            .od-table thead { display:table-header-group; }
            .od-table tr { break-inside:avoid; }
            .od-items-heading { padding-top:14px; }
            .od-totals { display:block; }
            .od-total-list { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(220px,.9fr); gap:8px 16px; padding:9px 12px; }
            .od-payment-summary { padding:8px 10px; }
            .od-paper, .od-table th { box-shadow:none; }
        }
    </style>

    <div class="order-detail">
        @if (Session::has('success'))
            <div class="alert alert-success od-alert"><strong>{{ session('success') }}</strong></div>
        @endif
        @if (Session::has('succes'))
            <div class="alert alert-success od-alert"><strong>{{ session('succes') }}</strong></div>
        @endif

        <div class="od-heading no-print">
            <div>
                <p class="od-eyebrow">Gestion des commandes</p>
                <h1 class="od-title">Commande {{ $Orders->code }}</h1>
                <p class="od-subtitle">Détail des articles, de la livraison et du règlement.</p>
                <div class="od-heading-facts"><span><small>Créée le</small><strong>{{ $orderDate }}</strong></span><span><small>Articles</small><strong>{{ $Orders->orderItems->count() }}</strong></span><span><small>Total à payer</small><strong>{{ number_format($amountDue, 0, ',', ' ') }} F CFA</strong></span></div>
            </div>
            <div class="od-heading-actions">
                <span class="od-hero-status badge-{{ $statusTone }}">{{ $statusLabel }}</span>
                <a href="{{ route('order') }}" class="od-btn"><span aria-hidden="true">←</span> Retour</a>
                <button type="button" onclick="printDiv('printableArea')" class="od-btn od-btn-primary">Imprimer la commande</button>
            </div>
        </div>

        <div id="printableArea" class="od-paper">
            <section class="od-receipt-head">
                <div class="od-brand">
                    @forelse ($settings as $setting)
                        <img src="{{ url('image/', $setting->img) }}" class="od-logo" alt="Logo {{ $setting->name ?? 'TENACOS' }}">
                        <div>
                            <p class="od-eyebrow">{{ $Orders->is_wholesale ? 'Commande grossiste' : 'Commande client' }}</p>
                            <div class="od-brand-name">{{ $setting->name ?? 'TENACOS' }}</div>
                            <p>{{ $setting->phone ?? '' }}</p>
                        </div>
                    @empty
                        <img src="{{ asset('assets/images/tena.png') }}" class="od-logo" alt="Logo TENACOS">
                        <div><p class="od-eyebrow">Commande client</p><div class="od-brand-name">TENACOS</div></div>
                    @endforelse
                </div>

                <div class="od-info">
                    <h2>Cliente</h2>
                    <p><strong>{{ $Orders->costumer->name ?? '—' }}</strong></p>
                    <p><span class="od-detail-label">Téléphone de la fiche</span>{{ $Orders->costumer->phone ?? 'Non renseigné' }}</p>
                    <p class="{{ $Orders->delivery_phone ? 'od-delivery-phone' : '' }}"><span class="od-detail-label">À appeler pour cette livraison</span>{{ $Orders->delivery_phone ?: ($Orders->costumer->phone ?? 'Non renseigné') }}</p>
                    <p>{{ ($Orders->delivery_address ?: ($Orders->costumer->adresse ?? '')) ?: 'Adresse non renseignée' }}</p>
                </div>

                <div class="od-info">
                    <h2>Livreur</h2>
                    <p><strong>{{ $Orders->user->name ?? 'Non attribué' }}</strong></p>
                    <p>{{ $Orders->user->phone ?? '—' }}</p>
                    <p>{{ $Orders->user->adresse ?? '—' }}</p>
                </div>

                <div class="od-info">
                    <h2>Informations commande</h2>
                    <p>Référence : <strong>{{ $Orders->code }}</strong></p>
                    <p>Créée le : <strong>{{ $orderDate }}</strong></p>
                    <p>Livraison : <strong>{{ $Orders->time ?: 'À définir' }}</strong></p>
                    <p>Statut : <span class="od-status badge-{{ $statusTone }}">{{ $statusLabel }}</span></p>
                    @if ($Orders->status === 'canceled' && $Orders->motif)
                        <p>Motif : <strong>{{ $Orders->motif }}</strong></p>
                    @endif
                </div>
            </section>

            @if ($Orders->delivery_mode === 'expedition')
                <section class="od-shipping-card">
                    <h2>Détails de l’expédition</h2>
                    <p><strong>Transporteur :</strong> {{ $Orders->transportCompany->name ?? 'Non renseigné' }}</p>
                    <p><strong>Destination :</strong> {{ \App\Support\WestAfricaShippingCatalog::countries()[$Orders->shipping_country]['label'] ?? $Orders->shipping_country }} · {{ $Orders->shipping_city ?: 'Ville non renseignée' }}</p>
                </section>
            @endif

            <section>
                <div class="od-items-heading">
                    <h2>Articles de la commande</h2>
                    <span>{{ $Orders->orderItems->count() }} article(s)</span>
                </div>
                <div class="od-table-wrap">
                    <table class="od-table">
                        <thead>
                            <tr><th>Désignation</th><th class="number">Prix unitaire</th><th class="number">Quantité</th><th class="number">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($Orders->orderItems as $items)
                                @php
                                    $unitPrice = (float) $items->price;
                                    $lineTotal = $unitPrice * (int) $items->quantity;
                                    $priceLabel = $items->product && $items->product->price == $items->price ? 'Détail' : 'Gros';
                                @endphp
                                <tr>
                                    <td class="od-product">{{ $items->product->name ?? 'Produit supprimé' }}
                                        @if ($items->product && $items->product->reference)
                                            <small>Réf. {{ $items->product->reference }}</small>
                                        @endif
                                    </td>
                                    <td class="number">{{ number_format($unitPrice, 0, ',', ' ') }} F <small>({{ $priceLabel }})</small></td>
                                    <td class="number">{{ $items->quantity }}</td>
                                    <td class="number">{{ number_format($lineTotal, 0, ',', ' ') }} F</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">Aucun article dans cette commande.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($Orders->deliveryPhotos->isNotEmpty() && auth()->user()?->user_type !== \App\Models\User::ROLE_CALL_CENTER)
                <section class="od-package-photos no-print">
                    <h2>Photos des colis · {{ $Orders->deliveryPhotos->count() }}</h2>
                    <div class="od-photo-grid">
                        @foreach ($Orders->deliveryPhotos as $photo)
                            <div class="od-photo-card">
                                <a href="{{ route('livrableshow.package-photo', [$Orders->id, $photo->id]) }}" target="_blank" rel="noopener">
                                    <img src="{{ route('livrableshow.package-photo', [$Orders->id, $photo->id]) }}" alt="Photo de colis pour {{ $Orders->code }}" loading="lazy">
                                </a>
                                <a href="{{ route('ordershow.package-photo.download', [$Orders->id, $photo->id]) }}">Télécharger la photo</a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="od-totals">
                <div class="od-total-list">
                    <div class="od-breakdown">
                        <h3 class="od-breakdown-title">Détail du calcul</h3>
                        <div class="od-total-row"><span>Sous-total</span><strong>{{ number_format($Orders->subtotal, 0, ',', ' ') }} F</strong></div>
                        <div class="od-total-row"><span>Remise</span><strong>{{ $Orders->remis }} %</strong></div>
                        <div class="od-total-row"><span>Total articles après remise</span><strong>{{ number_format($Orders->total, 0, ',', ' ') }} F</strong></div>
                        <div class="od-total-row"><span>Frais de livraison</span><strong>{{ number_format($Orders->tax, 0, ',', ' ') }} F</strong></div>
                    </div>
                    <div class="od-payment-summary">
                        <h3 class="od-payment-summary-title">Récapitulatif du paiement</h3>
                        <div class="od-total-row od-total-final"><span>Montant total à payer</span><strong>{{ number_format($amountDue, 0, ',', ' ') }} F</strong></div>
                        @if ($paidAmount !== null)
                            <div class="od-total-row"><span>Déjà payé</span><strong>{{ number_format((float) $paidAmount, 0, ',', ' ') }} F</strong></div>
                            <div class="od-total-row"><span>Reste à payer</span><strong>{{ number_format($remainingAmount, 0, ',', ' ') }} F</strong></div>
                        @endif
                        @if ($paidAmount !== null && $remainingAmount <= 0)
                            <div class="od-payment-status" style="border-left-color:#23824e;background:#effaf3;color:#185b37"><strong>Commande payée.</strong></div>
                        @elseif ($paidAmount !== null && $paidAmount > 0)
                            <div class="od-payment-status"><strong>Paiement partiel.</strong></div>
                        @else
                            <div class="od-payment-status"><strong>À encaisser.</strong></div>
                        @endif
                    </div>
                    @if (in_array(auth()->user()?->user_type, ['ADMINUSER', 'MNG', \App\Models\User::ROLE_COMMERCIAL_MANAGER], true))
                        <form action="{{ route('ordershow.payment', $Orders->id) }}" method="POST" class="od-payment-form no-print">
                            @csrf
                            <div class="od-field">
                                <label for="order-paid-amount">Enregistrer un encaissement (F CFA)</label>
                                <input id="order-paid-amount" type="number" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" min="0" max="{{ (int) $amountDue }}" step="1" value="{{ old('paid_amount', $paidAmount ?? 0) }}" required>
                                @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="od-btn od-btn-primary">Enregistrer le paiement</button>
                        </form>
                    @endif
                </div>
                @if ($Orders->avis)
                    <div class="od-note"><strong>Note sur la commande :</strong> {{ $Orders->avis }}</div>
                @endif
            </section>
        </div>

        <section class="od-actions no-print">
            <h2 class="od-actions-title">Actions sur la commande</h2>
            <div class="od-panel od-delivery-editor" style="margin-bottom:14px">
                <h3>Coordonnées de livraison</h3>
                <p class="od-editor-intro">Si la cliente appelle pour changer le numéro à contacter, saisis-le ici. La fiche client restera inchangée.</p>
                <form action="{{ route('ordershow.delivery-address', $Orders) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="od-field od-delivery-fields">
                      <div>
                        <label for="order-delivery-address">Adresse, quartier ou repère</label>
                        <textarea id="order-delivery-address" class="form-control @error('delivery_address') is-invalid @enderror" name="delivery_address" rows="2" maxlength="1000" required>{{ old('delivery_address', $Orders->delivery_address ?: ($Orders->costumer->adresse ?? '')) }}</textarea>
                        @error('delivery_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </div>
                      <div>
                        <label for="order-delivery-phone">Téléphone à appeler <span class="od-optional">Facultatif</span></label>
                        <input id="order-delivery-phone" type="tel" class="form-control @error('delivery_phone') is-invalid @enderror" name="delivery_phone" maxlength="50" value="{{ old('delivery_phone', $Orders->delivery_phone) }}" placeholder="Laisser vide pour {{ $Orders->costumer->phone ?? 'le numéro client' }}">
                        @error('delivery_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </div>
                    </div>
                    <button type="submit" class="od-btn od-btn-primary">Enregistrer les coordonnées</button>
                </form>
            </div>
            <div class="od-action-grid">
                @if (auth()->user()?->user_type !== \App\Models\User::ROLE_CALL_CENTER)
                <div class="od-panel">
                    <h3>Relancer ou réattribuer</h3>
                    <form action="{{ route('refreshorder', $Orders->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="od-field">
                            <label for="order-time-refresh">Heure de livraison</label>
                            <input id="order-time-refresh" type="time" class="form-control @error('time') is-invalid @enderror" name="time" value="{{ $Orders->time }}">
                            @error('time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="od-field">
                            <label for="order-deliverer">Livreur</label>
                            <select id="order-deliverer" class="js-example-basic-single form-control" name="user_take">
                                <option value="">{{ $Orders->is_wholesale ? 'Choisir un livreur général ou du pays de livraison' : 'Choisir un livreur du pays de la commande' }}</option>
                                @foreach ($livreurs as $livreur)
                                    <option value="{{ $livreur->id }}" {{ old('user_take', $Orders->user_id) == $livreur->id ? 'selected' : '' }}>{{ $livreur->name }}{{ $livreur->country_code ? ' · '.$livreur->country_code : ' · Général' }}</option>
                                @endforeach
                            </select>
                            @if ($livreurs->isEmpty()) <small class="text-muted d-block mt-1">Aucun livreur actif compatible. Vérifie les comptes livreurs et leur pays d’affectation.</small> @endif
                            @error('user_take') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="od-btn od-btn-primary">Enregistrer et relancer</button>
                    </form>
                </div>
                @endif

                <div class="od-panel">
                    <h3>Mettre à jour le statut</h3>
                    <form action="{{ route('changestatus', $Orders->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="od-field">
                            <label for="order-status">Nouveau statut</label>
                            <select name="status" id="order-status" class="form-control">
                                <option value="ordered" {{ $Orders->status === 'ordered' ? 'selected' : '' }}>En cours</option>
                                <option value="delivered" {{ $Orders->status === 'delivered' ? 'selected' : '' }}>Valider / livrée</option>
                                <option value="canceled" {{ $Orders->status === 'canceled' ? 'selected' : '' }}>Annuler</option>
                            </select>
                        </div>
                        <div class="od-field">
                            <label for="order-motif">Motif d’annulation, si nécessaire</label>
                            <input id="order-motif" type="text" class="form-control @error('motif') is-invalid @enderror" name="motif" value="{{ old('motif') }}" placeholder="Motif">
                            @error('motif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="od-btn od-btn-primary">Enregistrer le statut</button>
                    </form>
                </div>

                <div class="od-panel">
                    <h3>{{ auth()->user()?->user_type === 'PT' ? 'Reprogrammer la commande' : 'Modifier l’heure' }}</h3>
                    <form action="{{ route('edit-hours', $Orders->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @if(auth()->user()?->user_type === 'PT')
                            <div class="od-field">
                                <label for="order-date">Nouvelle date de livraison</label>
                                <input id="order-date" type="date" class="form-control @error('date_order') is-invalid @enderror" name="date_order" min="{{ now()->toDateString() }}" value="{{ old('date_order', substr((string) $Orders->date_order, 0, 10) ?: now()->toDateString()) }}" required>
                                @error('date_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif
                        <div class="od-field">
                            <label for="order-time">Nouvelle heure de livraison</label>
                            <input id="order-time" type="time" class="form-control @error('time') is-invalid @enderror" name="time" value="{{ old('time', preg_match('/^\d{2}:\d{2}$/', (string) $Orders->time) ? $Orders->time : '') }}" required>
                            @error('time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="od-btn">{{ auth()->user()?->user_type === 'PT' ? 'Reprogrammer la commande' : 'Enregistrer l’heure' }}</button>
                    </form>
                </div>

            </div>
        </section>

        <div class="od-back no-print"><a href="{{ route('order') }}" class="od-btn">← Retour à la liste des commandes</a></div>
    </div>
@endsection
