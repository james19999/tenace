@extends('layouts.admin')
@section('content')
@php($defaultPeriodStart = now()->startOfWeek())
<div class="container-fluid finance-page">
    <header class="finance-page-heading">
        <div>
            <a href="{{ route('finance.remittances.index') }}">← Remises</a>
            <h1>Préparer une remise {{ $isWholesaleManager ? 'du grossiste' : 'de la boutique' }}</h1>
            <p>Le responsable signe avec son compte. Le collecteur confirmera ensuite la prise en charge.</p>
        </div>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="finance-panel">
        <form method="POST" action="{{ route('finance.remittances.store') }}" id="remittance-form">
            @csrf
            <div class="finance-form-grid">
                <label>{{ $isWholesaleManager ? 'Espace grossiste' : 'Boutique' }}
                    <select class="form-control" name="stock_location_id" id="remittance-location" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('stock_location_id', $locations->first()->id) == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Période · lundi
                    <input class="form-control" type="date" name="period_start" id="period-start" max="{{ today()->format('Y-m-d') }}" value="{{ old('period_start', $defaultPeriodStart->format('Y-m-d')) }}" required>
                </label>
                <label>Fin de période
                    <input class="form-control" type="date" name="period_end" id="period-end" max="{{ today()->format('Y-m-d') }}" value="{{ old('period_end', today()->format('Y-m-d')) }}" required>
                    <small>Tu peux générer le rapport en cours de semaine. Le calcul s’arrête à la date choisie.</small>
                </label>

                <div class="remittance-cash-summary" aria-live="polite">
                    <div class="remittance-cash-item">
                        <span>Solde théorique à remettre à la fin de la période</span>
                        <strong id="expected-amount">Calcul en cours…</strong>
                        <small>Fonds initial et encaissements cumulés jusqu’à cette date, moins dépenses, remboursements et remises déjà effectuées. Les frais de livraison sont exclus.</small>
                    </div>
                    <div class="remittance-cash-item">
                        <label for="counted-amount">Montant compté ensemble (F CFA)</label>
                        <input class="form-control" type="number" min="0" step="1" name="counted_amount" id="counted-amount" value="{{ old('counted_amount') }}" required>
                    </div>
                    <div class="remittance-difference" id="remittance-difference">Saisis le montant compté pour voir l’écart.</div>
                </div>

                <label>Personne chargée de la collecte
                    <select class="form-control" name="collector_id" required>
                        <option value="">Choisir la personne</option>
                        @foreach($collectors as $collector)
                            <option value="{{ $collector->id }}" @selected(old('collector_id') == $collector->id)>{{ $collector->name }}{{ $collector->isAdmin() ? ' · DG' : '' }}</option>
                        @endforeach
                    </select>
                    <small>Cette personne devra confirmer avec son propre compte.</small>
                </label>
                <label>Destination des fonds
                    <select class="form-control" name="destination" required>
                        <option value="general_cash" @selected(old('destination') === 'general_cash')>Remise à la caisse générale</option>
                        <option value="bank_deposit" @selected(old('destination') === 'bank_deposit')>Dépôt bancaire pour TENACOS</option>
                    </select>
                    <small>Dans les deux cas, la confirmation du DG crédite la caisse générale du logiciel.</small>
                </label>
                <label>Référence du bon papier
                    <input class="form-control" name="paper_voucher_reference" value="{{ old('paper_voucher_reference') }}" maxlength="120">
                </label>
                <label class="finance-form-wide" id="discrepancy-label">Explication de l’écart
                    <textarea class="form-control" name="discrepancy_note" id="discrepancy-note" rows="2">{{ old('discrepancy_note') }}</textarea>
                    <small id="discrepancy-help">Obligatoire si le montant compté diffère du montant attendu.</small>
                </label>
                <label class="finance-form-wide">Autres informations
                    <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                </label>
            </div>
            <div class="finance-signature-note"><span class="material-icons">draw</span><span>En créant le bon, tu confirmes le comptage comme responsable {{ $isWholesaleManager ? 'du grossiste' : 'de boutique' }}. L’autre personne le signera après avoir pris les fonds. Un bon imprimable sera disponible.</span></div>
            <button class="btn btn-primary" type="submit">Créer et signer le bon</button>
        </form>
    </section>
</div>

<style>
    .finance-page{max-width:1150px;padding:18px 22px 40px}.finance-page-heading{margin-bottom:18px}.finance-page-heading h1{font-weight:800;margin:8px 0}.finance-page-heading p{color:#758194}.finance-panel{padding:24px;background:#fff;border:1px solid #e1e5eb}.finance-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.finance-form-grid>label{font-weight:700;color:#465164}.finance-form-grid small{display:block;font-weight:400;color:#758194;margin-top:5px}.finance-form-wide{grid-column:1/-1}.remittance-cash-summary{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:0;border:1px solid #dce5dd;background:#f7fbf7}.remittance-cash-item{padding:18px 20px}.remittance-cash-item+ .remittance-cash-item{border-left:1px solid #dce5dd}.remittance-cash-item>span,.remittance-cash-item>label{display:block;color:#59677a;font-weight:700;margin:0 0 7px}.remittance-cash-item>strong{display:block;font-size:28px;line-height:1.25;color:#187b2a}.remittance-cash-item small{display:block;color:#758194;margin-top:6px}.remittance-difference{grid-column:1/-1;padding:12px 20px;border-top:1px solid #dce5dd;color:#536174;font-weight:700}.remittance-difference.is-match{color:#187b2a;background:#f1f8f2}.remittance-difference.is-shortage,.remittance-difference.is-overage{color:#a4510b;background:#fff8ed}.remittance-difference.is-error{color:#a82929;background:#fff3f3}.finance-signature-note{display:flex;gap:12px;background:#f5f8f5;padding:13px;margin:20px 0;color:#4f5b6a}.finance-signature-note .material-icons{color:#187b2a}@media(max-width:700px){.finance-page{padding:10px}.finance-panel{padding:16px}.finance-form-grid{grid-template-columns:1fr}.finance-form-wide{grid-column:auto}.remittance-cash-summary{grid-template-columns:1fr}.remittance-cash-item+.remittance-cash-item{border-left:0;border-top:1px solid #dce5dd}.remittance-cash-item>strong{font-size:24px}}
</style>
<script>
    (() => {
        const locationInput = document.getElementById('remittance-location');
        const startInput = document.getElementById('period-start');
        const endInput = document.getElementById('period-end');
        const countedInput = document.getElementById('counted-amount');
        const expectedOutput = document.getElementById('expected-amount');
        const differenceOutput = document.getElementById('remittance-difference');
        const discrepancyInput = document.getElementById('discrepancy-note');
        let expectedAmount = null;
        let requestVersion = 0;
        const money = amount => `${new Intl.NumberFormat('fr-FR').format(amount)} F`;
        const isValidPeriod = () => {
            if (!startInput.value || !endInput.value) return false;
            const start = new Date(`${startInput.value}T00:00:00`);
            const end = new Date(`${endInput.value}T00:00:00`);
            return start.getDay() === 1 && end >= start && end <= new Date('{{ today()->format('Y-m-d') }}T00:00:00');
        };

        function renderDifference() {
            differenceOutput.className = 'remittance-difference';
            if (expectedAmount === null || countedInput.value === '') {
                differenceOutput.textContent = expectedAmount === null ? 'Le montant attendu n’a pas encore pu être calculé.' : 'Saisis le montant compté pour voir l’écart.';
                discrepancyInput.required = false;
                return;
            }
            const counted = Number(countedInput.value);
            const gap = counted - expectedAmount;
            if (gap === 0) {
                differenceOutput.textContent = `Aucun écart : ${money(expectedAmount)} attendus et comptés.`;
                differenceOutput.classList.add('is-match');
                discrepancyInput.required = false;
            } else if (gap < 0) {
                differenceOutput.textContent = `Manque de ${money(Math.abs(gap))} · attendu : ${money(expectedAmount)} · compté : ${money(counted)}.`;
                differenceOutput.classList.add('is-shortage');
                discrepancyInput.required = true;
            } else {
                differenceOutput.textContent = `Excédent de ${money(gap)} · attendu : ${money(expectedAmount)} · compté : ${money(counted)}.`;
                differenceOutput.classList.add('is-overage');
                discrepancyInput.required = true;
            }
        }

        async function refreshExpectedAmount() {
            const version = ++requestVersion;
            expectedAmount = null;
            expectedOutput.textContent = 'Calcul en cours…';
            renderDifference();
            if (!locationInput.value || !startInput.value || !endInput.value) {
                expectedOutput.textContent = 'Sélectionne l’origine des fonds et la période.';
                return;
            }
            if (!isValidPeriod()) {
                expectedOutput.textContent = 'Période invalide';
                differenceOutput.textContent = 'Choisis un lundi comme début de période et une date de fin comprise entre ce lundi et aujourd’hui.';
                differenceOutput.className = 'remittance-difference is-error';
                return;
            }
            const query = new URLSearchParams({
                stock_location_id: locationInput.value,
                period_start: startInput.value,
                period_end: endInput.value,
            });
            try {
                const response = await fetch(`{{ route('finance.remittances.expected-amount') }}?${query}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Calcul indisponible');
                const result = await response.json();
                if (version !== requestVersion) return;
                expectedAmount = Number(result.expected_amount);
                expectedOutput.textContent = money(expectedAmount);
                renderDifference();
            } catch (error) {
                if (version !== requestVersion) return;
                expectedOutput.textContent = 'Impossible de calculer le montant attendu.';
                renderDifference();
            }
        }

        locationInput.addEventListener('change', refreshExpectedAmount);
        startInput.addEventListener('change', refreshExpectedAmount);
        endInput.addEventListener('change', refreshExpectedAmount);
        countedInput.addEventListener('input', renderDifference);
        refreshExpectedAmount();
    })();
</script>
@endsection
