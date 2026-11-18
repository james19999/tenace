@extends('layouts.admin')

@section('content')
<main class="container-fluid profitability-page">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @php
        $money = fn ($amount) => $amount === null ? 'À rapprocher' : number_format((float) $amount, 0, ',', ' ').' F CFA';
        $labels = ['sales_discount'=>'Remise commerciale','direct_supply_cost'=>'Frais directs d’approvisionnement','other_income'=>'Autre produit','other_expense'=>'Autre charge','income_tax'=>'Impôt sur le bénéfice'];
        $comparisonRows = [
            ['Chiffre d’affaires net', 'net_sales'], ['Encaissements', 'cash_receipts'], ['Coût des marchandises vendues', 'cost_of_goods_sold'],
            ['Marge brute', 'gross_margin'], ['Charges d’exploitation', 'operating_expenses'], ['Résultat net', 'net_result'],
            ['Trésorerie disponible', 'cash_available'],
        ];
    @endphp

    <header class="profit-hero">
        <div><span class="profit-kicker">TENACOS · PILOTAGE DE DIRECTION</span><h1>Rentabilité &amp; performance</h1><p>Les ventes suivent les commandes livrées (boutique et grossiste). Les encaissements suivent la date de paiement ; les créances montrent le reste dû sur ces commandes.</p></div>
        <div class="profit-exports">
            @foreach(['xlsx'=>'Excel','pdf'=>'PDF','csv'=>'CSV'] as $format=>$title)
                <a href="{{ route('finance.profitability.export', array_merge(request()->query(), ['format'=>$format])) }}"><span class="material-icons" aria-hidden="true">{{ ['xlsx'=>'grid_on','pdf'=>'picture_as_pdf','csv'=>'table_view'][$format] }}</span>{{ $title }}</a>
            @endforeach
        </div>
    </header>

    <form class="profit-filters" method="GET" action="{{ route('finance.profitability.index') }}">
        <label>Vue<select name="mode" id="profit-mode"><option value="month" @selected($filters['mode']==='month')>Par mois</option><option value="quarter" @selected($filters['mode']==='quarter')>Par trimestre</option><option value="year" @selected($filters['mode']==='year')>Par année</option></select></label>
        <label id="profit-month-label"><span id="profit-month-title">Mois</span><input type="month" name="period" id="profit-month" value="{{ $filters['mode']!=='year' ? ($filters['mode']==='month'?$filters['period']:$filters['from']->format('Y-m')) : now()->format('Y-m') }}"></label>
        <label id="profit-year-label">Année<input type="number" name="period" id="profit-year" min="2020" max="{{ now()->year+1 }}" value="{{ $filters['mode']==='year' ? $filters['period'] : now()->year }}"></label>
        <label class="profit-compare-label">Comparer à<input type="month" name="compare" value="{{ $filters['compare'] }}"></label>
        <button type="submit">Afficher la période</button>
    </form>

    <div class="profit-period-line">
        <span><span class="material-icons" aria-hidden="true">calendar_month</span>{{ $filters['from']->translatedFormat('d F Y') }} – {{ $filters['to']->translatedFormat('d F Y') }}</span>
        @if($filters['mode'] === 'month')
            @if($monthlyPeriod?->status === 'closed')
                <b class="profit-state is-closed"><span class="material-icons">lock</span>Période clôturée · {{ $monthlyPeriod->closed_at?->format('d/m/Y H:i') }}</b>
            @else
                <b class="profit-state is-open"><span class="material-icons">edit_note</span>Données en cours</b>
            @endif
        @else
            <b class="profit-state is-open"><span class="material-icons">query_stats</span>Vue consolidée · périodes mensuelles</b>
        @endif
    </div>

    @if($warnings)<section class="profit-warnings"><div class="profit-warnings-title"><span class="material-icons">warning_amber</span><strong>À vérifier pour fiabiliser le résultat</strong></div>@foreach($warnings as $warning)<p>{{ $warning }}</p>@endforeach</section>@endif

    <section class="profit-kpis" aria-label="Indicateurs de rentabilité">
        <article class="is-primary"><span>Chiffre d’affaires net</span><strong>{{ $money($metrics['net_sales']) }}</strong><small>{{ number_format($metrics['sales_count']) }} commande(s) livrée(s) · hors taxe</small></article>
        <article><span>Encaissements clients</span><strong>{{ $money($metrics['cash_receipts']) }}</strong><small>Paiements confirmés, toutes méthodes, à leur date d’encaissement</small></article>
        <article><span>Créances clients</span><strong>{{ $money($metrics['receivables']) }}</strong><small>Reste dû sur les commandes livrées pendant la période</small></article>
        <article><span>Coût des marchandises vendues</span><strong>{{ $money($metrics['cost_of_goods_sold']) }}</strong><small>{{ $metrics['cost_source']==='sale_movements' ? 'Calculé sur les coûts enregistrés à la sortie des produits vendus' : 'Estimé par stock initial + achats − stock final' }}</small></article>
        <article class="is-positive"><span>Marge brute</span><strong>{{ $money($metrics['gross_margin']) }}</strong><small>Taux de marge brute : {{ $metrics['gross_margin_rate'] === null ? 'À rapprocher' : number_format($metrics['gross_margin_rate'],2,',',' ').' %' }}</small></article>
        <article><span>Charges d’exploitation</span><strong>{{ $money($metrics['operating_expenses']) }}</strong><small>Personnel, commissions et dépenses payées</small></article>
        <article><span>Résultat avant impôts</span><strong>{{ $money($metrics['pre_tax_result']) }}</strong><small>Après autres charges et autres produits</small></article>
        <article class="is-result {{ $metrics['net_result'] !== null && $metrics['net_result']<0?'is-negative':'' }}"><span>Résultat net</span><strong>{{ $money($metrics['net_result']) }}</strong><small>Rentabilité nette : {{ $metrics['net_margin_rate'] === null ? 'À rapprocher' : number_format($metrics['net_margin_rate'],2,',',' ').' %' }}</small></article>
        <article><span>Valeur du stock final</span><strong>{{ $money($metrics['closing_stock']) }}</strong><small>Valorisation au coût d’achat enregistré</small></article>
        <article><span>Trésorerie disponible</span><strong>{{ $money($metrics['cash_available']) }}</strong><small>Solde net de caisse, boutique, transit et banque</small></article>
    </section>

    <section class="profit-details-grid">
        <article class="profit-panel">
            <header><div><span class="profit-kicker">DÉCOMPOSITION</span><h2>Calcul du résultat</h2></div></header>
            <dl class="profit-breakdown">
                <div><dt>Ventes avant annulations</dt><dd>{{ $money($metrics['gross_sales']) }}</dd></div>
                <div><dt>Annulations de commandes livrées</dt><dd>− {{ $money($metrics['cancelled_sales']) }}</dd></div>
                <div><dt>Remises déjà appliquées aux commandes</dt><dd>− {{ $money($metrics['recorded_discounts']) }}</dd></div>
                <div><dt>Remises complémentaires saisies</dt><dd>− {{ $money($metrics['manual_discounts']) }}</dd></div>
                <div class="is-total"><dt>Chiffre d’affaires net</dt><dd>{{ $money($metrics['net_sales']) }}</dd></div>
                @if($metrics['cost_source']==='sale_movements')
                    <div><dt>Coût des sorties de stock vendues</dt><dd>{{ $money($metrics['cost_of_goods_sold']) }}</dd></div>
                @else
                    <div><dt>Stock initial</dt><dd>{{ $money($metrics['opening_stock']) }}</dd></div>
                    <div><dt>Achats reçus</dt><dd>+ {{ $money($metrics['purchases']) }}</dd></div>
                    <div><dt>Frais d’approvisionnement saisis</dt><dd>+ {{ $money($metrics['direct_supply_costs']) }}</dd></div>
                    <div><dt>Stock final</dt><dd>− {{ $money($metrics['closing_stock']) }}</dd></div>
                @endif
                <div class="is-total"><dt>Coût des marchandises vendues</dt><dd>{{ $money($metrics['cost_of_goods_sold']) }}</dd></div>
                <div><dt>Charges d’exploitation</dt><dd>− {{ $money($metrics['operating_expenses']) }}</dd></div>
                <div><dt>Autres charges / produits</dt><dd>{{ $money($metrics['other_income']-$metrics['other_expenses']) }}</dd></div>
                <div><dt>Impôt sur le bénéfice saisi</dt><dd>− {{ $money($metrics['income_tax']) }}</dd></div>
                <div class="is-result"><dt>Résultat net</dt><dd>{{ $money($metrics['net_result']) }}</dd></div>
            </dl>
        </article>
        <article class="profit-panel profit-comparison">
            <header><div><span class="profit-kicker">ÉVOLUTION</span><h2>Comparaison de périodes</h2><p>{{ $filters['compare_from']->translatedFormat('d F Y') }} – {{ $filters['compare_to']->translatedFormat('d F Y') }}</p></div></header>
            <dl class="profit-breakdown">
                @foreach($comparisonRows as [$label,$key])
                    @php($delta=$metrics[$key]===null || $comparison[$key]===null ? null : (int)$metrics[$key]-(int)$comparison[$key])
                    <div><dt>{{ $label }}<small>Période précédente : {{ $money($comparison[$key]) }}</small></dt><dd class="{{ $delta !== null && $delta<0?'is-down':'is-up' }}">{{ $delta === null ? 'À rapprocher' : (($delta>0?'+':'').$money($delta)) }}</dd></div>
                @endforeach
            </dl>
        </article>
    </section>

    <section class="profit-panel">
        <header><div><span class="profit-kicker">CHARGES DÉJÀ ENREGISTRÉES</span><h2>Détail des charges d’exploitation</h2><p>Dépenses payées, salaires et commissions. Les achats de marchandises sont comptés dans le coût des ventes.</p></div></header>
        @if(empty($metrics['operating_expense_lines']))<p class="profit-empty">Aucune charge d’exploitation payée sur cette période.</p>@else
            <div class="profit-table-wrap"><table class="profit-expense-table"><thead><tr><th>Catégorie</th><th>Montant</th><th>Nature</th></tr></thead><tbody>
                @foreach($metrics['operating_expense_lines'] as $line)<tr><td>{{ $line['label'] }}</td><td>{{ $money($line['amount']) }}</td><td>Non classée</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>

    @if($canManage && $monthlyPeriod?->status!=='closed')
        <section class="profit-panel profit-adjustment-panel">
            <header><div><span class="profit-kicker">SAISIE TRAÇABLE</span><h2>Ajouter un ajustement</h2><p>À utiliser seulement pour une donnée qui n’existe pas encore dans les ventes, stocks ou dépenses.</p></div></header>
            <form method="POST" action="{{ route('finance.profitability.adjustments.store', request()->query()) }}" class="profit-adjustment-form">@csrf
                <label>Mois concerné<input type="month" name="period" value="{{ $filters['from']->format('Y-m') }}" min="{{ $filters['from']->format('Y-m') }}" max="{{ $filters['to']->format('Y-m') }}" required></label>
                <label>Nature<select name="kind" required>@foreach($labels as $kind=>$label)<option value="{{ $kind }}">{{ $label }}</option>@endforeach</select></label>
                <label>Charge<select name="nature"><option value="variable">Variable</option><option value="fixed">Fixe</option></select></label>
                <label>Montant (FCFA)<input type="number" min="1" step="1" name="amount" required></label>
                <label class="profit-field-wide">Libellé<input name="description" maxlength="500" required placeholder="Ex. Transport d’achat non enregistré sur la réception"></label>
                <label class="profit-field-wide">Motif / justificatif<textarea name="reason" rows="2" maxlength="1000" required placeholder="Explique pourquoi l’ajustement est nécessaire"></textarea></label>
                <button type="submit"><span class="material-icons">add</span>Enregistrer et tracer</button>
            </form>
        </section>
    @endif

    <section class="profit-panel profit-adjustment-panel">
        <header><div><span class="profit-kicker">AJUSTEMENTS</span><h2>Saisies complémentaires</h2><p>Les corrections sont conservées dans le journal d’audit avec leur motif.</p></div></header>
        @if($adjustments->isEmpty())<p class="profit-empty">Aucun ajustement manuel sur cette période.</p>@else
            <div class="profit-table-wrap"><table><thead><tr><th>Mois</th><th>Nature</th><th>Charge</th><th>Libellé</th><th>Montant</th><th>État</th><th>Action</th></tr></thead><tbody>
                @foreach($adjustments as $adjustment)@php($adjustmentMonthIsClosed=$closedPeriodStarts->contains($adjustment->period_start->toDateString()))<tr><td>{{ $adjustment->period_start->format('m/Y') }}</td><td>{{ $labels[$adjustment->kind] ?? $adjustment->kind }}</td><td>{{ $adjustment->nature==='fixed'?'Fixe':'Variable' }}</td><td>{{ $adjustment->description }}<small>Par {{ $adjustment->creator?->name ?? '—' }} · {{ $adjustment->created_at->format('d/m/Y H:i') }}@if($adjustment->void_reason)<br>Motif d’annulation : {{ $adjustment->void_reason }}@endif</small></td><td>{{ $money($adjustment->amount) }}</td><td><span class="profit-adjustment-state {{ $adjustment->status==='active'?'is-active':'is-voided' }}">{{ $adjustment->status==='active'?'Actif':'Annulé' }}</span></td><td>@if($canManage && $adjustment->status==='active' && !$adjustmentMonthIsClosed)<form method="POST" action="{{ route('finance.profitability.adjustments.void', array_merge(['adjustment'=>$adjustment->id],request()->query())) }}" onsubmit="return confirm('Annuler cet ajustement ? Son motif sera conservé dans l’audit.')">@csrf<input type="text" name="reason" placeholder="Motif obligatoire" required maxlength="1000" aria-label="Motif de l’annulation"><button class="profit-void-button" type="submit">Annuler</button></form>@else—@endif</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>

    @if($canManage && $filters['mode']==='month' && $monthlyPeriod?->status!=='closed')
        <section class="profit-close-panel"><div><span class="material-icons">lock_clock</span><div><strong>Clôturer ce mois</strong><p>La Direction enregistre un instantané définitif. Vérifie les avertissements et ajuste les données avant de confirmer.</p></div></div><form method="POST" action="{{ route('finance.profitability.close', request()->query()) }}">@csrf<input type="hidden" name="period" value="{{ $filters['period'] }}"><label class="sr-only" for="profit-close-reason">Motif</label><input id="profit-close-reason" name="reason" placeholder="Note de clôture" maxlength="1000" required><label class="profit-confirm"><input type="checkbox" name="confirmation" value="1" required> J’ai vérifié les données et clôture cette période</label><button type="submit">Clôturer la période</button></form></section>
    @endif

    <section class="profit-panel profit-history"><header><div><span class="profit-kicker">HISTORIQUE CONSERVÉ</span><h2>Périodes clôturées</h2><p>Résultats figés au moment de la clôture.</p></div></header>
        @if($closedPeriods->isEmpty())<p class="profit-empty">Aucune période n’a encore été clôturée.</p>@else<div class="profit-table-wrap"><table><thead><tr><th>Mois</th><th>Chiffre d’affaires net</th><th>Charges</th><th>Résultat net</th><th>Clôturée par</th><th>Date</th></tr></thead><tbody>@foreach($closedPeriods as $closed)<tr><td>{{ $closed->period_start->translatedFormat('F Y') }}</td><td>{{ $money($closed->snapshot['net_sales']??0) }}</td><td>{{ $money($closed->snapshot['operating_expenses']??0) }}</td><td><strong>{{ !array_key_exists('cost_reliable', $closed->snapshot ?? []) ? 'À rapprocher' : $money($closed->snapshot['net_result']??null) }}</strong></td><td>{{ $closed->closer?->name??'—' }}</td><td>{{ $closed->closed_at?->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody></table></div>@endif
    </section>
</main>

<style>
.profitability-page{max-width:1500px;margin:auto;padding:24px 22px 48px;color:#28313f}.profit-hero{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;padding:26px 30px;margin-bottom:16px;background:linear-gradient(115deg,#fff 55%,#fff8f4);border:1px solid #ece6e3;border-top:4px solid #821e1e}.profit-kicker{display:block;color:#8b2424;font-size:10px;font-weight:850;letter-spacing:.13em;text-transform:uppercase}.profit-hero h1{margin:9px 0;color:#222a37;font-size:clamp(27px,3vw,39px);font-weight:900;letter-spacing:-.04em}.profit-hero p{max-width:680px;margin:0;color:#788494;font-size:14px;line-height:1.55}.profit-exports{display:flex;gap:8px;flex-shrink:0}.profit-exports a{display:flex;align-items:center;gap:7px;padding:10px 12px;background:#fff;border:1px solid #e6e9ed;color:#4a5564;font-size:12px;font-weight:800;text-decoration:none}.profit-exports a:hover{border-color:#8b2424;color:#8b2424}.profit-exports .material-icons{font-size:18px}.profit-filters{display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;padding:15px 17px;margin-bottom:15px;background:#fff;border:1px solid #e5e9ee}.profit-filters label,.profit-adjustment-form label{display:flex;min-width:0;flex-direction:column;gap:6px;color:#667282;font-size:11px;font-weight:800}.profit-filters input,.profit-filters select,.profit-adjustment-form input,.profit-adjustment-form select,.profit-adjustment-form textarea,.profit-close-panel input:not([type=checkbox]){width:100%;min-height:42px;padding:9px 11px;background:#fff;border:1px solid #dce1e7;border-radius:0;color:#303948;font:inherit;font-size:13px}.profit-filters button,.profit-adjustment-form>button,.profit-close-panel button{display:flex;min-height:42px;align-items:center;justify-content:center;gap:7px;padding:10px 16px;background:#821e1e;border:0;color:#fff;font-size:12px;font-weight:850;cursor:pointer}.profit-filters button:hover,.profit-adjustment-form>button:hover,.profit-close-panel button:hover{background:#641616}.profit-compare-label{grid-column:4}.profit-period-line{display:flex;justify-content:space-between;align-items:center;gap:14px;margin:0 0 13px;color:#647182;font-size:12px}.profit-period-line>span,.profit-state{display:inline-flex;align-items:center;gap:7px}.profit-period-line .material-icons,.profit-state .material-icons{font-size:17px}.profit-state{padding:7px 10px;font-size:11px;font-weight:800}.profit-state.is-open{background:#eef7ef;color:#347547}.profit-state.is-closed{background:#f1f3f6;color:#556273}.profit-warnings{padding:14px 17px;margin-bottom:15px;background:#fff8e9;border:1px solid #f1dfb4;color:#705a29;font-size:12px}.profit-warnings-title{display:flex;align-items:center;gap:8px;margin-bottom:7px;color:#795b18}.profit-warnings-title .material-icons{font-size:19px}.profit-warnings p{margin:4px 0 0 27px;line-height:1.5}.profit-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}.profit-kpis article{display:flex;min-width:0;min-height:132px;flex-direction:column;align-items:flex-start;gap:7px;padding:17px;background:#fff;border:1px solid #e3e7eb;box-shadow:0 3px 13px rgba(31,43,57,.025)}.profit-kpis article>span{color:#6e7989;font-size:11px;font-weight:800;line-height:1.4}.profit-kpis article strong{overflow-wrap:anywhere;color:#28313f;font-size:clamp(19px,1.7vw,26px);font-weight:900;letter-spacing:-.035em;line-height:1.2}.profit-kpis article small{margin-top:auto;color:#8993a0;font-size:10px;line-height:1.45}.profit-kpis .is-primary{background:#7f1d1d;border-color:#7f1d1d}.profit-kpis .is-primary>span,.profit-kpis .is-primary small{color:#f4dada}.profit-kpis .is-primary strong{color:#fff}.profit-kpis .is-positive{border-top:3px solid #2b8752}.profit-kpis .is-result{border-top:3px solid #8b2424}.profit-kpis .is-result.is-negative strong{color:#ae3a31}.profit-details-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:14px;margin-bottom:15px}.profit-panel{min-width:0;margin-bottom:15px;background:#fff;border:1px solid #e3e7eb}.profit-details-grid .profit-panel{margin:0}.profit-panel>header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:17px 19px;border-bottom:1px solid #edf0f3}.profit-panel h2{margin:4px 0 0;color:#2b3442;font-size:18px;font-weight:850}.profit-panel header p{margin:5px 0 0;color:#8791a0;font-size:11px}.profit-breakdown{margin:0;padding:3px 19px 12px}.profit-breakdown>div{display:flex;justify-content:space-between;gap:18px;padding:11px 0;border-bottom:1px solid #eff1f4}.profit-breakdown>div:last-child{border-bottom:0}.profit-breakdown dt{color:#626e7e;font-size:12px;line-height:1.45}.profit-breakdown dt small{display:block;margin-top:3px;color:#929ba7;font-size:10px}.profit-breakdown dd{flex-shrink:0;margin:0;color:#303948;font-size:12px;font-weight:800;text-align:right}.profit-breakdown .is-total{margin:0 -19px;padding:12px 19px;background:#f8f5f4}.profit-breakdown .is-result{margin:0 -19px;padding:14px 19px;background:#f6ebeb}.profit-breakdown .is-result dt,.profit-breakdown .is-result dd{color:#801f1f;font-weight:900}.profit-breakdown .is-up{color:#26804a}.profit-breakdown .is-down{color:#a03b35}.profit-adjustment-panel{padding-bottom:2px}.profit-adjustment-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:16px 18px}.profit-adjustment-form textarea{resize:vertical}.profit-adjustment-form .profit-field-wide{grid-column:span 2}.profit-adjustment-form>button{grid-column:4;align-self:end}.profit-table-wrap{max-width:100%;overflow-x:auto}.profit-table-wrap table{width:100%;min-width:780px;border-collapse:collapse}.profit-table-wrap th{padding:11px 13px;background:#f6f7f9;color:#697586;font-size:10px;font-weight:850;letter-spacing:.06em;text-align:left;text-transform:uppercase;white-space:nowrap}.profit-table-wrap td{padding:12px 13px;border-top:1px solid #edf0f3;color:#3d4654;font-size:12px;vertical-align:middle}.profit-table-wrap td small{display:block;max-width:360px;margin-top:4px;color:#8792a0;font-size:10px;line-height:1.45}.profit-adjustment-state{display:inline-flex;padding:5px 8px;font-size:10px;font-weight:800}.profit-adjustment-state.is-active{background:#eaf5ed;color:#2c7542}.profit-adjustment-state.is-voided{background:#f1f2f4;color:#747d88}.profit-void-button{padding:6px 9px;background:#fff;border:1px solid #d7dce1;color:#8c302d;font-size:10px;font-weight:800;cursor:pointer}.profit-empty{padding:17px 19px;color:#8993a0;font-size:12px}.profit-close-panel{display:grid;grid-template-columns:minmax(0,1fr) minmax(380px,1fr);gap:22px;align-items:center;padding:19px 20px;margin-bottom:15px;background:#fff;border:1px solid #e3e7eb;border-left:4px solid #821e1e}.profit-close-panel>div{display:flex;align-items:flex-start;gap:12px}.profit-close-panel>div>.material-icons{color:#842323}.profit-close-panel strong{color:#2e3745;font-size:15px}.profit-close-panel p{margin:4px 0 0;color:#838d9a;font-size:11px;line-height:1.5}.profit-close-panel form{display:grid;grid-template-columns:1fr auto;gap:8px}.profit-close-panel form>input{grid-column:1/-1}.profit-close-panel .profit-confirm{display:flex;align-items:center;gap:6px;color:#677382;font-size:10px}.profit-close-panel button{min-height:38px;padding:8px 11px;font-size:11px}.profit-history{margin-top:15px}
@media(max-width:1100px){.profit-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.profit-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.profit-compare-label{grid-column:auto}.profit-filters button{grid-column:3}.profit-details-grid{grid-template-columns:1fr}}
@media(max-width:650px){.profitability-page{padding:12px 10px 30px}.profit-hero{align-items:stretch;flex-direction:column;gap:15px;padding:19px 16px}.profit-hero h1{font-size:27px}.profit-hero p{font-size:12px}.profit-exports{width:100%;gap:6px}.profit-exports a{flex:1;justify-content:center;padding:9px 5px;font-size:11px}.profit-filters{grid-template-columns:1fr 1fr;gap:9px;padding:12px}.profit-filters label{font-size:10px}.profit-filters button{grid-column:1/-1}.profit-compare-label{grid-column:1/-1}.profit-period-line{align-items:flex-start;flex-direction:column}.profit-kpis{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.profit-kpis article{min-height:120px;padding:12px}.profit-kpis article strong{font-size:18px}.profit-kpis article>span{font-size:10px}.profit-kpis article small{font-size:9px}.profit-panel>header{padding:14px}.profit-panel h2{font-size:16px}.profit-breakdown{padding-right:13px;padding-left:13px}.profit-breakdown>div{gap:10px;padding:10px 0}.profit-breakdown dt,.profit-breakdown dd{font-size:11px}.profit-breakdown .is-total,.profit-breakdown .is-result{margin-right:-13px;margin-left:-13px;padding-right:13px;padding-left:13px}.profit-adjustment-form{grid-template-columns:1fr 1fr;gap:9px;padding:13px}.profit-adjustment-form .profit-field-wide{grid-column:1/-1}.profit-adjustment-form>button{grid-column:1/-1}.profit-close-panel{grid-template-columns:1fr;gap:14px;padding:15px}.profit-close-panel form{grid-template-columns:1fr}.profit-close-panel form>input,.profit-close-panel .profit-confirm,.profit-close-panel button{grid-column:1}.profit-table-wrap table{min-width:760px}}
</style>
<script>
(function(){const mode=document.getElementById('profit-mode'),monthLabel=document.getElementById('profit-month-label'),yearLabel=document.getElementById('profit-year-label'),month=document.getElementById('profit-month'),year=document.getElementById('profit-year'),title=document.getElementById('profit-month-title');if(!mode)return;const sync=()=>{const annual=mode.value==='year';monthLabel.hidden=annual;yearLabel.hidden=!annual;month.disabled=annual;year.disabled=!annual;document.querySelector('.profit-compare-label').hidden=annual;title.textContent=mode.value==='quarter'?'Mois dans le trimestre':'Mois';};mode.addEventListener('change',sync);sync();})();
</script>
@endsection
