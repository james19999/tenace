@extends('layouts.admin')

@section('content')
@php $typeLabels=['low_stock'=>'Stock bas','expiry'=>'Expiration','transfer_damage'=>'Écart de transfert','transfer_overdue'=>'Transfert en retard','receipt_difference'=>'Écart de réception']; @endphp
<div class="stock-control-page stock-alerts-page">
    <header class="stock-control-hero"><div class="stock-control-hero-copy"><span class="stock-control-kicker">TENACOS · SURVEILLANCE</span><h1>Alertes de stock</h1><p>Repère les ruptures, les seuils bas et les lots qui approchent de leur date limite.</p></div><form method="POST" action="{{ route('stock-control.alerts.refresh') }}">@csrf<button class="stock-control-hero-action" type="submit"><span class="material-icons">refresh</span>Actualiser les alertes</button></form></header>
    @if(session('success'))<div class="stock-control-alert stock-control-alert-success"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
    <section class="stock-alert-summary"><article><small>Alertes ouvertes</small><strong>{{ number_format($openCount,0,',',' ') }}</strong><span>À traiter</span></article><article><small>Alertes affichées</small><strong>{{ number_format($alerts->total(),0,',',' ') }}</strong><span>{{ $status==='open'?'En cours':'Historique' }}</span></article></section>
    <section class="stock-control-card"><div class="stock-control-section-head"><div><span class="stock-control-kicker">SUIVI</span><h2>Alertes détectées</h2></div><form method="GET"><select name="status" onchange="this.form.submit()"><option value="open" @selected($status==='open')>À traiter</option><option value="resolved" @selected($status==='resolved')>Traitées</option><option value="all" @selected($status==='all')>Toutes</option></select></form></div>
        @if($alerts->isEmpty())<div class="stock-control-empty"><span class="material-icons">task_alt</span><strong>Aucune alerte dans cette vue</strong><p>Actualise les alertes pour vérifier les seuils de stock et les dates des lots.</p><form method="POST" action="{{ route('stock-control.alerts.refresh') }}">@csrf<button class="stock-control-button stock-control-button-primary" type="submit">Actualiser les alertes</button></form></div>@else
        <div class="stock-alert-list">
            @foreach($alerts as $alert)
                <article class="stock-alert-row severity-{{ $alert->severity }}">
                    <div class="stock-alert-symbol material-icons">{{ $alert->severity === 'critical' ? 'priority_high' : 'warning_amber' }}</div>
                    <div class="stock-alert-content">
                        <div class="stock-alert-heading"><strong>{{ $alert->title }}</strong><span class="stock-alert-severity">{{ $alert->severity === 'critical' ? 'Urgent' : 'À surveiller' }}</span></div>
                        <p>{{ $alert->message }}</p>
                        <small>{{ $typeLabels[$alert->type] ?? ucfirst(str_replace('_', ' ', $alert->type)) }}{{ $alert->location ? ' · '.$alert->location->name : '' }} · détectée le {{ $alert->created_at?->format('d/m/Y H:i') }}</small>
                        <details class="stock-alert-details">
                            <summary>Voir les détails et la marche à suivre</summary>
                            <div class="stock-alert-details-panel">
                                @if($alert->type === 'expiry' && $alert->subject instanceof \App\Models\StockLot)
                                    @php
                                        $lot = $alert->subject;
                                        $quantity = (float) data_get($alert->details, 'quantity', 0);
                                        $expired = $lot->expires_at && $lot->expires_at->lt(today());
                                    @endphp
                                    <h3>Lot à contrôler</h3>
                                    <dl><div><dt>Produit</dt><dd>{{ $lot->product?->name ?? $alert->product?->name ?? '—' }}</dd></div><div><dt>Référence du lot</dt><dd>{{ $lot->lot_code ?: '#'.$lot->id }}</dd></div><div><dt>Quantité restante estimée</dt><dd>{{ number_format($quantity, 3, ',', ' ') }}</dd></div><div><dt>Date d’expiration</dt><dd>{{ $lot->expires_at?->format('d/m/Y') ?? '—' }} · {{ $expired ? 'date dépassée' : 'dans la période de surveillance' }}</dd></div><div><dt>État du lot</dt><dd>{{ ucfirst($lot->status) }}</dd></div></dl>
                                    <p>Cette quantité est calculée à partir des mouvements validés associés au lot. Vérifie physiquement le stock et applique la procédure interne : retrait de la vente si le lot est expiré, puis retour, destruction ou autre décision autorisée.</p>
                                    @if($lot->product)<a href="{{ route('show-product', $lot->product_id) }}" class="stock-alert-link">Ouvrir la fiche produit</a>@endif
                                @elseif($alert->type === 'receipt_difference' && $alert->subject instanceof \App\Models\StockReceipt)
                                    @php
                                        $receipt = $alert->subject;
                                        $eventNumber = data_get($alert->details, 'event_number');
                                        $receiptEvent = $receipt->events->firstWhere('event_number', $eventNumber);
                                    @endphp
                                    <h3>Écart constaté à la réception</h3>
                                    <dl><div><dt>Réception</dt><dd>{{ $receipt->receipt_number }}</dd></div><div><dt>Fournisseur / origine</dt><dd>{{ $receipt->origin_name ?: 'Non précisée' }}</dd></div><div><dt>Emplacement</dt><dd>{{ $receipt->location?->name ?? '—' }}</dd></div><div><dt>Livraison concernée</dt><dd>{{ $eventNumber ?: '—' }}</dd></div><div><dt>État de la réception</dt><dd>{{ ['draft'=>'Brouillon','pending'=>'À valider','partial'=>'Partielle','validated'=>'Complète','canceled'=>'Annulée'][$receipt->status] ?? $receipt->status }}</dd></div></dl>
                                    @if($receiptEvent)
                                        <h4>Articles de cette livraison</h4>
                                        <div class="stock-alert-detail-lines">@foreach($receiptEvent->lines as $line)<article><strong>{{ $line->product?->name ?? 'Produit' }}</strong><span>Reçu : {{ number_format((float) $line->quantity_received, 3, ',', ' ') }}</span><span>Abîmé : {{ number_format((float) $line->quantity_damaged, 3, ',', ' ') }}</span><span>Lot : {{ $line->lot?->lot_code ?: 'Sans lot' }}</span></article>@endforeach</div>
                                        @if($receiptEvent->attachments->isNotEmpty())<div class="stock-alert-detail-attachments"><strong>Photos et justificatifs</strong>@include('stocks.partials.attachment-list', ['attachments' => $receiptEvent->attachments])</div>@endif
                                    @endif
                                    <p>Les unités abîmées ont été isolées dans l’emplacement BLOQUÉ et ne sont pas disponibles à la vente. Compare les quantités avec le bon fournisseur, examine les justificatifs, puis décide du retour ou de la réclamation. Cette alerte ne corrige pas l’écart à elle seule.</p>
                                    <a href="{{ route('stock-control.receipts.show', $receipt) }}" class="stock-alert-link">Ouvrir la réception et ses justificatifs</a>
                                @elseif($alert->type === 'low_stock')
                                    @php $quantity = data_get($alert->details, 'quantity'); $threshold = data_get($alert->details, 'threshold'); @endphp
                                    <h3>Seuil de stock atteint</h3><dl><div><dt>Produit</dt><dd>{{ $alert->product?->name ?? '—' }}</dd></div><div><dt>Stock actuel</dt><dd>{{ number_format((float) $quantity, 0, ',', ' ') }}</dd></div><div><dt>Seuil configuré</dt><dd>{{ number_format((float) $threshold, 0, ',', ' ') }}</dd></div></dl><p>Vérifie le stock réel et prépare un réapprovisionnement si nécessaire.</p>
                                    @if($alert->product)<a href="{{ route('show-product', $alert->product_id) }}" class="stock-alert-link">Ouvrir la fiche produit</a>@endif
                                @elseif(in_array($alert->type, ['transfer_overdue', 'transfer_damage'], true) && $alert->subject instanceof \App\Models\StockTransfer)
                                    <h3>Transfert à vérifier</h3><dl><div><dt>Référence</dt><dd>{{ $alert->subject->transfer_number }}</dd></div><div><dt>Départ</dt><dd>{{ $alert->subject->source?->name ?? '—' }}</dd></div><div><dt>Destination</dt><dd>{{ $alert->subject->destination?->name ?? '—' }}</dd></div><div><dt>État</dt><dd>{{ ['submitted'=>'À autoriser','authorized'=>'À expédier','in_transit'=>'En transit','partial'=>'Réception partielle','closed'=>'Clôturé'][$alert->subject->status] ?? $alert->subject->status }}</dd></div></dl><p>{{ $alert->type === 'transfer_overdue' ? 'Le délai prévu est dépassé. Vérifie avec les deux emplacements si le transfert est parti ou arrivé.' : 'Des quantités ont été déclarées abîmées à l’arrivée. Vérifie leur isolement et les justificatifs du transfert.' }}</p><a href="{{ route('stock-control.transfers.show', $alert->subject) }}" class="stock-alert-link">Ouvrir le transfert</a>
                                @else
                                    <h3>Informations de l’alerte</h3><p>{{ $alert->message }}</p><p>Vérifie l’opération concernée avant de marquer cette alerte comme traitée.</p>
                                @endif
                            </div>
                        </details>
                    </div>
                    @if($alert->status === 'open')
                        <form method="POST" action="{{ route('stock-control.alerts.resolve', $alert) }}">@csrf<button class="stock-control-button" type="submit">Marquer traitée</button></form>
                    @else
                        <span class="stock-alert-resolved">Traitée{{ $alert->resolver ? ' par '.$alert->resolver->name : '' }}</span>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="receipt-pagination">{{ $alerts->links() }}</div>
        @endif
    </section>
</div>
@endsection
