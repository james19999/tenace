@extends('layouts.admin')
@section('content')
<div class="container-fluid wholesaler-dashboard">
    <header class="wholesale-heading">
        <div>
            <span class="wholesale-eyebrow">ESPACE GROSSISTE</span>
            <h1>Bonjour {{ auth()->user()->name }}</h1>
            <p>Retrouve tes commandes, les remises de caisse et tes demandes de dépenses.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('product') }}"><span class="material-icons">add_shopping_cart</span> Nouvelle commande</a>
    </header>

    <section class="wholesale-kpis" aria-label="Résumé de l’activité">
        <article><span>Commandes créées</span><strong>{{ number_format($summary['orders'], 0, ',', ' ') }}</strong><small>Tes commandes grossistes</small></article>
        <article><span>En cours</span><strong>{{ number_format($summary['in_progress'], 0, ',', ' ') }}</strong><small>À suivre ou à livrer</small></article>
        <article><span>Livrées</span><strong>{{ number_format($summary['delivered'], 0, ',', ' ') }}</strong><small>Commandes terminées</small></article>
        <article class="wholesale-kpi-revenue"><span>Montant des commandes livrées</span><strong>{{ number_format($summary['revenue'], 0, ',', ' ') }} F</strong><small>Montant enregistré pour tes livraisons</small></article>
    </section>

    <section class="wholesale-quick-links" aria-label="Actions financières">
        <a href="{{ route('finance.remittances.index') }}"><span class="material-icons">payments</span><span><strong>Remises grossiste</strong><small>Préparer un bon, suivre la collecte et les signatures</small></span><span class="material-icons wholesale-arrow">arrow_forward</span></a>
        <a href="{{ route('finance.expenses.index') }}"><span class="material-icons">request_quote</span><span><strong>Demandes de dépenses</strong><small>Créer une demande et suivre sa validation</small></span><span class="material-icons wholesale-arrow">arrow_forward</span></a>
    </section>

    <section class="wholesale-recent">
        <div class="wholesale-section-heading"><div><h2>Mes dernières commandes</h2><p>Les commandes grossistes créées avec ton compte.</p></div><a href="{{ route('order') }}">Voir toutes les commandes</a></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Commande</th><th>Client</th><th>Date</th><th>Montant</th><th>État</th></tr></thead><tbody>
            @forelse($recentOrders as $order)
                @php
                    $orderStatus = $order->status === 'canceled' || $order->partner_order_status === 'canceled'
                        ? 'Annulée'
                        : (($order->status === 'delivered' || $order->partner_order_status === 'delivered')
                            ? 'Livrée'
                            : ($order->status === 'ordered' ? 'En cours' : ucfirst(str_replace('_', ' ', (string) $order->status))));
                @endphp
                <tr><td><strong>{{ $order->code ?? 'Commande #'.$order->id }}</strong></td><td>{{ $order->costumer?->name ?? '—' }}</td><td>{{ \Illuminate\Support\Carbon::parse($order->date_order ?? $order->created_at)->format('d/m/Y') }}</td><td>{{ number_format((int) $order->subtotal, 0, ',', ' ') }} F</td><td><span class="wholesale-status">{{ $orderStatus }}</span></td></tr>
            @empty
                <tr><td colspan="5" class="wholesale-empty">Tu n’as pas encore créé de commande grossiste.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
<style>
.wholesaler-dashboard{max-width:1500px;padding:24px 26px 44px;color:#273142}.wholesale-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px;padding:25px 28px;background:#fff;border:1px solid #e2e7eb}.wholesale-eyebrow{color:#8b1b1b;font-size:11px;font-weight:850;letter-spacing:.12em}.wholesale-heading h1{margin:7px 0;font-size:clamp(26px,3vw,36px);font-weight:850;letter-spacing:-.03em}.wholesale-heading p,.wholesale-section-heading p{margin:0;color:#778397}.wholesale-heading .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 17px;white-space:nowrap}.wholesale-heading .material-icons{font-size:19px}.wholesale-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.wholesale-kpis article{min-width:0;padding:20px 21px;background:#fff;border:1px solid #e2e7eb}.wholesale-kpis article>span,.wholesale-kpis small{display:block;color:#758194}.wholesale-kpis article>span{font-size:13px;font-weight:700}.wholesale-kpis strong{display:block;margin:9px 0;font-size:clamp(24px,2.6vw,34px);font-weight:850;line-height:1.15;color:#293342;overflow-wrap:anywhere}.wholesale-kpis small{font-size:12px}.wholesale-kpi-revenue{border-top:3px solid #197b2a!important}.wholesale-kpi-revenue strong{color:#197b2a}.wholesale-quick-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:18px}.wholesale-quick-links>a{display:flex;align-items:center;gap:14px;min-width:0;padding:18px 20px;background:#fff;border:1px solid #e2e7eb;color:#293342;text-decoration:none}.wholesale-quick-links>a>.material-icons:first-child{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;background:#f8eeee;color:#8b1b1b}.wholesale-quick-links>a>span:nth-child(2){display:grid;gap:4px;min-width:0}.wholesale-quick-links strong{font-size:15px}.wholesale-quick-links small{color:#778397;line-height:1.4}.wholesale-arrow{margin-left:auto;color:#8b1b1b}.wholesale-quick-links>a:hover{border-color:#c99b9b;box-shadow:0 5px 16px rgba(40,25,25,.05)}.wholesale-recent{padding:21px 22px;background:#fff;border:1px solid #e2e7eb}.wholesale-section-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px}.wholesale-section-heading h2{margin:0 0 5px;font-size:20px;font-weight:800}.wholesale-section-heading>a{color:#197b2a;font-weight:750;white-space:nowrap}.wholesale-recent .table{min-width:680px;margin:0}.wholesale-recent thead th{padding:12px;background:#f4f6f8;border:0;color:#687486;font-size:10px;font-weight:850;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}.wholesale-recent tbody td{padding:14px 12px;border-top:1px solid #edf0f3;color:#434d5c;vertical-align:middle}.wholesale-status{display:inline-block;padding:5px 10px;border-radius:20px;background:#eff3f6;color:#59677a;font-size:12px;font-weight:750}.wholesale-empty{text-align:center;color:#778397;padding:28px!important}
@media(max-width:900px){.wholesale-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){.wholesaler-dashboard{padding:13px 11px 28px}.wholesale-heading{align-items:flex-start;flex-direction:column;padding:19px;margin-bottom:14px}.wholesale-heading .btn{width:100%;justify-content:center}.wholesale-kpis{gap:9px}.wholesale-kpis article{padding:15px 13px}.wholesale-kpis article>span{font-size:12px}.wholesale-kpis small{font-size:11px}.wholesale-quick-links{grid-template-columns:1fr;gap:9px;margin-bottom:14px}.wholesale-quick-links>a{padding:14px}.wholesale-recent{padding:15px 12px}.wholesale-section-heading{flex-direction:column}.wholesale-section-heading>a{white-space:normal}}
</style>
@endsection
