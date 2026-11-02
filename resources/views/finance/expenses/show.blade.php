@extends('layouts.admin')
@section('content')
@php($user=auth()->user())
@php($canPayGeneral=$user->isAdmin() || $user->isAccountant() || $user->hasRole(\App\Models\User::ROLE_CASHIER))
@php($canPayShop=$expense->stock_location_id && (int) $expense->location?->responsible_user_id === (int) $user->id)
<div class="container-fluid finance-page"><header class="finance-page-heading"><div><a href="{{ route('finance.expenses.index') }}">← Dépenses</a><h1>{{ $expense->reference }}</h1><p>{{ $expense->category_name }} · créée le {{ $expense->created_at?->format('d/m/Y à H:i') }}</p></div><span class="badge badge-info">{{ \App\Support\FinanceDisplayLabels::status($expense->status) }}</span></header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="finance-detail-grid"><section class="finance-panel"><h2>Détails de la demande</h2><dl class="finance-facts"><dt>Bénéficiaire</dt><dd>{{ $expense->beneficiary }}</dd><dt>Montant autorisé demandé</dt><dd>{{ number_format($expense->amount,0,',',' ') }} F</dd><dt>Déjà payé</dt><dd>{{ number_format($expense->paid_amount,0,',',' ') }} F</dd><dt>Reste à payer</dt><dd>{{ number_format(max(0,$expense->amount-$expense->paid_amount),0,',',' ') }} F</dd><dt>Boutique / axe</dt><dd>{{ $expense->location?->name ?? 'Dépense centrale' }}</dd><dt>Demandeur</dt><dd>{{ $expense->requester?->name }}</dd>@if($expense->budget_reference)<dt>Budget / projet</dt><dd>{{ $expense->budget_reference }}</dd>@endif</dl><p class="finance-description">{{ $expense->description }}</p>@if($expense->justification_path)<a href="{{ route('finance.attachments.download',['kind'=>'expense','id'=>$expense->id]) }}">Télécharger le justificatif fourni</a>@elseif($expense->justification_note)<p class="text-warning">Justificatif absent : {{ $expense->justification_note }}</p>@endif</section>
    <section class="finance-panel"><h2>Validations et paiements</h2><div class="finance-timeline">
        @forelse($expense->approvals as $approval)<article><span class="finance-timeline-dot"></span><strong>{{ $approval->stage === 'finance' ? 'Contrôle financier' : 'Décision du DG' }} · {{ $approval->decision === 'approved' ? 'Autorisé' : 'Refusé' }}</strong><small>{{ $approval->decider?->name }} · {{ $approval->decided_at?->format('d/m/Y H:i') }}</small>@if($approval->comment)<p>{{ $approval->comment }}</p>@endif</article>@empty<p class="text-muted">Aucune décision enregistrée pour le moment.</p>@endforelse
        @foreach($expense->payments as $payment)
            <article>
                <span class="finance-timeline-dot finance-timeline-paid"></span>
                <strong>Paiement de {{ number_format($payment->amount,0,',',' ') }} F</strong>
                <small>{{ $payment->payer?->name }} · {{ $payment->paid_at?->format('d/m/Y H:i') }} · {{ \App\Support\FinanceDisplayLabels::custody($payment->custody_account) }}</small>
                @if ($payment->payment_reference)
                    <small>Réf. {{ $payment->payment_reference }}</small>
                @endif
                @if ($payment->proof_path)
                    <a href="{{ route('finance.attachments.download',['kind'=>'payment','id'=>$payment->id]) }}">Justificatif du paiement</a>
                @endif
                <a href="{{ route('finance.expenses.payments.voucher', $payment->id) }}" target="_blank">Imprimer le bon de décaissement</a>
            </article>
        @endforeach
    </div></section></div>

    @if($user->isAccountant() && $expense->status==='awaiting_finance')<section class="finance-panel"><h2>Contrôle du responsable financier</h2><form method="POST" action="{{ route('finance.expenses.finance-decision',$expense) }}">@csrf<label>Commentaire / vérification<textarea class="form-control" name="comment" rows="2" maxlength="2000"></textarea></label><div class="finance-form-actions"><button name="decision" value="approved" class="btn btn-success">Contrôler et autoriser</button><button name="decision" value="rejected" class="btn btn-danger">Refuser avec motif obligatoire</button></div></form></section>@endif

    @if($user->isAdmin() && in_array($expense->status,['awaiting_director','awaiting_finance'],true))<section class="finance-panel"><h2>Décision du DG</h2>@if($expense->amount>50000 && !$expense->finance_reviewed_at)<p class="text-warning">Le contrôle financier est requis avant ta décision.</p>@else<form method="POST" action="{{ route('finance.expenses.director-decision',$expense) }}">@csrf<label>Commentaire<textarea class="form-control" name="comment" rows="2" maxlength="2000"></textarea></label><div class="finance-form-actions"><button name="decision" value="approved" class="btn btn-success">Autoriser la dépense</button><button name="decision" value="rejected" class="btn btn-danger">Refuser avec motif obligatoire</button></div></form>@endif</section>@endif

    @if($expense->staff_advance_type === 'loan')<section class="finance-panel"><h2>Ce dossier est un prêt, pas une dépense</h2><p>Le décaissement sort de la caisse et crée une somme à récupérer. Une retenue mensuelle sera appliquée au salaire selon le plan accepté. Le prêt doit être remis en une seule fois.</p></section>@endif

    @if(in_array($expense->status,['authorized_to_pay','partially_paid'],true) && ($canPayGeneral || $canPayShop))
        <section class="finance-panel">
            <h2>{{ $expense->staff_advance_type === 'loan' ? 'Enregistrer la remise du prêt' : 'Enregistrer le paiement réellement effectué' }}</h2>
                    <p>{{ $expense->staff_advance_type === 'loan' ? 'Enregistre le montant total au moment où il est remis à l’employé. Le dossier crée alors son solde de prêt et son plan de retenue.' : 'La dépense a été autorisée. Utilise ce formulaire au moment où l’argent est effectivement remis au bénéficiaire. Si tu n’as pas encore payé, ne le valide pas. Un paiement partiel est possible ; il reste '.number_format(max(0, $expense->amount - $expense->paid_amount), 0, ',', ' ').' F CFA à payer.' }} Après validation, le système crée un bon de décaissement imprimable dans l’historique ci-dessus.</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('finance.expenses.pay',$expense) }}">@csrf
                <div class="finance-form-grid">
                    <label>{{ $expense->staff_advance_type === 'loan' ? 'Montant total remis (F CFA)' : 'Montant remis maintenant (F CFA)' }}
                        <input class="form-control" type="number" name="amount" min="1" max="{{ $expense->amount-$expense->paid_amount }}" value="{{ $expense->staff_advance_type === 'loan' ? $expense->amount-$expense->paid_amount : '' }}" required>
                        <small>{{ $expense->staff_advance_type === 'loan' ? 'Le prêt doit être versé en une seule fois.' : 'Saisis uniquement la somme remise lors de ce paiement.' }}</small>
                    </label>
                    <label>Caisse d’où sort l’argent
                        <select class="form-control" name="custody_account" required>
                            @if($canPayGeneral)<option value="general_cash">Caisse générale</option>@endif
                            @if($canPayShop)<option value="shop">Espèces de la boutique</option>@endif
                        </select>
                        <small>Choisis la caisse qui a réellement fourni l’argent.</small>
                    </label>
                    <label>Référence du paiement (facultatif)
                        <input class="form-control" name="payment_reference" maxlength="120" placeholder="N° de reçu ou référence de transaction">
                    </label>
                    <label>Pièce complémentaire (facultative)
                        <input class="form-control-file" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png">
                        <small>Le bon de décaissement sera généré automatiquement ; ajoute ici une autre preuve si tu en as une.</small>
                    </label>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Confirmer le décaissement</button>
            </form>
        </section>
    @endif
</div>
<style>.finance-page{max-width:1300px;padding:18px 22px 40px}.finance-page-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.finance-page-heading h1{font-weight:800;margin:8px 0}.finance-page-heading p{color:#758194;margin:0}.finance-page-heading .badge{text-transform:capitalize;padding:9px 12px}.finance-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.finance-panel{padding:22px;background:#fff;border:1px solid #e1e5eb;margin-bottom:16px}.finance-panel h2{font-size:20px;font-weight:800;margin-bottom:17px}.finance-facts{display:grid;grid-template-columns:1fr auto;gap:11px}.finance-facts dt{color:#6f7b8d;font-weight:500}.finance-facts dd{font-weight:800;text-align:right;margin:0}.finance-description{white-space:pre-line;border-top:1px solid #edf0f3;padding-top:14px;margin-top:15px}.finance-timeline article{position:relative;padding:0 0 18px 22px;border-left:2px solid #e1e7e2;margin-left:6px}.finance-timeline article:last-child{border-left-color:transparent}.finance-timeline-dot{position:absolute;left:-7px;top:2px;width:12px;height:12px;border-radius:50%;background:#187b2a}.finance-timeline-paid{background:#5771aa}.finance-timeline strong,.finance-timeline small{display:block}.finance-timeline small{color:#758194;margin-top:4px}.finance-timeline p{margin:6px 0;color:#4c5868}.finance-form-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.finance-panel label{font-weight:700}.finance-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}@media(max-width:800px){.finance-detail-grid{grid-template-columns:1fr}}@media(max-width:600px){.finance-page{padding:10px}.finance-panel{padding:15px}.finance-form-grid{grid-template-columns:1fr}.finance-page-heading{align-items:flex-start;gap:10px}}</style>
@endsection
