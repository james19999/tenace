@extends('layouts.admin')

@section('content')
<main class="external-loan-form-page">
    <header class="external-loan-form-heading"><a href="{{ route('finance.loans.index') }}">← Emprunts bancaires</a><span class="external-loans-kicker">NOUVEAU CONTRAT</span><h1>Enregistrer un emprunt</h1><p>Après validation, le montant effectivement reçu sera ajouté à la caisse générale et la dette sera suivie ici.</p></header>
    @if($errors->any())<div class="alert alert-danger"><strong>Corrige les informations suivantes :</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="external-loan-form-panel">
        <div class="external-loan-cash-note"><span class="material-icons" aria-hidden="true">account_balance_wallet</span><span><small>Solde actuel de la caisse générale</small><strong>{{ number_format($generalCash, 0, ',', ' ') }} F</strong></span></div>
        <form method="POST" action="{{ route('finance.loans.store') }}">@csrf
            <div class="external-loan-form-grid">
                <label>Banque ou microfinance <input class="form-control" name="lender_name" value="{{ old('lender_name') }}" placeholder="Ex. FUCEC, COOPEC…" maxlength="160" required></label>
                <label>Référence du contrat <small>Facultatif</small><input class="form-control" name="contract_reference" value="{{ old('contract_reference') }}" maxlength="100" placeholder="N° du contrat ou du dossier"></label>
                <label>Montant réellement reçu (F CFA) <input class="form-control" type="number" min="1" step="1" name="received_amount" value="{{ old('received_amount') }}" required></label>
                <label>Montant total à rembourser (F CFA) <input class="form-control" type="number" min="1" step="1" name="total_repayable" value="{{ old('total_repayable') }}" required><small>Capital et intérêts ou frais prévus au contrat.</small></label>
                <label>Déjà remboursé avant cet enregistrement (F CFA) <input class="form-control" type="number" min="0" step="1" name="initial_repaid_amount" value="{{ old('initial_repaid_amount', 0) }}"><small>Pour saisir le solde actuel d’un prêt déjà en cours.</small></label>
                <label>Date de réception des fonds <input class="form-control" type="date" name="received_on" max="{{ now()->toDateString() }}" value="{{ old('received_on', now()->toDateString()) }}" required></label>
                <label>Fréquence des remboursements <small>Choisis le rythme prévu au contrat. Tu pourras enregistrer un paiement n’importe quel jour.</small><select class="form-control" name="repayment_frequency" id="repayment-frequency"><option value="" @selected(old('repayment_frequency') === '')>Pas de fréquence fixe</option><option value="weekly" @selected(old('repayment_frequency') === 'weekly')>Chaque semaine</option><option value="monthly" @selected(old('repayment_frequency') === 'monthly')>Chaque mois</option></select></label>
                <label id="first-payment-field">Date de départ des remboursements <small id="first-payment-help">Date de la prochaine échéance, si elle est déjà connue.</small><input class="form-control" type="date" name="first_payment_on" min="{{ old('received_on', now()->toDateString()) }}" value="{{ old('first_payment_on') }}"><small id="first-payment-required-help">Requise quand une fréquence est définie. Les échéances suivantes seront calculées automatiquement.</small></label>
                <label>Montant indicatif d’une échéance (F CFA) <small>Facultatif · aide au suivi, le paiement saisi peut être différent.</small><input class="form-control" type="number" min="1" step="1" name="installment_amount" value="{{ old('installment_amount') }}" placeholder="Ex. 250 000"></label>
                <label class="external-loan-notes">Notes <small>Conditions, périodicité ou informations utiles</small><textarea class="form-control" name="notes" rows="3" maxlength="4000">{{ old('notes') }}</textarea></label>
            </div>
            <input type="hidden" name="credit_general_cash" value="0"><label class="external-loan-credit-option"><input type="checkbox" name="credit_general_cash" value="1" @checked(old('credit_general_cash', '1') == '1')><span><strong>Ajouter maintenant le montant reçu à la caisse générale</strong><small>Laisse cette case cochée pour un nouveau versement. Décoche-la si tu enregistres un prêt ancien dont les fonds sont déjà compris dans la caisse, afin d’éviter un doublon.</small></span></label>
            <div class="external-loan-form-summary"><span class="material-icons" aria-hidden="true">info</span><p>Le montant à rembourser inclut le capital et le coût du financement. Les remboursements déjà effectués avant la mise en place du suivi ne créent pas de nouvelle sortie de caisse.</p></div>
            <div class="external-loan-form-actions"><a href="{{ route('finance.loans.index') }}" class="btn btn-outline-secondary">Annuler</a><button class="btn btn-primary" type="submit"><span class="material-icons" aria-hidden="true">verified</span>Valider et créditer la caisse générale</button></div>
        </form>
    </section>
</main>

<script>
(() => {
    const frequency = document.getElementById('repayment-frequency');
    const field = document.querySelector('[name="first_payment_on"]');
    const help = document.getElementById('first-payment-help');
    const requiredHelp = document.getElementById('first-payment-required-help');
    if (!frequency || !field) return;
    const updateScheduleHint = () => {
        const scheduled = frequency.value !== '';
        field.required = scheduled;
        requiredHelp.hidden = !scheduled;
        help.textContent = scheduled
            ? 'Cette première date servira de point de départ au calcul des échéances suivantes.'
            : 'Facultatif si une prochaine échéance est déjà convenue.';
    };
    frequency.addEventListener('change', updateScheduleHint);
    updateScheduleHint();
})();
</script>

<style>
.external-loan-form-page{max-width:1050px;margin:auto;padding:20px 24px 42px;color:var(--app-text,#273142)}.external-loan-form-heading{padding:4px 0 19px}.external-loan-form-heading>a{display:block;margin-bottom:15px;color:var(--app-primary,#187b2a);font-size:13px;font-weight:750;text-decoration:none}.external-loans-kicker{color:var(--app-primary,#187b2a);font-size:10px;font-weight:850;letter-spacing:.13em}.external-loan-form-heading h1{margin:5px 0;font-size:clamp(27px,3vw,36px);font-weight:850}.external-loan-form-heading p{margin:0;color:var(--app-muted,#788496)}.external-loan-form-panel{padding:22px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.external-loan-cash-note{display:flex;align-items:center;gap:12px;width:max-content;max-width:100%;margin-bottom:21px;padding:11px 15px;background:#eef7ef;border:1px solid #d8eadb;color:#187b2a}.external-loan-cash-note>.material-icons{font-size:25px}.external-loan-cash-note small,.external-loan-cash-note strong{display:block}.external-loan-cash-note small{font-size:11px}.external-loan-cash-note strong{font-size:18px}.external-loan-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}.external-loan-form-grid label{display:grid;align-content:start;gap:6px;margin:0;color:var(--app-text,#364152);font-size:12px;font-weight:800}.external-loan-form-grid label>small{margin-top:-4px;color:var(--app-muted,#788496);font-size:10px;font-weight:500}.external-loan-form-grid .form-control{width:100%;min-width:0;height:43px;border-color:#d7dee6;border-radius:4px;background:var(--app-surface,#fff);color:var(--app-text,#273142);font-size:14px}.external-loan-form-grid textarea.form-control{height:auto;min-height:92px}.external-loan-notes{grid-column:1/-1}.external-loan-credit-option{display:flex;align-items:flex-start;gap:10px;margin:19px 0 0;padding:13px 14px;background:#f0f8f1;border:1px solid #d8eadb;cursor:pointer}.external-loan-credit-option input{flex:none;width:18px;height:18px;margin:2px 0 0;accent-color:#187b2a}.external-loan-credit-option strong,.external-loan-credit-option small{display:block}.external-loan-credit-option strong{font-size:12px}.external-loan-credit-option small{margin-top:4px;color:var(--app-muted,#788496);font-size:11px;font-weight:500;line-height:1.45}.external-loan-form-summary{display:flex;gap:9px;margin-top:12px;padding:12px 14px;background:#f5f7f9;border:1px solid #e4e8ed;color:#596679}.external-loan-form-summary>.material-icons{flex:none;color:#187b2a}.external-loan-form-summary p{margin:0;font-size:12px;line-height:1.5}.external-loan-form-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:18px}.external-loan-form-actions .btn{display:inline-flex;align-items:center;gap:6px;min-height:42px;font-weight:750}.external-loan-form-actions .material-icons{font-size:19px}@media(max-width:600px){.external-loan-form-page{padding:11px 12px 28px}.external-loan-form-panel{padding:15px}.external-loan-form-grid{grid-template-columns:1fr;gap:13px}.external-loan-notes{grid-column:auto}.external-loan-form-actions{flex-direction:column-reverse}.external-loan-form-actions .btn{justify-content:center;width:100%}}
</style>
@endsection
