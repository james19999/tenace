@extends('layouts.admin')
@section('content')
<div class="container-fluid fs-page"><header class="fs-header"><a href="{{ route('finance.suppliers.index') }}">← Factures fournisseurs</a><h1>Enregistrer une facture fournisseur</h1><p>@if($selectedReceipt)Depuis la réception {{ $selectedReceipt->receipt_number }}, le fournisseur et le montant reçu sont repris. Complète les dates et joins la facture.@else Renseigne les informations indiquées sur la facture.@endif</p></header>
    @if($errors->any())<div class="alert alert-danger"><strong>Vérifie les informations.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="fs-card" method="POST" enctype="multipart/form-data" action="{{ route('finance.suppliers.store') }}">@csrf
        <section class="fs-section">
            <h2>Informations de la facture</h2>
            <div class="fs-grid">
                <label>Nom du fournisseur *<input class="form-control" name="supplier_name" value="{{ $supplierName }}" maxlength="160" required></label>
                <label>Date de facture *<input class="form-control" type="date" name="invoice_date" value="{{ old('invoice_date',now()->format('Y-m-d')) }}" required></label>
                <label>Date limite de paiement<input class="form-control" type="date" name="due_date" value="{{ old('due_date') }}"></label>
            </div>
        </section>
        <section class="fs-section">
            <h2>Réception et montant</h2>
            <div class="fs-grid">
                <label>Réception de stock correspondante (facultatif)
                    <select id="supplier-stock-receipt" class="form-control" name="stock_receipt_id"><option value="">Aucune · service ou achat sans réception enregistrée</option>@foreach($receipts as $receipt)<option value="{{ $receipt->id }}" data-amount="{{ $receipt->calculated_invoice_amount ?? 0 }}" @selected((string)old('stock_receipt_id', $selectedReceiptId)===(string)$receipt->id)>{{ $receipt->receipt_number }} · {{ $receipt->origin_name ?: 'Fournisseur non précisé' }} · @if($receipt->calculated_invoice_amount !== null){{ number_format($receipt->calculated_invoice_amount,0,',',' ') }} F @else coût manquant @endif</option>@endforeach</select>
                    <small id="supplier-receipt-total">Choisis une réception pour calculer le total des quantités reçues et de leurs coûts unitaires.</small>
                </label>
                <label>Montant total à payer (F CFA) *<input id="supplier-invoice-amount" class="form-control" type="number" min="1" step="1" name="amount" value="{{ $invoiceAmount }}" required></label>
            </div>
        </section>
        <section class="fs-section">
            <h2>Pièce justificative</h2>
            <div class="fs-grid">
                <label class="fs-wide">Photo ou PDF de la facture fournisseur *<input class="form-control-file" type="file" name="invoice" accept=".pdf,.jpg,.jpeg,.png" required><small>Cette pièce sera archivée pour le contrôle comptable. PDF ou image, 10 Mo maximum.</small></label>
                <label class="fs-wide">Note de rapprochement (facultatif)<textarea class="form-control" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea><small>À remplir si le montant diffère de la réception ou si le comptable doit vérifier un point.</small></label>
            </div>
        </section>
        <div class="fs-actions"><a class="btn btn-light" href="{{ route('finance.suppliers.index') }}">Annuler</a><button class="btn btn-primary" type="submit">Enregistrer pour contrôle</button></div>
    </form>
</div>
<style>.fs-page{max-width:1050px;padding:20px 24px 42px}.fs-header{margin-bottom:18px}.fs-header h1{font-weight:800;margin:8px 0}.fs-header p,.fs-grid small{color:#758194}.fs-card{background:#fff;border:1px solid #e1e5eb;padding:24px}.fs-section+.fs-section{border-top:1px solid #e8ebef;margin-top:22px;padding-top:20px}.fs-section h2{font-size:18px;font-weight:800;margin:0 0 14px;color:#303a49}.fs-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.fs-grid label{font-weight:700}.fs-grid small{display:block;margin-top:5px;font-weight:400;line-height:1.45}.fs-wide{grid-column:1/-1}.fs-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}@media(max-width:650px){.fs-page{padding:12px}.fs-card{padding:15px}.fs-grid{grid-template-columns:1fr}.fs-wide{grid-column:auto}.fs-actions{flex-direction:column-reverse}.fs-actions .btn{width:100%}}</style>
<script>
(() => {
    const receiptSelect = document.getElementById('supplier-stock-receipt');
    const amountInput = document.getElementById('supplier-invoice-amount');
    const receiptTotal = document.getElementById('supplier-receipt-total');
    if (!receiptSelect || !amountInput || !receiptTotal) return;

    let amountManuallyEdited = amountInput.value.trim() !== '';
    amountInput.addEventListener('input', () => { amountManuallyEdited = true; });
    const applyReceiptAmount = () => {
        const option = receiptSelect.options[receiptSelect.selectedIndex];
        if (!receiptSelect.value) {
            if (!amountManuallyEdited && amountInput.dataset.fromReceipt) amountInput.value = '';
            delete amountInput.dataset.fromReceipt;
            receiptTotal.textContent = 'Choisis une réception pour calculer le total des quantités reçues × leur coût unitaire.';
            return;
        }
        const amount = Number(option?.dataset.amount || 0);
        if (amount > 0) {
            receiptTotal.textContent = `Total calculé depuis la réception : ${new Intl.NumberFormat('fr-FR').format(amount)} F CFA.`;
            if (!amountManuallyEdited) {
                amountInput.value = String(Math.round(amount));
                amountInput.dataset.fromReceipt = '1';
            }
        } else {
            if (!amountManuallyEdited && amountInput.dataset.fromReceipt) amountInput.value = '';
            delete amountInput.dataset.fromReceipt;
            receiptTotal.textContent = 'Coût unitaire manquant dans cette réception : renseigne le montant d’après la facture.';
        }
    };
    receiptSelect.addEventListener('change', applyReceiptAmount);
    if (receiptSelect.value) applyReceiptAmount();
})();
</script>
@endsection
