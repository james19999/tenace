@extends('layouts.admin')
@section('content')
<main class="tontine-detail-page">
    <header class="tontine-detail-heading"><div><a href="{{ route('finance.tontines.index') }}">← Tontines</a><span class="tontine-reference">{{ $tontine->reference }} · {{ $tontine->status === 'recovered' ? 'Récupérée' : 'En cours' }}</span><h1>{{ $tontine->name }}</h1><p>{{ $isGeneralCashier ? 'Enregistre la cotisation lorsque tu remets réellement les fonds depuis la caisse générale.' : 'Indique le montant à cotiser : la demande part directement au caissier général, sans validation.' }}</p>@if($canManage)<a class="btn btn-sm btn-outline-primary" href="{{ route('finance.tontines.edit', $tontine) }}">Modifier la tontine</a>@endif</div></header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($canManage)<section class="tontine-detail-stats"><article><span>Total cotisé</span><strong>{{ number_format($contributed, 0, ',', ' ') }} F</strong></article><article><span>Total récupéré</span><strong>{{ number_format($recovered, 0, ',', ' ') }} F</strong></article><article><span>Disponible à récupérer</span><strong>{{ number_format($recoveryRemaining, 0, ',', ' ') }} F</strong></article><article><span>Cotisation cette semaine</span><strong>{{ $tontine->status !== 'active' ? 'Terminée' : ($contributionThisWeek ? 'Enregistrée' : 'À enregistrer') }}</strong></article></section>@endif

    <section class="tontine-detail-grid {{ $canManage ? '' : 'tontine-assignee-info' }}">
        <article class="tontine-detail-card"><span class="material-icons">account_balance_wallet</span><div><strong>Caisse utilisée</strong><p>Caisse générale TENACOS</p></div></article>
        @if($canManage)
            <article class="tontine-detail-card"><span class="material-icons">groups</span><div><strong>Personnes chargées</strong><p>{{ $tontine->assignees->pluck('name')->join(', ') }}</p></div></article>
            <article class="tontine-detail-card"><span class="material-icons">event</span><div><strong>Première cotisation</strong><p>{{ $tontine->first_contribution_on?->format('d/m/Y') ?? 'En attente du premier paiement' }}</p></div></article>
            <article class="tontine-detail-card"><span class="material-icons">person</span><div><strong>Créée par</strong><p>{{ $tontine->creator?->name ?? '—' }}</p></div></article>
        @else
            <article class="tontine-detail-card"><span class="material-icons">event</span><div><strong>Cotisation cette semaine</strong><p>{{ $tontine->status !== 'active' ? 'Tontine terminée' : ($contributionThisWeek ? 'Déjà enregistrée' : 'À enregistrer le jour du paiement') }}</p></div></article>
        @endif
    </section>
    @if($canManage && $tontine->notes)<section class="tontine-detail-note"><strong>Note</strong><p>{{ $tontine->notes }}</p></section>@endif

    <section class="tontine-actions-grid">
        @if($canContribute)
            <article class="tontine-action-card">
                <span class="tontine-action-kicker">COTISATION HEBDOMADAIRE</span>
                <h2>{{ $isGeneralCashier ? 'Enregistrer la cotisation remise' : 'Demander une cotisation' }}</h2>
                <p>
                    @if($isGeneralCashier)
                        Enregistre le montant lorsque tu remets les fonds. La sortie sera prise sur la caisse générale.
                    @else
                        Indique le montant à verser. La demande sera envoyée directement au caissier général, sans validation intermédiaire.
                    @endif
                </p>
                <form method="POST" enctype="multipart/form-data" action="{{ route('finance.tontines.contributions.store', $tontine) }}">
                    @csrf
                    @if($isGeneralCashier)
                        <input type="hidden" name="submission_key" value="{{ $contributionSubmissionKey }}">
                    @endif
                    <label>Montant à cotiser (F CFA)<input class="form-control" type="number" name="amount" min="1" max="1000000000" value="{{ old('amount') }}" required></label>
                    @if($isGeneralCashier)
                        <label>Date du paiement<input class="form-control" type="date" name="occurred_at" value="{{ today()->format('Y-m-d') }}" min="{{ today()->startOfWeek()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required><small>La date du jour est proposée automatiquement.</small></label>
                        <label>Preuve (facultatif)<input class="form-control-file" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png"><small>PDF ou image · 10 Mo maximum</small></label>
                    @endif
                    <label>Note (facultatif)<textarea class="form-control" name="note" rows="2" maxlength="2000"></textarea></label>
                    <button class="btn btn-primary" type="submit">{{ $isGeneralCashier ? 'Enregistrer le paiement' : 'Envoyer au caissier général' }}</button>
                </form>
            </article>
        @elseif($pendingContributionRequest)
            <article class="tontine-action-card tontine-info-card"><h2>Demande envoyée au caissier</h2><p>{{ number_format($pendingContributionRequest->amount, 0, ',', ' ') }} F · {{ $pendingContributionRequest->reference }}. Le paiement sera enregistré dès que le caissier général remettra les fonds.</p></article>
        @elseif($hasPendingContributionRequest)
            <article class="tontine-action-card tontine-info-card"><h2>Une demande attend le caissier</h2><p>Une cotisation a déjà été demandée pour cette semaine. Le caissier général doit la remettre avant une nouvelle demande.</p></article>
        @elseif($contributionThisWeek)
            <article class="tontine-action-card tontine-info-card"><h2>Cotisation de cette semaine enregistrée</h2><p>La prochaine cotisation pourra être demandée la semaine prochaine.</p></article>
        @endif

        @if($canManage && $recoveryRemaining > 0)
            <article class="tontine-action-card tontine-recovery-card"><span class="tontine-action-kicker">ARGENT REÇU</span><h2>Enregistrer une récupération</h2><p>Enregistre ce que TENACOS reçoit réellement. Tu peux récupérer tout ou partie du solde cotisé disponible.</p><form method="POST" enctype="multipart/form-data" action="{{ route('finance.tontines.recoveries.store', $tontine) }}">@csrf<input type="hidden" name="submission_key" value="{{ $recoverySubmissionKey }}"><label>Montant reçu maintenant (F CFA)<input class="form-control" type="number" name="amount" min="1" max="{{ $recoveryRemaining }}" value="{{ $recoveryRemaining }}" required></label><small>Maximum disponible : {{ number_format($recoveryRemaining, 0, ',', ' ') }} F.</small><label>Date de réception<input class="form-control" type="date" name="occurred_at" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required></label><label>Preuve de réception *<input class="form-control-file" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required><small>Joins le reçu ou une preuve du versement reçu.</small></label><label>Note (facultatif)<textarea class="form-control" name="note" rows="2" maxlength="2000"></textarea></label><button class="btn btn-primary" type="submit">Confirmer l’argent reçu</button></form></article>
        @endif
    </section>

    <section class="tontine-history-card"><div class="tontine-history-heading"><div><h2>{{ $canManage ? 'Historique complet' : 'Mes cotisations enregistrées' }}</h2><p>{{ $canManage ? 'Cotisations et récupérations réellement enregistrées dans la caisse.' : 'Retrouve ici les versements que tu as déclarés.' }}</p></div><span>{{ $displayMovements->count() }} mouvement{{ $displayMovements->count() === 1 ? '' : 's' }}</span></div><div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Opération</th><th>Montant</th>@if($canManage)<th>Enregistré par</th>@endif<th>Preuve / note</th></tr></thead><tbody>
        @forelse($displayMovements as $movement)
            <tr><td>{{ $movement->occurred_at->format('d/m/Y') }}<small>{{ $movement->reference }}</small></td><td><span class="tontine-movement-type {{ $movement->type === 'recovery' ? 'is-recovery' : '' }}">{{ $movement->type === 'contribution' ? 'Cotisation payée' : 'Somme récupérée' }}</span></td><td><strong>{{ number_format($movement->amount, 0, ',', ' ') }} F</strong></td>@if($canManage)<td>{{ $movement->creator?->name ?? '—' }}</td>@endif<td>@if($movement->proof_path)<a href="{{ route('finance.tontines.attachments.download', $movement) }}">Voir la preuve</a>@endif @if($movement->note)<small>{{ $movement->note }}</small>@endif @if(!$movement->proof_path && !$movement->note)—@endif</td></tr>
        @empty<tr><td colspan="{{ $canManage ? 5 : 4 }}" class="tontine-no-movements">{{ $canManage ? 'Aucun versement enregistré pour le moment.' : 'Tu n’as encore enregistré aucun versement.' }}</td></tr>@endforelse
        </tbody></table></div></section>
</main>
<style>
.tontine-detail-page{max-width:1450px;padding:22px 24px 44px;color:var(--app-text,#273142)}.tontine-detail-heading{padding:19px 22px;margin-bottom:14px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.tontine-detail-heading>a{display:inline-block;margin-bottom:9px;color:var(--app-primary,#187b2a);font-size:13px;font-weight:750;text-decoration:none}.tontine-reference{display:block;color:var(--app-muted,#778397);font-size:11px;font-weight:800;letter-spacing:.06em}.tontine-detail-heading h1{margin:5px 0;font-size:clamp(25px,2.4vw,33px);font-weight:850}.tontine-detail-heading p{margin:0;color:var(--app-muted,#778397)}.tontine-detail-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;margin-bottom:14px}.tontine-detail-stats article{padding:15px 17px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.tontine-detail-stats span{display:block;color:var(--app-muted,#778397);font-size:12px;font-weight:700}.tontine-detail-stats strong{display:block;margin-top:6px;font-size:clamp(19px,2vw,27px);font-weight:850;overflow-wrap:anywhere}.tontine-detail-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}.tontine-detail-card{display:flex;align-items:flex-start;gap:10px;min-width:0;padding:13px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.tontine-detail-card>.material-icons{color:var(--app-primary,#187b2a)}.tontine-detail-card strong{display:block;font-size:12px}.tontine-detail-card p{margin:4px 0 0;color:var(--app-muted,#778397);font-size:12px;overflow-wrap:anywhere}.tontine-detail-note{margin:12px 0;padding:13px;background:var(--app-primary-soft,#f4f8f4);border:1px solid var(--app-border,#e2e7eb)}.tontine-detail-note p{margin:5px 0 0;white-space:pre-line}.tontine-actions-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-bottom:15px}.tontine-action-card{min-width:0;padding:18px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.tontine-action-kicker{color:var(--app-primary,#187b2a);font-size:10px;font-weight:850;letter-spacing:.09em}.tontine-action-card h2{margin:5px 0;font-size:19px;font-weight:820}.tontine-action-card>p{color:var(--app-muted,#778397);font-size:13px;line-height:1.45}.tontine-action-card form{display:grid;gap:11px}.tontine-action-card label{display:block;margin:0;font-size:12px;font-weight:750}.tontine-action-card label small,.tontine-action-card form>small{display:block;margin-top:4px;color:var(--app-muted,#778397);font-weight:450}.tontine-action-card .btn{justify-self:start}.tontine-recovery-card{border-top:3px solid #197b2a}.tontine-info-card{background:var(--app-primary-soft,#f5f8f5)}.tontine-history-card{padding:18px 20px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.tontine-history-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:13px}.tontine-history-heading h2{margin:0 0 4px;font-size:19px;font-weight:820}.tontine-history-heading p{margin:0;color:var(--app-muted,#778397);font-size:13px}.tontine-history-heading>span{flex:none;padding:5px 9px;border:1px solid var(--app-border,#e2e7eb);border-radius:20px;color:var(--app-muted,#778397);font-size:11px}.tontine-history-card .table{min-width:740px;margin:0}.tontine-history-card thead th{padding:11px;background:var(--app-primary-soft,#f4f6f8);border:0;color:var(--app-muted,#687486);font-size:10px;font-weight:850;text-transform:uppercase;white-space:nowrap}.tontine-history-card tbody td{padding:12px;border-top:1px solid var(--app-border,#edf0f3);vertical-align:middle}.tontine-history-card td small{display:block;margin-top:3px;color:var(--app-muted,#778397);font-size:11px}.tontine-history-card td a{color:var(--app-primary,#187b2a);font-size:12px;font-weight:750;white-space:nowrap}.tontine-movement-type{display:inline-block;padding:5px 8px;border-radius:20px;background:#fff4df;color:#90600a;font-size:11px;font-weight:800}.tontine-movement-type.is-recovery{background:#e8f5eb;color:#24733a}.tontine-no-movements{text-align:center;padding:28px!important;color:var(--app-muted,#778397)}
.tontine-detail-grid.tontine-assignee-info{grid-template-columns:repeat(2,minmax(0,1fr))}@media(max-width:950px){.tontine-detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.tontine-detail-page{padding:12px}.tontine-detail-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.tontine-detail-stats article{padding:12px}.tontine-actions-grid{grid-template-columns:1fr}.tontine-history-card{padding:13px}.tontine-action-card .btn{width:100%}}@media(max-width:450px){.tontine-detail-grid{grid-template-columns:1fr 1fr}.tontine-history-heading{flex-direction:column}}
</style>
@endsection
