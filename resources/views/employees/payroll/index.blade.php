@extends('layouts.admin')

@section('content')
<main class="container-fluid employee-payroll">
    <a class="ep-back" href="{{ route('employees.show',$employee->employeeProfile->tracking_id) }}">← Retour au dossier employé</a>
    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <header class="ep-heading"><div><span class="ep-eyebrow">SUIVI INTERNE · ESPÈCES</span><h1>Salaires de {{ $employee->name }}</h1><p>Enregistre le salaire convenu, les avances et les versements réellement remis.</p></div><div class="ep-current-salary">@if($employee->employeeProfile?->monthly_salary)<span>Salaire mensuel actuel</span><strong>{{ number_format($employee->employeeProfile->monthly_salary,0,',',' ') }} F</strong><small>Depuis {{ $employee->employeeProfile->salary_effective_from?->format('m/Y') ?? 'date à confirmer' }}</small>@else<span>Salaire non renseigné</span><a href="{{ route('employees.edit',$employee->employeeProfile->tracking_id) }}">Renseigner le salaire</a>@endif</div></header>

    <section class="ep-guidance"><strong>La retenue du prêt est appliquée automatiquement au versement du salaire.</strong><span>Indique le salaire brut réglé. Le montant prévu pour les prêts est déduit automatiquement ; la caisse est débitée uniquement du montant net remis au salarié. Aucun calcul de retenues légales ni bulletin de paie n’est effectué.</span></section>

    @if($employee->employeeProfile?->monthly_salary && $employee->employeeProfile?->salary_effective_from)
        <section class="ep-panel ep-create"><div><h2>Ajouter un mois au suivi</h2><p>Le montant est copié depuis le salaire actuel. Une modification future du salaire ne changera pas les mois déjà créés.</p></div><form method="POST" action="{{ route('employees.payroll.periods.store',$employee->employeeProfile->tracking_id) }}">@csrf<label>Mois<input type="month" name="period" value="{{ old('period',now()->format('Y-m')) }}" required></label><label>Note (facultative)<input name="note" value="{{ old('note') }}" maxlength="500" placeholder="Ex. Salaire de base"></label><button class="btn btn-primary">Ajouter ce mois</button></form></section>
    @endif

    <section class="ep-period-list">
        @forelse($periods as $period)
            @php($paidTotal=(int)$period->payments->sum('amount'))
            @php($linkedLegacyPaymentIds=$period->staffAdvances->pluck('employee_payroll_payment_id')->filter()->map(fn($id)=>(int)$id)->all())
            @php($legacyUnlinkedAdvances=$period->payments->where('kind','advance')->whereNotIn('id',$linkedLegacyPaymentIds))
            @php($legacyAdvanceTotal=(int)$legacyUnlinkedAdvances->sum('amount'))
            @php($linkedAdvanceTotal=(int)$period->staffAdvances->sum('amount'))
            @php($advanceTotal=$legacyAdvanceTotal+$linkedAdvanceTotal)
            @php($salaryPaid=(int)$period->payments->where('kind','salary')->sum('amount'))
            @php($loanDeducted=(int)$period->payments->where('kind','salary')->sum('loan_deduction_amount'))
            @php($paidTotal=$salaryPaid+$advanceTotal)
            @php($remaining=max(0,(int)$period->salary_amount-$paidTotal))
            @php($availableToPay=max(0,$remaining-(int)$period->pending_advance_amount))
            <article class="ep-period-card">
                <header class="ep-period-heading"><div><span class="ep-eyebrow">{{ $period->reference }}</span><h2>{{ $period->period_start->translatedFormat('F Y') }}</h2></div><span class="ep-state {{ $remaining===0?'is-paid':($paidTotal>0?'is-partial':'is-due') }}">{{ $remaining===0?'Soldé':($paidTotal>0?'Partiellement versé':'À verser') }}</span></header>
                <div class="ep-summary"><div><span>Salaire convenu</span><strong>{{ number_format($period->salary_amount,0,',',' ') }} F</strong></div><div><span>Avances</span><strong>{{ number_format($advanceTotal,0,',',' ') }} F</strong></div><div><span>Retenue des prêts</span><strong>{{ number_format($loanDeducted,0,',',' ') }} F</strong></div><div><span>Salaire brut réglé</span><strong>{{ number_format($salaryPaid,0,',',' ') }} F</strong></div><div class="is-balance"><span>Reste dû</span><strong>{{ number_format($remaining,0,',',' ') }} F</strong></div></div>
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
                    <details class="ep-payment-form"><summary>Enregistrer un versement de salaire</summary><form method="POST" action="{{ route('employees.payroll.payments.store',[$employee->employeeProfile->tracking_id,$period]) }}">@csrf<input type="hidden" name="submission_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="kind" value="salary"><div class="ep-payment-grid"><label>Salaire brut réglé (F CFA)<input class="js-gross-salary" data-loan-due="{{ min((int)$period->loan_deduction_due, $availableToPay) }}" type="number" name="amount" min="1" max="{{ $availableToPay }}" step="1" required><small>Montant avant retenue du prêt.</small></label><label>Date de remise<input type="date" name="paid_at" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></label><label>Référence éventuelle<input name="payment_reference" maxlength="120" placeholder="Référence interne"></label></div><p class="ep-net-preview" hidden></p><button class="btn btn-primary mt-3">Confirmer le versement effectué</button><small>Le système déduit automatiquement la mensualité prévue et enregistre uniquement le net en sortie de caisse.</small></form></details>
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
document.querySelectorAll('.js-gross-salary').forEach(function (input) {
    const preview = input.closest('form').querySelector('.ep-net-preview');
    const update = function () {
        const gross = Number(input.value || 0);
        const deduction = Math.min(gross, Number(input.dataset.loanDue || 0));
        if (gross > 0 && deduction > 0) {
            preview.hidden = false;
            preview.textContent = 'Retenue automatique : ' + new Intl.NumberFormat('fr-FR').format(deduction) + ' F CFA · Net remis : ' + new Intl.NumberFormat('fr-FR').format(gross - deduction) + ' F CFA';
        } else {
            preview.hidden = true;
        }
    };
    input.addEventListener('input', update);
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
.ep-net-preview{margin:10px 0 0;padding:10px 12px;background:color-mix(in srgb,var(--app-primary) 8%,var(--app-surface));border-radius:6px;color:var(--app-primary);font-weight:750}
@media(max-width:900px){.ep-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endsection
