@extends('layouts.admin')

@section('content')
    <div class="product-detail-page">
        <header class="product-detail-hero">
            <a class="product-detail-back" href="{{ route('product') }}"><span class="material-icons">arrow_back</span>Retour au catalogue</a>
            <div class="product-detail-identity">
                <div class="product-detail-image">
                    @if($product->img)
                        <img src="{{ url('image/' . ltrim($product->img, '/')) }}" alt="{{ $product->name }}" onerror="this.remove()">
                    @else
                        <span class="material-icons">inventory_2</span>
                    @endif
                </div>
                <div class="product-detail-title">
                    <span class="product-detail-eyebrow">FICHE PRODUIT · RÉF. {{ $product->id }}</span>
                    <h1>{{ $product->name }}</h1>
                    <p>Prix, quantités et historique des mouvements du produit.</p>
                </div>
            </div>
            <a class="product-detail-edit" href="{{ route('stock-control.transfers.create', ['replenishment' => 1]) }}"><span class="material-icons">store</span>Préparer un transfert multi-produits</a>
            @if(auth()->user()?->user_type === 'ADMINUSER')
                <a class="product-detail-edit" href="{{ route('editproduct', $product->id) }}"><span class="material-icons">edit</span>Modifier le produit</a>
            @endif
        </header>

        @if(session('message'))
            <div class="product-detail-flash" role="status"><span class="material-icons">check_circle</span>{{ session('message') }}</div>
        @endif
        @if($errors->any())
            <div class="product-detail-errors" role="alert"><strong>Vérifie les informations saisies.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="product-detail-metrics" aria-label="Indicateurs du produit">
            <article><span class="product-detail-metric-icon">payments</span><div><small>Prix d’achat</small><strong>{{ number_format((float)$product->price_by, 0, ',', ' ') }} F</strong></div></article>
            <article><span class="product-detail-metric-icon">trending_up</span><div><small>Bénéfice</small><strong>{{ number_format((float)$product->benefice, 0, ',', ' ') }} F</strong></div></article>
            <article><span class="product-detail-metric-icon">sell</span><div><small>Prix de vente</small><strong>{{ number_format((float)$product->price, 0, ',', ' ') }} F</strong></div></article>
            <article><span class="product-detail-metric-icon">shopping_bag</span><div><small>Quantité vendue</small><strong>{{ number_format((float)$product->qts_sell, 0, ',', ' ') }}</strong></div></article>
            <article><span class="product-detail-metric-icon">inventory</span><div><small>Stock restant</small><strong>{{ number_format((float)$product->qt_initial, 0, ',', ' ') }}</strong></div></article>
            <article><span class="product-detail-metric-icon">warehouse</span><div><small>Entrées enregistrées</small><strong>{{ number_format((float)$totalEnterStock, 0, ',', ' ') }}</strong></div></article>
            <article><span class="product-detail-metric-icon">outbox</span><div><small>Sorties enregistrées</small><strong>{{ number_format((float)$totalOuttock, 0, ',', ' ') }}</strong></div></article>
            <article><span class="product-detail-metric-icon">price_check</span><div><small>Prix d’achat unitaire</small><strong>{{ number_format((float)$product->price_market, 0, ',', ' ') }} F</strong></div></article>
        </section>

        <section class="product-detail-workspace">
            <nav class="nav nav-tabs product-detail-tabs" aria-label="Détails du produit">
                <a class="nav-link active" href="#product-entry" data-toggle="tab" role="tab"><span class="material-icons">move_to_inbox</span>Entrées de stock <span class="product-detail-tab-count">{{ $product->enterstocks->count() }}</span></a>
                <a class="nav-link" href="#product-exit" data-toggle="tab" role="tab"><span class="material-icons">outbox</span>Sorties de stock <span class="product-detail-tab-count">{{ $product->outstocks->count() }}</span></a>
            </nav>

            <div class="tab-content product-detail-tab-content">
                <section class="tab-pane fade show active" id="product-entry">
                    <div class="product-detail-section-head"><div><span class="product-detail-eyebrow">APPROVISIONNEMENT</span><h2>Enregistrer une entrée</h2></div></div>
                    <form action="{{ route('enter_stock', $product) }}" method="POST" class="product-detail-form">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <label><span>Quantité reçue</span><input type="number" min="0" name="qt_stock" value="{{ old('qt_stock', 0) }}" required></label>
                        <label><span>Montant total (FCFA)</span><input type="number" min="0" name="amount" value="{{ old('amount', 0) }}" required></label>
                        <button type="submit" class="product-detail-button"><span class="material-icons">add</span>Enregistrer l’entrée</button>
                    </form>
                    <div class="product-detail-section-head product-detail-history-head"><div><span class="product-detail-eyebrow">JOURNAL</span><h2>Historique des entrées</h2></div></div>
                    <div class="product-detail-table-wrap">
                        <table id="example" class="table product-detail-table">
                            <thead><tr><th>N°</th><th>Quantité</th><th>Montant</th><th>Date</th><th>Enregistré par</th></tr></thead>
                            <tbody>@foreach($product->enterstocks as $index => $entry)<tr><td>{{ $index + 1 }}</td><td>{{ number_format((float)$entry->qt_stock, 0, ',', ' ') }}</td><td>{{ number_format((float)$entry->amount, 0, ',', ' ') }} F</td><td>{{ $entry->created_at?->format('d/m/Y H:i') }}</td><td>{{ $entry->user->name ?? '—' }}</td></tr>@endforeach</tbody>
                        </table>
                    </div>
                </section>

                <section class="tab-pane fade" id="product-exit">
                    <div class="product-detail-section-head"><div><span class="product-detail-eyebrow">MOUVEMENT DE STOCK</span><h2>Enregistrer une sortie</h2></div></div>
                    <form action="{{ route('out_stock', $product) }}" method="POST" class="product-detail-form product-detail-exit-form">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <label><span>Quantité sortie</span><input type="number" min="0" name="qt_stock" value="{{ old('qt_stock', 0) }}" required></label>
                        <label class="product-detail-reason"><span>Motif de la sortie</span><textarea name="raison" rows="2" required placeholder="Indique le motif">{{ old('raison') }}</textarea></label>
                        <button type="submit" class="product-detail-button"><span class="material-icons">remove</span>Enregistrer la sortie</button>
                    </form>
                    <div class="product-detail-section-head product-detail-history-head"><div><span class="product-detail-eyebrow">JOURNAL</span><h2>Historique des sorties</h2></div></div>
                    <div class="product-detail-table-wrap">
                        <table id="examples" class="table product-detail-table">
                            <thead><tr><th>N°</th><th>Motif</th><th>Quantité</th><th>Date</th><th>Enregistré par</th></tr></thead>
                            <tbody>@foreach($product->outstocks as $index => $exit)<tr><td>{{ $index + 1 }}</td><td>{{ $exit->raison }}</td><td>{{ number_format((float)$exit->qt_stock, 0, ',', ' ') }}</td><td>{{ $exit->created_at?->format('d/m/Y H:i') }}</td><td>{{ $exit->user->name ?? '—' }}</td></tr>@endforeach</tbody>
                        </table>
                    </div>
                </section>

            </div>
        </section>
    </div>
    <script>
        $(function () {
            $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });
        });
    </script>
@endsection
