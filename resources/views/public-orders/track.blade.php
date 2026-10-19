<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#7e1615"><title>Suivi de commande {{ $order->code }} · TENACOS</title>
<style>:root{--red:#7e1615;--ink:#252a34;--muted:#687386;--line:#e5e8ee;--bg:#f6f7f9}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{max-width:760px;margin:auto;padding:24px 16px}.card{background:#fff;border:1px solid var(--line);border-radius:0;padding:22px;margin-bottom:16px}.tag{color:var(--red);font-size:12px;font-weight:800;letter-spacing:.16em}.muted{color:var(--muted)}h1{font-size:28px;margin:6px 0}h2{font-size:19px;margin:0 0 12px}.row{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid var(--line)}.row:last-child{border:0}.alert{background:#fff5df;border-left:4px solid #d59c31;padding:14px;border-radius:0;color:var(--red)}.total{font-size:20px;font-weight:850}.items{margin:0;padding-left:20px}@media(max-width:550px){.wrap{padding:12px}.card{padding:17px}.row{align-items:flex-start}.row strong{text-align:right}}</style></head>
<body><main class="wrap">
    @if (session('success'))<section class="card" style="border-color:#9bd2ad;color:#176b37">{{ session('success') }}</section>@endif
    @if ($errors->any())<section class="card" style="border-color:#e6a1a1;color:#a32222">{{ $errors->first() }}</section>@endif
    <section class="card"><span class="tag">SUIVI DE COMMANDE</span><h1>Commande {{ $order->code }}</h1><p class="muted mb-0">Conserve ce lien pour retrouver l’avancement de ta commande.</p><a href="{{ route('customer-portal.activate',$order->public_tracking_token) }}" style="display:inline-flex;align-items:center;justify-content:center;margin-top:14px;min-height:44px;padding:10px 14px;border:1px solid var(--red);background:var(--red);color:#fff;text-decoration:none;font-weight:750">Créer ou retrouver mon espace client</a></section>
    <section class="card"><h2>Avancement</h2><p class="status">@switch($order->partner_order_status)
        @case('awaiting_delivery_quote') Le partenaire prépare le montant de livraison. @break
        @case('awaiting_customer_confirmation') Confirme le montant final auprès du partenaire. @break
        @case('awaiting_prepayment') Paiement avant livraison attendu. @break
        @case('preparing') Le partenaire prépare ta commande. @break
        @case('ready_for_courier') La commande attend un livreur. @break
        @case('assigned_to_courier') Un livreur a été affecté à ta commande. @break
        @case('handed_to_courier') Le colis a été confié au livreur. @break
        @case('out_for_delivery') Le livreur est en route. @break
        @case('delivered') Commande livrée. @break
        @case('canceled') Commande annulée. @break
        @default Demande envoyée au partenaire. @endswitch</p>
        @if ($order->partner_order_status === 'awaiting_delivery_quote')<div class="alert">Les frais de livraison ne sont pas encore inclus. Le partenaire te communiquera le montant total à confirmer.</div>@endif
        @if ($order->partner)<p class="muted">Partenaire : <strong>{{ $order->partner->name }}</strong> · {{ $order->partner->phone }}</p>@endif
        @if ($order->partner_order_status === 'awaiting_customer_confirmation')
            <form method="POST" action="{{ route('public-order.quote.confirm', $order->public_tracking_token) }}">@csrf
                <button type="submit" style="width:100%;padding:14px;border:0;border-radius:0;background:var(--red);color:#fff;font-weight:800">Accepter le total et continuer</button>
            </form>
        @elseif ($order->partner_order_status === 'awaiting_prepayment')
            <div class="alert">Le paiement anticipé demandé est de {{ number_format(max(0, (int) $order->montant - (int) $order->paid_amount), 0, ',', ' ') }} F via {{ $order->requested_payment_method === 'mobile_money' ? 'Mobile Money' : 'espèces auprès du partenaire' }}. Enregistre la référence ou les détails pour que le partenaire confirme.</div>
            <form method="POST" action="{{ route('public-order.payment', $order->public_tracking_token) }}">@csrf
                <label for="transaction-reference">Référence de transaction (si disponible)</label><input id="transaction-reference" name="transaction_reference" maxlength="160" style="display:block;width:100%;padding:12px;margin:6px 0 12px;border:1px solid var(--line);border-radius:0;width:100%;padding:12px;margin:6px 0 12px;border:1px solid var(--line);border-radius:0;padding:14px;border:0;border-radius:0;background:var(--red);color:#fff;font-weight:800">Envoyer au partenaire pour confirmation</button>
            </form>
        @endif
    </section>
    <section class="card"><h2>Produits</h2><ul class="items">@foreach ($order->orderItems as $item)<li>{{ $item->product?->name ?? 'Produit' }} × {{ $item->quantity }} — {{ number_format((float) $item->price * (int) $item->quantity, 0, ',', ' ') }} F</li>@endforeach</ul>
        <div class="row"><span>Sous-total produits</span><strong>{{ number_format((float) $order->subtotal, 0, ',', ' ') }} F</strong></div>
        @if (!is_null($order->tax) && (float) $order->tax > 0)<div class="row"><span>Livraison</span><strong>{{ number_format((float) $order->tax, 0, ',', ' ') }} F</strong></div>@endif
        <div class="row total"><span>Total</span><span>{{ number_format((float) ($order->montant ?? ((float) $order->total + (float) $order->tax)), 0, ',', ' ') }} F</span></div>
    </section>
    @if ($order->payments->isNotEmpty())<section class="card"><h2>Paiements</h2>@foreach ($order->payments as $payment)<div class="row"><span>{{ $payment->stage === 'before_delivery' ? 'Avant livraison' : 'À la livraison' }} · {{ $payment->method === 'mobile_money' ? 'Mobile Money' : 'Espèces' }}<br><small class="muted">{{ $payment->status === 'confirmed' ? 'Confirmé' : ($payment->status === 'collected' ? 'Collecté par le livreur, remise au partenaire en attente' : 'En attente de confirmation du partenaire') }}</small></span><strong>{{ number_format((int) $payment->amount, 0, ',', ' ') }} F</strong></div>@endforeach</section>@endif
</main></body></html>
