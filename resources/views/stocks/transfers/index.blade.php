@extends('layouts.admin')

@section('content')
    @php
        $transferStatuses = [
            'submitted' => ['À autoriser', 'pending'], 'authorized' => ['À expédier', 'neutral'],
            'in_transit' => ['En transit', 'transit'], 'partial' => ['Réception partielle', 'partial'],
            'closed' => ['Clôturé', 'validated'], 'canceled' => ['Annulé', 'neutral'],
        ];
    @endphp
    <div class="stock-control-page stock-transfer-page">
        <header class="stock-control-hero">
            <div class="stock-control-hero-copy"><span class="stock-control-kicker">TENACOS · MOUVEMENTS ENTRE SITES</span><h1>Transferts de stock</h1><p>Autorise, expédie et réceptionne les quantités avec un suivi du transit et des écarts.</p></div>
            <a href="{{ route('stock-control.transfers.create') }}" class="stock-control-hero-action"><span class="material-icons">add</span>Demander un transfert</a>
            <div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">swap_horiz</span></div>
        </header>

        @if(session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif

        <section class="stock-control-card transfer-list-card">
            <div class="stock-control-section-head"><div><span class="stock-control-kicker">REGISTRE DE TRANSFERTS</span><h2>Demandes et mouvements</h2></div><span class="stock-control-count">{{ number_format($transfers->total(), 0, ',', ' ') }} dossier(s)</span></div>
            <form method="GET" action="{{ route('stock-control.transfers.index') }}" class="stock-balance-filters transfer-filters">
                <label class="stock-control-field"><span>Rechercher</span><input class="stock-live-search" type="search" name="search" value="{{ $search }}" placeholder="Référence, motif ou site" autocomplete="off"></label>
                <label class="stock-control-field"><span>État</span><select name="status"><option value="all" @selected($status === 'all')>Tous les états</option>@foreach($transferStatuses as $value => [$label])<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></label>
                <div class="stock-balance-filter-actions"><button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">search</span>Filtrer</button><a class="stock-control-button" href="{{ route('stock-control.transfers.index') }}">Effacer</a></div>
            </form>
            @if($transfers->isEmpty())
                <div class="stock-control-empty"><span class="material-icons">swap_horiz</span><strong>Aucun transfert trouvé</strong><p>Crée une demande pour déplacer des produits entre deux emplacements.</p><a class="stock-control-button stock-control-button-primary" href="{{ route('stock-control.transfers.create') }}">Demander un transfert</a></div>
            @else
                <div class="transfer-list">
                    @foreach($transfers as $transfer)
                        @php [$label, $class] = $transferStatuses[$transfer->status] ?? [$transfer->status, 'neutral']; @endphp
                        <a href="{{ route('stock-control.transfers.show', $transfer) }}" class="transfer-list-row">
                            <div class="transfer-ref"><span class="material-icons">swap_horiz</span><div><strong>{{ $transfer->transfer_number }}</strong><small>{{ $transfer->created_at?->format('d/m/Y H:i') }}</small></div></div>
                            <div class="transfer-route"><span>{{ $transfer->source?->name }}</span><span class="material-icons">east</span><strong>{{ $transfer->destination?->name }}</strong></div>
                            <div class="transfer-list-info"><span>Demandeur</span><strong>{{ $transfer->requester?->name ?? 'Utilisateur inconnu' }}</strong><small>{{ $transfer->lines->count() }} produit(s)</small></div>
                            <span class="receipt-status-pill {{ $class }}">{{ $label }}</span><span class="receipt-open-icon material-icons">arrow_forward</span>
                        </a>
                    @endforeach
                </div>
                <div class="receipt-pagination">{{ $transfers->links() }}</div>
            @endif
        </section>
    </div>
@endsection
