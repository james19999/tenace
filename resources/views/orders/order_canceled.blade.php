@extends('layouts.admin')

@section('content')
<main class="cancelled-orders-page">
    <header class="cancelled-orders-heading">
        <div>
            <span class="cancelled-orders-eyebrow"><span class="material-icons" aria-hidden="true">history</span> SUIVI DES COMMANDES</span>
            <h1>Commandes annulées</h1>
            <p>Consulte les motifs, les coordonnées client, les articles et les montants des commandes annulées.</p>
        </div>
        <span class="cancelled-orders-count">{{ number_format($orders->total(), 0, ',', ' ') }} commande(s)</span>
    </header>

    @if($pendingCancellationRequests->isNotEmpty())
        <section class="cancellation-requests-panel">
            <header><div><span class="material-icons" aria-hidden="true">pending_actions</span><div><h2>Demandes des livreurs à valider</h2><p>La commande reste bloquée pour le livreur jusqu’à ta décision.</p></div></div><strong>{{ $pendingCancellationRequests->total() }} en attente</strong></header>
            <div class="cancellation-requests-list">
                @foreach($pendingCancellationRequests as $cancellationRequest)
                    <article class="cancellation-request-card">
                        <div class="cancellation-request-order"><strong>{{ $cancellationRequest->order?->code ?: 'Commande #'.$cancellationRequest->order_id }}</strong><small>{{ $cancellationRequest->request_type === 'waiting' ? 'Demande de mise en attente' : 'Demande d’annulation' }} · {{ $cancellationRequest->courier?->name ?? 'Livreur' }} · {{ $cancellationRequest->created_at?->format('d/m/Y à H:i') }}</small></div>
                        <div class="cancellation-request-reason"><span>Motif donné</span><p>{{ $cancellationRequest->reason }}</p>@if($cancellationRequest->order?->costumer)<small>Client : {{ $cancellationRequest->order->costumer->name }} · {{ $cancellationRequest->order->costumer->phone }}</small>@endif</div>
                        @if(in_array(auth()->user()?->user_type, ['ADMINUSER', 'MNG'], true))
                            <div class="cancellation-request-actions">
                                <form method="post" action="{{ route('courier-cancellations.decision', $cancellationRequest->id) }}" data-app-confirm-title="{{ $cancellationRequest->request_type === 'waiting' ? 'Accepter la mise en attente ?' : 'Accepter l’annulation ?' }}" data-app-confirm-message="{{ $cancellationRequest->request_type === 'waiting' ? 'La commande sera mise en attente et restera attribuée au livreur, qui devra la terminer avant d’en prendre une autre.' : 'La commande sera annulée et le livreur pourra ensuite prendre une autre commande.' }}" data-app-confirm-label="Accepter" data-app-confirm-variant="danger">@csrf<input type="hidden" name="decision" value="approved"><button class="cancellation-approve" type="submit"><span class="material-icons" aria-hidden="true">check</span>{{ $cancellationRequest->request_type === 'waiting' ? 'Accepter la mise en attente' : 'Accepter l’annulation' }}</button></form>
                                <form method="post" action="{{ route('courier-cancellations.decision', $cancellationRequest->id) }}" data-app-confirm-title="Refuser l’annulation ?" data-app-confirm-message="La demande restera refusée et le livreur ne pourra pas reprendre une autre commande." data-app-confirm-label="Refuser">@csrf<input type="hidden" name="decision" value="rejected"><input name="review_note" maxlength="500" placeholder="Motif du refus (facultatif)"><button class="cancellation-reject" type="submit"><span class="material-icons" aria-hidden="true">close</span>Refuser</button></form>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="cancelled-orders-pagination">{{ $pendingCancellationRequests->links() }}</div>
        </section>
    @endif

    <section class="cancelled-orders-panel">
        @if($orders->isEmpty())
            <div class="cancelled-orders-empty"><span class="material-icons" aria-hidden="true">task_alt</span><h2>Aucune commande annulée</h2><p>Les commandes annulées apparaîtront ici avec leur motif.</p></div>
        @else
            <div class="cancelled-orders-table-wrap">
                <table class="cancelled-orders-table">
                    <thead><tr><th>Commande</th><th>Client</th><th>Articles</th><th>Livraison</th><th>Montants</th><th>Motif d’annulation</th><th></th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td><strong>{{ $order->code ?: 'Commande #'.$order->id }}</strong><small>Créée le {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}</small><small>Annulée le {{ ($order->canceled_at ?? $order->updated_at)?->format('d/m/Y H:i') ?? '—' }}@if($order->canceled_at_is_estimated) · date estimée @endif</small></td>
                            <td><strong>{{ $order->costumer?->name ?? 'Client non renseigné' }}</strong><small>{{ $order->costumer?->phone ?? $order->delivery_phone ?? 'Téléphone non renseigné' }}</small>@if($order->partner)<small>Partenaire : {{ $order->partner->name }}</small>@endif</td>
                            <td><ul class="cancelled-order-items">@forelse($order->orderItems->take(4) as $item)<li>{{ $item->product?->name ?? 'Article supprimé' }} × {{ $item->quantity }}</li>@empty<li>Articles non renseignés</li>@endforelse</ul>@if($order->orderItems->count() > 4)<small>et {{ $order->orderItems->count() - 4 }} autre(s)</small>@endif</td>
                            <td><strong>{{ $order->user?->name ?? 'Aucun livreur' }}</strong><small>{{ $order->user?->phone ?? '—' }}</small><small>{{ $order->delivery_address ?: $order->shipping_city ?: 'Adresse non renseignée' }}</small></td>
                            <td><strong>{{ number_format((float) ($order->total ?? 0), 0, ',', ' ') }} F</strong><small>Sous-total : {{ number_format((float) ($order->subtotal ?? 0), 0, ',', ' ') }} F</small><small>Remise : {{ number_format((float) ($order->remis ?? 0), 0, ',', ' ') }} %</small><small>Payé : {{ number_format((float) ($order->paid_amount ?? 0), 0, ',', ' ') }} F</small></td>
                            <td><span class="cancelled-order-reason">{{ filled($order->motif) ? $order->motif : 'Motif non renseigné' }}</span></td>
                            <td class="cancelled-order-actions">@if(in_array(auth()->user()?->user_type, ['ADMINUSER','MNG','SCR','VDS','PT','CSA','CALLCENTER'], true))<a class="cancelled-order-details" href="{{ route('edit-order', ['order' => $order->id, 'relaunch' => 1]) }}"><span class="material-icons" aria-hidden="true">restart_alt</span><span>Modifier & relancer</span></a>@endif<a class="cancelled-order-details" href="{{ route('ordershow', $order->id) }}"><span class="material-icons" aria-hidden="true">visibility</span><span>Détails</span></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="cancelled-orders-pagination">{{ $orders->links() }}</div>
        @endif
    </section>
</main>

<style>
.cancelled-orders-page{max-width:1600px;margin:0 auto;padding:24px;color:#273142}.cancelled-orders-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:20px}.cancelled-orders-eyebrow{display:flex;align-items:center;gap:7px;color:#8a2525;font-size:11px;font-weight:850;letter-spacing:.1em}.cancelled-orders-eyebrow .material-icons{font-size:17px}.cancelled-orders-heading h1{margin:8px 0;font-size:clamp(26px,3vw,36px);font-weight:850;letter-spacing:-.035em}.cancelled-orders-heading p{margin:0;color:#778397;line-height:1.5}.cancelled-orders-count{flex:none;padding:9px 12px;border:1px solid #ead8d8;border-radius:7px;background:#fff7f6;color:#8a2525;font-size:13px;font-weight:800}.cancelled-orders-panel{overflow:hidden;background:#fff;border:1px solid #e2e6eb;box-shadow:0 5px 22px rgba(24,35,50,.04)}.cancelled-orders-table-wrap{overflow:auto}.cancelled-orders-table{width:100%;min-width:1100px;border-collapse:collapse}.cancelled-orders-table th{padding:13px 14px;background:#f5f6f8;color:#6d7888;text-align:left;font-size:10px;font-weight:850;letter-spacing:.07em;text-transform:uppercase;white-space:nowrap}.cancelled-orders-table td{padding:14px;border-top:1px solid #edf0f3;color:#3b4654;font-size:13px;vertical-align:top}.cancelled-orders-table td strong,.cancelled-orders-table td small{display:block}.cancelled-orders-table td strong{color:#283344;font-weight:800}.cancelled-orders-table td small{margin-top:5px;color:#7c8796;font-size:11px;line-height:1.4}.cancelled-order-items{min-width:145px;margin:0;padding-left:17px}.cancelled-order-items li{margin:0 0 5px;line-height:1.4}.cancelled-order-reason{display:block;min-width:165px;max-width:260px;padding:9px 10px;border-left:3px solid #bd4b43;background:#fff5f4;color:#713b38;line-height:1.45;overflow-wrap:anywhere}.cancelled-order-details{display:inline-flex;align-items:center;gap:5px;padding:8px 10px;border:1px solid #e0e5e9;border-radius:6px;color:#465366;font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap}.cancelled-order-details:hover{border-color:#9b3434;color:#8a2525;text-decoration:none}.cancelled-order-details .material-icons{font-size:17px}.cancelled-orders-pagination{padding:14px 16px}.cancelled-orders-pagination .pagination{justify-content:flex-end;margin:0}.cancelled-orders-pagination .page-link{color:#8a2525}.cancelled-orders-empty{display:grid;justify-items:center;padding:60px 20px;text-align:center}.cancelled-orders-empty>.material-icons{color:#438357;font-size:40px}.cancelled-orders-empty h2{margin:12px 0 4px;font-size:20px;font-weight:800}.cancelled-orders-empty p{margin:0;color:#778397}@media(max-width:700px){.cancelled-orders-page{padding:16px 12px 28px}.cancelled-orders-heading{align-items:flex-start;flex-direction:column}.cancelled-orders-count{align-self:flex-start}.cancelled-orders-pagination .pagination{justify-content:center}.cancelled-orders-table{min-width:1050px}}
.cancellation-requests-panel{margin:0 0 18px;overflow:hidden;border:1px solid #ead8d8;background:#fff;box-shadow:0 5px 22px rgba(24,35,50,.04)}.cancellation-requests-panel>header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;background:#fff7f6;border-bottom:1px solid #f0dfdd}.cancellation-requests-panel>header>div{display:flex;align-items:center;gap:10px}.cancellation-requests-panel>header>.material-icons,.cancellation-requests-panel>header>div>.material-icons{color:#8a2525;font-size:24px}.cancellation-requests-panel h2{margin:0;color:#283344;font-size:18px;font-weight:850}.cancellation-requests-panel header p{margin:3px 0 0;color:#778397;font-size:12px}.cancellation-requests-panel header>strong{flex:none;color:#8a2525;font-size:12px}.cancellation-requests-list{display:grid}.cancellation-request-card{display:grid;grid-template-columns:minmax(170px,.7fr) minmax(240px,1.2fr) minmax(260px,1fr);align-items:center;gap:16px;padding:16px;border-bottom:1px solid #edf0f3}.cancellation-request-order strong,.cancellation-request-order small{display:block}.cancellation-request-order strong{color:#283344;font-size:14px}.cancellation-request-order small,.cancellation-request-reason small{margin-top:5px;color:#7c8796;font-size:11px;line-height:1.4}.cancellation-request-reason{padding:10px 12px;border-left:3px solid #bd4b43;background:#fff8f6}.cancellation-request-reason span{color:#8a2525;font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.06em}.cancellation-request-reason p{margin:4px 0 0;color:#713b38;font-size:13px;line-height:1.45;overflow-wrap:anywhere}.cancellation-request-reason small{display:block}.cancellation-request-actions{display:grid;gap:7px}.cancellation-request-actions form{display:flex;gap:7px;min-width:0}.cancellation-request-actions input{flex:1;min-width:0;padding:9px;border:1px solid #dce2e8;border-radius:5px}.cancellation-request-actions button{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-height:38px;padding:8px 10px;border:0;border-radius:5px;font-size:11px;font-weight:800;white-space:nowrap;cursor:pointer}.cancellation-request-actions button .material-icons{font-size:16px}.cancellation-approve{background:#7e1d20;color:#fff}.cancellation-reject{background:#eef1f4;color:#3b4654}@media(max-width:800px){.cancellation-request-card{grid-template-columns:1fr;gap:10px}.cancellation-request-actions form{flex-wrap:wrap}.cancellation-request-actions input{flex-basis:100%}}@media(max-width:520px){.cancellation-requests-panel>header{align-items:flex-start}.cancellation-request-actions button{flex:1}.cancellation-requests-panel header p{max-width:250px}}
</style>
@endsection
