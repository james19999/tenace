@extends('layouts.admin')
@section('content')
<main class="tontine-remittance-page">
    <header class="tontine-remittance-heading">
        <a href="{{ route('finance.expenses.index', ['cashier_tab' => 'tontines']) }}">← Retour aux cotisations à remettre</a>
        <span>REMISE DE LA CAISSE GÉNÉRALE · {{ $contributionRequest->reference }}</span>
        <h1>Remettre la cotisation</h1>
        <p>Vérifie la demande et confirme les informations après avoir remis l’argent à la personne chargée.</p>
    </header>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="tontine-remittance-layout">
        <article class="tontine-remittance-summary">
            <span class="material-icons">savings</span>
            <h2>{{ $contributionRequest->tontine?->name ?? 'Tontine supprimée' }}</h2>
            <dl>
                <div><dt>Personne chargée</dt><dd>{{ $contributionRequest->requester?->name ?? '—' }}</dd></div>
                <div><dt>Référence de demande</dt><dd>{{ $contributionRequest->reference }}</dd></div>
                <div><dt>Demandé le</dt><dd>{{ $contributionRequest->requested_at?->format('d/m/Y à H:i') ?? '—' }}</dd></div>
                <div><dt>Motif ou précision</dt><dd>{{ $contributionRequest->note ?: 'Aucune précision fournie.' }}</dd></div>
            </dl>
        </article>

        <section class="tontine-remittance-form-card">
            <div class="tontine-remittance-form-heading"><span class="material-icons">account_balance_wallet</span><div><h2>Confirmer la remise réelle</h2><p>Cette confirmation débitera la caisse générale et enregistrera la cotisation dans la tontine.</p></div></div>
            <form method="POST" action="{{ route('finance.tontines.contribution-requests.pay', $contributionRequest) }}">
                @csrf
                <label>Montant demandé et à remettre (F CFA)
                    <input class="form-control" type="number" name="amount" value="{{ old('amount', $contributionRequest->amount) }}" readonly>
                    <small>Le montant remis doit correspondre à la demande.</small>
                </label>
                <label>Date réelle de remise
                    <input class="form-control" type="date" name="occurred_at" value="{{ old('occurred_at', today()->format('Y-m-d')) }}" min="{{ today()->startOfWeek()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required>
                </label>
                <label>Note sur la remise (facultatif)
                    <textarea class="form-control" name="cash_note" rows="3" maxlength="1000" placeholder="Précision utile sur la remise">{{ old('cash_note') }}</textarea>
                </label>
                <label class="tontine-remittance-confirm"><input type="checkbox" name="cash_confirmed" value="1" @checked(old('cash_confirmed')) required><span>Je confirme avoir remis {{ number_format($contributionRequest->amount, 0, ',', ' ') }} F à {{ $contributionRequest->requester?->name ?? 'la personne chargée' }} depuis la caisse générale.</span></label>
                <div class="tontine-remittance-actions"><a class="btn btn-outline-primary" href="{{ route('finance.expenses.index', ['cashier_tab' => 'tontines']) }}">Annuler</a><button class="btn btn-primary" type="submit">Confirmer la remise et débiter la caisse</button></div>
            </form>
        </section>
    </section>
</main>
<style>
.tontine-remittance-page{max-width:1180px;margin:0 auto;padding:22px 24px 44px;color:var(--app-text,#273142)}
.tontine-remittance-heading{padding:21px 24px;margin-bottom:16px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}
.tontine-remittance-heading>a{display:inline-block;margin-bottom:15px;color:var(--app-primary,#811515);font-size:13px;font-weight:750;text-decoration:none}
.tontine-remittance-heading>span{display:block;color:var(--app-muted,#778397);font-size:10px;font-weight:850;letter-spacing:.08em}
.tontine-remittance-heading h1{margin:6px 0;font-size:clamp(25px,2.5vw,34px);font-weight:850}
.tontine-remittance-heading p{margin:0;color:var(--app-muted,#778397);line-height:1.5}
.tontine-remittance-layout{display:grid;grid-template-columns:minmax(250px,.8fr) minmax(0,1.3fr);gap:15px;align-items:start}
.tontine-remittance-summary,.tontine-remittance-form-card{min-width:0;padding:21px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}
.tontine-remittance-summary>.material-icons{display:grid;place-items:center;width:43px;height:43px;margin-bottom:12px;background:var(--app-primary-soft,#f7eeee);color:var(--app-primary,#811515);font-size:24px}
.tontine-remittance-summary h2,.tontine-remittance-form-heading h2{margin:0 0 15px;font-size:19px;font-weight:820;overflow-wrap:anywhere}
.tontine-remittance-summary dl{display:grid;gap:0;margin:0}.tontine-remittance-summary dl>div{padding:11px 0;border-top:1px solid var(--app-border,#edf0f3)}
.tontine-remittance-summary dt{margin-bottom:4px;color:var(--app-muted,#778397);font-size:11px;font-weight:750}.tontine-remittance-summary dd{margin:0;font-size:13px;font-weight:650;overflow-wrap:anywhere;white-space:pre-line}
.tontine-remittance-form-heading{display:flex;gap:11px;align-items:flex-start;margin-bottom:17px}.tontine-remittance-form-heading>.material-icons{flex:none;color:var(--app-primary,#811515);font-size:24px}.tontine-remittance-form-heading h2{margin:0 0 3px}.tontine-remittance-form-heading p{margin:0;color:var(--app-muted,#778397);font-size:13px;line-height:1.5}
.tontine-remittance-form-card form{display:grid;gap:15px}.tontine-remittance-form-card label{display:grid;gap:6px;margin:0;font-size:12px;font-weight:750}.tontine-remittance-form-card label small{color:var(--app-muted,#778397);font-weight:450}.tontine-remittance-form-card .form-control{min-height:44px}.tontine-remittance-form-card input[readonly]{background:#f5f6f7;color:#293342;font-weight:800}
.tontine-remittance-form-card .tontine-remittance-confirm{display:flex;align-items:flex-start;gap:9px;padding:13px;background:var(--app-primary-soft,#f8f4f4);border:1px solid var(--app-border,#e2e7eb);line-height:1.5}.tontine-remittance-confirm input{flex:none;margin-top:3px;accent-color:var(--app-primary,#811515)}
.tontine-remittance-actions{display:flex;justify-content:flex-end;gap:9px;flex-wrap:wrap}.tontine-remittance-actions .btn{min-height:43px;padding:10px 14px;font-size:12px;font-weight:750;white-space:normal}
@media(max-width:760px){.tontine-remittance-page{padding:12px}.tontine-remittance-heading,.tontine-remittance-summary,.tontine-remittance-form-card{padding:16px}.tontine-remittance-layout{grid-template-columns:1fr}.tontine-remittance-actions{display:grid;grid-template-columns:1fr}.tontine-remittance-actions .btn{width:100%}}
</style>
@endsection
