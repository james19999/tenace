@extends('layouts.admin')

@section('content')
    @php
        $receiptStatuses = [
            'draft' => ['Brouillon', 'neutral'],
            'pending' => ['Livraison à valider', 'pending'],
            'partial' => ['Partielle · reliquat ouvert', 'partial'],
            'validated' => ['Complète', 'validated'],
            'canceled' => ['Annulée', 'neutral'],
        ];
        [$statusText, $statusClass] = $receiptStatuses[$receipt->status] ?? [$receipt->status, 'neutral'];
        $expectedTotal = (float) $receipt->lines->sum('quantity_expected');
        $receivedTotal = (float) $receipt->lines->sum('quantity_received');
        $damagedTotal = (float) $receipt->lines->sum('quantity_damaged');
        $pendingEvent = $receipt->events->firstWhere('status', 'submitted');
        $latestApprovedEvent = $receipt->events->where('status', 'approved')->sortByDesc('occurred_at')->first();
        $expectedReceiptAt = $receipt->expected_at ?? $receipt->occurred_at;
    @endphp
    <div class="stock-control-page stock-receipts-page">
        <div class="receipt-page-heading receipt-detail-heading">
            <a href="{{ route('stock-control.receipts.index') }}" class="receipt-back-link"><span class="material-icons">arrow_back</span>Toutes les réceptions</a>
            <div class="receipt-title-line"><div><span class="stock-control-kicker">RÉCEPTION FOURNISSEUR</span><h1>{{ $receipt->receipt_number }}</h1></div><div class="transfer-detail-heading-actions">@if($latestApprovedEvent)<a class="stock-control-button" href="{{ route('stock-control.receipts.delivery-receipt', [$receipt, $latestApprovedEvent]) }}" target="_blank" rel="noopener"><span class="material-icons">print</span>Dernier bon</a>@endif<span class="receipt-status-pill {{ $statusClass }}">{{ $statusText }}</span></div></div>
            <p>{{ $receipt->origin_name ?: 'Origine non précisée' }} · Réception prévue le {{ ($receipt->expected_at ?? $receipt->occurred_at)?->format('d/m/Y à H:i') ?? 'Date non précisée' }}</p>
        </div>

        @if (session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="stock-control-alert stock-control-alert-error" role="alert"><span class="material-icons">error_outline</span><div><strong>Action non effectuée.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

        <section class="receipt-metric-grid">
            <article><span>Destination</span><strong>{{ $receipt->location->name }}</strong><small>{{ $receipt->location->code }}</small></article>
            <article><span>Quantité attendue</span><strong>{{ number_format($expectedTotal, 3, ',', ' ') }}</strong><small>{{ $receipt->lines->count() }} référence(s)</small></article>
            <article><span>Physiquement reçue</span><strong>{{ number_format($receivedTotal, 3, ',', ' ') }}</strong><small>Dont {{ number_format($damagedTotal, 3, ',', ' ') }} abîmée(s)</small></article>
            <article><span>Créée par</span><strong>{{ $receipt->creator?->name ?? 'Utilisateur inconnu' }}</strong><small>{{ $receipt->created_at?->format('d/m/Y H:i') }}</small></article>
        </section>

        <section class="stock-control-card receipt-detail-card">
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">RAPPROCHEMENT</span><h2>Articles attendus et reçus</h2></div></div>
            <div class="receipt-detail-lines">
                @foreach($receipt->lines as $line)
                    @php $remaining = max(0, (float) $line->quantity_expected - (float) $line->quantity_received); @endphp
                    <article class="receipt-detail-line">
                        <div class="receipt-detail-product"><div class="receipt-line-product-mark"><span class="material-icons">inventory_2</span></div><div><strong>{{ $line->product->name }}</strong><small>@if($line->lot_code)Lot {{ $line->lot_code }}@else Lot non renseigné@endif @if($line->expires_at) · Exp. {{ $line->expires_at->format('d/m/Y') }}@endif</small></div></div>
                        <div class="receipt-quantity-stat"><span>Attendu</span><strong>{{ number_format((float) $line->quantity_expected, 3, ',', ' ') }}</strong></div>
                        <div class="receipt-quantity-stat"><span>Reçu</span><strong>{{ number_format((float) $line->quantity_received, 3, ',', ' ') }}</strong></div>
                        <div class="receipt-quantity-stat"><span>Abîmé</span><strong class="{{ (float) $line->quantity_damaged > 0 ? 'receipt-damaged-qty' : '' }}">{{ number_format((float) $line->quantity_damaged, 3, ',', ' ') }}</strong></div>
                        <div class="receipt-quantity-stat"><span>Reliquat</span><strong>{{ number_format($remaining, 3, ',', ' ') }}</strong></div>
                    </article>
                @endforeach
            </div>
            @if($receipt->notes)<div class="receipt-internal-note"><strong>Note</strong><p>{{ $receipt->notes }}</p></div>@endif
            @if($receipt->attachments->isNotEmpty())
                <div class="receipt-attachments"><strong>Documents de la réception</strong>@include('stocks.partials.attachment-list', ['attachments' => $receipt->attachments])</div>
            @endif
        </section>

        @if(in_array($receipt->status, ['draft', 'partial'], true) && ! $pendingEvent)
            <section class="stock-control-card receipt-event-card receipt-schedule-card">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">PLANIFICATION</span><h2>Date prévue de réception</h2></div></div>
                <form method="POST" action="{{ route('stock-control.receipts.schedule', $receipt) }}" class="receipt-schedule-form">
                    @csrf
                    @method('PUT')
                    <label class="stock-control-field"><span>Modifier la date et l’heure prévues</span><input type="datetime-local" name="expected_at" value="{{ old('expected_at', $expectedReceiptAt?->format('Y-m-d\\TH:i')) }}" required></label>
                    <button type="submit" class="stock-control-button"><span class="material-icons">event</span>Enregistrer la date</button>
                </form>
            </section>
        @endif

        @if($pendingEvent)
            <section class="stock-control-card receipt-event-card receipt-pending-card">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">VALIDATION REQUISE · {{ $pendingEvent->event_number }}</span><h2>Livraison saisie par {{ $pendingEvent->receiver?->name ?? 'un utilisateur' }}</h2></div><span class="receipt-status-pill pending">En attente</span></div>
                <div class="receipt-event-lines">
                    @foreach($pendingEvent->lines as $eventLine)
                        <div class="receipt-event-line"><strong>{{ $eventLine->product->name }}</strong><span>Reçu : {{ number_format((float) $eventLine->quantity_received, 3, ',', ' ') }}</span><span>Abîmé : {{ number_format((float) $eventLine->quantity_damaged, 3, ',', ' ') }}</span>@if($eventLine->discrepancy_note)<small>{{ $eventLine->discrepancy_note }}</small>@endif</div>
                    @endforeach
                </div>
                <p class="receipt-awaiting-info">Aucune quantité n’a été ajoutée au stock. La validation enregistrera le mouvement et isolera les produits abîmés.</p>
                @if($pendingEvent->attachments->isNotEmpty())<div class="receipt-attachments"><strong>Justificatifs de cette livraison</strong>@include('stocks.partials.attachment-list', ['attachments' => $pendingEvent->attachments])</div>@endif
                @if($rules['allow_self_approval'] || (int) $pendingEvent->received_by !== (int) auth()->id())
                    <form method="POST" action="{{ route('stock-control.receipts.deliveries.approve', [$receipt, $pendingEvent]) }}" class="receipt-approval-form" data-stock-confirm-title="Approuver cette livraison ?" data-stock-confirm-message="Les quantités validées seront inscrites au journal du stock. Vérifie les quantités, lots et justificatifs avant de continuer.">
                        @csrf
                        <label class="receipt-confirm-check"><input type="checkbox" required><span>J’ai contrôlé les quantités, les lots et les justificatifs.</span></label>
                        <button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">task_alt</span>Approuver et mettre en stock</button>
                    </form>
                @else
                    <div class="receipt-awaiting-info">Une autre personne habilitée doit approuver cette livraison.</div>
                @endif
            </section>
        @endif

        @if(in_array($receipt->status, ['draft', 'partial'], true) && ! $pendingEvent)
            @if(now()->greaterThanOrEqualTo($expectedReceiptAt))
                <section class="stock-control-card receipt-event-card">
                    <div class="stock-control-section-head"><div><span class="stock-control-kicker">NOUVELLE LIVRAISON</span><h2>Saisir les quantités constatées</h2></div></div>
                    <div class="receipt-validation-note"><span class="material-icons">rule</span><p>La saisie sera envoyée à une autre personne pour validation. Le stock restera inchangé jusque-là. Les quantités abîmées seront dirigées vers <strong>BLOQUÉ</strong>.</p></div>
                    <form method="POST" action="{{ route('stock-control.receipts.deliveries.store', $receipt) }}" enctype="multipart/form-data" class="receipt-delivery-form">
                        @csrf
                        <label class="stock-control-field receipt-delivery-date"><span>Date et heure réelles de réception</span><input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', $expectedReceiptAt->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required><small>Préremplie avec la date prévue ; modifie-la si la livraison est arrivée à un autre moment.</small></label>
                    <div class="receipt-delivery-lines">
                        @foreach($receipt->lines as $line)
                            @php $remaining = max(0, (float) $line->quantity_expected - (float) $line->quantity_received); @endphp
                            @if($remaining > 0)
                                <div class="receipt-delivery-line">
                                    <div class="receipt-delivery-product"><strong>{{ $line->product->name }}</strong><small>Reliquat attendu : {{ number_format($remaining, 3, ',', ' ') }} @if($line->lot_code)· Lot {{ $line->lot_code }}@endif</small></div>
                                    <input type="hidden" name="lines[{{ $loop->index }}][receipt_line_id]" value="{{ $line->id }}">
                                    <label class="stock-control-field"><span>Reçu</span><input type="number" name="lines[{{ $loop->index }}][quantity_received]" min="0" step="1" value="{{ old('lines.'.$loop->index.'.quantity_received', 0) }}" required></label>
                                    <label class="stock-control-field"><span>Abîmé (inclus dans reçu)</span><input type="number" name="lines[{{ $loop->index }}][quantity_damaged]" min="0" step="1" value="{{ old('lines.'.$loop->index.'.quantity_damaged', 0) }}" required></label>
                                    <label class="stock-control-field"><span>Note d’écart</span><input name="lines[{{ $loop->index }}][discrepancy_note]" value="{{ old('lines.'.$loop->index.'.discrepancy_note') }}" placeholder="Facultatif"></label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="receipt-delivery-bottom">
                        <div class="receipt-delivery-final-fields">
                            <label class="stock-control-field"><span>Justificatif / photo</span><input type="file" name="attachments[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple><small>Obligatoire si un produit est déclaré abîmé.</small></label>
                            <label class="stock-control-field"><span>Note pour la validation</span><textarea name="notes" rows="2" maxlength="5000" placeholder="Expliquer les écarts observés">{{ old('notes') }}</textarea></label>
                        </div>
                        <button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">send</span>Soumettre pour validation</button>
                    </div>
                    </form>
                </section>
            @else
                <section class="stock-control-card receipt-event-card">
                    <div class="stock-control-section-head"><div><span class="stock-control-kicker">RÉCEPTION PLANIFIÉE</span><h2>Comptage disponible à la date prévue</h2></div></div>
                    <div class="receipt-validation-note"><span class="material-icons">schedule</span><p>La saisie des quantités sera disponible le <strong>{{ $expectedReceiptAt->format('d/m/Y à H:i') }}</strong>. À l’arrivée du fournisseur, tu pourras renseigner et modifier la date et l’heure réelles de réception.</p></div>
                </section>
            @endif
        @endif

        <section class="stock-control-card receipt-event-card">
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">TRACE COMPLÈTE</span><h2>Livraisons et validations</h2></div><span class="stock-control-count">{{ $receipt->events->count() }} événement(s)</span></div>
            @if($receipt->events->isEmpty())
                <div class="receipt-empty-events">Aucune livraison n’a encore été saisie.</div>
            @else
                <div class="receipt-event-list">
                    @foreach($receipt->events->sortByDesc('occurred_at') as $event)
                        <article class="receipt-timeline-item">
                            <span class="receipt-timeline-dot {{ $event->status === 'approved' ? 'is-approved' : '' }}"></span>
                            <div class="receipt-timeline-content">
                                <div class="receipt-timeline-heading"><div><strong>{{ $event->event_number }}</strong><small>{{ $event->occurred_at?->format('d/m/Y à H:i') }}</small></div><div class="transfer-detail-heading-actions">@if($event->status === 'approved')<a class="stock-control-button" href="{{ route('stock-control.receipts.delivery-receipt', [$receipt, $event]) }}" target="_blank" rel="noopener"><span class="material-icons">print</span>Bon</a>@endif<span class="receipt-status-pill {{ $event->status === 'approved' ? 'validated' : 'pending' }}">{{ $event->status === 'approved' ? 'Approuvée' : 'En attente' }}</span></div></div>
                                <p>Saisie par {{ $event->receiver?->name ?? 'utilisateur inconnu' }}@if($event->approver) · approuvée par {{ $event->approver->name }}@endif</p>
                                <div class="receipt-event-lines">@foreach($event->lines as $eventLine)<div class="receipt-event-line"><strong>{{ $eventLine->product->name }}</strong><span>Reçu {{ number_format((float) $eventLine->quantity_received, 3, ',', ' ') }}</span><span>Abîmé {{ number_format((float) $eventLine->quantity_damaged, 3, ',', ' ') }}</span></div>@endforeach</div>
                                @if($event->notes)<small class="receipt-event-note">{{ $event->notes }}</small>@endif
                                @if($event->attachments->isNotEmpty())<div class="receipt-attachments">@include('stocks.partials.attachment-list', ['attachments' => $event->attachments])</div>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
