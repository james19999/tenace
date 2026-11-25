@extends('layouts.admin')

@section('content')
<main class="bonus-payout-page">
    <header class="bonus-payout-heading">
        <a href="{{ route('courier-bonuses.index') }}"><span class="material-icons" aria-hidden="true">arrow_back</span> Primes des livreurs</a>
        <span>PAIEMENT DES PRIMES HEBDOMADAIRES</span>
        <h1>Demandes de retrait</h1>
        <p>Vérifie la demande puis confirme uniquement après avoir remis l’argent au livreur.</p>
    </header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="bonus-payout-list" aria-label="Demandes de retrait des primes">
        @forelse($requests as $item)
            <article class="bonus-payout-item status-{{ $item->status }}">
                <div class="bonus-payout-person"><span class="material-icons" aria-hidden="true">badge</span><div><strong>{{ $item->courier_name }}</strong><small>{{ $item->courier_phone ?: 'Téléphone non renseigné' }}</small></div></div>
                <div class="bonus-payout-amount"><strong>{{ number_format($item->amount,0,',',' ') }} F CFA</strong><small>Semaine du {{ CarbonCarbon::parse($item->week_start)->format('d/m') }} au {{ CarbonCarbon::parse($item->week_end)->format('d/m/Y') }}</small></div>
                <div class="bonus-payout-state"><b>{{ ['requested'=>'À traiter','paid'=>'Argent remis','rejected'=>'Refusée'][$item->status] ?? $item->status }}</b><small>Demande du {{ CarbonCarbon::parse($item->requested_at)->format('d/m/Y à H:i') }}</small>@if($item->handler_name)<small>Traité par {{ $item->handler_name }} · {{ CarbonCarbon::parse($item->handled_at)->format('d/m/Y à H:i') }}</small>@endif @if($item->manager_note)<small>{{ $item->manager_note }}</small>@endif</div>
                @if($item->status === 'requested')
                    <div class="bonus-payout-actions">
                        <form method="post" action="{{ route('courier-bonuses.payouts.handle',$item->id) }}" data-app-confirm-title="Confirmer la remise ?" data-app-confirm-message="Confirme que {{ $item->courier_name }} a reçu {{ number_format($item->amount,0,',',' ') }} F CFA en main propre." data-app-confirm-label="Confirmer la remise">@csrf<input type="hidden" name="decision" value="paid"><button class="bonus-payout-confirm"><span class="material-icons" aria-hidden="true">payments</span>Confirmer la remise</button></form>
                        <form method="post" action="{{ route('courier-bonuses.payouts.handle',$item->id) }}">@csrf<input type="hidden" name="decision" value="rejected"><input name="manager_note" minlength="3" maxlength="1000" placeholder="Motif si la demande est incorrecte" required><button class="bonus-payout-reject">Refuser</button></form>
                    </div>
                @endif
            </article>
        @empty
            <div class="bonus-payout-empty"><span class="material-icons" aria-hidden="true">task_alt</span><strong>Aucune demande pour le moment</strong><p>Les demandes de retrait des livreurs apparaîtront ici après la clôture et l’approbation d’une semaine.</p></div>
        @endforelse
    </section>
    @if($requests->hasPages())<div class="bonus-payout-pagination">{{ $requests->links() }}</div>@endif
</main>
<style>
.bonus-payout-page{max-width:1180px;margin:auto;padding:clamp(14px,2.4vw,30px);color:var(--app-text)}.bonus-payout-heading{margin-bottom:16px;padding:clamp(18px,2.4vw,28px);background:var(--app-surface);border:1px solid var(--app-border);border-left:4px solid var(--app-primary)}.bonus-payout-heading>a{display:inline-flex;align-items:center;gap:5px;color:var(--app-primary);font-size:12px;font-weight:750;text-decoration:none}.bonus-payout-heading>a .material-icons{font-size:17px}.bonus-payout-heading>span{display:block;margin-top:15px;color:var(--app-primary);font-size:10px;font-weight:850;letter-spacing:.12em}.bonus-payout-heading h1{margin:5px 0;color:var(--app-text);font-size:clamp(25px,3vw,35px);font-weight:850}.bonus-payout-heading p{margin:0;color:var(--app-muted);font-size:13px;line-height:1.5}.bonus-payout-list{display:grid;gap:10px}.bonus-payout-item{display:grid;grid-template-columns:minmax(150px,1fr) minmax(180px,.8fr) minmax(180px,1fr) minmax(260px,1.2fr);align-items:center;gap:14px;padding:16px;background:var(--app-surface);border:1px solid var(--app-border);border-top:3px solid #d39b3b}.bonus-payout-item.status-paid{border-top-color:var(--app-primary)}.bonus-payout-item.status-rejected{border-top-color:#a33e39}.bonus-payout-person{display:flex;align-items:center;gap:10px;min-width:0}.bonus-payout-person>.material-icons{display:grid;place-items:center;flex:none;width:38px;height:38px;background:var(--app-primary-soft);color:var(--app-primary)}.bonus-payout-person>div,.bonus-payout-amount,.bonus-payout-state{display:grid;gap:4px;min-width:0}.bonus-payout-person strong{font-size:14px}.bonus-payout-person small,.bonus-payout-amount small,.bonus-payout-state small{color:var(--app-muted);font-size:11px;line-height:1.4;overflow-wrap:anywhere}.bonus-payout-amount>strong{font-size:17px;color:var(--app-primary)}.bonus-payout-state b{font-size:12px}.bonus-payout-actions{display:grid;gap:7px}.bonus-payout-actions form{display:flex;gap:7px;min-width:0}.bonus-payout-actions button{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:8px 11px;border:0;border-radius:7px;font-size:11px;font-weight:800;cursor:pointer;white-space:nowrap}.bonus-payout-confirm{background:var(--app-primary);color:#fff}.bonus-payout-confirm .material-icons{font-size:17px}.bonus-payout-reject{background:#f8e9e8;color:#9d3833}.bonus-payout-actions input{flex:1;min-width:0;padding:8px 9px;border:1px solid var(--app-border);border-radius:7px;background:var(--app-surface);color:var(--app-text);font-size:11px}.bonus-payout-empty{display:grid;justify-items:center;gap:8px;padding:40px 18px;text-align:center;background:var(--app-surface);border:1px solid var(--app-border);color:var(--app-muted)}.bonus-payout-empty>.material-icons{font-size:32px;color:var(--app-primary)}.bonus-payout-empty strong{color:var(--app-text);font-size:16px}.bonus-payout-empty p{margin:0;font-size:12px;line-height:1.5}.bonus-payout-pagination{margin-top:14px}html[data-app-theme="dark"] .bonus-payout-actions input{background:var(--app-surface)}
@media(max-width:900px){.bonus-payout-item{grid-template-columns:repeat(2,minmax(0,1fr))}.bonus-payout-actions{grid-column:1/-1}}@media(max-width:560px){.bonus-payout-page{padding:12px 9px 24px}.bonus-payout-item{grid-template-columns:1fr;gap:11px;padding:14px}.bonus-payout-actions{grid-column:auto}.bonus-payout-actions form{flex-direction:column}.bonus-payout-actions button{width:100%;min-height:42px}.bonus-payout-heading{padding:17px}.bonus-payout-heading h1{font-size:26px}}
</style>
@endsection
