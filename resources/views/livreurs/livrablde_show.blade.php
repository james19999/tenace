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
        .delivery-detail { --dd-red:#851b1b; --dd-ink:#292d38; --dd-muted:#747b89; --dd-line:#e8eaf0; color:var(--dd-ink); padding:8px 0 32px; }
        .delivery-detail * { box-sizing:border-box; }
        .dd-heading { position:relative; display:flex; align-items:center; justify-content:space-between; gap:18px; width:100%; min-height:92px; margin:0 0 24px; clear:both; }
        .dd-heading > div:first-child { min-width:0; flex:1 1 auto; }
        .dd-eyebrow { margin:0 0 6px; color:var(--dd-red); font-size:11px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .dd-title { margin:0; font-size:clamp(24px,3vw,34px); line-height:1.15; font-weight:800; }
        .dd-subtitle { display:block; position:static; max-width:680px; margin:7px 0 0; color:var(--dd-muted); line-height:1.5; overflow-wrap:anywhere; }
        .dd-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:42px; padding:9px 15px; border:1px solid var(--dd-line); border-radius:10px; background:#fff; color:var(--dd-ink); font-weight:700; text-decoration:none; cursor:pointer; }
        .dd-btn:hover { text-decoration:none; filter:brightness(.97); }
        .dd-btn-primary { color:#fff; background:var(--dd-red); border-color:var(--dd-red); }
        .dd-heading-actions { display:flex; gap:9px; flex-wrap:wrap; }
        .dd-paper, .dd-panel { background:#fff; border:1px solid var(--dd-line); border-radius:16px; box-shadow:0 8px 24px rgba(27,34,49,.045); }
        .dd-paper { position:relative; clear:both; padding:24px; }
        .dd-alert { margin-bottom:15px; border-radius:12px; }
        .dd-receipt-head { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:20px; padding-bottom:22px; border-bottom:1px solid var(--dd-line); }
        .dd-info{min-width:0;padding:12px;border:1px solid var(--dd-line);background:#fbfcfd}
        .dd-brand { display:flex; align-items:flex-start; gap:12px; }
        .dd-logo { width:58px; height:58px; flex:none; object-fit:contain; padding:5px; border:1px solid var(--dd-line); border-radius:12px; background:#fff; }
        .dd-brand-name { margin:2px 0 5px; font-size:16px; font-weight:800; }
        .dd-info h2 { margin:0 0 10px; font-size:13px; font-weight:800; }
        .dd-brand p, .dd-info p { margin:4px 0; color:var(--dd-muted); font-size:13px; line-height:1.45; overflow-wrap:anywhere; }
        .dd-info strong { color:var(--dd-ink); font-weight:700; }
        .dd-status { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; }
        .dd-status.badge-warning { color:#8b5b00; background:#fff2cc; }
        .dd-status.badge-success { color:#176b43; background:#dff5e9; }
        .dd-status.badge-danger { color:#a62828; background:#fde5e5; }
        .dd-status.badge-secondary { color:#4f5663; background:#eceef2; }
        .dd-items-heading { display:flex; align-items:end; justify-content:space-between; gap:12px; padding:21px 0 12px; }
        .dd-items-heading h2 { margin:0; font-size:18px; font-weight:800; }
        .dd-items-heading span { color:var(--dd-muted); font-size:13px; }
        .dd-table-wrap { overflow-x:auto; }
        .dd-table { width:100%; border-collapse:collapse; min-width:560px; }
        .dd-table th { padding:11px 12px; color:var(--dd-muted); background:#f7f8fa; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
        .dd-table td { padding:14px 12px; border-bottom:1px solid var(--dd-line); vertical-align:middle; }
        .dd-table tbody tr:last-child td { border-bottom:0; }
        .dd-table .number { text-align:right; white-space:nowrap; font-weight:700; }
        .dd-mobile-label{display:none}
        .dd-product { font-weight:750; }
        .dd-product small { display:block; margin-top:3px; color:var(--dd-muted); font-weight:400; }
        .dd-totals { display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,390px); gap:24px; align-items:end; padding-top:18px; border-top:1px solid var(--dd-line); }
        .dd-note { color:var(--dd-muted); line-height:1.55; overflow-wrap:anywhere; }
        .dd-total-row { display:flex; justify-content:space-between; gap:16px; padding:6px 0; color:var(--dd-muted); }
        .dd-total-row strong { color:var(--dd-ink); white-space:nowrap; }
        .dd-total-final { margin-top:6px; padding-top:12px; border-top:1px solid var(--dd-line); color:var(--dd-ink); font-size:18px; font-weight:800; }
        .dd-total-final strong { color:var(--dd-red); font-size:20px; }
        .dd-order-alerts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px;padding-top:17px;border-top:1px solid var(--dd-line)}
        .dd-order-alert{min-width:0;padding:14px 16px;border:1px solid #efc2c2;border-left:5px solid #b42323;background:#fff5f5;color:#681616}
        .dd-order-alert h2{margin:0 0 7px;color:#851b1b;font-size:13px;font-weight:900;letter-spacing:.04em;text-transform:uppercase}
        .dd-order-alert p{margin:3px 0;color:#572323;font-size:13px;line-height:1.45;overflow-wrap:anywhere}
        .dd-order-alert strong{color:#451111}
        .dd-payment-paid{border-color:#b9dfc9;border-left-color:#23824e;background:#effaf3;color:#185b37}
        .dd-payment-paid h2,.dd-payment-paid strong,.dd-payment-paid p{color:#185b37}
        .dd-payment-unknown{border-color:#ecd7aa;border-left-color:#bd8123;background:#fff9ed;color:#735315}
        .dd-payment-unknown h2,.dd-payment-unknown strong,.dd-payment-unknown p{color:#735315}
        .dd-payment-form{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:8px;margin-top:12px;padding-top:10px;border-top:1px solid rgba(120,50,50,.16)}
        .dd-payment-form label{grid-column:1/-1;margin:0;color:#4d2630;font-size:11px;font-weight:800}
        .dd-payment-form input{width:100%;min-width:0;height:38px;padding:7px 9px;border:1px solid #d9bcbc;background:#fff;color:var(--dd-ink)}
        .dd-payment-form button{min-height:38px;padding:7px 10px;border:1px solid var(--dd-red);background:var(--dd-red);color:#fff;font-size:12px;font-weight:800;cursor:pointer}
        .dd-payment-form .invalid-feedback{grid-column:1/-1;display:block}
        .dd-payment-edit-card{display:block}
        .dd-actions { margin-top:20px; }
        .dd-panel { max-width:680px; padding:19px; }
        .dd-panel h2 { margin:0 0 14px; font-size:18px; font-weight:800; }
        .dd-form-row { display:grid; grid-template-columns:minmax(170px,.7fr) minmax(200px,1.5fr) auto; gap:12px; align-items:end; }
        .dd-field label { display:block; margin-bottom:6px; color:#4d5360; font-size:12px; font-weight:750; }
        .dd-field .form-control { min-height:42px; border-color:#dfe2e9; border-radius:9px; }
        .dd-field .invalid-feedback { display:block; }
        .dd-back { margin-top:18px; }
        @media (max-width:1050px) { .dd-receipt-head { grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; } }
        @media (max-width:650px) {
            .delivery-detail { padding-top:0; }
            .dd-heading { align-items:stretch; flex-direction:column; gap:12px; min-height:0; margin-bottom:18px; }
            .dd-heading-actions, .dd-heading-actions .dd-btn { width:100%; }
            .dd-paper { padding:14px 11px; border-radius:0; }
            .dd-receipt-head { grid-template-columns:minmax(0,1fr); gap:9px; padding-bottom:14px; }
            .dd-brand { grid-column:1/-1; }
            .dd-brand{align-items:center;padding:3px 2px 8px}
            .dd-logo{width:48px;height:48px}
            .dd-info{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:4px 10px;padding:10px}
            .dd-info h2{grid-column:1/-1;margin:0 0 2px;font-size:12px}
            .dd-info p{min-width:0;margin:0;font-size:12px;overflow-wrap:anywhere}
            .dd-info p:last-child{grid-column:1/-1}
            .dd-brand p{font-size:12px}
            .dd-totals { grid-template-columns:1fr; gap:10px; }
            .dd-order-alerts { grid-template-columns:1fr; }
            .dd-order-alert{padding:12px}
            .dd-note { order:2; }
            .dd-form-row { grid-template-columns:1fr; }
            .dd-panel { padding:16px; }
            .dd-panel .dd-btn { width:100%; }
            .dd-table-wrap{overflow:visible}
            .dd-table{display:block;width:100%;min-width:0}
            .dd-table thead{display:none}
            .dd-table tbody{display:grid;gap:9px}
            .dd-table tbody tr{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:5px 10px;padding:11px;border:1px solid var(--dd-line);background:#fff}
            .dd-table td{display:flex;min-width:0;align-items:baseline;justify-content:space-between;gap:8px;padding:5px 0;border:0;text-align:right;white-space:normal;font-size:12px}
            .dd-table td:first-child{grid-column:1/-1;display:block;padding-bottom:8px;border-bottom:1px solid var(--dd-line);text-align:left}
            .dd-table td:nth-child(2),.dd-table td:nth-child(3){display:grid;grid-template-columns:minmax(0,1fr);align-content:start;justify-content:stretch;gap:3px;text-align:left}
            .dd-table td:nth-child(2)::before,.dd-table td:nth-child(3)::before{content:none}
            .dd-table .dd-mobile-label{display:block;color:var(--dd-muted);font-size:10px;font-weight:700;line-height:1.3}
            .dd-table td:nth-child(2) .dd-unit-price,.dd-table td:nth-child(3) .dd-cell-value{display:block;justify-self:start;white-space:nowrap;font-size:13px;font-weight:800}
            .dd-table td:nth-child(2) small{display:block;color:var(--dd-muted);font-size:10px;text-align:left;white-space:nowrap}
            .dd-table td:nth-child(4){grid-column:1/-1;border-top:1px solid var(--dd-line);font-weight:800}
            .dd-table td:nth-child(4)::before{content:'Total';color:var(--dd-muted);font-size:10px;font-weight:700}
            .dd-table td[colspan]{grid-column:1/-1;display:block;text-align:center}
            .dd-table td[colspan]::before{content:none}
            .dd-table .number{white-space:normal}
            .dd-payment-form{grid-template-columns:minmax(0,1fr)}
            .dd-payment-form label,.dd-payment-form .invalid-feedback{grid-column:auto}
            .dd-payment-form button{width:100%;min-height:42px}
        }
        @media print {
            @page { size:A4; margin:14mm; }
            body { background:#fff !important; }
            body * { visibility:hidden !important; }
            #printableArea, #printableArea * { visibility:visible !important; }
            #printableArea { position:absolute; inset:0 auto auto 0; width:100%; padding:0; border:0; box-shadow:none; }
            .dd-receipt-head { grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; }
            .dd-table { min-width:0;display:table;width:100%; }
            .dd-table-wrap{overflow:visible}
            .dd-table thead{display:table-header-group}
            .dd-table tbody{display:table-row-group}
            .dd-table tbody tr{display:table-row;padding:0;border:0;background:transparent}
            .dd-table td{display:table-cell;padding:10px 7px;text-align:left;border-bottom:1px solid var(--dd-line);font-size:10px}
            .dd-table td:first-child{display:table-cell;padding:10px 7px;border-bottom:1px solid var(--dd-line);text-align:left}
            .dd-table td::before{content:none!important}
            .dd-table .dd-mobile-label{display:none!important}
            .dd-table td:nth-child(4){grid-column:auto;border-top:0}
            .dd-table thead { display:table-header-group; }
            .dd-table tr { break-inside:avoid; }
            .dd-items-heading { padding-top:14px; }
            .dd-totals { grid-template-columns:1fr 45%; }
            .dd-paper, .dd-table th { box-shadow:none; }
            .dd-payment-edit-card{display:none!important}
        }
    </style>

    <div class="delivery-detail">
        @if (Session::has('success'))
            <div class="alert alert-success dd-alert"><strong>{{ session('success') }}</strong></div>
        @endif

        <div class="dd-heading no-print">
            <div>
                <p class="dd-eyebrow">Gestion des livraisons</p>
                <h1 class="dd-title">Commande {{ $Orders->code }}</h1>
                <p class="dd-subtitle">Informations de livraison et articles à remettre à la cliente.</p>
            </div>
            <div class="dd-heading-actions">
                @if ($Orders->status === 'delivered')
                    <button type="button" onclick="printDiv('printableArea')" class="dd-btn dd-btn-primary">Imprimer la commande</button>
                @endif
            </div>
        </div>

        <div id="printableArea" class="dd-paper">
            <section class="dd-receipt-head">
                <div class="dd-brand">
                    @forelse ($settings as $setting)
                        <img src="{{ url('image/', $setting->img) }}" class="dd-logo" alt="Logo {{ $setting->name ?? 'TENACOS' }}">
                        <div>
                            <p class="dd-eyebrow">Bon de livraison</p>
                            <div class="dd-brand-name">{{ $setting->name ?? 'TENACOS' }}</div>
                            <p>{{ $setting->phone ?? '' }}</p>
                        </div>
                    @empty
                        <img src="{{ asset('assets/images/tena.png') }}" class="dd-logo" alt="Logo TENACOS">
                        <div><p class="dd-eyebrow">Bon de livraison</p><div class="dd-brand-name">TENACOS</div></div>
                    @endforelse
                </div>
                <div class="dd-info">
                    <h2>Cliente</h2>
                    <p><strong>{{ $Orders->costumer->name ?? '—' }}</strong></p>
                    <p>{{ $Orders->costumer->phone ?? 'Téléphone non renseigné' }}</p>
                    <p>{{ $Orders->costumer->adresse ?? 'Adresse non renseignée' }}</p>
                </div>
                <div class="dd-info">
                    <h2>Livreur</h2>
                    <p><strong>{{ $Orders->user->name ?? 'Non attribué' }}</strong></p>
                    <p>{{ $Orders->user->phone ?? 'Téléphone non renseigné' }}</p>
                    <p>{{ $Orders->user->adresse ?? 'Adresse non renseignée' }}</p>
                </div>
                <div class="dd-info">
                    <h2>Commande créée par</h2>
                    <p><strong>{{ $Orders->createduser->name ?? 'Responsable non renseigné' }}</strong></p>
                    <p><strong>Téléphone :</strong> {{ $Orders->createduser->phone ?? 'Non renseigné' }}</p>
                </div>
                <div class="dd-info">
                    <h2>Informations commande</h2>
                    <p>Référence : <strong>{{ $Orders->code }}</strong></p>
                    <p>Créée le : <strong>{{ $orderDate }}</strong></p>
                    <p>Heure de livraison : <strong>{{ $Orders->time ?: 'À définir' }}</strong></p>
                    @if ($Orders->status === 'canceled' && $Orders->motif)
                        <p>Motif : <strong>{{ $Orders->motif }}</strong></p>
                    @endif
                </div>
            </section>

            <section class="dd-order-alerts" aria-label="Statut et paiement de la commande">
                <div class="dd-order-alert">
                    <h2>Statut de livraison</h2>
                    <p><strong>{{ $statusLabel }}</strong>
                        @if ($Orders->status === 'ordered')
                            · Livraison à effectuer
                        @elseif ($Orders->status === 'delivered')
                            · Livraison confirmée
                        @elseif ($Orders->status === 'canceled')
                            · Commande annulée
                        @endif
                    </p>
                    @if ($Orders->status === 'canceled' && $Orders->motif)<p>Motif : {{ $Orders->motif }}</p>@endif
                </div>
                @if ($paidAmount === null)
                    <div class="dd-order-alert">
                        <h2>À encaisser à la livraison</h2>
                        <p>Montant à recevoir : <strong>{{ number_format($amountDue, 0, ',', ' ') }} F</strong></p>
                    </div>
                @elseif ($remainingAmount <= 0)
                    <div class="dd-order-alert dd-payment-paid">
                        <h2>Déjà payé · ne rien encaisser</h2>
                        <p>Montant reçu : <strong>{{ number_format((float) $paidAmount, 0, ',', ' ') }} F</strong></p>
                    </div>
                @else
                    <div class="dd-order-alert">
                        <h2>Montant à encaisser</h2>
                        <p>Déjà reçu : <strong>{{ number_format((float) $paidAmount, 0, ',', ' ') }} F</strong></p>
                        <p>Reste à encaisser : <strong>{{ number_format($remainingAmount, 0, ',', ' ') }} F</strong></p>
                    </div>
                @endif
                @if (in_array(auth()->user()?->user_type, ['ADMINUSER', 'MNG'], true))
                    <div class="dd-order-alert dd-payment-unknown dd-payment-edit-card">
                        <h2>Mettre à jour le règlement</h2>
                        <p>Enregistre ici le montant reçu pour que le livreur sache quoi encaisser.</p>
                        <form action="{{ route('livrableshow.payment', $Orders->id) }}" method="POST" class="dd-payment-form">
                            @csrf
                            <label for="delivery-paid-amount">Montant déjà encaissé (F CFA)</label>
                            <input id="delivery-paid-amount" type="number" name="paid_amount" min="0" max="{{ (int) $amountDue }}" step="1" value="{{ old('paid_amount', $paidAmount ?? 0) }}" required>
                            <button type="submit">Enregistrer</button>
                            @error('paid_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </form>
                    </div>
                @endif
            </section>

            <section>
                <div class="dd-items-heading">
                    <h2>Articles à livrer</h2>
                    <span>{{ $Orders->orderItems->count() }} article(s)</span>
                </div>
                <div class="dd-table-wrap">
                    <table class="dd-table">
                        <thead><tr><th>Désignation</th><th class="number">Prix unitaire</th><th class="number">Quantité</th><th class="number">Total</th></tr></thead>
                        <tbody>
                            @forelse ($Orders->orderItems as $items)
                                @php
                                    $unitPrice = (float) $items->price;
                                    $lineTotal = $unitPrice * (int) $items->quantity;
                                    $priceLabel = $items->product && $items->product->price == $items->price ? 'Détail' : 'Gros';
                                @endphp
                                <tr>
                                    <td class="dd-product">{{ $items->product->name ?? 'Produit supprimé' }}
                                        @if ($items->product && $items->product->reference)<small>Réf. {{ $items->product->reference }}</small>@endif
                                    </td>
                                    <td class="number"><span class="dd-mobile-label">Prix unitaire :</span><span class="dd-unit-price">{{ number_format($unitPrice, 0, ',', ' ') }} F</span><small>({{ $priceLabel }})</small></td>
                                    <td class="number"><span class="dd-mobile-label">Quantité</span><span class="dd-cell-value">{{ $items->quantity }}</span></td>
                                    <td class="number">{{ number_format($lineTotal, 0, ',', ' ') }} F</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">Aucun article dans cette commande.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="dd-totals">
                <div class="dd-note">
                    @if ($Orders->avis)<strong>Note</strong><br>{{ $Orders->avis }}@else Merci pour votre achat chez TENACOS Cosmétiques.@endif
                </div>
                <div>
                    <div class="dd-total-row"><span>Sous-total</span><strong>{{ number_format($Orders->subtotal, 0, ',', ' ') }} F</strong></div>
                    <div class="dd-total-row"><span>Remise</span><strong>{{ $Orders->remis }} %</strong></div>
                    <div class="dd-total-row"><span>Total articles</span><strong>{{ number_format($Orders->total, 0, ',', ' ') }} F</strong></div>
                    <div class="dd-total-row"><span>Frais de livraison</span><strong>{{ number_format($Orders->tax, 0, ',', ' ') }} F</strong></div>
                    <div class="dd-total-row dd-total-final"><span>Montant à payer</span><strong>{{ number_format($Orders->total + $Orders->tax, 0, ',', ' ') }} F</strong></div>
                </div>
            </section>

        </div>

        @if ($Orders->status !== 'delivered')
            <section class="dd-actions no-print">
                <form action="{{ route('changestatususer', $Orders->id) }}" method="POST" class="dd-panel">
                    @csrf
                    @method('PUT')
                    <h2>Confirmer le résultat de la livraison</h2>
                    <div class="dd-form-row">
                        <div class="dd-field">
                            <label for="delivery-status">Statut</label>
                            <select name="status" id="delivery-status" class="form-control">
                                <option value="delivered">Valider la livraison</option>
                                <option value="canceled">Annuler la commande</option>
                            </select>
                        </div>
                        <div class="dd-field">
                            <label for="delivery-motif">Motif, si annulation</label>
                            <input id="delivery-motif" type="text" class="form-control @error('motif') is-invalid @enderror" name="motif" value="{{ old('motif') }}" placeholder="Préciser le motif d’annulation">
                            @error('motif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="dd-btn dd-btn-primary">Enregistrer</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection
