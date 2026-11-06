@extends('layouts.admin')

@section('content')
<main class="container-fluid employee-payroll">
    <a class="ep-back" href="{{ route('employees.show',$employee->employeeProfile->tracking_id) }}">← Retour au dossier employé</a>
    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <header class="ep-heading"><div><span class="ep-eyebrow">SUIVI INTERNE · ESPÈCES</span><h1>Salaires de {{ $employee->name }}</h1><p>Enregistre le salaire convenu, les avances et les versements réellement remis.</p></div><div class="ep-current-salary">@if($employee->employeeProfile?->monthly_salary)<span>Salaire mensuel actuel</span><strong>{{ number_format($employee->employeeProfile->monthly_salary,0,',',' ') }} F</strong><small>Depuis {{ $employee->employeeProfile->salary_effective_from?->format('m/Y') ?? 'date à confirmer' }}</small>@else<span>Salaire non renseigné</span><a href="{{ route('employees.edit',$employee->employeeProfile->tracking_id) }}">Renseigner le salaire</a>@endif</div></header>

    <section class="ep-guidance"><strong>La retenue du prêt est appliquée automatiquement au versement du salaire.</strong><span>Indique le salaire brut réglé. Le montant prévu pour les prêts est déduit automatiquement ; la caisse est débitée uniquement du montant net remis au salarié. Aucun calcul de retenues légales ni bulletin de paie n’est effectué.</span></section>

    @if($activeLoans->isNotEmpty())
        <section class="ep-loan-overview" aria-label="Dettes en cours du salarié">
            <header><div><span class="ep-eyebrow">DETTES EN COURS</span><h2>Prêts à rembourser</h2><p>Vérifie les soldes et les retenues prévues avant d’enregistrer un salaire.</p></div><strong>{{ number_format((int)$activeLoans->sum(fn($loan) => max(0,$loan->amount-$loan->repaid_amount)),0,',',' ') }} F CFA<small>Reste total dû</small></strong></header>
            <div class="ep-loan-overview-list">
                @foreach($activeLoans as $loan)
                    <article><div><strong>{{ $loan->reference }}</strong><span>{{ $loan->reason ?: 'Prêt au personnel' }}</span><small>Accordé le {{ $loan->granted_on?->format('d/m/Y') ?? '—' }}</small></div><div class="ep-loan-overview-amount"><span>Reste dû</span><strong>{{ number_format(max(0,$loan->amount-$loan->repaid_amount),0,',',' ') }} F</strong><small>Retenue prévue : @if($loan->repayment_method === 'salary_percent'){{ number_format((float)$loan->repayment_value,2,',',' ') }} % du salaire@elseif($loan->repayment_method === 'fixed_amount'){{ number_format((int)$loan->repayment_value,0,',',' ') }} F / mois @else à définir @endif</small></div></article>
                @endforeach
            </div>
        </section>
    @endif

    @if($employee->employeeProfile?->monthly_salary && $employee->employeeProfile?->salary_effective_from)
        <section class="ep-panel ep-create"><div><h2>Ajouter un mois au suivi</h2><p>Le montant est copié depuis le salaire actuel. Une modification future du salaire ne changera pas les mois déjà créés.</p></div><form method="POST" action="{{ route('employees.payroll.periods.store',$employee->employeeProfile->tracking_id) }}">@csrf<label>Mois<input type="month" name="period" value="{{ old('period',now()->format('Y-m')) }}" required></label><label>Note (facultative)<input name="note" value="{{ old('note') }}" maxlength="500" placeholder="Ex. Salaire de base"></label><button class="btn btn-primary">Ajouter ce mois</button></form></section>
    @endif

    <section class="ep-period-list">
        @forelse($periods as $period)
            @php($paidTotal=(int)$period->payments->sum('amount'))
            @php($linkedLegacyPaymentIds=$period->staffAdvances->pluck('employee_payroll_payment_id')->filter()->map(fn($id)=>(int)$id)->all())
            @php($legacyUnlinkedAdvances=$period->payments->where('kind','advance')->whereNotIn('id',$linkedLegacyPaymentIds))
            @php($legacyAdvanceTotal=(int)$legacyUnlinkedAdvances->sum('amount'))
            @php($linkedAdvanceTotal=(int)$period->staffAdvances->where('advance_type','salary_advance')->sum('amount'))
            @php($advanceTotal=$legacyAdvanceTotal+$linkedAdvanceTotal)
            @php($salaryPaid=(int)$period->payments->where('kind','salary')->sum('amount'))
            @php($loanDeducted=(int)$period->payments->where('kind','salary')->sum('loan_deduction_amount'))
            @php($paidTotal=$salaryPaid+$advanceTotal)
            @php($remaining=max(0,(int)$period->salary_amount-$paidTotal))
            @php($availableToPay=max(0,$remaining-(int)$period->pending_advance_amount))
            @php($defaultLoanDeduction=min($availableToPay,(int)$period->loan_deduction_due))
            @php($defaultNetToPay=max(0,$availableToPay-$defaultLoanDeduction))
            <article class="ep-period-card">
                <header class="ep-period-heading"><div><span class="ep-eyebrow">{{ $period->reference }}</span><h2>{{ $period->period_start->translatedFormat('F Y') }}</h2></div><span class="ep-state {{ $remaining===0?'is-paid':($paidTotal>0?'is-partial':'is-due') }}">{{ $remaining===0?'Soldé':($paidTotal>0?'Partiellement versé':'À verser') }}</span></header>
                @if($period->available_at)
                    <p class="ep-availability is-available"><span class="material-icons" aria-hidden="true">visibility</span>Salaire visible par le salarié depuis le {{ $period->available_at->format('d/m/Y à H:i') }}@if($period->availabilityConfirmer) · {{ $period->availabilityConfirmer->name }}@endif</p>
                @else
                    <form class="ep-availability-form" method="POST" action="{{ route('employees.payroll.periods.available',[$employee->employeeProfile->tracking_id,$period]) }}">@csrf<span>Le salarié ne voit pas encore le salaire de ce mois.</span><button class="btn btn-outline-primary">Rendre le salaire disponible</button></form>
                @endif
                <div class="ep-summary" aria-label="Résumé du salaire">
                    <div><span>Salaire brut du mois</span><strong>{{ number_format($period->salary_amount,0,',',' ') }} F</strong><small>Montant convenu</small></div>
                    <div><span>Avances sur salaire</span><strong>{{ number_format($advanceTotal,0,',',' ') }} F</strong><small>Déjà remises au salarié</small></div>
                    <div><span>Prêts déjà retenus</span><strong>{{ number_format($loanDeducted,0,',',' ') }} F</strong><small>Déduits des salaires versés</small></div>
                    <div><span>Salaire brut comptabilisé</span><strong>{{ number_format($salaryPaid,0,',',' ') }} F</strong><small>Avant les retenues de prêt</small></div>
                    <div class="is-balance"><span>Reste brut à solder</span><strong>{{ number_format($remaining,0,',',' ') }} F</strong><small>Après les avances et salaires versés</small></div>
                </div>
                @if($period->loan_deduction_due > 0)<p class="ep-note">Retenue prévue ce mois : jusqu’à {{ number_format($period->loan_deduction_due,0,',',' ') }} F CFA, dans la limite du salaire brut réglé et du solde des prêts.</p>@endif
                @if($period->note)<p class="ep-note">{{ $period->note }}</p>@endif
                @if($period->pending_advance_amount>0)<p class="ep-note">Avances demandées en attente de validation ou de paiement : {{ number_format($period->pending_advance_amount,0,',',' ') }} F. Ce montant est réservé et ne sera compté comme payé qu’après remise effective.</p>@endif

                @if($period->payments->isNotEmpty() || $period->staffAdvances->isNotEmpty())
                    <div class="table-responsive ep-payments"><table class="table"><thead><tr><th>Date</th><th>Opération</th><th>Référence</th><th>Enregistré par</th><th class="text-right">Montant</th><th></th></tr></thead><tbody>
                        @foreach($period->payments->where('kind','salary') as $payment)<tr><td>{{ $payment->paid_at?->format('d/m/Y') }}</td><td>Versement de salaire @if($payment->loan_deduction_amount > 0)<small>Retenue prêt : {{ number_format($payment->loan_deduction_amount,0,',',' ') }} F</small>@endif</td><td>{{ $payment->payment_reference ?: $payment->reference }}</td><td>{{ $payment->payer?->name ?? '—' }}</td><td class="text-right font-weight-bold">Net remis : {{ number_format($payment->net_amount ?? $payment->amount,0,',',' ') }} F<small>Salaire brut : {{ number_format($payment->amount,0,',',' ') }} F</small></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('employees.payroll.payments.voucher',$payment) }}">Voir le bon</a></td></tr>@endforeach
                        @foreach($legacyUnlinkedAdvances as $payment)<tr><td>{{ $payment->paid_at?->format('d/m/Y') }}</td><td>Avance sur salaire</td><td>{{ $payment->payment_reference ?: $payment->reference }}</td><td>{{ $payment->payer?->name ?? '—' }}</td><td class="text-right font-weight-bold">{{ number_format($payment->amount,0,',',' ') }} F</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('employees.payroll.payments.voucher',$payment) }}">Voir le bon</a></td></tr>@endforeach
                        @foreach($period->staffAdvances as $advance)<tr><td>{{ $advance->granted_on->format('d/m/Y') }}</td><td>Avance sur salaire · validée</td><td>{{ $advance->reference }}</td><td>{{ $advance->creator?->name ?? $advance->employeePayrollPayment?->payer?->name ?? '—' }}</td><td class="text-right font-weight-bold">{{ number_format($advance->amount,0,',',' ') }} F</td><td>@if($advance->financeExpensePayment?->expense)<a class="btn btn-sm btn-outline-primary" href="{{ route('finance.expenses.show',$advance->financeExpensePayment->expense) }}">Voir la demande</a>@elseif($advance->employeePayrollPayment)<a class="btn btn-sm btn-outline-primary" href="{{ route('employees.payroll.payments.voucher',$advance->employeePayrollPayment) }}">Voir le bon</a>@else—@endif</td></tr>@endforeach
                    </tbody></table></div>
                @endif

                @if($availableToPay>0)
                    @if($period->available_at)
                    <details class="ep-payment-form"><summary>Enregistrer un versement de salaire</summary><form method="POST" action="{{ route('employees.payroll.payments.store',[$employee->employeeProfile->tracking_id,$period]) }}" data-payroll-month="{{ $period->period_start->format('Y-m') }}" data-month-salary="{{ (int)$period->salary_amount }}" data-salary-paid="{{ $salaryPaid }}" data-advances-paid="{{ $advanceTotal }}" data-advances-pending="{{ (int)$period->pending_advance_amount }}" data-available-gross="{{ $availableToPay }}">@csrf<input type="hidden" name="submission_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="kind" value="salary">
                        <section class="ep-loan-payment-preview"><div class="ep-loan-payment-heading"><div><strong>Détail avant paiement</strong><small>Indique le montant net que le salarié recevra. Le brut et les retenues sont calculés automatiquement.</small></div><strong class="ep-preview-net-total" aria-live="polite">Net à remettre : {{ number_format($defaultNetToPay,0,',',' ') }} F</strong></div>
                            <div class="ep-salary-breakdown"><div><span>Salaire du mois</span><strong>{{ number_format((int)$period->salary_amount,0,',',' ') }} F</strong></div><div><span>Avances déjà remises <small>(déjà déduites du solde)</small></span><strong>{{ number_format($advanceTotal,0,',',' ') }} F</strong></div><div><span>Salaire brut déjà versé</span><strong>{{ number_format($salaryPaid,0,',',' ') }} F</strong></div>@if($period->pending_advance_amount>0)<div><span>Avances en attente réservées</span><strong>{{ number_format((int)$period->pending_advance_amount,0,',',' ') }} F</strong></div>@endif<div><span>Brut imputé au salaire</span><strong class="ep-preview-gross">{{ number_format($availableToPay,0,',',' ') }} F</strong></div><div><span>Retenues mensuelles des prêts</span><strong class="ep-preview-deductions">{{ number_format($defaultLoanDeduction,0,',',' ') }} F</strong></div><div class="ep-breakdown-net"><span>Montant net à remettre</span><strong class="ep-preview-net">{{ number_format($defaultNetToPay,0,',',' ') }} F</strong></div><div><span>Reste du salaire après ce versement</span><strong class="ep-preview-balance">{{ number_format(max(0,$remaining-$availableToPay),0,',',' ') }} F</strong></div></div>
                            @if($activeLoans->isNotEmpty())
                                <div class="ep-loan-preview-list">
                                    @foreach($activeLoans as $loan)
                                        @php($loanMonthlyPlan=$loan->repayment_method==='salary_percent'?max(1,(int)round($period->salary_amount*((float)$loan->repayment_value/100))):(int)$loan->repayment_value)
                                        <article class="ep-loan-preview-row {{ !$loan->repayment_method ? 'is-plan-missing' : '' }}" data-outstanding="{{ max(0,(int)$loan->amount-(int)$loan->repaid_amount) }}" data-granted-on="{{ $loan->granted_on?->toDateString() }}" data-monthly-plan="{{ $loanMonthlyPlan }}" data-repayments="{{ $loan->repayments->map(fn($repayment)=>['date'=>$repayment->received_at->format('Y-m-d'),'amount'=>(int)$repayment->amount])->values()->toJson() }}">
                                            <div>
                                                <strong>{{ $loan->reference }} · Prêt</strong>
                                                <small>
                                                    Dette restante : {{ number_format(max(0,$loan->amount-$loan->repaid_amount),0,',',' ') }} F ·
                                                    @if(!$loan->repayment_method)
                                                        Mensualité non définie : aucune retenue automatique
                                                    @else
                                                        Mensualité prévue : {{ number_format($loanMonthlyPlan,0,',',' ') }} F
                                                    @endif
                                                </small>
                                                @if(!$loan->repayment_method)
                                                    <a href="{{ route('finance.management.index',['tab'=>'team','team_view'=>'loans']) }}">Vérifier ce prêt dans le suivi des avances et prêts</a>
                                                @endif
                                            </div>
                                            <div class="ep-loan-preview-values"><span>Retenu sur ce versement <b class="ep-loan-this-deduction">—</b></span><span>Reste après versement <b class="ep-loan-after-balance">—</b></span></div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <p class="ep-no-debt">Aucun prêt en cours à déduire.</p>
                            @endif
                        </section>
                        <div class="ep-payment-grid"><label>Montant net à remettre (F CFA)<input class="js-net-salary" type="number" name="net_amount" value="{{ $defaultNetToPay }}" min="0" max="{{ $defaultNetToPay }}" step="1" required><small>Montant que le salarié recevra après les retenues mensuelles de ses prêts.</small></label><label>Date de remise<input class="js-salary-paid-date" type="date" name="paid_at" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></label><label>Référence éventuelle<input name="payment_reference" maxlength="120" placeholder="Référence interne"></label></div><p class="ep-net-preview" hidden></p><button class="btn btn-primary mt-3">Confirmer le versement effectué</button><small>Le brut imputé au salaire et les retenues sont calculés à partir du montant net saisi. Les avances sur salaire déjà remises sont déjà déduites du solde.</small></form></details>
                    @else
                        <p class="ep-note">Rends d’abord le salaire disponible : le salarié pourra alors le voir dans son espace. Tu pourras ensuite enregistrer sa remise réelle.</p>
                    @endif
                    <details class="ep-payment-form"><summary>{{ auth()->user()->isAdmin() ? 'Enregistrer une avance sur ce mois' : 'Demander une avance sur ce mois' }}</summary><form method="POST" action="{{ route('finance.management.advances.store') }}">@csrf<input type="hidden" name="user_id" value="{{ $employee->id }}"><input type="hidden" name="advance_type" value="salary_advance"><input type="hidden" name="payroll_period_id" value="{{ $period->id }}"><input type="hidden" name="granted_on" value="{{ today()->toDateString() }}"><div class="ep-payment-grid"><label>Montant (F CFA)<input type="number" name="amount" min="1" max="{{ $availableToPay }}" step="1" required></label><label class="column-span-2">Motif<textarea name="reason" rows="2" maxlength="2000" required placeholder="Pourquoi cette avance est-elle accordée ?"></textarea></label></div><button class="btn btn-outline-primary mt-3">{{ auth()->user()->isAdmin() ? 'Autoriser l’avance' : 'Soumettre à validation' }}</button><small>@if(auth()->user()->isAdmin())L’avance est autorisée directement. Enregistre ensuite le paiement pour mettre à jour la caisse et le suivi du salarié.@elseAprès autorisation et paiement, cette avance apparaîtra ici et dans le suivi d’équipe. Elle ne sera pas saisie une deuxième fois.@endif</small></form></details>
                @elseif($remaining>0)
                    <p class="ep-note">Le reste est réservé par une demande d’avance en cours de validation ou de paiement.</p>
                @endif
            </article>
        @empty
            <div class="ep-empty"><strong>Aucun mois de salaire enregistré</strong><p>Ajoute un mois pour suivre le montant dû, les avances et les paiements.</p></div>
        @endforelse
    </section>
    <div class="ep-pagination">{{ $periods->links() }}</div>
</main>

<script>
document.querySelectorAll('.js-net-salary').forEach(function (input) {
    const form = input.closest('form');
    const preview = form.querySelector('.ep-net-preview');
    const netTotal = form.querySelector('.ep-preview-net-total');
    const paidDate = form.querySelector('.js-salary-paid-date');
    const month = form.dataset.payrollMonth;
    const loanRows = Array.from(form.querySelectorAll('.ep-loan-preview-row'));
    const money = value => new Intl.NumberFormat('fr-FR').format(Math.max(0, Math.round(value))) + ' F CFA';
    const moneyShort = value => new Intl.NumberFormat('fr-FR').format(Math.max(0, Math.round(value))) + ' F';
    const update = function () {
        const paymentDate = paidDate.value;
        const availableGross = Number(form.dataset.availableGross || 0);
        const dues = loanRows.map(function (row) {
            const outstanding = Number(row.dataset.outstanding || 0);
            const grantedOn = row.dataset.grantedOn || '';
            const monthlyPlan = Number(row.dataset.monthlyPlan || 0);
            const repayments = JSON.parse(row.dataset.repayments || '[]');
            const eligible = Boolean(paymentDate && grantedOn && month >= grantedOn.slice(0, 7) && paymentDate >= grantedOn);
            const repaidThisMonth = eligible ? repayments.reduce(function (sum, repayment) {
                return repayment.date.slice(0, 7) === month && repayment.date <= paymentDate
                    ? sum + Number(repayment.amount || 0)
                    : sum;
            }, 0) : 0;
            const dueThisMonth = eligible ? Math.max(0, monthlyPlan - repaidThisMonth) : 0;
            return { row: row, outstanding: outstanding, due: Math.min(dueThisMonth, outstanding) };
        });
        const totalScheduledDeductions = dues.reduce(function (sum, item) { return sum + item.due; }, 0);
        const maximumNet = Math.max(0, availableGross - Math.min(availableGross, totalScheduledDeductions));
        input.max = String(maximumNet);
        if (Number(input.value || 0) > maximumNet) input.value = String(maximumNet);
        const net = Number(input.value || 0);
        const gross = Math.min(availableGross, net + totalScheduledDeductions);
        let remainingGross = gross;
        let totalDeduction = 0;
        dues.forEach(function (item) {
            const deduction = Math.min(item.due, remainingGross);
            remainingGross -= deduction;
            totalDeduction += deduction;
            item.row.querySelector('.ep-loan-this-deduction').textContent = money(deduction);
            item.row.querySelector('.ep-loan-after-balance').textContent = money(item.outstanding - deduction);
        });
        const salaryBalance = Math.max(0, Number(form.dataset.monthSalary || 0) - Number(form.dataset.advancesPaid || 0) - Number(form.dataset.salaryPaid || 0) - gross);
        form.querySelector('.ep-preview-gross').textContent = moneyShort(gross);
        form.querySelector('.ep-preview-deductions').textContent = moneyShort(totalDeduction);
        form.querySelector('.ep-preview-net').textContent = moneyShort(net);
        form.querySelector('.ep-preview-balance').textContent = moneyShort(salaryBalance);
        if (net > 0 || gross > 0) {
            preview.hidden = false;
            preview.textContent = 'Brut imputé au salaire : ' + money(gross) + ' · Retenues des prêts : ' + money(totalDeduction) + ' · Net à remettre : ' + money(net) + ' · Reste du salaire : ' + money(salaryBalance);
            netTotal.textContent = 'Net à remettre : ' + money(net);
        } else {
            preview.hidden = true;
            netTotal.textContent = 'Saisis le net à remettre';
        }
    };
    input.addEventListener('input', update);
    paidDate?.addEventListener('change', update);
    update();
});
</script>

<style>
.employee-payroll{max-width:1320px;padding:clamp(14px,2vw,28px);color:var(--app-text)}.ep-back{color:var(--app-primary);font-weight:700}.ep-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin:18px 0}.ep-eyebrow{display:block;color:var(--app-primary);font-size:11px;letter-spacing:.08em;font-weight:850}.ep-heading h1{margin:5px 0;font-size:clamp(25px,3vw,35px);font-weight:850}.ep-heading p,.ep-panel p{margin:0;color:var(--app-muted)}.ep-current-salary{display:grid;gap:3px;min-width:220px;padding:16px 20px;background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius)}.ep-current-salary span,.ep-current-salary small{color:var(--app-muted);font-size:12px}.ep-current-salary strong{font-size:23px}.ep-current-salary a{color:var(--app-primary);font-weight:700}.ep-guidance{display:grid;gap:3px;padding:14px 17px;margin:14px 0 18px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));border:1px solid color-mix(in srgb,var(--app-primary) 22%,var(--app-surface));border-radius:var(--app-radius)}.ep-guidance span{color:var(--app-muted);font-size:13px}.ep-panel,.ep-period-card,.ep-empty{padding:clamp(16px,2vw,23px);background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius)}.ep-create{display:grid;grid-template-columns:minmax(220px,.75fr) minmax(0,1.8fr);gap:20px;align-items:center;margin-bottom:15px}.ep-create h2,.ep-period-heading h2{margin:0 0 5px;font-size:19px;font-weight:800}.ep-create p{font-size:13px}.ep-create form{display:grid;grid-template-columns:1fr 1.4fr auto;gap:10px;align-items:end}.ep-create label,.ep-payment-grid label{display:grid;gap:5px;margin:0;font-size:12px;font-weight:750}.ep-create input,.ep-payment-grid input,.ep-payment-grid select{min-width:0;width:100%;height:42px;padding:7px 10px;border:1px solid color-mix(in srgb,var(--app-text) 18%,var(--app-surface));border-radius:5px;background:var(--app-surface);color:var(--app-text)}.ep-period-list{display:grid;gap:14px}.ep-period-heading{display:flex;justify-content:space-between;align-items:center;gap:12px}.ep-period-heading h2{text-transform:capitalize}.ep-state{padding:6px 10px;border-radius:20px;font-size:12px;font-weight:800;white-space:nowrap}.ep-state.is-paid{background:#e7f4eb;color:#187b36}.ep-state.is-partial{background:#fff3d9;color:#885600}.ep-state.is-due{background:#f2e9e9;color:#8a3434}.ep-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:15px 0}.ep-summary>div{display:grid;gap:4px;padding:12px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));border-radius:6px}.ep-summary span{color:var(--app-muted);font-size:12px}.ep-summary strong{font-size:17px}.ep-summary .is-balance{background:color-mix(in srgb,var(--app-primary) 9%,var(--app-surface))}.ep-note{margin:0 0 10px;color:var(--app-muted);font-size:13px}.ep-payments{margin:6px 0 12px}.ep-payments .table{margin:0}.ep-payment-form{border-top:1px solid color-mix(in srgb,var(--app-text) 12%,var(--app-surface));padding-top:12px}.ep-payment-form summary{color:var(--app-primary);font-weight:800;cursor:pointer}.ep-payment-form form{padding:14px 0 0}.ep-payment-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.ep-payment-form small{display:block;margin-top:8px;color:var(--app-muted)}.ep-empty{text-align:center;color:var(--app-muted)}.ep-empty strong{display:block;color:var(--app-text);font-size:17px}.ep-empty p{margin:5px 0 0}.ep-pagination{margin-top:16px}
@media(max-width:900px){.ep-create{grid-template-columns:1fr}.ep-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.ep-payment-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ep-create form{grid-template-columns:1fr 1fr}.ep-create form button{grid-column:1/-1}}
.ep-payment-grid textarea{min-width:0;width:100%;padding:8px 10px;border:1px solid color-mix(in srgb,var(--app-text) 18%,var(--app-surface));border-radius:5px;background:var(--app-surface);color:var(--app-text);resize:vertical}.ep-payment-grid .column-span-2{grid-column:span 2}
@media(max-width:600px){.employee-payroll{padding:12px 9px}.ep-heading{display:grid;align-items:start}.ep-current-salary{min-width:0}.ep-summary{gap:7px}.ep-summary>div{padding:10px}.ep-summary strong{font-size:15px}.ep-period-card{padding:14px}.ep-payment-grid,.ep-create form{grid-template-columns:1fr}.ep-payment-grid .column-span-2{grid-column:auto}.ep-create form button{grid-column:auto}.ep-period-heading{align-items:flex-start}.ep-state{font-size:11px}}
</style>
<style>
.ep-summary{grid-template-columns:repeat(5,minmax(0,1fr))}
.ep-summary>div{align-content:start;min-height:88px;padding:12px}
.ep-summary span{min-height:2.5em;line-height:1.25}
.ep-summary small{color:var(--app-muted);font-size:10px;line-height:1.35}
.ep-summary .is-balance small{color:color-mix(in srgb,var(--app-primary) 75%,var(--app-text))}
.ep-net-preview{margin:10px 0 0;padding:10px 12px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));border-radius:6px;color:var(--app-primary);font-weight:750}
.ep-loan-overview{padding:17px 20px;margin:0 0 16px;background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius)}
.ep-loan-overview>header,.ep-loan-payment-heading{display:flex;justify-content:space-between;align-items:center;gap:14px}
.ep-loan-overview h2{margin:2px 0 4px;font-size:18px;font-weight:800}
.ep-loan-overview p,.ep-loan-overview header>strong small,.ep-loan-overview-list small{display:block;margin:0;color:var(--app-muted);font-size:12px}
.ep-loan-overview header>strong{text-align:right;color:var(--app-primary);font-size:20px;white-space:nowrap}
.ep-loan-overview-list{display:grid;gap:8px;margin-top:13px}
.ep-loan-overview-list article{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));border-radius:7px}
.ep-loan-overview-list article>div:first-child{display:grid;gap:2px;min-width:0}
.ep-loan-overview-list article>div:first-child strong{font-size:12px}
.ep-loan-overview-list article>div:first-child span{font-size:12px;overflow-wrap:anywhere}
.ep-loan-overview-amount{text-align:right;flex:none}
.ep-loan-overview-amount>span,.ep-loan-overview-amount>strong{display:block}
.ep-loan-overview-amount>span{color:var(--app-muted);font-size:11px}
.ep-loan-overview-amount>strong{font-size:14px}
.ep-loan-payment-preview{padding:12px;margin:0 0 12px;background:color-mix(in srgb,var(--app-primary) 4%,var(--app-surface));border:1px solid color-mix(in srgb,var(--app-primary) 17%,var(--app-surface));border-radius:8px}
.ep-loan-payment-heading{align-items:flex-start;margin-bottom:9px}
.ep-loan-payment-heading>div{display:grid;gap:3px}
.ep-loan-payment-heading>div strong{font-size:13px}
.ep-loan-payment-heading small{color:var(--app-muted);font-size:11px}
.ep-preview-net-total{color:var(--app-primary);font-size:12px;text-align:right}
.ep-salary-breakdown{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px 12px;margin:10px 0;padding:10px;background:var(--app-surface);border-radius:6px}
.ep-salary-breakdown>div{display:flex;justify-content:space-between;align-items:center;gap:8px;min-width:0;padding:4px 0;border-bottom:1px solid color-mix(in srgb,var(--app-text) 8%,var(--app-surface));font-size:11px}
.ep-salary-breakdown>div>span{color:var(--app-muted)}
.ep-salary-breakdown>div>span small{display:block;font-size:9px}
.ep-salary-breakdown>div>strong{flex:none;text-align:right}
.ep-salary-breakdown .ep-breakdown-net{grid-column:1/-1;padding:8px 9px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));border:0;border-radius:5px;font-size:13px}
.ep-breakdown-net strong{color:var(--app-primary);font-size:16px}
.ep-loan-preview-list{display:grid;gap:6px}
.ep-loan-preview-row{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:9px 10px;background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 10%,var(--app-surface));border-radius:6px}
.ep-loan-preview-row>div:first-child{display:grid;gap:3px;min-width:0}
.ep-loan-preview-row>div:first-child strong{font-size:11px}
.ep-loan-preview-row>div:first-child small{color:var(--app-muted);font-size:10px;line-height:1.4}
.ep-loan-preview-row.is-plan-missing{border-color:#e9d4a4;background:#fffaf0}
.ep-loan-preview-row.is-plan-missing>div:first-child>a{margin-top:4px;color:#8a5a00;font-size:10px;font-weight:750}
.ep-loan-preview-values{display:grid;gap:3px;flex:none;text-align:right;font-size:10px}
.ep-loan-preview-values span{color:var(--app-muted)}
.ep-loan-preview-values b{display:inline-block;min-width:90px;color:var(--app-text)}
.ep-no-debt{margin:0;color:var(--app-muted);font-size:12px}
.ep-availability,.ep-availability-form{display:flex;align-items:center;gap:8px;margin:0 0 12px;padding:9px 11px;border-radius:7px;font-size:12px;line-height:1.4}
.ep-availability .material-icons{font-size:18px}
.ep-availability.is-available{background:#edf7ef;color:#237445}
.ep-availability-form{justify-content:space-between;flex-wrap:wrap;background:color-mix(in srgb,var(--app-primary) 5%,var(--app-surface));color:var(--app-muted)}
.ep-availability-form .btn{white-space:normal}
@media(max-width:900px){.ep-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.ep-loan-overview{padding:13px}.ep-loan-overview>header,.ep-loan-preview-row{align-items:flex-start;flex-direction:column}.ep-loan-overview header>strong{text-align:left}.ep-loan-overview-list article{align-items:flex-start}.ep-loan-preview-values{width:100%;text-align:left}.ep-loan-preview-values b{float:right}.ep-preview-net-total{text-align:right;max-width:55%}.ep-availability-form{align-items:stretch;flex-direction:column}.ep-availability-form .btn{width:100%}.ep-salary-breakdown{grid-template-columns:1fr}.ep-salary-breakdown .ep-breakdown-net{grid-column:auto}}
</style>
@endsection
