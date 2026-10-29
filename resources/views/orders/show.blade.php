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
        .order-detail { --od-red:#851b1b; --od-ink:#292d38; --od-muted:#747b89; --od-line:#e8eaf0; color:var(--od-ink); padding:8px 0 32px; }
        .order-detail * { box-sizing:border-box; }
        .od-heading { position:relative; display:flex; align-items:center; justify-content:space-between; gap:18px; width:100%; min-height:92px; margin:0 0 24px; clear:both; }
        .od-heading > div:first-child { min-width:0; flex:1 1 auto; }
        .od-eyebrow { margin:0 0 6px; color:var(--od-red); font-size:11px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .od-title { margin:0; font-size:clamp(24px,3vw,34px); line-height:1.15; font-weight:800; }
        .od-subtitle { display:block; position:static; max-width:680px; margin:7px 0 0; color:var(--od-muted); line-height:1.5; overflow-wrap:anywhere; }
        .od-heading-actions { display:flex; gap:9px; flex-wrap:wrap; }
        .od-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:42px; padding:9px 15px; border:1px solid var(--od-line); border-radius:10px; background:#fff; color:var(--od-ink); font-weight:700; text-decoration:none; cursor:pointer; }
        .od-btn:hover { text-decoration:none; filter:brightness(.97); }
        .od-btn-primary { color:#fff; background:var(--od-red); border-color:var(--od-red); }
        .od-alert { margin-bottom:15px; border-radius:12px; }
        .od-paper, .od-panel { background:#fff; border:1px solid var(--od-line); border-radius:16px; box-shadow:0 8px 24px rgba(27,34,49,.045); }
        .od-paper { position:relative; clear:both; margin-top:0; padding:24px; }
        .od-receipt-head { display:grid; grid-template-columns:1.1fr 1fr 1fr 1fr; gap:20px; padding-bottom:22px; border-bottom:1px solid var(--od-line); }
        .od-brand { display:flex; align-items:flex-start; gap:12px; }
        .od-logo { width:58px; height:58px; flex:none; object-fit:contain; padding:5px; border:1px solid var(--od-line); border-radius:12px; background:#fff; }
        .od-brand h2, .od-info h2 { margin:0 0 10px; font-size:13px; font-weight:800; }
        .od-brand-name { margin:2px 0 5px; font-size:16px; font-weight:800; }
        .od-brand p, .od-info p { margin:4px 0; color:var(--od-muted); font-size:13px; line-height:1.45; overflow-wrap:anywhere; }
        .od-info strong { color:var(--od-ink); font-weight:700; }
        .od-status { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; }
        .od-status.badge-warning { color:#8b5b00; background:#fff2cc; }
        .od-status.badge-success { color:#176b43; background:#dff5e9; }
        .od-status.badge-danger { color:#a62828; background:#fde5e5; }
        .od-status.badge-secondary { color:#4f5663; background:#eceef2; }
        .od-items-heading { display:flex; align-items:end; justify-content:space-between; gap:12px; padding:21px 0 12px; }
        .od-items-heading h2 { margin:0; font-size:18px; font-weight:800; }
        .od-items-heading span { color:var(--od-muted); font-size:13px; }
        .od-table-wrap { overflow-x:auto; }
        .od-table { width:100%; border-collapse:collapse; min-width:560px; }
        .od-table th { padding:11px 12px; color:var(--od-muted); background:#f7f8fa; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
        .od-table td { padding:14px 12px; border-bottom:1px solid var(--od-line); vertical-align:middle; }
        .od-table tbody tr:last-child td { border-bottom:0; }
        .od-table .number { text-align:right; white-space:nowrap; font-weight:700; }
        .od-product { font-weight:750; }
        .od-product small { display:block; margin-top:3px; color:var(--od-muted); font-weight:400; }
        .od-totals { display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,390px); gap:24px; align-items:end; padding-top:18px; border-top:1px solid var(--od-line); }
        .od-note { color:var(--od-muted); line-height:1.55; overflow-wrap:anywhere; }
        .od-total-list { margin:0; }
        .od-total-row { display:flex; justify-content:space-between; gap:16px; padding:6px 0; color:var(--od-muted); }
        .od-total-row strong { color:var(--od-ink); white-space:nowrap; }
        .od-total-final { margin-top:6px; padding-top:12px; border-top:1px solid var(--od-line); color:var(--od-ink); font-size:18px; font-weight:800; }
        .od-total-final strong { color:var(--od-red); font-size:20px; }
        .od-actions { margin-top:22px; }
        .od-actions-title { margin:0 0 13px; font-size:19px; font-weight:800; }
        .od-action-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .od-panel { padding:18px; }
        .od-panel h3 { margin:0 0 15px; font-size:15px; font-weight:800; }
        .od-field { margin-bottom:12px; }
        .od-field label { display:block; margin-bottom:6px; color:#4d5360; font-size:12px; font-weight:750; }
        .od-field .form-control { min-height:42px; border-color:#dfe2e9; border-radius:9px; }
        .od-field .invalid-feedback { display:block; }
        .od-panel .od-btn { width:100%; }
        .od-payment-status{margin:16px 0;padding:12px;border-left:4px solid var(--od-red);background:#fff5f5;color:#681616;font-size:13px;line-height:1.5}
        .od-payment-status strong{color:#451111}
        .od-payment-form{margin-top:13px;padding:13px;border:1px solid #efc2c2;border-left:4px solid var(--od-red);background:#fff8f8}
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
        @media (max-width: 650px) {
            .order-detail { padding-top:0; }
            .od-heading { display:block; min-height:0; margin-bottom:18px; }
            .od-heading-actions { position:static; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); width:100%; margin-top:14px; }
            .od-heading-actions .od-btn { width:100%; min-width:0; padding:9px 8px; }
            .od-paper { padding:16px 13px; border-radius:13px; }
            .od-receipt-head { grid-template-columns:1fr 1fr; gap:16px 12px; }
            .od-brand { grid-column:1/-1; }
            .od-info h2 { margin-bottom:7px; }
            .od-info p, .od-brand p { font-size:12px; }
            .od-totals { grid-template-columns:1fr; gap:10px; }
            .od-note { order:2; }
            .od-action-grid { grid-template-columns:1fr; }
            .od-panel:last-child { grid-column:auto; }
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
            .od-totals { grid-template-columns:1fr 45%; }
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
            </div>
            <div class="od-heading-actions">
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
                    <p>{{ $Orders->costumer->phone ?? 'Téléphone non renseigné' }}</p>
                    <p>{{ $Orders->costumer->adresse ?? 'Adresse non renseignée' }}</p>
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

            @if ($Orders->deliveryPhotos->isNotEmpty())
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
                <div class="od-note">
                    @if ($Orders->avis)
                        <strong>Note</strong><br>{{ $Orders->avis }}
                    @else
                        Merci pour votre achat chez TENACOS Cosmétiques.
                    @endif
                </div>
                <div class="od-total-list">
                    <div class="od-total-row"><span>Sous-total</span><strong>{{ number_format($Orders->subtotal, 0, ',', ' ') }} F</strong></div>
                    <div class="od-total-row"><span>Remise</span><strong>{{ $Orders->remis }} %</strong></div>
                    <div class="od-total-row"><span>Total articles</span><strong>{{ number_format($Orders->total, 0, ',', ' ') }} F</strong></div>
                    <div class="od-total-row"><span>Frais de livraison</span><strong>{{ number_format($Orders->tax, 0, ',', ' ') }} F</strong></div>
                    <div class="od-total-row od-total-final"><span>Montant à payer</span><strong>{{ number_format($amountDue, 0, ',', ' ') }} F</strong></div>
                    @if ($paidAmount !== null)
                        <div class="od-total-row"><span>Déjà payé</span><strong>{{ number_format((float) $paidAmount, 0, ',', ' ') }} F</strong></div>
                        <div class="od-total-row"><span>Reste à payer</span><strong>{{ number_format($remainingAmount, 0, ',', ' ') }} F</strong></div>
                    @endif
                    @if (in_array(auth()->user()?->user_type, ['ADMINUSER', 'MNG', \App\Models\User::ROLE_COMMERCIAL_MANAGER], true))
                        <form action="{{ route('ordershow.payment', $Orders->id) }}" method="POST" class="od-payment-form no-print">
                            @csrf
                            <div class="od-field">
                                <label for="order-paid-amount">Montant déjà payé (F CFA)</label>
                                <input id="order-paid-amount" type="number" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" min="0" max="{{ (int) $amountDue }}" step="1" value="{{ old('paid_amount', $paidAmount ?? 0) }}" required>
                                @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="od-btn od-btn-primary">Enregistrer le paiement</button>
                        </form>
                    @endif
                </div>
            </section>
            @if ($paidAmount !== null && $remainingAmount <= 0)
                <div class="od-payment-status" style="border-left-color:#23824e;background:#effaf3;color:#185b37"><strong>Commande déjà payée :</strong> ne rien réclamer à la cliente.</div>
            @elseif ($paidAmount !== null && $paidAmount > 0)
                <div class="od-payment-status"><strong>Paiement partiel :</strong> reste {{ number_format($remainingAmount, 0, ',', ' ') }} F à percevoir.</div>
            @else
                <div class="od-payment-status"><strong>À encaisser :</strong> {{ number_format($amountDue, 0, ',', ' ') }} F.</div>
            @endif
        </div>

        <section class="od-actions no-print">
            <h2 class="od-actions-title">Actions sur la commande</h2>
            <div class="od-action-grid">
                <div class="od-panel">
                    <h3>Relancer ou réattribuer</h3>
                    <form action="{{ route('refreshorder', $Orders->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="od-field">
                            <label for="order-address">Adresse de livraison</label>
                            <input id="order-address" type="text" class="form-control @error('adresse') is-invalid @enderror" name="adresse" value="{{ $Orders->costumer->adresse ?? '' }}" placeholder="Adresse">
                            @error('adresse') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
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
