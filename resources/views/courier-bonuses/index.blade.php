@extends('layouts.admin')

@section('content')
<main class="bonus-page">
    <header class="bonus-hero"><div><span>RÉMUNÉRATION VARIABLE · LIVRAISONS</span><h1>Primes hebdomadaires des livreurs</h1><p>Une enveloppe de 15 000 F CFA maximum, calculée sur les résultats du lundi au samedi.</p></div>
        @if($week)<div class="bonus-status status-{{ $week->status }}">{{ ['draft'=>'En préparation','manager_validated'=>'En attente de la DG','approved'=>'Approuvée et verrouillée','rejected'=>'À corriger'][$week->status] ?? $week->status }}</div>@endif
    </header>
    @if(session('success'))<div class="bonus-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="bonus-alert error">{{ $errors->first() }}</div>@endif

    <section class="bonus-toolbar">
        <form method="get" action="{{ route('courier-bonuses.index') }}" class="bonus-week-select"><label for="bonus-week">Semaine consultée</label><select id="bonus-week" name="week" onchange="this.form.submit()"><option value="">Sélectionner une semaine</option>@foreach($weeks as $item)<option value="{{ $item->week_start }}" @selected($week?->week_start === $item->week_start)>Du {{ \Carbon\Carbon::parse($item->week_start)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($item->week_end)->format('d/m/Y') }} · {{ number_format($item->budget,0,',',' ') }} F</option>@endforeach</select></form>
        <div class="bonus-toolbar-actions">@if(auth()->user()->user_type === 'MNG')<a class="bonus-button secondary" href="{{ route('courier-bonuses.payouts') }}">Demandes de retrait @if($pendingPayoutCount > 0)<span class="bonus-payout-badge">{{ $pendingPayoutCount }}</span>@endif</a>@endif @if($week)<a class="bonus-button secondary" href="{{ route('courier-bonuses.export', $week->id) }}">Télécharger le relevé CSV</a>@endif</div>
    </section>

    @if(!$week)
        <section class="bonus-panel bonus-create"><div><h2>Ouvrir une semaine de primes</h2><p>Choisis un lundi. Le système fixe automatiquement la fin de période au samedi.</p></div><form method="post" action="{{ route('courier-bonuses.weeks.store') }}">@csrf<label>Lundi de début<input type="date" name="week_start" value="{{ now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString() }}" required></label><label>Budget hebdomadaire<input type="number" name="budget" min="0" max="15000" step="1" value="15000" required></label><button class="bonus-button">Créer la semaine</button></form></section>
    @else
        <section class="bonus-envelope-grid">
            <article class="bonus-envelope primary"><span>Budget maximal de la semaine</span><strong>{{ number_format($week->budget,0,',',' ') }} F CFA</strong><small>Le total attribué ne dépassera jamais cette enveloppe.</small></article>
            <article class="bonus-envelope"><span>Livraisons terminées</span><strong>{{ $deliveredCount }}</strong><small>Commandes marquées livrées du lundi au samedi.</small></article>
            <article class="bonus-envelope"><span>Livraisons éligibles</span><strong>{{ $isLocked ? $lockedDeliveryIds->count() : $result['orders']->count() }}</strong><small>Statut « livrée » et encaissement confirmé. Photo requise seulement pour une expédition.</small></article>
            <article class="bonus-envelope"><span>Dans le délai de 45 min</span><strong>{{ $ontimeCount }}</strong><small>Départ et confirmation horodatés, ou retard exceptionnel validé.</small></article>
            <article class="bonus-envelope"><span>{{ $isLocked ? 'Prime validée' : 'Prime calculée' }}</span><strong>{{ number_format($lockedTotal,0,',',' ') }} F CFA</strong><small>{{ $week->budget - $lockedTotal }} F non distribués si aucun bénéficiaire n’existe.</small></article>
        </section>

        @if($ineligibleOrders->isNotEmpty())<section class="bonus-panel"><div class="bonus-panel-head"><div><h2>Livraisons exclues du calcul</h2><p>Elles sont livrées, mais ne comptent pas dans les primes tant que la preuve ou l’encaissement requis manque.</p></div></div><div class="bonus-late-list">@foreach($ineligibleOrders as $excluded)<article><div><b>{{ $excluded['code'] }}</b><span>{{ $excluded['courier'] }}</span></div><span>{{ $excluded['reason'] }}</span></article>@endforeach</div></section>@endif

        <section class="bonus-panel"><div class="bonus-panel-head"><div><h2>Critères et enveloppes</h2><p>Les enveloppes sans bénéficiaire sont redistribuées au prorata des enveloppes restantes.</p></div></div><div class="bonus-criteria">
            <div><b>Livraisons validées et encaissées</b><strong>{{ number_format(round($week->budget * .30),0,',',' ') }} F <small>30 %</small></strong><span>Partagée selon le nombre de livraisons éligibles.</span></div><div><b>Rapidité</b><strong>{{ number_format(round($week->budget * .40),0,',',' ') }} F <small>40 %</small></strong><span>≤ 45 minutes entre départ et confirmation client.</span></div><div><b>Présence</b><strong>{{ number_format(round($week->budget * .10),0,',',' ') }} F <small>10 %</small></strong><span>Au prorata des jours présents, lundi à samedi.</span></div><div><b>Professionnalisme</b><strong>{{ number_format(round($week->budget * .20),0,',',' ') }} F <small>20 %</small></strong><span>Note entière de 0 à 10 répartie par points.</span></div>
        </div></section>

        <form method="get" action="{{ route('courier-bonuses.index') }}" class="bonus-courier-filter">
            <input type="hidden" name="week" value="{{ $week->week_start }}">
            <label for="courier-search">Filtrer les livreurs</label>
            <input id="courier-search" type="search" name="courier_search" value="{{ $courierSearch }}" placeholder="Nom ou numéro de téléphone">
            <button class="bonus-button secondary" type="submit"><span class="material-icons" aria-hidden="true">filter_list</span>Filtrer</button>
            @if($courierSearch !== '')<a href="{{ route('courier-bonuses.index', ['week' => $week->week_start]) }}" class="bonus-clear-filter">Effacer</a>@endif
            <small>{{ $couriers->count() }} livreur(s) affiché(s) · {{ $courierTotal }} actif(s) au total</small>
        </form>

        @if(in_array(auth()->user()->user_type, ['MNG'], true) && in_array($week->status, ['draft', 'rejected'], true))
            <section class="bonus-panel"><div class="bonus-panel-head"><div><h2>Présences automatiques et évaluations</h2><p>Un livreur est présent le jour où il a au moins une livraison validée. Sans livraison, il est absent. Tu n’as rien à saisir pour les présences ; renseigne seulement son évaluation de professionnalisme.</p></div></div>
                @forelse($couriers as $courier)
                    @php($rating = $ratings->get($courier->id))
                    <form method="post" action="{{ route('courier-bonuses.inputs.save', $week->id) }}" class="bonus-courier-card">@csrf
                        <label class="bonus-correction-reason">Motif de saisie ou de correction de la note<input name="correction_reason" minlength="3" maxlength="500" placeholder="Ex. Évaluation hebdomadaire du responsable" required></label>
                        <div class="bonus-courier-title"><h3>{{ $courier->name }}</h3><span>{{ $courier->phone }}</span></div>
                        <div class="bonus-days">
                            @for($i=0;$i<6;$i++)
                                @php($date = \Carbon\Carbon::parse($week->week_start)->addDays($i))
                                @php($dateKey = $date->toDateString())
                                @php($autoCodes = $autoPresence[$courier->id.'|'.$dateKey] ?? [])
                                <div class="bonus-day-card {{ count($autoCodes) ? 'is-auto-present' : 'is-auto-absent' }}">
                                    <span>{{ ucfirst($date->locale('fr')->isoFormat('dddd')) }} <small>{{ $date->format('d/m') }}</small></span>
                                    @if(count($autoCodes))
                                        <span class="bonus-auto-presence"><span class="material-icons" aria-hidden="true">task_alt</span>Présent</span>
                                        <small class="bonus-auto-order">Livraison : {{ implode(', ', $autoCodes) }}</small>
                                    @else
                                        <span class="bonus-auto-absence"><span class="material-icons" aria-hidden="true">event_busy</span>Absent</span>
                                        <small class="bonus-auto-order">Aucune livraison validée</small>
                                    @endif
                                </div>
                            @endfor
                        </div>
                        <div class="bonus-rating"><div><b>Professionnalisme · 0 à 10</b><small>Attribue de 0 à 2 points à chaque critère.</small></div>@foreach(['politeness'=>'Politesse','presentation'=>'Présentation','instructions'=>'Consignes','money_handling'=>'Gestion commandes et argent','discipline'=>'Discipline'] as $key=>$label)<label>{{ $label }}<select name="ratings[{{ $courier->id }}][{{ $key }}]" required><option value="" disabled @selected(!$rating)>—</option>@for($score=0;$score<=2;$score++)<option value="{{ $score }}" @selected((int)($rating?->{$key} ?? -1) === $score)>{{ $score }}</option>@endfor</select></label>@endforeach</div>
                        <div class="bonus-save-row"><button type="submit" class="bonus-button">Enregistrer {{ $courier->name }}</button></div>
                    </form>
                @empty<div class="bonus-empty">Aucun livreur ne correspond au filtre.</div>@endforelse
                @if($couriers->hasPages())<nav class="bonus-pagination" aria-label="Pages de livreurs">{{ $couriers->links() }}</nav>@endif
                <p class="bonus-pagination-hint">Enregistre l’évaluation de chaque livreur séparément. Les présences viennent automatiquement des livraisons validées.</p>
            </section>

            @if($lateOrders->isNotEmpty())<section class="bonus-panel"><div class="bonus-panel-head"><div><h2>Retards et départs manquants</h2><p>Un retard exceptionnel ne devient éligible à la rapidité qu’après justification et validation du responsable. Sans heure de départ, aucun point de rapidité n’est attribué.</p></div></div><div class="bonus-late-list">@foreach($lateOrders as $order)@php($exception = $exceptions->get($order->id))<article><div><b>{{ $order->code }}</b><span>{{ $order->user?->name ?? 'Livreur inconnu' }} · {{ $order->courier_departed_at?->format('d/m H:i') ?? 'Départ non horodaté' }} → {{ $order->delivered_at?->format('d/m H:i') }}</span></div>@if(!$order->courier_departed_at)<span>Départ manquant · non éligible</span>@elseif($exception?->validated_at)<span class="bonus-approved">Exception validée</span>@else<form method="post" action="{{ route('courier-bonuses.late-exceptions.store', [$week->id,$order->id]) }}">@csrf<input name="reason" minlength="8" maxlength="1000" placeholder="Motif du retard exceptionnel" required><button class="bonus-button small">Valider l’exception</button></form>@endif</article>@endforeach</div></section>@endif

        <form method="post" action="{{ route('courier-bonuses.validate', $week->id) }}" class="bonus-validate-form">@csrf<button class="bonus-button dark">Vérifier les données et soumettre à la DG</button><small>La semaine doit être terminée et l’évaluation de chaque livreur renseignée. Les présences sont calculées depuis les livraisons.</small></form>
        @endif

        @if(auth()->user()->user_type === 'ADMINUSER' && in_array($week->status, ['draft', 'rejected'], true))<div class="bonus-alert">En attente de la validation du responsable. La décision DG sera disponible après la soumission des données.</div>@endif

        <section class="bonus-panel"><div class="bonus-panel-head"><div><h2>Résultat par livreur</h2><p>Une livraison locale validée « livrée » compte sans photo ; les expéditions doivent avoir leur preuve et toutes les ventes doivent être encaissées.</p></div></div><div class="bonus-table-wrap"><table class="bonus-table"><thead><tr><th>Livreur</th><th>Livraisons</th><th>≤ 45 min</th><th>Jours présents</th><th>Note /10</th><th>Productivité</th><th>Rapidité</th><th>Présence</th><th>Professionnalisme</th><th>Total</th></tr></thead><tbody>@forelse($resultPage as $row)<tr><td><b>{{ $row['name'] }}</b></td><td>{{ $row['points']['productivity'] ?? 0 }}</td><td>{{ $row['points']['speed'] ?? 0 }}</td><td>{{ $row['points']['presence'] ?? 0 }}/6</td><td>{{ $row['points']['professionalism'] ?? 0 }}/10</td>@foreach(['productivity','speed','presence','professionalism'] as $component)<td>{{ number_format($row['amounts'][$component],0,',',' ') }} F</td>@endforeach<td><b>{{ number_format($row['total'],0,',',' ') }} F</b></td></tr>@empty<tr><td colspan="10" class="bonus-empty">Aucun résultat pour ce filtre.</td></tr>@endforelse</tbody></table></div>@if($resultPage->hasPages())<nav class="bonus-pagination" aria-label="Pages de résultats">{{ $resultPage->links() }}</nav>@endif</section>

        @if($week->status === 'manager_validated')<section class="bonus-panel dg-panel"><h2>Décision finale · Direction Générale</h2><p>Après approbation, les montants sont verrouillés pour cette semaine.</p><div class="dg-actions"><form method="post" action="{{ route('courier-bonuses.decide',$week->id) }}">@csrf<input type="hidden" name="decision" value="approved"><button class="bonus-button">Approuver les primes</button></form><form method="post" action="{{ route('courier-bonuses.decide',$week->id) }}">@csrf<input type="hidden" name="decision" value="rejected"><input name="decision_note" placeholder="Motif du rejet, obligatoire" required><button class="bonus-button danger">Rejeter et demander correction</button></form></div></section>@endif

        @if($audits->isNotEmpty())
            @php($auditLabels = ['week_created' => 'Semaine de primes créée', 'attendance_corrected' => 'Présence corrigée', 'rating_corrected' => 'Évaluation professionnelle corrigée', 'late_exception_validated' => 'Retard exceptionnel validé', 'manager_validated' => 'Semaine vérifiée par le responsable', 'calculation_revalidated' => 'Calcul corrigé et soumis à nouveau', 'approved' => 'Primes approuvées par la Direction Générale', 'rejected' => 'Primes rejetées par la Direction Générale', 'payout_requested' => 'Demande de retrait envoyée par le livreur', 'payout_handed_over' => 'Prime remise au livreur', 'payout_rejected' => 'Demande de retrait refusée'])
            <section class="bonus-panel"><div class="bonus-panel-head"><div><h2>Historique des corrections et validations</h2></div></div><ul class="bonus-audit">@foreach($audits as $audit)<li><b>{{ $auditLabels[$audit->event] ?? 'Mise à jour de la semaine' }}</b><span>{{ $audit->actor_name ?? 'Compte supprimé' }} · {{ \Carbon\Carbon::parse($audit->created_at)->format('d/m/Y H:i') }}</span>@if($audit->reason)<small>{{ $audit->reason }}</small>@endif</li>@endforeach</ul></section>
        @endif
    @endif
</main>

<style>
.bonus-page{max-width:1500px;margin:auto;padding:24px;color:#273142}.bonus-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:25px 28px;background:linear-gradient(115deg,#244f3c,#31754e);color:white}.bonus-hero>div>span{font-size:10px;font-weight:850;letter-spacing:.14em;opacity:.8}.bonus-hero h1{margin:8px 0;font-size:clamp(24px,3vw,34px);font-weight:850}.bonus-hero p{margin:0;color:#e2eee6}.bonus-status{padding:10px 13px;background:#fff;color:#284c3a;font-size:12px;font-weight:800}.status-approved{background:#daf3e3}.status-rejected{color:#9d302d}.bonus-toolbar{display:flex;justify-content:space-between;align-items:end;gap:14px;margin:16px 0}.bonus-week-select{display:flex;align-items:center;gap:10px}.bonus-week-select label,.bonus-create label{font-size:12px;font-weight:750}.bonus-week-select select,.bonus-create input,.bonus-days select,.bonus-days input,.bonus-rating select,.bonus-late-list input,.dg-actions input{min-height:42px;padding:8px 10px;border:1px solid #dce2e7;border-radius:5px;background:#fff}.bonus-button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 15px;border:0;border-radius:4px;background:#287449;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}.bonus-button:hover{background:#1c5b37;color:#fff;text-decoration:none}.bonus-button.secondary{border:1px solid #dce2e7;background:#fff;color:#354253}.bonus-button.dark{background:#263443}.bonus-button.danger{background:#a33a34}.bonus-button.small{min-height:36px;padding:7px 10px;font-size:11px}.bonus-panel,.bonus-envelope{margin-bottom:16px;padding:20px;background:white;border:1px solid #e1e6ea;box-shadow:0 4px 16px #25334808}.bonus-create{display:flex;justify-content:space-between;align-items:center;gap:20px}.bonus-create h2,.bonus-panel h2{margin:0 0 5px;font-size:20px;font-weight:850}.bonus-create p,.bonus-panel-head p,.dg-panel p{margin:0;color:#748093;font-size:13px}.bonus-create form{display:flex;align-items:end;gap:10px}.bonus-create label{display:grid;gap:5px}.bonus-envelope-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:16px}.bonus-envelope{display:grid;gap:7px;margin:0}.bonus-envelope span{color:#637083;font-size:12px;font-weight:750}.bonus-envelope strong{font-size:26px;font-weight:850}.bonus-envelope small{color:#8792a1;line-height:1.5}.bonus-envelope.primary{background:#f0f7f2;border-color:#cfe3d5}.bonus-criteria{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:16px}.bonus-criteria>div{display:grid;gap:7px;padding:14px;border:1px solid #e6eaee;background:#fafbfc}.bonus-criteria b{font-size:13px}.bonus-criteria strong{font-size:18px}.bonus-criteria strong small{color:#8590a0;font-size:11px}.bonus-criteria span{color:#7a8696;font-size:12px;line-height:1.45}.bonus-panel-head{display:flex;justify-content:space-between;gap:14px;margin-bottom:14px}.bonus-courier-card{margin:12px 0;padding:15px;border:1px solid #e3e8ec}.bonus-courier-title{display:flex;align-items:center;gap:10px;margin-bottom:12px}.bonus-courier-title h3{margin:0;font-size:16px;font-weight:850}.bonus-courier-title span,.bonus-days small{color:#8590a0;font-size:11px}.bonus-days{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px}.bonus-days label,.bonus-rating label{display:grid;gap:5px;color:#566273;font-size:11px;font-weight:750}.bonus-days label>span{display:flex;justify-content:space-between;gap:4px}.bonus-days input{min-width:0;min-height:36px;font-size:10px}.bonus-days select,.bonus-rating select{min-height:36px;padding:5px;font-size:11px}.bonus-rating{display:grid;grid-template-columns:1.4fr repeat(5,minmax(90px,1fr));gap:9px;align-items:end;margin-top:14px;padding-top:13px;border-top:1px solid #edf0f2}.bonus-rating>div{display:grid;gap:4px}.bonus-rating b{font-size:12px}.bonus-rating small{color:#8792a1;font-size:10px}.bonus-late-list article{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:11px 0;border-top:1px solid #eef0f2}.bonus-late-list article>div{display:grid;gap:4px}.bonus-late-list article span{color:#7b8796;font-size:11px}.bonus-late-list form{display:flex;gap:8px}.bonus-late-list input{min-width:250px}.bonus-approved{color:#267348!important;font-weight:800}.bonus-save-row,.bonus-validate-form{display:flex;align-items:center;gap:12px;margin:14px 0}.bonus-save-row>span,.bonus-validate-form small{color:#798596;font-size:12px}.bonus-table-wrap{overflow:auto}.bonus-table{width:100%;min-width:1100px;border-collapse:collapse}.bonus-table th{padding:10px;background:#f5f7f8;color:#697587;text-align:left;font-size:10px;white-space:nowrap}.bonus-table td{padding:11px 10px;border-top:1px solid #edf0f2;font-size:12px;white-space:nowrap}.bonus-table td:first-child{white-space:normal}.bonus-table tr>*:last-child{background:#f3f8f4}.dg-panel{border-color:#d8e8dc}.dg-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:14px}.dg-actions form{display:flex;gap:8px}.bonus-audit{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:0;padding:0;list-style:none}.bonus-audit li{display:grid;gap:4px;padding:10px;background:#f7f8fa}.bonus-audit span,.bonus-audit small{color:#7e8998;font-size:11px}.bonus-alert{margin:14px 0;padding:12px 14px}.bonus-alert.success{background:#eef8f1;color:#286542}.bonus-alert.error{background:#fff1f0;color:#962f2a}.bonus-empty{padding:25px;color:#7b8796;text-align:center}
@media(min-width:1200px){.bonus-envelope-grid{grid-template-columns:repeat(5,minmax(0,1fr))}}
@media(max-width:950px){.bonus-criteria{grid-template-columns:repeat(2,minmax(0,1fr))}.bonus-rating{grid-template-columns:repeat(3,minmax(0,1fr))}.bonus-rating>div{grid-column:1/-1}.bonus-create{align-items:stretch;flex-direction:column}.bonus-create form{flex-wrap:wrap}.bonus-create label{flex:1;min-width:160px}}
@media(max-width:650px){.bonus-page{padding:12px}.bonus-hero{align-items:flex-start;flex-direction:column;padding:19px}.bonus-toolbar,.bonus-week-select{align-items:stretch;flex-direction:column}.bonus-week-select select{width:100%}.bonus-envelope-grid{grid-template-columns:1fr}.bonus-criteria{grid-template-columns:1fr 1fr}.bonus-days{grid-template-columns:repeat(2,minmax(0,1fr))}.bonus-rating{grid-template-columns:repeat(2,minmax(0,1fr))}.bonus-late-list article,.bonus-late-list form{align-items:stretch;flex-direction:column}.bonus-late-list input{min-width:0;width:100%}.bonus-create form{display:grid;grid-template-columns:1fr}.bonus-create .bonus-button{width:100%}.bonus-save-row,.bonus-validate-form,.dg-actions,.dg-actions form{align-items:stretch;flex-direction:column}.bonus-audit{grid-template-columns:1fr}.bonus-panel{padding:15px}}
/* Tenace brand palette and narrow-screen layout */
.bonus-page{box-sizing:border-box;width:100%;min-width:0;overflow-x:clip}
.bonus-page *{box-sizing:border-box}
.bonus-courier-filter{display:flex;align-items:end;gap:10px;margin:0 0 14px;padding:14px;background:#fff;border:1px solid #e1e6ea;box-shadow:0 4px 16px #25334808}
.bonus-courier-filter label{align-self:center;margin:0;color:#354253;font-size:13px;font-weight:800}
.bonus-courier-filter input[type=search]{width:min(360px,40vw);min-width:0;min-height:42px;padding:8px 11px;border:1px solid #dce2e7;border-radius:5px}
.bonus-courier-filter small{align-self:center;margin-left:auto;color:#7b8796;font-size:11px}
.bonus-clear-filter{align-self:center;color:#851b1b;font-size:12px;font-weight:750}
.bonus-pagination{overflow-x:auto;max-width:100%;padding-top:8px}
.bonus-pagination .pagination{flex-wrap:wrap;gap:3px;margin:0}
.bonus-pagination .page-link{border-color:#e1e5ea;color:#851b1b}
.bonus-pagination .page-item.active .page-link{border-color:#851b1b;background:#851b1b;color:#fff}
.bonus-pagination-hint{margin:8px 0 0;color:#7b8796;font-size:11px}
.bonus-hero{background:linear-gradient(115deg,#701616,#941f24)}
.bonus-status{border:1px solid #ead8d8;color:#851b1b;background:#fff7f6}
.status-approved,.status-rejected{border-color:#ead8d8;color:#851b1b;background:#fff1f0}
.bonus-button{background:#851b1b}
.bonus-button:hover{background:#681515}
.bonus-envelope.primary{background:#fff4f3;border-color:#ead1d0}
.bonus-approved{color:#851b1b!important}
.dg-panel{border-color:#ead1d0;background:#fffafa}
.bonus-alert.success{background:#fff4f3;color:#851b1b}
.bonus-table tr>*:last-child{background:#fff4f3}
.bonus-button:focus-visible,.bonus-page input:focus-visible,.bonus-page select:focus-visible{outline:3px solid #e5b6b4;outline-offset:1px}
@media(max-width:1024px){.bonus-days{grid-template-columns:repeat(3,minmax(0,1fr))}.bonus-rating{grid-template-columns:repeat(3,minmax(0,1fr))}.bonus-rating>div{grid-column:1/-1}}
@media(max-width:650px){.bonus-page{padding:12px 10px 24px}.bonus-hero{padding:18px}.bonus-hero h1{font-size:25px;line-height:1.15}.bonus-hero p{font-size:13px;line-height:1.5}.bonus-toolbar{margin:12px 0}.bonus-toolbar>.bonus-button{width:100%}.bonus-courier-filter{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;padding:12px}.bonus-courier-filter label{grid-column:1/-1}.bonus-courier-filter input[type=search]{grid-column:1/-1;width:100%}.bonus-courier-filter .bonus-button{min-width:110px}.bonus-courier-filter small{grid-column:1/-1;margin:0}.bonus-courier-filter .bonus-clear-filter{justify-self:start}.bonus-envelope-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.bonus-envelope{padding:13px}.bonus-envelope.primary{grid-column:1/-1}.bonus-envelope strong{font-size:20px;line-height:1.2;overflow-wrap:anywhere}.bonus-envelope span,.bonus-envelope small{font-size:11px}.bonus-panel{padding:14px 12px}.bonus-panel-head{align-items:flex-start;flex-direction:column}.bonus-panel h2{font-size:18px}.bonus-days{grid-template-columns:repeat(2,minmax(0,1fr))}.bonus-days label{min-width:0;padding:8px;border:1px solid #eceef1;background:#fafbfc}.bonus-days label>span{font-size:11px}.bonus-days select,.bonus-days input,.bonus-rating select{width:100%;min-width:0}.bonus-rating{grid-template-columns:repeat(2,minmax(0,1fr))}.bonus-courier-title{align-items:flex-start;flex-direction:column;gap:3px}.bonus-table-wrap{margin:0 -4px;overflow-x:auto;overscroll-behavior-inline:contain;-webkit-overflow-scrolling:touch}.bonus-table{min-width:980px}.bonus-late-list article>span{text-align:left}.bonus-audit li{min-width:0;overflow-wrap:anywhere}.bonus-save-row .bonus-button,.bonus-validate-form .bonus-button,.dg-actions .bonus-button{width:100%}}
@media(max-width:390px){.bonus-envelope-grid{grid-template-columns:1fr}.bonus-envelope.primary{grid-column:auto}.bonus-days{grid-template-columns:1fr}.bonus-criteria{grid-template-columns:1fr}.bonus-rating{grid-template-columns:1fr}}
/* Keep each courier form within the available content width. */
.bonus-courier-card{display:block;width:100%;max-width:100%;min-width:0;overflow:hidden}
.bonus-correction-reason{display:grid;grid-template-columns:minmax(210px,.4fr) minmax(0,1fr);align-items:center;gap:12px;width:100%;margin:0 0 14px;color:#566273;font-size:13px;font-weight:750}
.bonus-correction-reason input{display:block;width:100%;min-width:0;min-height:42px;padding:8px 11px;border:1px solid #dce2e7;border-radius:5px;background:#fff;font-size:13px;font-weight:400}
.bonus-days{width:100%;min-width:0;grid-template-columns:repeat(3,minmax(0,1fr))}
.bonus-days label{min-width:0;overflow:hidden}
.bonus-days input,.bonus-days select{width:100%;min-width:0}
.bonus-days label>span{min-width:0;align-items:baseline;flex-wrap:wrap}
.bonus-day-card{display:grid;align-content:start;gap:7px;min-width:0;min-height:86px;padding:10px;border:1px solid #e6eaee;background:#fafbfc}
.bonus-day-card>span:first-child{display:flex;justify-content:space-between;gap:5px;color:#566273;font-size:11px;font-weight:750}
.bonus-day-card.is-auto-present{border-color:#ead1d0;background:#fff7f6}
.bonus-day-card>span.bonus-auto-presence,.bonus-day-card>span.bonus-auto-absence{display:flex;align-items:center;gap:5px;color:#851b1b;font-size:11px;font-weight:800}
.bonus-day-card>span.bonus-auto-absence{color:#7b8796}
.bonus-auto-presence .material-icons{font-size:16px}
.bonus-auto-absence .material-icons{font-size:16px}
.bonus-days .bonus-auto-order{display:block;color:#7b8796;font-size:10px;overflow-wrap:anywhere}
.bonus-toolbar-actions{display:flex;align-items:center;gap:8px}.bonus-payout-badge{display:inline-grid;place-items:center;min-width:20px;height:20px;margin-left:6px;padding:0 5px;border-radius:99px;background:#a33a34;color:#fff;font-size:11px}
@media(max-width:650px){.bonus-toolbar-actions{display:grid;grid-template-columns:1fr;width:100%}.bonus-toolbar-actions .bonus-button{width:100%}}
@media(min-width:1450px){.bonus-days{grid-template-columns:repeat(6,minmax(0,1fr))}}
@media(max-width:650px){.bonus-correction-reason{grid-template-columns:1fr;gap:6px;margin-bottom:12px}.bonus-days{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:390px){.bonus-days{grid-template-columns:1fr}}
</style>
@endsection
