@extends('layouts.admin')

@section('content')
<div class="stock-control-page">
    <header class="stock-control-hero"><div class="stock-control-hero-copy"><span class="stock-control-kicker">TENACOS · INVENTAIRE</span><h1>Préparer un comptage</h1><p>Choisis l’emplacement et la personne qui comptera physiquement les produits.</p></div><a href="{{ route('stock-control.counts.index') }}" class="stock-control-hero-action"><span class="material-icons">arrow_back</span>Retour aux inventaires</a><div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">fact_check</span></div></header>
    @if($errors->any())<div class="stock-control-alert stock-control-alert-error"><span class="material-icons">error_outline</span><div><strong>Vérifie les champs indiqués.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <form method="POST" action="{{ route('stock-control.counts.store') }}" class="stock-control-card stock-count-form">@csrf
        <div class="stock-control-section-head"><div><span class="stock-control-kicker">PARAMÈTRES</span><h2>Nouvelle campagne</h2></div></div>
        <div class="stock-control-form-grid">
            <label class="stock-control-field"><span>Emplacement à compter</span><select name="location_id" required><option value="">Sélectionner un emplacement</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id')==$location->id)>{{ $location->name }} · {{ $location->code }}</option>@endforeach</select></label>
            <label class="stock-control-field"><span>Responsable du comptage</span><select name="counter_id" required><option value="">Sélectionner une personne</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('counter_id',auth()->id())==$user->id)>{{ $user->name }} · {{ $user->user_type }}</option>@endforeach</select></label>
            <label class="stock-control-field"><span>Date prévue</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at',now()->format('Y-m-d\TH:i')) }}"></label>
            <label class="stock-control-field"><span>Portée (facultatif)</span><input name="scope" value="{{ old('scope') }}" placeholder="Ex. tous les produits ou rayon savons"></label>
        </div>
        <aside class="stock-count-reconciliation-reminder">
            <span class="material-icons" aria-hidden="true">inventory</span>
            <div><strong>Rappel pour répartir le stock entre les emplacements</strong><p>Pour une reprise initiale, saisis la quantité réellement comptée dans l’emplacement choisi. Ne recopie pas le stock global dans chaque emplacement. Si le stock est réparti sur plusieurs sites, crée un comptage pour chaque site et répartis les quantités physiques entre eux.</p></div>
            @if($stockReconciliationExamples->isNotEmpty())
                <div class="stock-count-reconciliation-table-wrap">
                    <table class="stock-count-reconciliation-table">
                        <thead><tr><th>Produit à vérifier</th><th>Stock global</th><th>Total au journal</th><th>Écart global − journal</th></tr></thead>
                        <tbody>@foreach($stockReconciliationExamples as $example)<tr><td><strong>{{ $example->name }}</strong><small>Réf. {{ $example->id }}</small></td><td>{{ number_format((float) $example->global_quantity, 0, ',', ' ') }}</td><td>{{ number_format((float) $example->located_quantity, 0, ',', ' ') }}</td><td class="{{ (float) $example->difference < 0 ? 'has-excess' : 'has-unallocated' }}">{{ (float) $example->difference > 0 ? '+' : '' }}{{ number_format((float) $example->difference, 0, ',', ' ') }}</td></tr>@endforeach</tbody>
                    </table>
                    <small class="stock-count-reconciliation-caption">Exemples des écarts actuels. L’écart indique une différence à examiner, pas la quantité à saisir pour un emplacement.</small>
                </div>
            @else
                <p class="stock-count-reconciliation-clear">Aucun écart entre le stock global et les quantités des emplacements vendables n’a été détecté.</p>
            @endif
        </aside>
        <label class="stock-count-opening"><input type="checkbox" name="is_opening_balance" value="1" @checked(old('is_opening_balance'))><span><strong>Reprise initiale du stock par comptage</strong><small>À cocher pour inscrire les quantités physiques dans le journal sans augmenter le stock global déjà présent dans les produits.</small></span></label>
        <label class="stock-control-field stock-control-field-notes"><span>Consignes (facultatif)</span><textarea name="notes" rows="3" placeholder="Précisions pour la personne chargée du comptage">{{ old('notes') }}</textarea></label>
        <div class="stock-control-form-footer"><small>Le comptage sera à l’aveugle : les quantités théoriques ne seront pas affichées au responsable.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">playlist_add_check</span>Créer l’inventaire</button></div>
    </form>
</div>
@endsection
