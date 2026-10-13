@extends('layouts.admin')

@section('content')
    @php
        $typeLabels = [
            'warehouse' => 'Magasin',
            'shop' => 'Boutique',
            'transit' => 'Transit',
            'blocked' => 'Bloqué / quarantaine',
            'sales_rep' => 'Commercial terrain',
            'wholesaler' => 'Grossiste',
            'other' => 'Autre',
        ];
    @endphp

    <div class="stock-control-page">
        <header class="stock-control-hero">
            <div class="stock-control-hero-copy">
                <span class="stock-control-kicker">TENACOS · CONTRÔLE OPÉRATIONNEL</span>
                <h1>Contrôle des stocks</h1>
                <p>Préparez le suivi par site, les validations et la traçabilité avant l’ouverture des mouvements.</p>
            </div>
            <a href="{{ route('stock-control.balances') }}" class="stock-control-hero-action"><span class="material-icons">warehouse</span>Consulter le stock</a>
            <div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">inventory_2</span></div>
        </header>

        @if (session('success'))
            <div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="stock-control-alert stock-control-alert-error" role="alert">
                <span class="material-icons">error_outline</span>
                <div><strong>Vérifie les champs indiqués.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif

        <section class="stock-control-intro">
            <div class="stock-control-intro-icon"><span class="material-icons">verified_user</span></div>
            <div>
                <h2>Le stock actuel reste intact</h2>
                <p>Cette étape prépare les emplacements et les règles. Elle ne déplace pas le stock global des produits et ne valide aucun mouvement.</p>
            </div>
            @if ($locations->whereIn('code', $systemLocationCodes)->count() < count($systemLocationCodes))
                <form method="POST" action="{{ route('stock-control.locations.initialize') }}" class="stock-control-intro-action">
                    @csrf
                    <button type="submit" class="stock-control-button stock-control-button-primary">Créer les 3 emplacements de base</button>
                </form>
            @endif
        </section>

        <div class="stock-control-layout">
            <section class="stock-control-card stock-control-locations">
                <div class="stock-control-section-head">
                    <div><span class="stock-control-kicker">ORGANISATION</span><h2>Emplacements et détenteurs</h2></div>
                    <span class="stock-control-count">{{ $locations->count() }} emplacement(s)</span>
                </div>

                <form method="POST" action="{{ route('stock-control.locations.store') }}" class="stock-control-create-form">
                    @csrf
                    <h3>Ajouter un site ou un détenteur</h3>
                    <div class="stock-control-form-grid">
                        <label class="stock-control-field"><span>Code unique</span><input name="code" value="{{ old('code') }}" placeholder="BOUT-DJIDJOLE" maxlength="40" required></label>
                        <label class="stock-control-field"><span>Nom affiché</span><input name="name" value="{{ old('name') }}" placeholder="Boutique Djidjolé" maxlength="120" required></label>
                        <label class="stock-control-field"><span>Type</span><select name="type" required>@foreach ($locationTypes as $type)<option value="{{ $type }}" @selected(old('type', 'shop') === $type)>{{ $typeLabels[$type] }}</option>@endforeach</select></label>
                        <label class="stock-control-field"><span>Responsable</span><select name="responsible_user_id"><option value="">À désigner</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) old('responsible_user_id') === (string) $user->id)>{{ $user->name }} · {{ $user->user_type }}</option>@endforeach</select></label>
                    </div>
                    <label class="stock-control-field stock-control-field-notes"><span>Note (facultatif)</span><textarea name="notes" rows="2" placeholder="Précision utile sur ce site ou détenteur">{{ old('notes') }}</textarea></label>
                    <div class="stock-control-form-footer"><small>Créer un emplacement ne lui attribue aucune quantité.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">add</span>Ajouter l’emplacement</button></div>
                </form>

                @if ($locations->isEmpty())
                    <div class="stock-control-empty"><span class="material-icons">warehouse</span><strong>Aucun emplacement configuré</strong><p>Crée les emplacements de base, puis ajoute les boutiques, commerciaux et grossistes concernés.</p></div>
                @else
                    <div class="stock-location-list">
                        @foreach ($locations as $location)
                            <article class="stock-location-item">
                                <div class="stock-location-heading">
                                    <div class="stock-location-symbol"><span class="material-icons">{{ $location->type === 'shop' ? 'storefront' : ($location->type === 'sales_rep' || $location->type === 'wholesaler' ? 'person_pin_circle' : 'warehouse') }}</span></div>
                                    <div class="stock-location-title"><div class="stock-location-name">{{ $location->name }} @if (in_array($location->code, $systemLocationCodes, true))<span class="stock-control-system-tag">Système</span>@endif</div><div class="stock-location-meta"><code>{{ $location->code }}</code><span>{{ $typeLabels[$location->type] ?? 'Autre' }}</span></div></div>
                                    <span class="stock-control-state {{ $location->is_active ? 'is-active' : 'is-inactive' }}">{{ $location->is_active ? 'Actif' : 'Inactif' }}</span>
                                </div>
                                <div class="stock-location-responsible"><span>Responsable</span><strong>{{ $location->responsible?->name ?? 'À désigner' }}</strong></div>
                                <details class="stock-location-edit">
                                    <summary>Modifier les informations</summary>
                                    <form method="POST" action="{{ route('stock-control.locations.update', $location) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="stock-control-form-grid">
                                            <label class="stock-control-field"><span>Code</span><input value="{{ $location->code }}" disabled></label>
                                            <label class="stock-control-field"><span>Nom affiché</span><input name="name" value="{{ $location->name }}" maxlength="120" required></label>
                                            <label class="stock-control-field"><span>Responsable</span><select name="responsible_user_id"><option value="">À désigner</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) $location->responsible_user_id === (string) $user->id)>{{ $user->name }} · {{ $user->user_type }}</option>@endforeach</select></label>
                                            <label class="stock-control-field"><span>État</span>
                                                @if (in_array($location->code, $systemLocationCodes, true))
                                                    <input type="hidden" name="is_active" value="1"><select disabled><option>Actif (emplacement système)</option></select>
                                                @else
                                                    <select name="is_active"><option value="1" @selected($location->is_active)>Actif</option><option value="0" @selected(! $location->is_active)>Inactif</option></select>
                                                @endif
                                            </label>
                                        </div>
                                        <label class="stock-control-field stock-control-field-notes"><span>Note</span><textarea name="notes" rows="2">{{ $location->notes }}</textarea></label>
                                        <button class="stock-control-button" type="submit">Enregistrer</button>
                                    </form>
                                </details>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="stock-control-card stock-control-rules">
                <div class="stock-control-section-head">
                    <div><span class="stock-control-kicker">GARDE-FOUS</span><h2>Règles de contrôle</h2></div>
                    @if (! $isAdmin)<span class="stock-control-readonly"><span class="material-icons">lock</span>Lecture seule</span>@endif
                </div>
                @if ($isAdmin)
                    <form method="POST" action="{{ route('stock-control.rules.update') }}" class="stock-rules-form">
                        @csrf
                        @method('PUT')
                        <label class="stock-rule-toggle"><span><strong>Interdire le stock négatif</strong><small>Bloque une sortie qui dépasserait le disponible.</small></span><input type="checkbox" name="prevent_negative_stock" value="1" @checked($rules['prevent_negative_stock'])></label>
                        <label class="stock-rule-toggle"><span><strong>Motif obligatoire pour une sortie</strong><small>Chaque sortie doit être expliquée et rattachée à un motif.</small></span><input type="checkbox" name="require_reason_for_outbound" value="1" @checked($rules['require_reason_for_outbound'])></label>
                        <label class="stock-rule-toggle"><span><strong>Approbation indépendante des ajustements</strong><small>L’auteur ne valide pas sa propre correction.</small></span><input type="checkbox" name="adjustments_require_approval" value="1" @checked($rules['adjustments_require_approval'])></label>
                        <label class="stock-rule-toggle"><span><strong>Second comptage en cas d’écart</strong><small>Demande une vérification avant toute correction.</small></span><input type="checkbox" name="second_count_on_difference" value="1" @checked($rules['second_count_on_difference'])></label>
                        <label class="stock-rule-toggle"><span><strong>Justificatif pour casse ou perte</strong><small>Demande une pièce jointe selon les règles activées.</small></span><input type="checkbox" name="loss_requires_attachment" value="1" @checked($rules['loss_requires_attachment'])></label>
                        <div class="stock-control-rule-grid">
                            <label class="stock-control-field"><span>Emplacement de sortie des commandes</span><select name="default_sales_location_id"><option value="">Non configuré</option>@foreach($salesLocations as $salesLocation)<option value="{{ $salesLocation->id }}" @selected((string) old('default_sales_location_id', $rules['default_sales_location_id'] ?? '') === (string) $salesLocation->id)>{{ $salesLocation->name }} · {{ $salesLocation->code }}</option>@endforeach</select><small>Une localisation propre au livreur (type « commercial terrain ») sera prioritaire si elle existe.</small></label>
                            <label class="stock-control-field"><span>Seuil d’approbation (quantité)</span><input type="number" name="approval_threshold_quantity" min="0" step="0.001" value="{{ old('approval_threshold_quantity', $rules['approval_threshold_quantity']) }}"><small>0 = toute correction nécessite une approbation.</small></label>
                            <label class="stock-control-field"><span>Seuil d’approbation (FCFA)</span><input type="number" name="approval_threshold_value" min="0" step="1" value="{{ old('approval_threshold_value', $rules['approval_threshold_value']) }}"><small>0 = toute valeur est soumise à approbation.</small></label>
                            <label class="stock-control-field"><span>Retard transfert après (jours)</span><input type="number" name="transfer_overdue_days" min="1" max="365" value="{{ old('transfer_overdue_days', $rules['transfer_overdue_days']) }}"></label>
                            <label class="stock-control-field"><span>Alerte péremption avant (jours)</span><input type="number" name="expiry_alert_days" min="1" max="3650" value="{{ old('expiry_alert_days', $rules['expiry_alert_days']) }}"></label>
                            <label class="stock-control-field"><span>Intervalle inventaire (jours)</span><input type="number" name="inventory_interval_days" min="1" max="365" value="{{ old('inventory_interval_days', $rules['inventory_interval_days']) }}"></label>
                        </div>
                        <div class="stock-control-form-footer"><small>Ces valeurs seront utilisées par les opérations du module à mesure qu’elles seront activées.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">save</span>Enregistrer les règles</button></div>
                    </form>
                @else
                    <div class="stock-rules-readonly-list">
                        <div><span>Stock négatif</span><strong>{{ $rules['prevent_negative_stock'] ? 'Interdit' : 'Autorisé' }}</strong></div>
                        <div><span>Motif de sortie</span><strong>{{ $rules['require_reason_for_outbound'] ? 'Obligatoire' : 'Facultatif' }}</strong></div>
                        <div><span>Approbation ajustement</span><strong>{{ $rules['adjustments_require_approval'] ? 'Requise' : 'Selon seuil' }}</strong></div>
                        <div><span>Second comptage</span><strong>{{ $rules['second_count_on_difference'] ? 'En cas d’écart' : 'Désactivé' }}</strong></div>
                        <div><span>Seuil quantité</span><strong>{{ number_format((float) $rules['approval_threshold_quantity'], 3, ',', ' ') }}</strong></div>
                        <div><span>Seuil valeur</span><strong>{{ number_format((float) $rules['approval_threshold_value'], 0, ',', ' ') }} F</strong></div>
                        <div><span>Retard transfert</span><strong>{{ $rules['transfer_overdue_days'] }} jour(s)</strong></div>
                        <div><span>Alerte péremption</span><strong>{{ $rules['expiry_alert_days'] }} jour(s)</strong></div>
                        <div><span>Fréquence inventaire</span><strong>{{ $rules['inventory_interval_days'] }} jour(s)</strong></div>
                        <div><span>Sortie des commandes</span><strong>{{ $salesLocations->firstWhere('id', $rules['default_sales_location_id'] ?? null)?->name ?? 'Non configuré' }}</strong></div>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
