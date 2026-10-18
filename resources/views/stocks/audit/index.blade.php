@extends('layouts.admin')

@section('content')
<main class="stock-history-page">
    <section class="stock-history-header">
        <div>
            <span>CONTRÔLE DES STOCKS</span>
            <h1>Historique des opérations</h1>
            <p>Consulte qui a fait chaque opération et qui l’a validée.</p>
        </div>
        <a class="stock-history-download" href="{{ route('stock-control.audit.export', request()->query()) }}"><span class="material-icons">picture_as_pdf</span>Télécharger le rapport PDF</a>
    </section>

    <form class="stock-history-filter" method="GET" action="{{ route('stock-control.audit') }}">
        <label><span>Rechercher</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, opération ou motif"></label>
        <label><span>Opération</span><select name="action"><option value="">Toutes les opérations</option>@foreach ($actions as $key => $label)<option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Personne</span><select name="user_id"><option value="">Toute l’équipe</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></label>
        <label><span>Du</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label><span>Au</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <div class="stock-history-filter-actions"><button type="submit"><span class="material-icons">search</span>Rechercher</button><a href="{{ route('stock-control.audit') }}">Effacer</a></div>
    </form>

    <section class="stock-history-list">
        <div class="stock-history-list-title"><h2>Opérations</h2><span>{{ number_format($logs->total(), 0, ',', ' ') }} résultat(s)</span></div>
        @forelse ($logs as $log)
            @php
                $after = $log->after_values ?? [];
                $reference = collect($after)->first(fn ($value, $key) => str_contains((string) $key, 'number') || str_contains((string) $key, 'reference'));
                $subjectLabels = ['StockTransfer' => 'Transfert', 'StockReceipt' => 'Réception', 'StockReceiptEvent' => 'Réception', 'StockCount' => 'Inventaire', 'StockAdjustment' => 'Correction', 'StockLocation' => 'Emplacement', 'StockMovement' => 'Mouvement', 'StockAlert' => 'Alerte', 'Product' => 'Produit'];
                $reference = $reference ?: (($subjectLabels[class_basename($log->subject_type)] ?? 'Opération').' n°'.$log->subject_id);
                $isValidation = in_array($log->action, ['transfer.authorized', 'receipt.delivery_approved', 'stock.adjustment_approved', 'stock.adjustment_rejected', 'inventory.count_completed'], true);
                $details = [];
                foreach ($after as $key => $value) {
                    if (in_array($key, ['lines', 'product_id', 'location_id', 'source_location_id', 'destination_location_id', 'movement_id', 'adjustment_id', 'count_id', 'receipt_status'], true) || is_array($value)) continue;
                    $label = [
                        'product_name' => 'Produit', 'quantity' => 'Quantité', 'requested_quantity' => 'Quantité demandée',
                        'quantity_before' => 'Quantité avant', 'quantity_delta' => 'Écart', 'quantity_after' => 'Quantité après',
                        'location_name' => 'Emplacement', 'source_location_name' => 'Départ', 'destination_location_name' => 'Destination',
                        'line_count' => 'Nombre de produits', 'fully_received' => 'Réception complète',
                        'approval_required' => 'Validation requise', 'active_detected' => 'Alertes détectées',
                        'high_price' => 'Prix grossiste', 'amount' => 'Montant', 'scope' => 'Périmètre',
                    ][$key] ?? null;
                    if ($label) $details[] = [$label, is_bool($value) ? ($value ? 'Oui' : 'Non') : $value];
                }
                $lines = $after['lines'] ?? [];
            @endphp
            <article class="stock-history-row">
                <div class="stock-history-date"><span class="material-icons">schedule</span><time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y') }}<small>{{ $log->created_at->format('H:i') }}</small></time></div>
                <div class="stock-history-main">
                    <div class="stock-history-title-line"><h3>{{ $actionLabels[$log->action] ?? \Illuminate\Support\Str::headline(str_replace('.', ' ', $log->action)) }}</h3><span class="stock-history-status {{ $log->result === 'success' ? 'is-ok' : 'is-error' }}">{{ $log->result === 'success' ? 'Effectué' : 'À vérifier' }}</span></div>
                    <div class="stock-history-reference">{{ $reference }}</div>
                    <div class="stock-history-people"><span><small>{{ $isValidation ? 'Validé par' : 'Enregistré par' }}</small><strong>{{ $log->user?->name ?? 'Compte supprimé' }}</strong></span>@if ($log->reason)<span><small>Motif</small><strong>{{ $log->reason }}</strong></span>@endif</div>
                    @if ($details || $lines)
                        <details class="stock-history-details"><summary>Afficher le détail</summary>
                            <div class="stock-history-detail-grid">@foreach ($details as [$label, $value])<div><span>{{ $label }}</span><strong>{{ is_scalar($value) ? $value : '—' }}</strong></div>@endforeach</div>
                            @foreach ($lines as $line)<p class="stock-history-line">{{ $line['product_name'] ?? 'Produit' }} : {{ $line['counted_quantity'] ?? '—' }} compté(s), écart {{ $line['difference'] ?? '—' }}</p>@endforeach
                        </details>
                    @endif
                </div>
            </article>
        @empty
            <div class="stock-history-empty"><span class="material-icons">inventory_2</span><strong>Aucune opération trouvée</strong><p>Modifie les filtres ou efface la recherche.</p></div>
        @endforelse
        <div class="stock-history-pagination">{{ $logs->links() }}</div>
    </section>
</main>
<style>
.stock-history-page{max-width:1480px;margin:0 auto;padding:4px 0 30px;color:#292e3a}.stock-history-header{display:flex;justify-content:space-between;align-items:center;gap:24px;padding:26px 30px;background:#fff;border:1px solid #e3e6ec;border-top:4px solid #8d1b1b}.stock-history-header>div>span{color:#8d1b1b;font-size:11px;font-weight:800;letter-spacing:1.3px}.stock-history-header h1{margin:6px 0;font-size:30px;font-weight:800}.stock-history-header p{margin:0;color:#747d8d}.stock-history-download,.stock-history-filter-actions button{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border:0;background:#8d1b1b;color:#fff;font-weight:700;text-decoration:none;white-space:nowrap}.stock-history-download:hover,.stock-history-filter-actions button:hover{background:#741313;color:#fff}.stock-history-filter{display:grid;grid-template-columns:2fr 1.3fr 1.3fr 1fr 1fr auto;gap:13px;align-items:end;margin:18px 0;padding:18px;background:#fff;border:1px solid #e3e6ec}.stock-history-filter label{display:grid;gap:6px;margin:0}.stock-history-filter label>span{font-size:12px;font-weight:700;color:#555e6e}.stock-history-filter input,.stock-history-filter select{height:43px;min-width:0;padding:0 11px;border:1px solid #d9dee7;background:#fff;color:#343a46}.stock-history-filter-actions{display:flex;align-items:center;gap:12px}.stock-history-filter-actions button{height:43px;cursor:pointer}.stock-history-filter-actions a{color:#7f1d1d;font-weight:700;white-space:nowrap}.stock-history-list{background:#fff;border:1px solid #e3e6ec}.stock-history-list-title{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e7e9ee}.stock-history-list-title h2{margin:0;font-size:18px;font-weight:800}.stock-history-list-title>span{color:#747d8d;font-size:13px}.stock-history-row{display:grid;grid-template-columns:100px minmax(0,1fr);gap:20px;padding:20px 24px;border-bottom:1px solid #eceef2}.stock-history-date{display:flex;gap:8px;color:#858d9b}.stock-history-date .material-icons{font-size:18px}.stock-history-date time{font-size:13px;font-weight:700;color:#555e6e}.stock-history-date small{display:block;margin-top:3px;color:#8a92a0;font-weight:400}.stock-history-title-line{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.stock-history-title-line h3{margin:0;font-size:16px;font-weight:800}.stock-history-status{padding:4px 9px;border-radius:3px;font-size:11px;font-weight:800}.stock-history-status.is-ok{background:#e8f5ed;color:#247346}.stock-history-status.is-error{background:#fff0e3;color:#9a5300}.stock-history-reference{margin-top:4px;color:#818999;font-size:13px}.stock-history-people{display:flex;gap:28px;flex-wrap:wrap;margin-top:13px}.stock-history-people>span{display:grid;gap:3px}.stock-history-people small{font-size:11px;color:#818999}.stock-history-people strong{font-size:13px;font-weight:700}.stock-history-details{margin-top:12px}.stock-history-details summary{color:#8d1b1b;font-size:12px;font-weight:700;cursor:pointer}.stock-history-detail-grid{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}.stock-history-detail-grid>div{min-width:130px;padding:9px 11px;background:#f6f7f9}.stock-history-detail-grid span{display:block;color:#7b8493;font-size:11px}.stock-history-detail-grid strong{display:block;margin-top:3px;font-size:13px}.stock-history-line{margin:8px 0 0;padding:9px;background:#f6f7f9;font-size:13px}.stock-history-empty{display:grid;justify-items:center;gap:8px;padding:50px 20px;text-align:center;color:#7b8493}.stock-history-empty .material-icons{font-size:34px;color:#a4aab4}.stock-history-empty strong{color:#343a46}.stock-history-empty p{margin:0}.stock-history-pagination{display:flex;justify-content:flex-end;padding:14px 22px}.stock-history-pagination nav{margin-left:auto}.stock-history-pagination nav>div:first-child{display:none}@media(max-width:1000px){.stock-history-filter{grid-template-columns:repeat(3,minmax(0,1fr))}.stock-history-filter-actions{grid-column:1/-1}}@media(max-width:620px){.stock-history-header{align-items:flex-start;flex-direction:column;padding:20px}.stock-history-header h1{font-size:25px}.stock-history-filter{grid-template-columns:1fr 1fr;padding:14px}.stock-history-filter label:first-child,.stock-history-filter-actions{grid-column:1/-1}.stock-history-row{grid-template-columns:1fr;gap:8px;padding:17px}.stock-history-date{align-items:center}.stock-history-date time small{display:inline;margin:0 0 0 7px}.stock-history-list-title{padding:15px 17px}}
</style>
@endsection
