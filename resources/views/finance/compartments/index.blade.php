@extends('layouts.admin')

@section('content')
<main class="cash-plan-page">
    <header class="cash-plan-header">
        <div><a href="{{ route('finance.dashboard') }}">← Suivi financier</a><p class="eyebrow">TENACOS · TRÉSORERIE GÉNÉRALE</p><h1>Répartition des fonds</h1><p>La caisse générale garde l’argent physiquement. Ces compartiments indiquent quelle part est réservée à chaque usage.</p></div>
        <div class="cash-plan-status {{ $summary['percentages_configured'] ? 'is-ready' : '' }}">{{ $summary['percentages_configured'] ? 'Répartition automatique active' : 'Répartition à configurer' }}</div>
    </header>

    @if(session('success'))<div class="cash-plan-alert">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="cash-plan-error">{{ $errors->first() }}</div>@endif

    <section class="cash-plan-kpis">
        <article><span>Encaissements réels · ce mois</span><strong>{{ number_format($realReceipts, 0, ',', ' ') }} F</strong></article>
        @if(auth()->user()->isAdmin())<article><span>Fonds à répartir</span><strong>{{ number_format($summary['unallocated_balance'], 0, ',', ' ') }} F</strong></article>@endif
        <article><span>Dépenses payées · ce mois</span><strong>{{ number_format($expenses, 0, ',', ' ') }} F</strong></article>
        @if(auth()->user()->isAdmin())<article><span>Solde physique caisse générale</span><strong>{{ number_format($summary['cash_balance'], 0, ',', ' ') }} F</strong></article>@endif
    </section>

    @if(! $summary['percentages_configured'])
        <div class="cash-plan-notice"><b>Configure la répartition avant les prochains encaissements.</b><span>Les pourcentages sont à définir par le DG et doivent totaliser 100 %. Aucun montant fictif n’est appliqué. Le solde non réparti actuel sera distribué dès l’enregistrement.</span></div>
    @endif

    <section class="cash-plan-section">
        <div class="cash-plan-section-title"><div><h2>Les huit compartiments</h2><p>Un paiement autorisé est imputé au compartiment choisi dans la demande.</p></div>@if(auth()->user()->isAdmin())<span>Solde total affecté : <b>{{ number_format($summary['allocated_balance'], 0, ',', ' ') }} F</b></span>@endif</div>
        <div class="cash-plan-grid">
            @foreach($summary['compartments'] as $compartment)
                <article class="cash-plan-card">
                    <div class="cash-plan-card-top"><span>{{ $compartment->percentage }} %</span><small>{{ $compartment->entries_count ?? '' }}</small></div>
                    <h3>{{ $compartment->name }}</h3><p>{{ $compartment->description }}</p>
                    @if(auth()->user()->isAdmin())<div class="cash-plan-balance"><span>Solde virtuel</span><strong class="{{ $compartment->balance < 0 ? 'is-negative' : '' }}">{{ number_format($compartment->balance, 0, ',', ' ') }} F</strong></div>@endif
                    <div class="cash-plan-progress {{ $compartment->utilization > 100 ? 'is-over' : '' }}"><i style="width:{{ min(100, max(0, $compartment->utilization)) }}%"></i></div>
                    <small>Enveloppe : {{ number_format((int) $compartment->allocated_total, 0, ',', ' ') }} F · Dépensé : {{ number_format((int) $compartment->spent_total, 0, ',', ' ') }} F · Utilisé : {{ $compartment->utilization }} %</small>
                    <small>Demandes en cours : {{ number_format($compartment->pending_amount, 0, ',', ' ') }} F</small>
                </article>
            @endforeach
        </div>
    </section>

    @if(auth()->user()->isAdmin())
        <section class="cash-plan-section cash-plan-config">
            <div class="cash-plan-section-title"><div><h2>Pourcentages de répartition</h2><p>Chaque nouvel encaissement réel sera réparti automatiquement selon ces parts.</p></div><span>Total requis : <b>100 %</b></span></div>
            <form method="POST" action="{{ route('finance.compartments.update') }}">@csrf
                <div class="cash-plan-percent-grid">
                    @foreach($summary['compartments'] as $compartment)
                        <label><span>{{ $compartment->name }}</span><div><input type="number" name="percentages[{{ $compartment->id }}]" min="0" max="100" step="1" value="{{ old('percentages.'.$compartment->id, $compartment->percentage) }}" required><b>%</b></div></label>
                    @endforeach
                </div>
                <div class="cash-plan-form-footer"><span id="cash-plan-percent-total">Total saisi : 0 %</span><button type="submit">Enregistrer et répartir le solde actuel</button></div>
            </form>
        </section>
    @endif

    <section class="cash-plan-section">
        <div class="cash-plan-section-title"><div><h2>Historique des répartitions et dépenses</h2><p>Chaque ligne conserve le mouvement de caisse d’origine.</p></div></div>
        <div class="cash-plan-table-wrap"><table><thead><tr><th>Date</th><th>Compartiment</th><th>Opération</th><th>Répartition</th><th>Montant</th><th>Origine</th></tr></thead><tbody>
            @forelse($entries as $entry)
                <tr><td>{{ $entry->occurred_at?->format('d/m/Y H:i') }}</td><td>{{ $entry->compartment->name }}</td><td>{{ $entry->entry_type === 'allocation' ? 'Fonds affectés' : 'Dépense payée' }}</td><td>{{ $entry->percentage_snapshot ? $entry->percentage_snapshot.' %' : '—' }}</td><td class="{{ $entry->entry_type === 'allocation' ? 'positive' : 'negative' }}">{{ $entry->entry_type === 'allocation' ? '+' : '−' }}{{ number_format($entry->amount, 0, ',', ' ') }} F</td><td>{{ $entry->cashMovement?->source_reference ?? $entry->description ?? 'Répartition du solde existant' }}</td></tr>
            @empty<tr><td colspan="6" class="cash-plan-empty">Aucune opération de répartition. Les nouveaux encaissements apparaîtront ici dès que les pourcentages seront configurés.</td></tr>@endforelse
        </tbody></table></div>
        {{ $entries->links() }}
    </section>
</main>
<style>
.cash-plan-page{max-width:1440px;margin:auto;padding:28px 24px 48px;color:#292e3b}.cash-plan-header{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;padding:28px 32px;background:#851719;color:#fff;margin-bottom:22px}.cash-plan-header a{color:#ffe0d5;text-decoration:none}.cash-plan-header .eyebrow{margin:22px 0 6px;font-size:12px;letter-spacing:.13em;font-weight:800}.cash-plan-header h1{font-size:34px;font-weight:800;margin:0 0 8px}.cash-plan-header p:last-child{margin:0;max-width:780px;color:#f3dede}.cash-plan-status{background:#fff0d4;color:#80531a;padding:10px 14px;font-weight:800;white-space:nowrap}.cash-plan-status.is-ready{background:#e5f4e9;color:#19733a}.cash-plan-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.cash-plan-kpis article,.cash-plan-section,.cash-plan-notice{background:#fff;border:1px solid #e1e5eb}.cash-plan-kpis article{padding:19px}.cash-plan-kpis span,.cash-plan-balance span{display:block;color:#778296;font-size:13px}.cash-plan-kpis strong{display:block;font-size:24px;margin-top:8px}.cash-plan-notice{display:flex;flex-direction:column;gap:5px;padding:16px 20px;margin-bottom:22px;border-left:4px solid #d18d18;background:#fffaf0;color:#5d4b2b}.cash-plan-section{padding:22px;margin-bottom:22px}.cash-plan-section-title{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}.cash-plan-section-title h2{margin:0 0 5px;font-size:22px;font-weight:800}.cash-plan-section-title p{margin:0;color:#778296}.cash-plan-section-title>span{color:#667185}.cash-plan-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.cash-plan-card{border:1px solid #e3e7ed;padding:17px;min-width:0}.cash-plan-card-top{display:flex;justify-content:space-between;align-items:center}.cash-plan-card-top>span{background:#f7eeee;color:#851719;font-weight:800;padding:5px 9px}.cash-plan-card h3{font-size:17px;font-weight:800;margin:14px 0 6px}.cash-plan-card p{min-height:38px;color:#778296;font-size:13px;margin:0 0 18px}.cash-plan-balance{display:flex;justify-content:space-between;align-items:end;gap:8px}.cash-plan-balance strong{font-size:19px}.cash-plan-balance .is-negative,.cash-plan-table-wrap .negative{color:#aa3434}.cash-plan-progress{height:5px;background:#edf0f4;margin:13px 0 8px}.cash-plan-progress i{height:100%;display:block;background:#258047}.cash-plan-progress.is-over i{background:#b53636}.cash-plan-card>small{display:block;color:#778296;margin-top:4px}.cash-plan-config{background:#fbfbfc}.cash-plan-percent-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.cash-plan-percent-grid label{display:grid;gap:7px;font-weight:700;font-size:13px}.cash-plan-percent-grid label>div{display:flex;align-items:center;border:1px solid #d6dce5;background:#fff}.cash-plan-percent-grid input{width:100%;min-width:0;border:0;padding:11px 12px;outline:0}.cash-plan-percent-grid b{padding:0 12px;color:#737e8d}.cash-plan-form-footer{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:18px}.cash-plan-form-footer span{font-weight:800}.cash-plan-form-footer button{border:0;background:#851719;color:#fff;padding:12px 18px;font-weight:800}.cash-plan-table-wrap{overflow-x:auto}.cash-plan-table-wrap table{width:100%;border-collapse:collapse;min-width:760px}.cash-plan-table-wrap th,.cash-plan-table-wrap td{text-align:left;padding:12px;border-bottom:1px solid #e9edf2}.cash-plan-table-wrap th{color:#748095;background:#f5f6f8;font-size:12px;text-transform:uppercase}.cash-plan-table-wrap td{font-size:14px}.cash-plan-table-wrap .positive{color:#218144;font-weight:800}.cash-plan-table-wrap .negative{font-weight:800}.cash-plan-empty{text-align:center!important;color:#778296;padding:28px!important}.cash-plan-alert,.cash-plan-error{padding:13px 16px;margin-bottom:16px;background:#edf7ef;color:#25683b}.cash-plan-error{background:#fff0f0;color:#982727}@media(max-width:1050px){.cash-plan-grid,.cash-plan-percent-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cash-plan-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.cash-plan-page{padding:12px}.cash-plan-header{align-items:flex-start;flex-direction:column;padding:22px 18px}.cash-plan-header h1{font-size:27px}.cash-plan-status{white-space:normal}.cash-plan-kpis{grid-template-columns:1fr 1fr;gap:9px}.cash-plan-kpis article{padding:13px}.cash-plan-kpis strong{font-size:18px}.cash-plan-section{padding:15px}.cash-plan-section-title{align-items:flex-start;flex-direction:column}.cash-plan-grid,.cash-plan-percent-grid{grid-template-columns:1fr}.cash-plan-card p{min-height:0}.cash-plan-form-footer{align-items:stretch;flex-direction:column}.cash-plan-form-footer button{width:100%}}
</style>
@if(auth()->user()->isAdmin())<script>
document.addEventListener('DOMContentLoaded',()=>{const inputs=[...document.querySelectorAll('.cash-plan-percent-grid input')], total=document.getElementById('cash-plan-percent-total');const refresh=()=>{const sum=inputs.reduce((n,input)=>n+(parseInt(input.value||'0',10)||0),0);total.textContent=`Total saisi : ${sum} %`;total.style.color=sum===100?'#218144':'#9a3434'};inputs.forEach(input=>input.addEventListener('input',refresh));refresh()});
</script>@endif
@endsection
