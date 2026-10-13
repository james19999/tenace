@extends('layouts.admin')

@section('content')
    @php
        $transferStatuses = [
            'submitted' => ['À autoriser', 'pending'], 'authorized' => ['À expédier', 'neutral'],
            'in_transit' => ['En transit', 'transit'], 'partial' => ['Réception partielle', 'partial'],
            'closed' => ['Clôturé', 'validated'], 'canceled' => ['Annulé', 'neutral'],
        ];
        [$statusLabel, $statusClass] = $transferStatuses[$transfer->status] ?? [$transfer->status, 'neutral'];
        $remainingTotal = $transfer->lines->sum(fn ($line) => max(0, (float) $line->quantity_shipped - (float) $line->quantity_received));
    @endphp
    <div class="stock-control-page stock-transfer-page">
        <div class="receipt-page-heading transfer-detail-heading">
            <a href="{{ route('stock-control.transfers.index') }}" class="receipt-back-link"><span class="material-icons">arrow_back</span>Tous les transferts</a>
            <div class="receipt-title-line"><div><span class="stock-control-kicker">TRANSFERT ENTRE EMPLACEMENTS</span><h1>{{ $transfer->transfer_number }}</h1></div><div class="transfer-detail-heading-actions">@if($transfer->events->contains('type', 'shipment'))<a class="stock-control-button" href="{{ route('stock-control.transfers.shipment-receipt', $transfer) }}" target="_blank" rel="noopener"><span class="material-icons">print</span>Bon d’expédition</a>@endif<span class="receipt-status-pill {{ $statusClass }}">{{ $statusLabel }}</span></div></div>
            <p>{{ $transfer->source->name }} <span class="material-icons transfer-heading-arrow">east</span> {{ $transfer->destination->name }} · Demandé le {{ $transfer->created_at?->format('d/m/Y à H:i') }}</p>
        </div>

        @if(session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="stock-control-alert stock-control-alert-error" role="alert"><span class="material-icons">error_outline</span><div><strong>Action non effectuée.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

        <section class="transfer-overview-grid">
            <article><span>Emplacement source</span><strong>{{ $transfer->source->name }}</strong><small>{{ $transfer->source->code }} · {{ $transfer->source->responsible?->name ?? 'Responsable non désigné' }}</small></article>
            <article><span>Destination</span><strong>{{ $transfer->destination->name }}</strong><small>{{ $transfer->destination->code }} · {{ $transfer->destination->responsible?->name ?? 'Responsable non désigné' }}</small></article>
            <article><span>Demandeur</span><strong>{{ $transfer->requester?->name ?? 'Utilisateur inconnu' }}</strong><small>{{ $transfer->submitted_at?->format('d/m/Y H:i') }}</small></article>
            <article><span>Quantité encore en transit</span><strong>{{ number_format($remainingTotal, 0, ',', ' ') }}</strong><small>Unité(s) à réceptionner</small></article>
        </section>

        <section class="stock-control-card transfer-lines-card">
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">DÉTAIL DU TRANSFERT</span><h2>Produits et quantités</h2></div><span class="stock-control-count">{{ $transfer->lines->count() }} produit(s)</span></div>
            <div class="transfer-product-list">
                @foreach($transfer->lines as $line)
                    <article class="transfer-product-row">
                        <div><strong>{{ $line->product->name }}</strong><small>@if($line->lot)Lot {{ $line->lot->lot_code ?: 'sans référence' }}@else Lot non précisé@endif</small></div>
                        <div><span>Demandé</span><strong>{{ number_format((float) $line->quantity_requested, 0, ',', ' ') }}</strong></div>
                        <div><span>Expédié</span><strong>{{ number_format((float) $line->quantity_shipped, 0, ',', ' ') }}</strong></div>
                        <div><span>Reçu</span><strong>{{ number_format((float) $line->quantity_received, 0, ',', ' ') }}</strong></div>
                        <div><span>Abîmé</span><strong class="{{ (float) $line->quantity_damaged > 0 ? 'transfer-damage-text' : '' }}">{{ number_format((float) $line->quantity_damaged, 0, ',', ' ') }}</strong></div>
                    </article>
                @endforeach
            </div>
            <div class="transfer-reason"><strong>Motif</strong><p>{{ $transfer->reason }}</p>@if($transfer->notes)<small>{{ $transfer->notes }}</small>@endif</div>
        </section>

        @if($transfer->status === 'submitted')
            <section class="stock-control-card transfer-action-card">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">DÉCISION</span><h2>Autoriser cette demande</h2></div></div>
                <div class="receipt-validation-note"><span class="material-icons">verified_user</span><p>L’autorisation ne modifie pas le stock. La quantité disponible à la source sera vérifiée à nouveau au moment de l’expédition.</p></div>
                @if($rules['allow_self_approval'] || (int) $transfer->requested_by !== (int) auth()->id())
                    <form method="POST" action="{{ route('stock-control.transfers.authorize', $transfer) }}" class="transfer-action-form" data-stock-confirm-title="Autoriser le transfert ?" data-stock-confirm-message="La demande sera autorisée. Le stock ne sera déplacé qu’au moment de l’expédition.">@csrf<button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">task_alt</span>Autoriser le transfert</button></form>
                @else
                    <p class="transfer-policy-note">Une autre personne habilitée doit autoriser cette demande.</p>
                @endif
            </section>
        @endif

        @if($transfer->status === 'authorized')
            <section class="stock-control-card transfer-action-card">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">PRÉPARATION</span><h2>Enregistrer l’expédition</h2></div></div>
                <div class="receipt-validation-note"><span class="material-icons">local_shipping</span><p>Les quantités demandées seront retirées du site source et placées dans <strong>{{ $transit?->name ?? 'TRANSIT' }}</strong>. Le stock global disponible baissera jusqu’à leur réception.</p></div>
                <form method="POST" action="{{ route('stock-control.transfers.ship', $transfer) }}" class="transfer-action-form transfer-ship-form" data-stock-confirm-title="Confirmer l’expédition ?" data-stock-confirm-message="Les quantités vont quitter le stock disponible à la source et passer en transit.">
                    @csrf
                    <label class="stock-control-field"><span>Date et heure de départ</span><input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required></label>
                    <label class="stock-control-field"><span>Note d’expédition</span><input name="notes" value="{{ old('notes') }}" maxlength="3000" placeholder="Bon de sortie ou précision"></label>
                    <button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">local_shipping</span>Confirmer l’expédition</button>
                </form>
            </section>
        @endif

        @if(in_array($transfer->status, ['in_transit', 'partial'], true))
            <section class="stock-control-card transfer-action-card">
                <div class="stock-control-section-head"><div><span class="stock-control-kicker">RÉCEPTION · {{ $transfer->destination->name }}</span><h2>Saisir les quantités arrivées</h2></div></div>
                <div class="receipt-validation-note"><span class="material-icons">inventory</span><p>Une réception partielle laisse le reliquat en transit. Les articles abîmés sont sortis du transit, isolés dans <strong>{{ $blocked?->name ?? 'BLOQUÉ' }}</strong> et ne réintègrent pas le stock global disponible.</p></div>
                <form method="POST" action="{{ route('stock-control.transfers.receive', $transfer) }}" enctype="multipart/form-data" class="transfer-receive-form">
                    @csrf
                    <label class="stock-control-field transfer-receive-date"><span>Date et heure réelles d’arrivée</span><input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required></label>
                    <div class="transfer-receive-lines">
                        @foreach($transfer->lines as $line)
                            @php $remaining = max(0, (float) $line->quantity_shipped - (float) $line->quantity_received); @endphp
                            @if($remaining > 0)
                                <article class="transfer-receive-row">
                                    <div><strong>{{ $line->product->name }}</strong><small>Expédié : {{ number_format((float) $line->quantity_shipped, 0, ',', ' ') }} · Reliquat : {{ number_format($remaining, 0, ',', ' ') }}</small></div>
                                    <input type="hidden" name="lines[{{ $loop->index }}][transfer_line_id]" value="{{ $line->id }}">
                                    <label class="stock-control-field"><span>Reçu</span><input type="number" name="lines[{{ $loop->index }}][quantity]" value="{{ old('lines.'.$loop->index.'.quantity', 0) }}" min="0" max="{{ $remaining }}" step="1" required></label>
                                    <label class="stock-control-field"><span>Abîmé (inclus)</span><input type="number" name="lines[{{ $loop->index }}][quantity_damaged]" value="{{ old('lines.'.$loop->index.'.quantity_damaged', 0) }}" min="0" step="1" required></label>
                                    <label class="stock-control-field"><span>Note</span><input name="lines[{{ $loop->index }}][notes]" value="{{ old('lines.'.$loop->index.'.notes') }}" maxlength="1500" placeholder="Facultatif"></label>
                                </article>
                            @endif
                        @endforeach
                    </div>
                    <div class="transfer-receive-footer">
                        <div class="transfer-receive-notes"><label class="stock-control-field"><span>Photo ou justificatif</span><input type="file" name="attachments[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple><small>Requis si la règle de justificatif est activée et qu’un article est abîmé.</small></label><label class="stock-control-field"><span>Note de réception</span><textarea name="notes" rows="2" maxlength="3000" placeholder="Préciser les écarts ou conditions d’arrivée">{{ old('notes') }}</textarea></label></div>
                        <button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">move_to_inbox</span>Enregistrer la réception</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="stock-control-card transfer-action-card">
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">JOURNAL NON EFFAÇABLE</span><h2>Expéditions et réceptions</h2></div><span class="stock-control-count">{{ $transfer->events->count() }} événement(s)</span></div>
            @if($transfer->events->isEmpty())
                <div class="receipt-empty-events">Aucun mouvement physique enregistré à ce stade.</div>
            @else
                <div class="receipt-event-list">
                    @foreach($transfer->events->sortByDesc('occurred_at') as $event)
                        <article class="receipt-timeline-item"><span class="receipt-timeline-dot is-approved"></span><div class="receipt-timeline-content">
                            <div class="receipt-timeline-heading"><div><strong>{{ $event->type === 'shipment' ? 'Expédition' : 'Réception' }} · {{ $event->event_number }}</strong><small>{{ $event->occurred_at?->format('d/m/Y à H:i') }}</small></div><div class="transfer-detail-heading-actions"><a class="stock-control-button" href="{{ route('stock-control.transfers.event-receipt', [$transfer, $event]) }}" target="_blank" rel="noopener"><span class="material-icons">print</span>Bon</a><span class="receipt-status-pill {{ $event->type === 'shipment' ? 'transit' : 'validated' }}">{{ $event->type === 'shipment' ? 'Vers le transit' : 'Arrivée enregistrée' }}</span></div></div>
                            <p>Enregistré par {{ $event->user?->name ?? 'utilisateur inconnu' }}</p>
                            <div class="receipt-event-lines">
                                @foreach($event->lines as $eventLine)
                                    <div class="receipt-event-line">
                                        <strong>{{ $eventLine->product->name }}</strong>
                                        <span>Quantité : {{ number_format((float) $eventLine->quantity, 0, ',', ' ') }}</span>
                                        @if((float) $eventLine->quantity_damaged > 0)
                                            <span class="transfer-damage-text">Abîmé : {{ number_format((float) $eventLine->quantity_damaged, 0, ',', ' ') }}</span>
                                        @endif
                                        @if($eventLine->notes)
                                            <small>{{ $eventLine->notes }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @if($event->notes)<small class="receipt-event-note">{{ $event->notes }}</small>@endif
                            @if($event->attachments->isNotEmpty())<div class="receipt-attachments"><div>@foreach($event->attachments as $attachment)<a href="{{ route('stock-control.transfers.attachments.download', $attachment) }}"><span class="material-icons">attach_file</span>{{ $attachment->original_name }}</a>@endforeach</div></div>@endif
                        </div></article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
