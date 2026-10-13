@extends('layouts.admin')

@section('content')
@php $line=$adjustment->lines->first(); $labels=['submitted'=>'En attente d’approbation','posted'=>'Appliqué','rejected'=>'Refusé']; @endphp
<div class="stock-control-page stock-adjustments-page">
    <header class="stock-control-hero"><div class="stock-control-hero-copy"><span class="stock-control-kicker">CORRECTION DE STOCK</span><h1>{{ $adjustment->adjustment_number }}</h1><p>{{ $labels[$adjustment->status] ?? $adjustment->status }} · créée le {{ $adjustment->created_at?->format('d/m/Y à H:i') }}</p></div><div class="transfer-detail-heading-actions">@if($adjustment->status==='posted')<a href="{{ route('stock-control.adjustments.print', $adjustment) }}" target="_blank" rel="noopener" class="stock-control-hero-action"><span class="material-icons">print</span>Bon de correction</a>@endif<a href="{{ route('stock-control.adjustments.index') }}" class="stock-control-hero-action"><span class="material-icons">arrow_back</span>Retour</a></div></header>
    @if(session('success'))<div class="stock-control-alert stock-control-alert-success"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="stock-control-alert stock-control-alert-error"><span class="material-icons">error_outline</span><div><strong>Action impossible.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <section class="stock-control-card adjustment-detail-card"><div class="stock-control-section-head"><div><span class="stock-control-kicker">ÉTAT</span><h2>{{ $labels[$adjustment->status] ?? $adjustment->status }}</h2></div><span class="receipt-status-pill {{ $adjustment->status==='submitted'?'pending':($adjustment->status==='posted'?'validated':'neutral') }}">{{ $labels[$adjustment->status] ?? $adjustment->status }}</span></div>
        <dl class="adjustment-detail-grid"><div><dt>Produit</dt><dd>{{ $line?->product?->name ?? '—' }}</dd></div><div><dt>Emplacement</dt><dd>{{ $line?->location?->name ?? '—' }}</dd></div><div><dt>Stock enregistré sur cet emplacement</dt><dd>{{ number_format((float) $line?->quantity_before, 0, ',', ' ') }}</dd></div><div><dt>Stock global actuel</dt><dd>{{ number_format($globalQuantity, 0, ',', ' ') }}</dd></div><div><dt>Variation demandée</dt><dd class="{{ (float) $line?->quantity_delta < 0 ? 'adjustment-negative' : 'adjustment-positive' }}">{{ (float) $line?->quantity_delta > 0 ? '+' : '' }}{{ number_format((float) $line?->quantity_delta, 0, ',', ' ') }}</dd></div><div><dt>Stock après correction sur le site</dt><dd>{{ number_format((float) $line?->quantity_counted, 0, ',', ' ') }}</dd></div><div><dt>Demandé par</dt><dd>{{ $adjustment->requester?->name ?? '—' }}</dd></div><div><dt>Traité par</dt><dd>{{ $adjustment->approver?->name ?? '—' }}</dd></div><div><dt>Motif</dt><dd>{{ ucfirst(str_replace('_',' ',$adjustment->reason_code)) }}</dd></div></dl>
        @if(abs((float) $unallocatedQuantity) >= 0.0005)
            <div class="stock-control-alert stock-control-alert-error adjustment-reconciliation-warning"><span class="material-icons">info</span><div><strong>Stock historique pas encore réparti</strong><p>Le stock global est de {{ number_format($globalQuantity, 0, ',', ' ') }}, mais le journal ne retrouve que {{ number_format($locatedQuantity, 0, ',', ' ') }} unité(s) dans les emplacements vendables. L’écart de {{ number_format($unallocatedQuantity, 0, ',', ' ') }} doit être affecté via un inventaire initial. N’approuve pas cette demande.</p></div></div>
        @endif
        <div class="adjustment-reason-box"><strong>Justification</strong><p>{{ $adjustment->reason }}</p></div>
        @if($canApprove)
            <div class="adjustment-review-actions">
                <button type="button" class="stock-control-button stock-control-button-primary" id="openAdjustmentApproval"><span class="material-icons">check</span>Approuver et appliquer</button>
                <form method="POST" action="{{ route('stock-control.adjustments.reject',$adjustment) }}">@csrf<label class="stock-control-field"><span>Motif du refus</span><input name="rejection_reason" required minlength="3" maxlength="2000" placeholder="Expliquer le refus"></label><button type="submit" class="stock-control-button adjustment-reject-button">Refuser la demande</button></form>
            </div>
            <dialog class="stock-approval-dialog" id="adjustmentApprovalDialog" aria-labelledby="adjustmentApprovalTitle">
                <div class="stock-approval-dialog-head"><span class="material-icons">inventory</span><button type="button" class="stock-approval-dialog-close" data-close-approval aria-label="Fermer">×</button></div>
                <h2 id="adjustmentApprovalTitle">Appliquer cet ajustement ?</h2>
                <p>Cette validation modifiera immédiatement le stock global et le stock de <strong>{{ $line?->product?->name ?? 'ce produit' }}</strong> à l’emplacement <strong>{{ $line?->location?->name ?? 'sélectionné' }}</strong>.</p>
                <div class="stock-approval-dialog-quantity">Stock actuel <strong>{{ number_format((float) $line?->quantity_before, 0, ',', ' ') }}</strong><span>→</span> Après correction <strong>{{ number_format((float) $line?->quantity_counted, 0, ',', ' ') }}</strong></div>
                <div class="stock-approval-dialog-actions"><button type="button" class="stock-control-button" data-close-approval>Annuler</button><form method="POST" action="{{ route('stock-control.adjustments.approve',$adjustment) }}">@csrf<button type="submit" class="stock-control-button stock-control-button-primary"><span class="material-icons">check</span>Confirmer l’approbation</button></form></div>
            </dialog>
            <script>
                (() => {
                    const dialog = document.getElementById('adjustmentApprovalDialog');
                    const openButton = document.getElementById('openAdjustmentApproval');
                    if (!dialog || !openButton) return;
                    openButton.addEventListener('click', () => dialog.showModal());
                    dialog.querySelectorAll('[data-close-approval]').forEach(button => button.addEventListener('click', () => dialog.close()));
                    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
                })();
            </script>
        @elseif($adjustment->status==='submitted' && $approvalBlockedByReconciliation)
            <p class="adjustment-waiting-note">L’approbation est bloquée tant que le stock historique n’est pas réparti par inventaire initial.</p>
        @elseif($adjustment->status==='submitted')
            <p class="adjustment-waiting-note">Cette demande attend l’approbation d’une autre personne.</p>
        @endif
    </section>
</div>
@endsection
