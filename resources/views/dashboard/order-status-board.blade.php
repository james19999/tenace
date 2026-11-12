@extends('layouts.admin')

@section('content')
<main class="order-status-page">
    @php
        $tabs = [
            'in_progress' => ['label' => 'En cours', 'icon' => 'local_shipping'],
            'waiting' => ['label' => 'En attente', 'icon' => 'hourglass_top'],
            'delivered' => ['label' => 'Livrées', 'icon' => 'task_alt'],
            'canceled' => ['label' => 'Annulées', 'icon' => 'cancel'],
        ];
        $activeLabel = $tabs[$activeTab]['label'];
    @endphp

    <header class="order-status-heading">
        <div>
            <a class="order-status-back" href="{{ route('Admin') }}"><span class="material-icons" aria-hidden="true">arrow_back</span> Tableau de bord</a>
            <span class="order-status-kicker">SUIVI DES COMMANDES</span>
            <h1>Commandes {{ mb_strtolower($activeLabel) }}</h1>
            <p>Consulte les commandes selon leur état et ouvre leur dossier pour le détail.</p>
        </div>
        <div class="order-status-total"><span>{{ number_format($counts[$activeTab], 0, ',', ' ') }}</span><small>commande(s) {{ mb_strtolower($activeLabel) }}</small></div>
    </header>

    <nav class="order-status-tabs" aria-label="Filtrer les commandes par statut">
        @foreach ($tabs as $tabKey => $tab)
            <a href="{{ route('orders.status-board', ['tab' => $tabKey]) }}" class="{{ $activeTab === $tabKey ? 'is-active' : '' }}" @if($activeTab === $tabKey) aria-current="page" @endif>
                <span class="material-icons" aria-hidden="true">{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
                <strong>{{ number_format($counts[$tabKey], 0, ',', ' ') }}</strong>
            </a>
        @endforeach
    </nav>

    <section class="order-status-list" aria-label="Liste des commandes {{ mb_strtolower($activeLabel) }}">
        @if ($orders->isEmpty())
            <div class="order-status-empty"><span class="material-icons" aria-hidden="true">inventory_2</span><strong>Aucune commande {{ mb_strtolower($activeLabel) }}</strong><p>Les nouvelles commandes correspondantes apparaîtront ici.</p></div>
        @else
            <div class="order-status-desktop-table">
                <table>
                    <thead><tr><th>Commande</th><th>Client</th><th>Date de livraison</th><th>Livreur</th><th>Montant</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><strong>{{ $order->code ?: 'N° '.$order->id }}</strong><small>Créée le {{ $order->created_at?->format('d/m/Y à H:i') ?: '—' }}</small></td>
                            <td><strong>{{ $order->costumer?->name ?: 'Client non renseigné' }}</strong><small>{{ $order->delivery_phone ?: ($order->costumer?->phone ?: 'Téléphone non renseigné') }}</small></td>
                            <td>{{ $order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : 'À confirmer' }}<small>{{ $order->time ?: 'Heure à confirmer' }}</small></td>
                            <td>{{ $order->user?->name ?: 'Non affecté' }}</td>
                            <td class="order-status-amount">{{ number_format((float) $order->total, 0, ',', ' ') }} F</td>
                            <td><a class="order-status-open" href="{{ route('ordershow', $order) }}" aria-label="Ouvrir la commande {{ $order->code ?: $order->id }}">Ouvrir <span class="material-icons" aria-hidden="true">arrow_forward</span></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="order-status-mobile-list">
                @foreach ($orders as $order)
                    <article class="order-status-mobile-card">
                        <div class="order-status-mobile-top"><strong>{{ $order->code ?: 'N° '.$order->id }}</strong><span>{{ number_format((float) $order->total, 0, ',', ' ') }} F</span></div>
                        <p>{{ $order->costumer?->name ?: 'Client non renseigné' }}</p>
                        <small>Livraison : {{ $order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : 'À confirmer' }} · {{ $order->time ?: 'heure à confirmer' }}</small>
                        <a class="order-status-open" href="{{ route('ordershow', $order) }}">Ouvrir le dossier <span class="material-icons" aria-hidden="true">arrow_forward</span></a>
                    </article>
                @endforeach
            </div>
            <div class="order-status-pagination">{{ $orders->links() }}</div>
        @endif
    </section>
</main>

<style>
    .order-status-page{--os-red:#861b1b;--os-ink:#282b35;--os-muted:#778191;--os-line:#e4e8ed;max-width:1500px;margin:0 auto;padding:12px 0 36px;color:var(--os-ink)}
    .order-status-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:20px;padding:22px 24px;background:#fff;border:1px solid var(--os-line)}
    .order-status-back{display:inline-flex;align-items:center;gap:5px;margin-bottom:14px;color:var(--os-red);font-size:13px;font-weight:700;text-decoration:none}.order-status-back .material-icons{font-size:18px}
    .order-status-kicker{display:block;color:var(--os-red);font-size:10px;font-weight:850;letter-spacing:.14em}
    .order-status-heading h1{margin:5px 0;color:var(--os-ink);font-size:clamp(26px,3vw,36px);font-weight:850;letter-spacing:-.04em}
    .order-status-heading p{margin:0;color:var(--os-muted);font-size:13px}
    .order-status-total{display:grid;min-width:145px;justify-items:end;padding:12px 16px;background:#f8f0ef;border:1px solid #eddddd}.order-status-total span{color:var(--os-red);font-size:27px;font-weight:850;line-height:1.1}.order-status-total small{margin-top:3px;color:var(--os-muted);font-size:11px}
    .order-status-tabs{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin-bottom:16px}
    .order-status-tabs a{display:flex;align-items:center;gap:9px;min-width:0;padding:12px 14px;color:#525d6d;background:#fff;border:1px solid var(--os-line);text-decoration:none}
    .order-status-tabs a.is-active{color:var(--os-red);background:#fff8f7;border-color:#dcb7b5;box-shadow:inset 0 -3px 0 var(--os-red)}
    .order-status-tabs .material-icons{font-size:20px}.order-status-tabs a>span:nth-child(2){overflow:hidden;font-size:13px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}.order-status-tabs strong{margin-left:auto;font-size:13px;font-variant-numeric:tabular-nums}
    .order-status-list{background:#fff;border:1px solid var(--os-line)}.order-status-desktop-table{overflow:auto}.order-status-desktop-table table{width:100%;border-collapse:collapse}.order-status-desktop-table th{padding:12px 15px;color:#687487;background:#f5f7f9;font-size:10px;font-weight:850;letter-spacing:.07em;text-align:left;text-transform:uppercase}.order-status-desktop-table td{padding:13px 15px;border-top:1px solid #edf0f3;color:#343b48;font-size:13px;vertical-align:middle}.order-status-desktop-table td strong,.order-status-desktop-table td small{display:block}.order-status-desktop-table td small{margin-top:3px;color:var(--os-muted);font-size:11px}.order-status-amount{font-weight:800;white-space:nowrap}.order-status-open{display:inline-flex;align-items:center;gap:4px;color:var(--os-red);font-size:12px;font-weight:800;text-decoration:none;white-space:nowrap}.order-status-open .material-icons{font-size:17px}
    .order-status-mobile-list{display:none}.order-status-empty{display:grid;justify-items:center;padding:48px 20px;text-align:center}.order-status-empty>.material-icons{margin-bottom:10px;color:#9aa3af;font-size:34px}.order-status-empty strong{font-size:16px}.order-status-empty p{margin:5px 0 0;color:var(--os-muted);font-size:13px}.order-status-pagination{padding:10px 14px;border-top:1px solid var(--os-line)}
    html[data-app-theme="dark"] .order-status-heading,html[data-app-theme="dark"] .order-status-tabs a,html[data-app-theme="dark"] .order-status-list{background:#1e232b;border-color:#373f4b;color:#edf1f5}html[data-app-theme="dark"] .order-status-heading h1,html[data-app-theme="dark"] .order-status-tabs a{color:#edf1f5}html[data-app-theme="dark"] .order-status-total{background:#302323;border-color:#563838}html[data-app-theme="dark"] .order-status-desktop-table th{background:#252b34;color:#b8c0cc}html[data-app-theme="dark"] .order-status-desktop-table td{border-color:#373f4b;color:#edf1f5}
    @media(max-width:767.98px){.order-status-page{padding:4px 0 25px}.order-status-heading{align-items:stretch;flex-direction:column;margin-bottom:12px;padding:16px}.order-status-back{margin-bottom:10px}.order-status-heading h1{font-size:26px}.order-status-total{justify-items:start;min-width:0;padding:9px 12px}.order-status-total span{font-size:23px}.order-status-tabs{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;margin-bottom:12px}.order-status-tabs a{gap:6px;padding:10px 8px}.order-status-tabs a>span:nth-child(2){font-size:11px}.order-status-desktop-table{display:none}.order-status-mobile-list{display:grid;gap:8px;padding:9px}.order-status-mobile-card{padding:13px;background:#fff;border:1px solid var(--os-line)}.order-status-mobile-top{display:flex;justify-content:space-between;gap:10px}.order-status-mobile-top strong{color:var(--os-ink);font-size:14px}.order-status-mobile-top span{color:var(--os-red);font-size:13px;font-weight:800;white-space:nowrap}.order-status-mobile-card p{margin:7px 0 2px;font-size:13px;font-weight:700}.order-status-mobile-card>small{display:block;color:var(--os-muted);font-size:11px}.order-status-mobile-card .order-status-open{margin-top:10px;padding-top:9px;border-top:1px solid var(--os-line)}html[data-app-theme="dark"] .order-status-mobile-card{background:#1e232b;border-color:#373f4b}html[data-app-theme="dark"] .order-status-mobile-top strong,html[data-app-theme="dark"] .order-status-mobile-card p{color:#edf1f5}}
</style>
@endsection
