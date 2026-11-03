@extends('layouts.admin')
@section('content')
@php($user=auth()->user())
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

    @if($expense->receipt_paths)
        <section class="finance-panel receipt-panel"><h2>Reçus d’achat transmis</h2><p class="text-muted">Dernier envoi : {{ $expense->receipts_submitted_at?->format('d/m/Y à H:i') }}</p><div class="receipt-links">@foreach($expense->receipt_paths as $index => $path)<a href="{{ route('finance.attachments.download',['kind'=>'expense-receipt','id'=>$expense->id,'file'=>$index]) }}" target="_blank"><span class="material-icons">description</span>Reçu {{ $index + 1 }}</a>@endforeach</div></section>
    @endif

    @if($canSubmitReceipt && (int) $expense->paid_amount > 0)
        @php($canSubmitForAnother = (int) $expense->requested_by !== (int) $user->id)
        <section id="receipts-upload" class="finance-panel receipt-panel"><h2>{{ $canSubmitForAnother ? 'Ajouter les reçus pour le demandeur' : 'Transmettre les reçus après l’achat' }}</h2><p>@if($canSubmitForAnother)Le demandeur reste chargé de transmettre les reçus. Tu peux les ajouter ici si tu les as reçus directement.@elseTu as reçu les fonds : joins ici les photos ou PDF des reçus pour que la Direction puisse vérifier l’utilisation de l’argent.@endif</p><form method="POST" enctype="multipart/form-data" action="{{ route('finance.expenses.receipts.store',$expense) }}">@csrf<label>Photos ou PDF des reçus<input class="form-control-file" type="file" name="receipts[]" accept=".pdf,.jpg,.jpeg,.png" multiple required><small>Jusqu’à 8 fichiers de 10 Mo. Tu peux revenir ajouter d’autres reçus si nécessaire.</small></label><button class="btn btn-primary mt-3" type="submit">{{ $canSubmitForAnother ? 'Enregistrer les reçus' : 'Envoyer les reçus à la Direction' }}</button></form></section>
    @endif

    @if($user->isAccountant() && $expense->status==='awaiting_finance')<section class="finance-panel"><h2>Contrôle du responsable financier</h2><form method="POST" action="{{ route('finance.expenses.finance-decision',$expense) }}">@csrf<label>Commentaire / vérification<textarea class="form-control" name="comment" rows="2" maxlength="2000"></textarea></label><div class="finance-form-actions"><button name="decision" value="approved" class="btn btn-success">Contrôler et autoriser</button><button name="decision" value="rejected" class="btn btn-danger">Refuser avec motif obligatoire</button></div></form></section>@endif

    @if($user->isAdmin() && in_array($expense->status,['awaiting_director','awaiting_finance'],true))<section class="finance-panel"><h2>Décision du DG</h2>@if($expense->amount>50000 && !$expense->finance_reviewed_at)<p class="text-warning">Le contrôle financier est requis avant ta décision.</p>@else<form method="POST" action="{{ route('finance.expenses.director-decision',$expense) }}">@csrf<label>Commentaire<textarea class="form-control" name="comment" rows="2" maxlength="2000"></textarea></label><div class="finance-form-actions"><button name="decision" value="approved" class="btn btn-success">Autoriser la dépense</button><button name="decision" value="rejected" class="btn btn-danger">Refuser avec motif obligatoire</button></div></form>@endif</section>@endif

    @if($expense->staff_advance_type === 'loan')<section class="finance-panel"><h2>Ce dossier est un prêt, pas une dépense</h2><p>Le décaissement sort de la caisse et crée une somme à récupérer. Une retenue mensuelle sera appliquée au salaire selon le plan accepté. Le prêt doit être remis en une seule fois.</p></section>@endif

    @if(in_array($expense->status,['authorized_to_pay','partially_paid'],true) && $canPayExpense)
        <section class="finance-panel">
            <h2>{{ $expense->staff_advance_type === 'loan' ? 'Enregistrer la remise du prêt' : 'Enregistrer le paiement réellement effectué' }}</h2>
            <p>Cette demande a été validée. Remets les fonds uniquement lorsque tu les donnes réellement au bénéficiaire. Le décaissement sera enregistré dans la caisse générale.</p>
            <form method="POST" action="{{ route('finance.expenses.pay',$expense) }}">@csrf
                <div class="finance-form-grid">
                    <label>{{ $expense->staff_advance_type === 'loan' ? 'Montant total remis (F CFA)' : 'Montant remis maintenant (F CFA)' }}
                        <input class="form-control" type="number" name="amount" min="1" max="{{ $expense->amount-$expense->paid_amount }}" value="{{ $expense->staff_advance_type === 'loan' ? $expense->amount-$expense->paid_amount : '' }}" required>
                        <small>{{ $expense->staff_advance_type === 'loan' ? 'Le prêt doit être versé en une seule fois.' : 'Saisis uniquement la somme remise lors de ce paiement.' }}</small>
                    </label>
                    <label>Référence du paiement (facultatif)
                        <input class="form-control" name="payment_reference" maxlength="120" placeholder="N° de reçu ou référence de transaction">
                    </label>
                </div>
                <div class="cashier-cash-source"><span class="material-icons">account_balance</span>Sortie enregistrée sur la caisse générale</div>
                <button class="btn btn-primary mt-3" type="submit">Confirmer la remise des fonds</button>
            </form>
        </section>
    @endif
    @if(in_array($expense->status,['authorized_to_pay','partially_paid'],true) && !$canPayExpense && (int) $expense->requested_by === (int) $user->id)
        <div class="cashier-notice"><span class="material-icons">hourglass_top</span><span>La demande est autorisée et attend le responsable de la caisse générale. Tu n’as aucune remise d’argent à enregistrer ici. Après réception des fonds et l’achat, tu pourras envoyer les reçus dans cette page.</span></div>
    @endif
</div>
<style>.finance-page{max-width:1300px;padding:18px 22px 40px}.finance-page-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.finance-page-heading h1{font-weight:800;margin:8px 0}.finance-page-heading p{color:#758194;margin:0}.finance-page-heading .badge{text-transform:capitalize;padding:9px 12px}.finance-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.finance-panel{padding:22px;background:#fff;border:1px solid #e1e5eb;margin-bottom:16px}.finance-panel h2{font-size:20px;font-weight:800;margin-bottom:17px}.finance-facts{display:grid;grid-template-columns:1fr auto;gap:11px}.finance-facts dt{color:#6f7b8d;font-weight:500}.finance-facts dd{font-weight:800;text-align:right;margin:0}.finance-description{white-space:pre-line;border-top:1px solid #edf0f3;padding-top:14px;margin-top:15px}.finance-timeline article{position:relative;padding:0 0 18px 22px;border-left:2px solid #e1e7e2;margin-left:6px}.finance-timeline article:last-child{border-left-color:transparent}.finance-timeline-dot{position:absolute;left:-7px;top:2px;width:12px;height:12px;border-radius:50%;background:#187b2a}.finance-timeline-paid{background:#5771aa}.finance-timeline strong,.finance-timeline small{display:block}.finance-timeline small{color:#758194;margin-top:4px}.finance-timeline p{margin:6px 0;color:#4c5868}.finance-form-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.finance-panel label{font-weight:700}.finance-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}@media(max-width:800px){.finance-detail-grid{grid-template-columns:1fr}}@media(max-width:600px){.finance-page{padding:10px}.finance-panel{padding:15px}.finance-form-grid{grid-template-columns:1fr}.finance-page-heading{align-items:flex-start;gap:10px}}</style>
<style>.receipt-panel p{color:#687486;line-height:1.55}.receipt-panel label{display:block}.receipt-panel small{display:block;color:#758194;font-weight:400;margin-top:7px}.receipt-links{display:flex;flex-wrap:wrap;gap:9px}.receipt-links a{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;background:#f3f7f4;color:#187b2a;font-weight:700;text-decoration:none}.receipt-links .material-icons{font-size:18px}.cashier-cash-source,.cashier-notice{display:flex;align-items:center;gap:10px;padding:13px 15px;margin-top:15px;background:#eef7ef;color:#315f39}.cashier-cash-source .material-icons,.cashier-notice .material-icons{color:#187b2a}.cashier-notice{margin:0 0 16px}</style>
@endsection
