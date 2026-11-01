@extends('layouts.admin')

@section('content')
<main class="employee-self-service">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <header class="ess-heading"><div><span>MON ESPACE PERSONNEL</span><h1>{{ $activePage === 'purchases' ? 'Achats de matériel' : 'Bonjour '.$employee->name }}</h1><p>{{ $activePage === 'purchases' ? 'Envoie les besoins d’achat au DG, puis joins les reçus après avoir reçu les fonds.' : 'Ton salaire, tes demandes et tes remboursements réunis au même endroit.' }}</p></div><div class="ess-heading-actions"><small><span class="material-icons" aria-hidden="true">storefront</span>{{ $employeeShopLocation?->name ?? 'Boutique non renseignée' }}</small>@if($activePage === 'payroll')<a class="ess-primary-link" href="#ess-new-advance"><span class="material-icons" aria-hidden="true">add</span>Faire une demande</a>@endif</div></header>

    @if($activePage==='payroll')
    <section class="ess-summary" aria-label="Résumé de ta situation">
        <article class="ess-summary-salary"><span class="ess-summary-icon material-icons" aria-hidden="true">payments</span><div><span>Salaire mensuel convenu</span><strong>{{ $profile->monthly_salary ? number_format($profile->monthly_salary,0,',',' ').' F CFA' : 'À renseigner' }}</strong><small>@if($profile->salary_effective_from)Applicable depuis {{ $profile->salary_effective_from->format('m/Y') }}@else Demande à l’administration de renseigner ton salaire @endif</small></div></article>
        <article class="ess-summary-available"><span class="ess-summary-icon material-icons" aria-hidden="true">account_balance_wallet</span><div><span>Avance disponible ce mois</span><strong>{{ number_format($availableForAdvance,0,',',' ') }} F CFA</strong><small>Après déduction du salaire payé et des avances déjà demandées ou versées.</small></div></article>
    </section>

    <section class="ess-card ess-request-card" id="ess-new-advance"><div class="ess-card-head"><span class="ess-section-icon material-icons" aria-hidden="true">request_quote</span><div><span class="ess-section-kicker">BESOIN D’ARGENT</span><h2>Demander une avance ou un prêt</h2><p>Choisis le type de demande et indique le montant. La Direction te répondra ici.</p></div></div>
        <form class="ess-request-form" method="POST" action="{{ route('employees.self-service.advances.store') }}">@csrf
            <label>Type de demande<select class="form-control" name="advance_type" id="ess-advance-type" required><option value="salary_advance">Avance sur salaire</option><option value="loan">Prêt à rembourser séparément</option></select></label>
            <label>Montant demandé (F CFA)<input class="form-control" id="ess-advance-amount" data-salary-max="{{ $availableForAdvance }}" type="number" name="amount" min="1" max="{{ $availableForAdvance ?: '' }}" step="1" required><small id="ess-advance-cap">@if($profile->monthly_salary && $profile->salary_effective_from)Maximum sur salaire : {{ number_format($availableForAdvance,0,',',' ') }} F CFA.@elseLe salaire n’est pas encore renseigné ; une avance sur salaire ne pourra pas être demandée.@endif</small></label>
            <label>Motif<textarea class="form-control" name="reason" rows="3" maxlength="2000" required placeholder="Explique brièvement le motif de ta demande"></textarea></label>
            <button class="btn btn-primary">Envoyer ma demande</button>
        </form>
        <script>
            (function(){const type=document.getElementById('ess-advance-type'),amount=document.getElementById('ess-advance-amount'),note=document.getElementById('ess-advance-cap');if(!type||!amount||!note)return;const salaryMax=Number(amount.dataset.salaryMax||0);const update=()=>{if(type.value==='loan'){amount.removeAttribute('max');note.textContent='Le prêt sera suivi séparément et devra être remboursé.';}else{if(salaryMax>0)amount.max=String(salaryMax);else amount.removeAttribute('max');note.textContent=salaryMax>0?'Maximum sur salaire : '+new Intl.NumberFormat('fr-FR').format(salaryMax)+' F CFA.':'Ton salaire n’est pas renseigné ou aucun montant n’est disponible pour une avance sur salaire.';}};type.addEventListener('change',update);update();})();
        </script>
    </section>

    <div class="ess-records-grid">
    <section class="ess-card ess-record-card"><div class="ess-card-head"><span class="ess-section-icon material-icons" aria-hidden="true">history</span><div><span class="ess-section-kicker">SUIVI</span><h2>Mes demandes</h2><p>Avances et prêts soumis à la Direction.</p></div></div>
        <div class="ess-request-list">
            @forelse($advanceRequests as $advanceRequest)
                @php
                    $statusLabel = match ($advanceRequest->status) {
                        'awaiting_finance' => 'En attente de décision',
                        'awaiting_director' => 'En attente du DG',
                        'authorized_to_pay' => 'Acceptée · argent à récupérer',
                        'partially_paid' => 'Partiellement remise',
                        'paid' => 'Payée',
                        'rejected' => 'Refusée',
                        'cancelled' => 'Annulée',
                        default => 'En cours',
                    };
                @endphp
                <article><div><strong>{{ $advanceRequest->reference }} · {{ $advanceRequest->staff_advance_type === 'loan' ? 'Prêt' : 'Avance sur salaire' }}</strong><span>{{ $advanceRequest->description }}</span><small>Demandée le {{ $advanceRequest->submitted_at?->format('d/m/Y') ?? $advanceRequest->created_at->format('d/m/Y') }} · demandé : {{ number_format($advanceRequest->amount,0,',',' ') }} F CFA · déjà remis : {{ number_format($advanceRequest->paid_amount,0,',',' ') }} F CFA</small></div><b class="ess-status ess-status-{{ $advanceRequest->status }}">{{ $statusLabel }}</b></article>
                @if($advanceRequest->finance_comment)<p class="ess-comment">Commentaire : {{ $advanceRequest->finance_comment }}</p>@endif
                @if($advanceRequest->director_comment)<p class="ess-comment">Décision de la Direction : {{ $advanceRequest->director_comment }}</p>@endif
            @empty<p class="ess-empty">Tu n’as pas encore envoyé de demande d’avance.</p>@endforelse
        </div>
    </section>

    <section class="ess-card ess-record-card"><div class="ess-card-head"><span class="ess-section-icon material-icons" aria-hidden="true">account_balance</span><div><span class="ess-section-kicker">REMBOURSEMENTS</span><h2>Mes prêts</h2><p>Montant reçu, remboursements et solde restant.</p></div></div>
        @forelse($personalLoans as $loan)<article class="ess-loan"><div><strong>{{ $loan->reference }}</strong><span>{{ $loan->reason ?: 'Prêt au personnel' }} · accordé le {{ $loan->granted_on?->format('d/m/Y') }}</span>@foreach($loan->repayments as $repayment)<small>Remboursement du {{ $repayment->received_at->format('d/m/Y') }} : {{ number_format($repayment->amount,0,',',' ') }} F CFA</small>@endforeach</div><div class="ess-loan-balance"><small>Montant reçu</small><strong>{{ number_format($loan->amount,0,',',' ') }} F</strong><small>Remboursé : {{ number_format($loan->repaid_amount,0,',',' ') }} F · reste dû</small><strong>{{ number_format(max(0,$loan->amount-$loan->repaid_amount),0,',',' ') }} F</strong></div></article>
        @empty<p class="ess-empty">Aucun prêt à rembourser n’est enregistré dans ton dossier.</p>@endforelse
    </section>
    </div>

    <section class="ess-card ess-salary-card"><div class="ess-card-head"><span class="ess-section-icon material-icons" aria-hidden="true">calendar_month</span><div><span class="ess-section-kicker">HISTORIQUE</span><h2>Suivi des salaires</h2><p>Salaires versés, avances prises et montant restant pour chaque mois.</p></div></div>
        <div class="table-responsive"><table class="table ess-table"><thead><tr><th>Mois</th><th>Salaire convenu</th><th>Avances payées</th><th>Salaire versé</th><th>Reste à verser</th></tr></thead><tbody>
            @forelse($periods as $period)
                @php
                    $salaryPaid = (int) $period->payments->where('kind', 'salary')->sum('amount');
                    $advancePaid = (int) $period->staffAdvances->sum('amount') + (int) $period->payments->where('kind', 'advance')->sum('amount');
                @endphp
                <tr><td>{{ $period->period_start->translatedFormat('F Y') }}</td><td>{{ number_format($period->salary_amount,0,',',' ') }} F</td><td>{{ number_format($advancePaid,0,',',' ') }} F</td><td>{{ number_format($salaryPaid,0,',',' ') }} F</td><td><strong>{{ number_format(max(0,$period->salary_amount-$salaryPaid-$advancePaid),0,',',' ') }} F</strong></td></tr>
            @empty<tr><td colspan="5" class="ess-empty">Le suivi des salaires sera affiché dès qu’un mois aura été enregistré.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    @else
    <div class="ess-material-page">
        @php
            $materialCurrentStep = 1;
            $materialStepMessage = 'Commence par décrire le prochain besoin d’achat.';
            if ($purchaseRequests->contains(fn ($item) => $item->status === 'funds_issued')) {
                $materialCurrentStep = 3;
                $materialStepMessage = 'Des fonds ont été remis : termine l’achat et transmets les reçus.';
            } elseif ($purchaseRequests->contains(fn ($item) => $item->status === 'assigned')) {
                $materialCurrentStep = 1;
                $materialStepMessage = 'Une demande doit être complétée avant de pouvoir être validée.';
            } elseif ($purchaseRequests->contains(fn ($item) => $item->status === 'awaiting_director')) {
                $materialCurrentStep = 2;
                $materialStepMessage = 'La demande a été envoyée. Le DG doit valider et remettre les fonds.';
            } elseif ($purchaseRequests->contains(fn ($item) => $item->status === 'submitted_for_review')) {
                $materialCurrentStep = 3;
                $materialStepMessage = 'Les reçus sont transmis ; le DG procède au contrôle final.';
            } elseif (! $canCreatePurchases && $purchaseRequests->isNotEmpty() && $purchaseRequests->every(fn ($item) => $item->status === 'closed')) {
                $materialCurrentStep = 4;
                $materialStepMessage = 'Tous les achats transmis sont contrôlés et clôturés.';
            }
        @endphp
        <div class="ess-material-progress" role="status"><span class="material-icons">{{ $materialCurrentStep === 4 ? 'task_alt' : 'my_location' }}</span><div><strong>{{ $materialCurrentStep === 4 ? 'Parcours terminé' : 'Étape '.$materialCurrentStep.' en cours' }}</strong><p>{{ $materialStepMessage }}</p></div></div>
        @if($canCreatePurchases)
            <a class="ess-create-purchase-button" href="#ess-new-purchase-form"><span class="material-icons ess-create-purchase-icon" aria-hidden="true">add</span><span class="ess-create-purchase-label">Créer un achat</span></a>
        @else
            @if($purchaseSetting?->assignee)
                <div class="ess-material-access-note"><span class="material-icons">info</span><p>Tu peux consulter les dossiers déjà confiés. La création des demandes est réservée au responsable désigné : <strong>{{ $purchaseSetting->assignee->name }}</strong>.</p></div>
            @else
                <div class="ess-material-access-note"><span class="material-icons">info</span><p>Tu peux consulter les dossiers déjà confiés. La création des demandes est réservée au responsable désigné par le DG.</p></div>
            @endif
        @endif
        <section class="ess-material-steps" aria-label="Étapes d’un achat de matériel">
            <article class="{{ $materialCurrentStep === 1 ? 'is-current' : ($materialCurrentStep > 1 ? 'is-complete' : '') }}" @if($materialCurrentStep === 1) aria-current="step" @endif><span class="ess-step-number">@if($materialCurrentStep > 1)<span class="material-icons">done</span>@else 1 @endif</span><div><strong>Décris le besoin</strong><small>Indique les articles et le montant estimé.</small></div>@if($materialCurrentStep === 1)<em>En cours</em>@endif</article>
            <article class="{{ $materialCurrentStep === 2 ? 'is-current' : ($materialCurrentStep > 2 ? 'is-complete' : '') }}" @if($materialCurrentStep === 2) aria-current="step" @endif><span class="ess-step-number">@if($materialCurrentStep > 2)<span class="material-icons">done</span>@else 2 @endif</span><div><strong>Validation du DG</strong><small>Les fonds sont remis après validation.</small></div>@if($materialCurrentStep === 2)<em>En cours</em>@endif</article>
            <article class="{{ $materialCurrentStep === 3 ? 'is-current' : ($materialCurrentStep > 3 ? 'is-complete' : '') }}" @if($materialCurrentStep === 3) aria-current="step" @endif><span class="ess-step-number">@if($materialCurrentStep > 3)<span class="material-icons">done</span>@else 3 @endif</span><div><strong>Achat et reçus</strong><small>Déclare les dépenses et joins les preuves.</small></div>@if($materialCurrentStep === 3)<em>En cours</em>@endif</article>
        </section>

        @if($canCreatePurchases)
            <section class="ess-material-card ess-material-new" id="ess-new-purchase-form">
                <div class="ess-material-card-heading"><span class="material-icons">add_shopping_cart</span><div><h2>Nouvelle demande d’achat</h2><p>Tu es le responsable désigné. Tu peux envoyer chaque nouveau besoin ici ; le DG validera le montant avant la remise des fonds.</p></div></div>
                <form class="ess-material-form" method="POST" action="{{ route('employees.self-service.purchases.store') }}">@csrf
                    <label>Objet de l’achat<input class="form-control" name="title" maxlength="160" required value="{{ old('title') }}" placeholder="Ex. fournitures pour la boutique"></label>
                    <label>Montant estimé <span>(F CFA)</span><input class="form-control" type="number" name="requested_amount" min="1" required value="{{ old('requested_amount') }}" placeholder="Ex. 25 000"></label>
                    <label class="ess-material-wide">Articles et quantités<textarea class="form-control" name="needed_items" rows="3" maxlength="5000" required placeholder="Ex. 2 chaises, 1 rallonge électrique…">{{ old('needed_items') }}</textarea></label>
                    <div class="ess-material-submit"><small>La demande sera envoyée au DG. Aucun argent ne sera débité avant son accord.</small><button class="btn btn-primary"><span class="material-icons">send</span> Envoyer la demande</button></div>
                </form>
            </section>
        @endif

        <section class="ess-material-card">
            <div class="ess-material-list-heading"><div><h2>Suivi de mes achats</h2><p>Retrouve ici les décisions du DG et les actions à faire.</p></div><span class="ess-count">{{ $purchaseRequests->count() }} dossier{{ $purchaseRequests->count() > 1 ? 's' : '' }}</span></div>
            @if($purchaseRequests->isEmpty())
                <div class="ess-material-empty"><span class="material-icons">inventory_2</span><strong>Aucun dossier pour le moment</strong><p>@if($canCreatePurchases)Tes demandes et leur avancement apparaîtront ici.@elseLe DG ne t’a pas encore confié d’achat de matériel.@endif</p></div>
            @else
            @foreach($purchaseRequests as $purchase)
                @php
                    $purchaseStatus = [
                        'assigned' => ['À compléter', 'pencil'],
                        'awaiting_director' => ['En attente du DG', 'schedule'],
                        'funds_issued' => ['Fonds remis · achat à effectuer', 'payments'],
                        'submitted_for_review' => ['Reçus envoyés · contrôle du DG', 'hourglass_top'],
                        'closed' => ['Achat clôturé', 'task_alt'],
                    ][$purchase->status] ?? ['En cours', 'info'];
                @endphp
                <article class="ess-material-request">
                    <div class="ess-material-request-top"><div><span class="ess-material-reference">{{ $purchase->reference }}</span><h3>{{ $purchase->title }}</h3></div><span class="ess-material-status status-{{ $purchase->status }}"><span class="material-icons">{{ $purchaseStatus[1] }}</span>{{ $purchaseStatus[0] }}</span></div>
                    <div class="ess-material-amounts"><div><small>Montant demandé</small><strong>{{ number_format($purchase->requested_amount,0,',',' ') }} F</strong></div>@if($purchase->cash_issued)<div><small>Argent remis</small><strong>{{ number_format($purchase->cash_issued,0,',',' ') }} F</strong></div>@endif @if($purchase->spent_amount !== null)<div><small>Dépensé · reliquat</small><strong>{{ number_format($purchase->spent_amount,0,',',' ') }} F · {{ number_format($purchase->returned_amount,0,',',' ') }} F</strong></div>@endif</div>
                    @if($purchase->needed_items)<div class="ess-material-detail"><small>Articles prévus</small><p>{{ $purchase->needed_items }}</p></div>@endif
                    @if($purchase->assignment_notes)<div class="ess-material-note"><strong>Consignes</strong><p>{{ $purchase->assignment_notes }}</p></div>@endif
                    @if($purchase->review_note)<div class="ess-material-note"><strong>Message du DG</strong><p>{{ $purchase->review_note }}</p></div>@endif
                    @if($purchase->status==='assigned')
                        <form class="ess-material-action" method="POST" action="{{ route('employees.self-service.purchases.estimate',$purchase) }}">@csrf
                            <label>Articles à acheter<textarea class="form-control" name="needed_items" rows="3" required placeholder="Précise les articles et les quantités">{{ old('needed_items') }}</textarea></label>
                            <label>Montant estimé (F CFA)<input class="form-control" type="number" name="requested_amount" min="1" value="{{ old('requested_amount') }}" required></label>
                            <button class="btn btn-primary">Envoyer au DG</button>
                        </form>
                    @endif
                    @if($purchase->status==='funds_issued')
                        <form class="ess-material-action ess-material-report" method="POST" enctype="multipart/form-data" action="{{ route('employees.self-service.purchases.report',$purchase) }}">@csrf
                            <label>Montant réellement dépensé (F CFA)<input class="form-control" type="number" name="spent_amount" min="0" max="{{ $purchase->cash_issued }}" required></label>
                            <label>Détail utile<textarea class="form-control" name="report_note" rows="2" maxlength="3000" placeholder="Précision facultative sur les achats"></textarea></label>
                            <div class="ess-receipt-choice ess-material-wide">
                                <div class="ess-receipt-option ess-camera-option"><span class="material-icons">photo_camera</span><strong>Prendre une photo</strong><small>La caméra s’ouvrira directement dans cette page.</small><button class="btn btn-outline-primary ess-camera-open" type="button"><span class="material-icons">videocam</span> Ouvrir la caméra</button><div class="ess-camera-panel" hidden><video class="ess-camera-video" playsinline autoplay muted></video><canvas class="ess-camera-canvas" hidden></canvas><div class="ess-camera-controls"><button class="btn btn-primary ess-camera-take" type="button"><span class="material-icons">photo_camera</span> Capturer</button><button class="btn btn-light ess-camera-close" type="button">Fermer</button></div></div><input type="file" name="receipt_photos[]" accept="image/*" multiple class="ess-receipt-camera" hidden><span class="ess-camera-feedback" aria-live="polite"></span></div>
                                <label class="ess-receipt-option"><span class="material-icons">attach_file</span><strong>Choisir des fichiers</strong><small>Tu peux joindre plusieurs photos ou PDF.</small><input type="file" name="receipt_photos[]" accept="image/*,.pdf" multiple class="ess-receipt-files"></label>
                                <small class="ess-receipt-selection" aria-live="polite">Ajoute au moins une photo ou un reçu.</small>
                            </div>
                            <button class="btn btn-primary"><span class="material-icons">upload</span> Envoyer les reçus au DG</button>
                        </form>
                    @endif
                    @if($purchase->receipt_paths)<div class="ess-material-receipts">@foreach($purchase->receipt_paths as $index=>$path)<a href="{{ route('employees.self-service.purchases.receipt',[$purchase,$index]) }}" target="_blank" rel="noopener"><span class="material-icons">description</span> Reçu {{ $loop->iteration }}</a>@endforeach</div>@endif
                </article>
            @endforeach
            @endif
        </section>
    </div>
    @endif
</main>
<style>
.employee-self-service{max-width:1180px;margin:auto;padding:clamp(14px,2vw,28px);color:var(--app-text)}.ess-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}.ess-heading>div>span{font-size:11px;font-weight:850;letter-spacing:.08em;color:var(--app-primary)}.ess-heading h1{margin:5px 0;font-size:clamp(25px,3vw,34px);font-weight:850}.ess-heading p,.ess-card-head p{margin:0;color:var(--app-muted);line-height:1.55}.ess-heading>small{padding:8px 12px;border-radius:999px;background:var(--app-surface);border:1px solid var(--app-border);font-weight:700}.ess-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:14px}.ess-summary article,.ess-card{min-width:0;padding:clamp(15px,2vw,22px);margin-bottom:14px;background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius)}.ess-summary article span,.ess-summary article strong,.ess-summary article small{display:block}.ess-summary article span{font-size:13px;color:var(--app-muted);font-weight:700}.ess-summary article strong{margin:7px 0;color:var(--app-primary);font-size:clamp(21px,3vw,28px)}.ess-summary article small{color:var(--app-muted);line-height:1.5}.ess-card-head{margin-bottom:14px}.ess-card-head h2{margin:0 0 5px;font-size:19px;font-weight:820}.ess-card-head p{font-size:13px}.ess-request-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:end;gap:12px}.ess-request-form label{margin:0;font-size:13px;font-weight:700}.ess-request-form .form-control{margin-top:5px}.ess-request-form small{display:block;margin-top:5px;color:var(--app-muted);font-weight:400}.ess-request-form button{white-space:nowrap;min-height:40px;grid-column:1/-1}.ess-request-form label:nth-child(3){grid-column:1/-1}.ess-empty{margin:0;padding:14px;color:var(--app-muted);background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));border-radius:8px}.ess-request-list article,.ess-purchase,.ess-loan{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:13px 0;border-bottom:1px solid var(--app-border)}.ess-request-list article:last-of-type,.ess-purchase:last-child,.ess-loan:last-child{border-bottom:0}.ess-request-list article div,.ess-purchase div,.ess-loan div{min-width:0;width:100%}.ess-request-list strong,.ess-request-list span,.ess-request-list small,.ess-purchase strong,.ess-purchase span,.ess-purchase small,.ess-loan strong,.ess-loan span,.ess-loan small{display:block}.ess-request-list span,.ess-purchase span,.ess-loan span{margin-top:4px;overflow-wrap:anywhere}.ess-request-list small,.ess-purchase small,.ess-loan small{margin-top:4px;color:var(--app-muted)}.ess-status{flex:none;padding:6px 10px;border-radius:999px;background:var(--app-primary-soft);color:var(--app-primary);font-size:12px}.ess-comment{margin:0 0 8px;color:var(--app-muted);font-size:13px}.ess-table{min-width:650px;margin:0;color:var(--app-text)}.ess-table th{color:var(--app-muted);font-size:11px;text-transform:uppercase;background:color-mix(in srgb,var(--app-primary) 6%,var(--app-surface))}.ess-table td{vertical-align:middle}.ess-purchase>span{flex:none;font-weight:800;color:var(--app-primary)}.ess-purchase-form{display:grid;grid-template-columns:minmax(170px,1fr) minmax(160px,.7fr) auto;align-items:end;gap:10px;margin-top:12px;padding:12px;background:color-mix(in srgb,var(--app-primary) 4%,var(--app-surface));border:1px solid var(--app-border);border-radius:10px}.ess-purchase-form label{margin:0;font-size:12px;font-weight:700}.ess-purchase-form .form-control,.ess-purchase-form .form-control-file{display:block;width:100%;margin-top:5px}.ess-purchase-form button{white-space:nowrap}.ess-report-form{grid-template-columns:repeat(2,minmax(0,1fr))}.ess-report-form label:nth-child(3),.ess-report-form button{grid-column:1/-1}.ess-receipts{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}.ess-receipts a{color:var(--app-primary);font-weight:700}.ess-loan-balance{max-width:230px;padding:10px 14px;background:color-mix(in srgb,var(--app-primary) 5%,var(--app-surface));border-radius:9px}.ess-loan-balance strong{color:var(--app-primary)}
@media(max-width:760px){.ess-request-form{grid-template-columns:1fr 1fr}.ess-request-form label:nth-child(2){grid-column:1/-1}.ess-request-form button{grid-column:1/-1}.ess-purchase-form{grid-template-columns:1fr 1fr}.ess-purchase-form button{grid-column:1/-1}.ess-report-form label:nth-child(3){grid-column:1/-1}}@media(max-width:560px){.employee-self-service{padding:12px 9px}.ess-heading{display:block}.ess-heading>small{display:inline-block;margin-top:10px}.ess-summary{grid-template-columns:1fr}.ess-request-form{grid-template-columns:1fr}.ess-request-form label:nth-child(2),.ess-request-form button{grid-column:auto}.ess-request-list article,.ess-purchase,.ess-loan{align-items:flex-start;flex-direction:column}.ess-purchase-form,.ess-report-form{grid-template-columns:1fr}.ess-report-form label:nth-child(3),.ess-report-form button{grid-column:auto}.ess-loan-balance{max-width:none;width:100%}}

/* Payroll self-service dashboard */
.ess-heading{padding:clamp(18px,3vw,28px);border:1px solid var(--app-border);border-radius:var(--app-radius);background:linear-gradient(115deg,var(--app-surface),color-mix(in srgb,var(--app-primary) 7%,var(--app-surface)));}
.ess-heading h1{letter-spacing:-.035em}.ess-heading-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}.ess-heading-actions>small{display:inline-flex;align-items:center;gap:6px;margin:0}.ess-heading-actions>small .material-icons{font-size:17px;color:var(--app-primary)}.ess-primary-link{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:40px;padding:9px 13px;border-radius:9px;background:var(--app-primary);color:var(--app-primary-contrast)!important;font-size:13px;font-weight:800;text-decoration:none}.ess-primary-link:hover{filter:brightness(.95)}.ess-primary-link .material-icons{font-size:18px}
.ess-summary{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:0 0 16px}.ess-summary article{display:flex;align-items:flex-start;gap:14px;min-height:142px;margin:0;padding:clamp(18px,2.4vw,26px);border-radius:var(--app-radius);box-shadow:0 7px 20px color-mix(in srgb,var(--app-text) 5%,transparent)}.ess-summary article>div{min-width:0}.ess-summary article span:not(.ess-summary-icon){font-size:13px;color:var(--app-muted);font-weight:750}.ess-summary article strong{margin:8px 0 7px;color:var(--app-text);font-size:clamp(23px,3vw,31px);letter-spacing:-.035em;line-height:1.15;overflow-wrap:anywhere}.ess-summary article small{font-size:12px;line-height:1.5}.ess-summary .ess-summary-icon,.ess-section-icon{display:grid;place-items:center;flex:none;width:44px;height:44px;border-radius:12px;background:var(--app-primary-soft);color:var(--app-primary);font-size:22px}.ess-summary-available{border-color:var(--app-primary-border)!important}.ess-summary-available strong{color:var(--app-primary)!important}
.ess-card{padding:clamp(17px,2.3vw,24px);border-radius:var(--app-radius);box-shadow:0 5px 18px color-mix(in srgb,var(--app-text) 4%,transparent)}.ess-request-card{scroll-margin-top:20px}.ess-card-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}.ess-card-head>div{min-width:0}.ess-section-icon{width:40px;height:40px;font-size:20px}.ess-section-kicker{display:block;margin:0 0 4px;color:var(--app-primary);font-size:10px;font-weight:850;letter-spacing:.1em}.ess-card-head h2{margin:0 0 4px;font-size:clamp(17px,2vw,20px);letter-spacing:-.02em}.ess-card-head p{max-width:720px;font-size:12px;line-height:1.5}
.ess-request-form{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}.ess-request-form label{font-size:12px}.ess-request-form .form-control{min-height:43px;border-color:var(--app-border);border-radius:8px;background:var(--app-surface);color:var(--app-text)}.ess-request-form textarea.form-control{min-height:84px}.ess-request-form .form-control:focus{border-color:var(--app-primary);box-shadow:0 0 0 3px var(--app-primary-soft)}.ess-request-form small{font-size:11px;line-height:1.45}.ess-request-form button{grid-column:auto;align-self:end;justify-self:start;min-height:43px;padding:10px 17px;border-radius:8px;font-weight:800}
.ess-records-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);align-items:start;gap:14px}.ess-records-grid .ess-card{height:100%}.ess-request-list article,.ess-loan{align-items:flex-start;padding:14px 0}.ess-request-list article>div,.ess-loan>div:first-child{min-width:0}.ess-request-list strong,.ess-loan strong{font-size:13px;line-height:1.4}.ess-request-list span,.ess-loan span{font-size:12px;line-height:1.5;color:var(--app-muted)}.ess-request-list small,.ess-loan small{font-size:11px;line-height:1.5}.ess-status{max-width:180px;text-align:center;line-height:1.35;font-weight:750}.ess-status-awaiting_finance,.ess-status-awaiting_director{background:#fff3dc;color:#915c09}.ess-status-authorized_to_pay,.ess-status-partially_paid{background:var(--app-primary-soft);color:var(--app-primary)}.ess-status-paid{background:#e8f5ed;color:#237344}.ess-status-rejected,.ess-status-cancelled{background:#fbe9e8;color:#a33e39}.ess-loan-balance{min-width:165px;padding:12px 14px;border:1px solid var(--app-border);background:color-mix(in srgb,var(--app-primary) 5%,var(--app-surface))}.ess-loan-balance small{font-size:10px}.ess-loan-balance strong{font-size:17px;color:var(--app-primary)}.ess-salary-card .table-responsive{border:1px solid var(--app-border);border-radius:9px}.ess-table{min-width:700px}.ess-table th{padding:12px 13px;border:0;font-size:10px;letter-spacing:.06em}.ess-table td{padding:13px;font-size:12px;border-color:var(--app-border)}.ess-table td strong{color:var(--app-primary)}
@media(max-width:900px){.ess-records-grid{grid-template-columns:1fr}.ess-records-grid .ess-card{height:auto}}
@media(max-width:760px){.ess-heading{align-items:flex-start}.ess-heading-actions{justify-content:flex-start}.ess-summary article{min-height:130px}.ess-request-form{grid-template-columns:1fr 1fr}.ess-request-form label:nth-child(2){grid-column:auto}.ess-request-form label:nth-child(3){grid-column:1/-1}.ess-request-form button{grid-column:1/-1}.ess-records-grid{gap:10px}}
@media(max-width:560px){.ess-heading{padding:16px}.ess-heading-actions{justify-content:flex-start}.ess-heading-actions>small{padding:7px 9px;font-size:11px}.ess-primary-link{min-height:38px;padding:8px 10px}.ess-summary{gap:9px}.ess-summary article{min-height:0;padding:16px;gap:11px}.ess-summary .ess-summary-icon{width:38px;height:38px;border-radius:10px;font-size:20px}.ess-summary article strong{font-size:23px}.ess-card{padding:15px;margin-bottom:10px}.ess-card-head{gap:9px;margin-bottom:14px}.ess-section-icon{width:36px;height:36px;font-size:18px}.ess-request-form{grid-template-columns:1fr;gap:12px}.ess-request-form label:nth-child(2),.ess-request-form label:nth-child(3),.ess-request-form button{grid-column:1}.ess-request-form button{width:100%}.ess-request-list article{gap:9px}.ess-status{max-width:none}.ess-loan{gap:10px}.ess-loan-balance{width:100%;max-width:none}.ess-salary-card .ess-card-head{margin-bottom:12px}}
@media(max-width:560px){.ess-heading{display:flex;align-items:flex-start;flex-direction:column;gap:14px}.ess-heading>div:first-child{width:100%}.ess-heading-actions{display:grid;width:100%;grid-template-columns:minmax(0,1fr) auto;align-items:stretch;gap:8px}.ess-heading-actions>small{justify-content:flex-start;min-width:0;overflow-wrap:anywhere}.ess-primary-link{align-self:stretch}.ess-summary{grid-template-columns:minmax(0,1fr)}.ess-summary article{align-items:center}.ess-summary article strong{font-size:clamp(20px,6vw,23px)}.ess-request-list article>div,.ess-loan>div:first-child{width:100%}.ess-status{align-self:flex-start}.ess-loan-balance{display:grid;grid-template-columns:1fr auto;align-items:center;gap:5px 12px}.ess-loan-balance strong{margin:0;text-align:right}.ess-loan-balance small{margin:0}.ess-salary-card .table-responsive{-webkit-overflow-scrolling:touch}.ess-table th:first-child,.ess-table td:first-child{position:sticky;left:0;z-index:1;background:var(--app-surface);box-shadow:1px 0 0 var(--app-border)}}
@media(max-width:380px){.employee-self-service{padding-inline:7px}.ess-heading{padding:14px}.ess-heading-actions{grid-template-columns:1fr}.ess-heading-actions>small,.ess-primary-link{width:100%;justify-content:center}.ess-summary article{padding:13px;gap:9px}.ess-summary .ess-summary-icon{width:34px;height:34px}.ess-summary article strong{font-size:20px}.ess-card{padding:13px}.ess-request-form button{white-space:normal}.ess-table{min-width:640px}}
</style>
<style>
.ess-material-page{display:grid;gap:16px}.ess-material-progress{display:flex;align-items:center;gap:12px;padding:13px 16px;border:1px solid var(--app-primary-border);border-left:4px solid var(--app-primary);border-radius:10px;background:var(--app-primary-soft);color:var(--app-text)}.ess-material-progress>.material-icons{color:var(--app-primary);font-size:22px}.ess-material-progress strong{display:block;font-size:14px}.ess-material-progress p{margin:3px 0 0;color:var(--app-muted);font-size:13px;line-height:1.4}.ess-material-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ess-material-steps article{position:relative;display:flex;align-items:center;gap:12px;min-width:0;padding:16px;background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius);transition:background .18s,border-color .18s,box-shadow .18s}.ess-material-steps article.is-current{background:var(--app-primary);border-color:var(--app-primary);box-shadow:0 8px 20px color-mix(in srgb,var(--app-primary) 20%,transparent);color:var(--app-primary-contrast)}.ess-material-steps article.is-complete{background:var(--app-primary-soft);border-color:var(--app-primary-border)}.ess-step-number{display:grid;place-items:center;flex:none;width:36px;height:36px;border-radius:10px;background:var(--app-primary-soft);color:var(--app-primary);font-weight:850}.ess-material-steps .is-current .ess-step-number{background:var(--app-primary-contrast);color:var(--app-primary)}.ess-material-steps .is-complete .ess-step-number{background:var(--app-primary);color:var(--app-primary-contrast)}.ess-step-number .material-icons{font-size:19px}.ess-material-steps strong,.ess-material-steps small{display:block}.ess-material-steps strong{font-size:14px}.ess-material-steps small{margin-top:3px;color:var(--app-muted);font-size:12px;line-height:1.4}.ess-material-steps .is-current small{color:color-mix(in srgb,var(--app-primary-contrast) 82%,transparent)}.ess-material-steps em{flex:none;margin-left:auto;padding:5px 8px;border-radius:999px;background:var(--app-primary-contrast);color:var(--app-primary);font-size:10px;font-style:normal;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.ess-material-card{min-width:0;padding:clamp(16px,2.2vw,24px);background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius)}.ess-material-card-heading{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}.ess-material-card-heading>.material-icons{display:grid;place-items:center;flex:none;width:42px;height:42px;border-radius:12px;background:var(--app-primary-soft);color:var(--app-primary);font-size:23px}.ess-material-card-heading h2,.ess-material-list-heading h2{margin:0 0 5px;font-size:20px;font-weight:820}.ess-material-card-heading p,.ess-material-list-heading p{margin:0;color:var(--app-muted);font-size:13px;line-height:1.5}.ess-material-form,.ess-material-action{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:end}.ess-material-form label,.ess-material-action label{display:block;min-width:0;margin:0;font-size:13px;font-weight:750}.ess-material-form label>span,.ess-material-action label>span{font-weight:500;color:var(--app-muted)}.ess-material-form .form-control,.ess-material-action .form-control,.ess-material-action .form-control-file{display:block;width:100%;margin-top:6px}.ess-material-wide{grid-column:1/-1}.ess-material-submit{grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:14px;padding-top:2px}.ess-material-submit small{color:var(--app-muted);line-height:1.4}.ess-material-submit .btn,.ess-material-action .btn{display:inline-flex;justify-content:center;align-items:center;gap:7px;min-height:42px;white-space:normal}.ess-material-submit .material-icons,.ess-material-action .material-icons{font-size:18px}.ess-material-list-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px}.ess-count{flex:none;padding:6px 10px;border:1px solid var(--app-border);border-radius:999px;color:var(--app-muted);font-size:12px;font-weight:750}.ess-material-request{padding:18px 0;border-top:1px solid var(--app-border)}.ess-material-request-top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px}.ess-material-reference{color:var(--app-muted);font-size:11px;font-weight:800;letter-spacing:.06em}.ess-material-request h3{margin:4px 0 0;font-size:17px;font-weight:820;overflow-wrap:anywhere}.ess-material-status{display:inline-flex;align-items:center;gap:5px;flex:none;padding:7px 10px;border-radius:999px;background:var(--app-primary-soft);color:var(--app-primary);font-size:11px;font-weight:800}.ess-material-status .material-icons{font-size:15px}.ess-material-status.status-funds_issued{background:#fff4df;color:#9a5a00}.ess-material-status.status-submitted_for_review{background:#eaf2ff;color:#285b9a}.ess-material-status.status-closed{background:#e9f7ef;color:#187344}.ess-material-amounts{display:flex;flex-wrap:wrap;gap:10px;margin:14px 0}.ess-material-amounts>div{min-width:145px;padding:10px 12px;background:color-mix(in srgb,var(--app-text) 4%,var(--app-surface));border:1px solid var(--app-border);border-radius:9px}.ess-material-amounts small,.ess-material-amounts strong{display:block}.ess-material-amounts small,.ess-material-detail>small{color:var(--app-muted);font-size:11px}.ess-material-amounts strong{margin-top:4px;font-size:15px}.ess-material-detail,.ess-material-note{margin:10px 0;padding:11px 13px;border-radius:9px;background:color-mix(in srgb,var(--app-text) 3%,var(--app-surface));border:1px solid var(--app-border)}.ess-material-detail p,.ess-material-note p{margin:4px 0 0;line-height:1.5;overflow-wrap:anywhere}.ess-material-note{background:var(--app-primary-soft);border-color:var(--app-primary-border)}.ess-material-note strong{font-size:12px}.ess-material-action{margin-top:14px;padding:15px;background:color-mix(in srgb,var(--app-primary) 4%,var(--app-surface));border:1px solid var(--app-border);border-radius:12px}.ess-material-action .btn{grid-column:1/-1;justify-self:start;padding-inline:18px}.ess-material-report .ess-material-wide{grid-column:1/-1}.ess-material-receipts{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}.ess-material-receipts a{display:inline-flex;align-items:center;gap:6px;padding:8px 10px;border:1px solid var(--app-border);border-radius:8px;color:var(--app-primary);font-size:12px;font-weight:750;text-decoration:none}.ess-material-receipts a:hover{background:var(--app-primary-soft)}.ess-material-receipts .material-icons{font-size:17px}.ess-material-empty{display:grid;justify-items:center;gap:7px;padding:32px 14px;text-align:center;color:var(--app-muted);border-top:1px solid var(--app-border)}.ess-material-empty>.material-icons{font-size:30px;color:var(--app-primary)}.ess-material-empty strong{color:var(--app-text);font-size:15px}.ess-material-empty p{margin:0;font-size:13px}
.ess-material-report label:nth-of-type(2){grid-column:2}.ess-receipt-choice{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.ess-receipt-option{position:relative;display:grid!important;grid-template-columns:28px 1fr;align-items:center;column-gap:9px;min-width:0;padding:12px!important;border:1px solid var(--app-border);border-radius:10px;background:var(--app-surface);cursor:pointer}.ess-receipt-option>.material-icons{grid-row:1/3;color:var(--app-primary);font-size:22px}.ess-receipt-option strong{font-size:13px}.ess-receipt-option small{grid-column:2;color:var(--app-muted);font-size:11px;font-weight:500;line-height:1.4}.ess-receipt-option input{grid-column:1/-1;width:100%;margin-top:9px;font-size:12px}.ess-receipt-selection{grid-column:1/-1;color:var(--app-muted);font-size:12px;font-weight:500}
.ess-camera-option{cursor:default}.ess-camera-open{grid-column:1/-1;justify-self:start;display:inline-flex;align-items:center;gap:6px;margin-top:8px}.ess-camera-open .material-icons{font-size:18px}.ess-camera-panel{grid-column:1/-1;width:100%;margin-top:10px}.ess-camera-panel video{display:block;width:100%;max-height:360px;aspect-ratio:4/3;object-fit:cover;border-radius:9px;background:#111}.ess-camera-controls{display:flex;gap:8px;flex-wrap:wrap;margin-top:9px}.ess-camera-controls button{display:inline-flex;align-items:center;gap:6px}.ess-camera-controls .material-icons{font-size:18px}.ess-camera-feedback{grid-column:1/-1;color:var(--app-muted);font-size:12px;font-weight:500;line-height:1.4}
.ess-create-purchase-button{justify-self:start;display:inline-flex!important;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:10px 15px;border:1px solid var(--app-primary);border-radius:9px;background:var(--app-primary);color:#fff!important;font-size:14px!important;font-weight:800;line-height:1.3;text-decoration:none!important;white-space:nowrap;visibility:visible!important;opacity:1!important}.ess-create-purchase-button:hover,.ess-create-purchase-button:focus{color:#fff!important;filter:brightness(.94);text-decoration:none!important}.ess-create-purchase-button .ess-create-purchase-icon{display:inline-block!important;flex:none;color:#fff!important;font-size:18px!important;line-height:1}.ess-create-purchase-button .ess-create-purchase-label{display:inline-block!important;color:#fff!important;font-size:14px!important;line-height:1.3;visibility:visible!important;opacity:1!important}.ess-material-access-note{display:flex;align-items:flex-start;gap:9px;padding:11px 13px;border:1px solid var(--app-border);border-radius:9px;background:var(--app-surface);color:var(--app-muted);font-size:12px;line-height:1.45}.ess-material-access-note>.material-icons{flex:none;color:var(--app-primary);font-size:18px}.ess-material-access-note p{margin:0}.ess-material-access-note strong{color:var(--app-text)}.ess-material-new{scroll-margin-top:24px}
html[data-app-theme="dark"] .ess-material-status.status-funds_issued{background:#49391b;color:#f3d28b}html[data-app-theme="dark"] .ess-material-status.status-submitted_for_review{background:#20364a;color:#acd4f1}html[data-app-theme="dark"] .ess-material-status.status-closed{background:#203d30;color:#a6dfbf}
@media(max-width:760px){.ess-material-steps{grid-template-columns:1fr}.ess-material-steps article{padding:12px}.ess-material-submit{align-items:stretch;flex-direction:column}.ess-material-submit .btn,.ess-material-action .btn{width:100%}.ess-material-report{grid-template-columns:1fr}.ess-material-report label:nth-of-type(2),.ess-material-report .ess-material-wide{grid-column:1}.ess-material-request-top{flex-direction:column}.ess-material-status{white-space:normal}}
@media(max-width:560px){.ess-material-card{padding:14px}.ess-material-form,.ess-material-action{grid-template-columns:1fr}.ess-material-wide,.ess-material-action .btn,.ess-material-report label:nth-of-type(2),.ess-material-report .ess-material-wide{grid-column:1}.ess-material-amounts{display:grid;grid-template-columns:1fr 1fr}.ess-material-amounts>div{min-width:0}.ess-material-list-heading{align-items:flex-start;flex-direction:column}.ess-material-request{padding:15px 0}}
</style>
<script>
document.querySelectorAll('.ess-material-report').forEach((form) => {
    const camera = form.querySelector('.ess-receipt-camera');
    const files = form.querySelector('.ess-receipt-files');
    const message = form.querySelector('.ess-receipt-selection');
    const openCamera = form.querySelector('.ess-camera-open');
    const takePhoto = form.querySelector('.ess-camera-take');
    const closeCamera = form.querySelector('.ess-camera-close');
    const cameraPanel = form.querySelector('.ess-camera-panel');
    const video = form.querySelector('.ess-camera-video');
    const canvas = form.querySelector('.ess-camera-canvas');
    const cameraFeedback = form.querySelector('.ess-camera-feedback');
    let cameraStream = null;
    let capturedPhotos = [];
    const updateSelection = () => {
        const count = (camera?.files?.length || 0) + (files?.files?.length || 0);
        if (message) message.textContent = count ? `${count} fichier${count > 1 ? 's' : ''} ajouté${count > 1 ? 's' : ''}.` : 'Ajoute au moins une photo ou un reçu.';
        files?.setCustomValidity(count ? '' : 'Prends une photo ou choisis au moins un fichier.');
    };
    const stopCamera = () => {
        cameraStream?.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        if (video) video.srcObject = null;
        if (cameraPanel) cameraPanel.hidden = true;
    };
    openCamera?.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            if (cameraFeedback) cameraFeedback.textContent = 'La caméra n’est pas accessible dans ce navigateur. Utilise « Choisir des fichiers ».';
            return;
        }
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            video.srcObject = cameraStream;
            cameraPanel.hidden = false;
            cameraFeedback.textContent = '';
            await video.play();
        } catch (error) {
            cameraFeedback.textContent = error.name === 'NotAllowedError'
                ? 'Autorise l’accès à la caméra dans ton navigateur, puis réessaie.'
                : 'Impossible d’ouvrir la caméra. Tu peux choisir une photo ou un PDF.';
        }
    });
    takePhoto?.addEventListener('click', () => {
        if (!video?.videoWidth || !canvas || !camera) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
            if (!blob) {
                if (cameraFeedback) cameraFeedback.textContent = 'La photo n’a pas pu être créée. Réessaie.';
                return;
            }
            capturedPhotos.push(new File([blob], `recu-achat-${Date.now()}.jpg`, { type: 'image/jpeg' }));
            const transfer = new DataTransfer();
            capturedPhotos.forEach((photo) => transfer.items.add(photo));
            camera.files = transfer.files;
            if (cameraFeedback) cameraFeedback.textContent = `${capturedPhotos.length} photo${capturedPhotos.length > 1 ? 's' : ''} prise${capturedPhotos.length > 1 ? 's' : ''}. Tu peux en capturer d’autres.`;
            updateSelection();
        }, 'image/jpeg', 0.9);
    });
    closeCamera?.addEventListener('click', stopCamera);
    form.addEventListener('submit', stopCamera);
    files?.addEventListener('change', updateSelection);
    form.addEventListener('submit', (event) => {
        updateSelection();
        if (!form.reportValidity()) event.preventDefault();
    });
});
</script>
@endsection
