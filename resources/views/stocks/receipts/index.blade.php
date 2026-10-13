@extends('layouts.admin')

@section('content')
    @php
        $receiptStatuses = [
            'draft' => ['Brouillon', 'neutral'],
            'pending' => ['À valider', 'pending'],
            'partial' => ['Partielle · reliquat ouvert', 'partial'],
            'validated' => ['Réception complète', 'validated'],
            'canceled' => ['Annulée', 'neutral'],
        ];
    @endphp
    <div class="stock-control-page stock-receipts-page">
        <header class="stock-control-hero">
            <div class="stock-control-hero-copy">
                <span class="stock-control-kicker">TENACOS · MOUVEMENTS ENTRANTS</span>
                <h1>Réceptions de marchandises</h1>
                <p>Chaque livraison est rapprochée de l’attendu, contrôlée puis validée avant d’entrer en stock.</p>
            </div>
            <a href="{{ route('stock-control.receipts.create') }}" class="stock-control-hero-action"><span class="material-icons">add</span>Nouvelle réception</a>
            <div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">move_to_inbox</span></div>
        </header>

        @if (session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif

        <section class="stock-control-card receipt-list-card">
            <div class="stock-control-section-head">
                <div><span class="stock-control-kicker">REGISTRE</span><h2>Suivi des réceptions</h2></div>
                <span class="stock-control-count">{{ number_format($receipts->total(), 0, ',', ' ') }} dossier(s)</span>
            </div>
            @if ($receipts->isEmpty())
                <div class="stock-control-empty"><span class="material-icons">inventory</span><strong>Aucune réception enregistrée</strong><p>Crée une réception attendue pour commencer le suivi fournisseur.</p><a href="{{ route('stock-control.receipts.create') }}" class="stock-control-button stock-control-button-primary">Créer une réception</a></div>
            @else
                <div class="receipt-list">
                    @foreach ($receipts as $receipt)
                        @php [$statusText, $statusClass] = $receiptStatuses[$receipt->status] ?? [$receipt->status, 'neutral']; @endphp
                        <a class="receipt-list-row" href="{{ route('stock-control.receipts.show', $receipt) }}">
                            <div class="receipt-ref"><span class="receipt-ref-icon material-icons">inventory_2</span><div><strong>{{ $receipt->receipt_number }}</strong><small>{{ $receipt->occurred_at?->format('d/m/Y à H:i') ?? '—' }}</small></div></div>
                            <div class="receipt-list-cell"><span>Origine</span><strong>{{ $receipt->origin_name ?: 'Origine non précisée' }}</strong>@if($receipt->source_reference)<small>Réf. {{ $receipt->source_reference }}</small>@endif</div>
                            <div class="receipt-list-cell"><span>Destination</span><strong>{{ $receipt->location?->name ?? 'Emplacement supprimé' }}</strong><small>{{ $receipt->location?->code }}</small></div>
                            <div class="receipt-list-cell"><span>Préparée par</span><strong>{{ $receipt->creator?->name ?? 'Utilisateur inconnu' }}</strong><small>{{ $receipt->lines_count }} référence(s)</small></div>
                            <span class="receipt-status-pill {{ $statusClass }}">{{ $statusText }}</span>
                            <span class="receipt-open-icon material-icons">arrow_forward</span>
                        </a>
                    @endforeach
                </div>
                <div class="receipt-pagination">{{ $receipts->links() }}</div>
            @endif
        </section>
    </div>
@endsection
