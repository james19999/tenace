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
        .dd-receipt-head { display:grid; grid-template-columns:1.1fr 1fr 1fr 1fr; gap:20px; padding-bottom:22px; border-bottom:1px solid var(--dd-line); }
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
        .dd-product { font-weight:750; }
        .dd-product small { display:block; margin-top:3px; color:var(--dd-muted); font-weight:400; }
        .dd-totals { display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,390px); gap:24px; align-items:end; padding-top:18px; border-top:1px solid var(--dd-line); }
        .dd-note { color:var(--dd-muted); line-height:1.55; overflow-wrap:anywhere; }
        .dd-total-row { display:flex; justify-content:space-between; gap:16px; padding:6px 0; color:var(--dd-muted); }
        .dd-total-row strong { color:var(--dd-ink); white-space:nowrap; }
        .dd-total-final { margin-top:6px; padding-top:12px; border-top:1px solid var(--dd-line); color:var(--dd-ink); font-size:18px; font-weight:800; }
        .dd-total-final strong { color:var(--dd-red); font-size:20px; }
        .dd-actions { margin-top:20px; }
        .dd-panel { max-width:680px; padding:19px; }
        .dd-panel h2 { margin:0 0 14px; font-size:18px; font-weight:800; }
        .dd-form-row { display:grid; grid-template-columns:minmax(170px,.7fr) minmax(200px,1.5fr) auto; gap:12px; align-items:end; }
        .dd-field label { display:block; margin-bottom:6px; color:#4d5360; font-size:12px; font-weight:750; }
        .dd-field .form-control { min-height:42px; border-color:#dfe2e9; border-radius:9px; }
        .dd-field .invalid-feedback { display:block; }
        .dd-back { margin-top:18px; }
        @media (max-width:1050px) { .dd-receipt-head { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:650px) {
            .delivery-detail { padding-top:0; }
            .dd-heading { align-items:stretch; flex-direction:column; gap:12px; min-height:0; margin-bottom:18px; }
            .dd-heading-actions, .dd-heading-actions .dd-btn { width:100%; }
            .dd-paper { padding:16px 13px; border-radius:13px; }
            .dd-receipt-head { grid-template-columns:1fr 1fr; gap:16px 12px; }
            .dd-brand { grid-column:1/-1; }
            .dd-info h2 { margin-bottom:7px; }
            .dd-info p, .dd-brand p { font-size:12px; }
            .dd-totals { grid-template-columns:1fr; gap:10px; }
            .dd-note { order:2; }
            .dd-form-row { grid-template-columns:1fr; }
            .dd-panel { padding:16px; }
            .dd-panel .dd-btn { width:100%; }
        }
        @media print {
            @page { size:A4; margin:14mm; }
            body { background:#fff !important; }
            body * { visibility:hidden !important; }
            #printableArea, #printableArea * { visibility:visible !important; }
            #printableArea { position:absolute; inset:0 auto auto 0; width:100%; padding:0; border:0; box-shadow:none; }
            .dd-receipt-head { grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
            .dd-table { min-width:0; }
            .dd-table thead { display:table-header-group; }
            .dd-table tr { break-inside:avoid; }
            .dd-items-heading { padding-top:14px; }
            .dd-totals { grid-template-columns:1fr 45%; }
            .dd-paper, .dd-table th { box-shadow:none; }
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
                    <p>{{ $Orders->user->phone ?? '—' }}</p>
                    <p>{{ $Orders->user->adresse ?? '—' }}</p>
                </div>
                <div class="dd-info">
                    <h2>Informations commande</h2>
                    <p>Référence : <strong>{{ $Orders->code }}</strong></p>
                    <p>Créée le : <strong>{{ $orderDate }}</strong></p>
                    <p>Heure de livraison : <strong>{{ $Orders->time ?: 'À définir' }}</strong></p>
                    <p>Statut : <span class="dd-status badge-{{ $statusTone }}">{{ $statusLabel }}</span></p>
                    @if ($Orders->status === 'canceled' && $Orders->motif)
                        <p>Motif : <strong>{{ $Orders->motif }}</strong></p>
                    @endif
                </div>
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
