@extends('layouts.admin')

@section('content')
    @php
        $typeLabels = [
            'warehouse' => 'Entrepôt',
            'shop' => 'Boutique',
            'transit' => 'Transit',
            'blocked' => 'Bloqué / quarantaine',
        ];
        $legacyTypeLabels = ['sales_rep' => 'Site historique', 'wholesaler' => 'Site historique', 'other' => 'Site historique'];
    @endphp

    <div class="stock-control-page">
        <header class="stock-control-hero">
            <div class="stock-control-hero-copy">
                <span class="stock-control-kicker">TENACOS · CONTRÔLE OPÉRATIONNEL</span>
                <h1>Contrôle des stocks</h1>
                <p>Organise les réceptions en entrepôt, les transferts vers les boutiques et les sorties liées aux commandes.</p>
            </div>
            @if ($isAdmin)
                <a href="{{ route('stock-control.balances') }}" class="stock-control-hero-action"><span class="material-icons">warehouse</span>Consulter le stock</a>
                <a href="{{ route('stock-control.audit') }}" class="stock-control-hero-action"><span class="material-icons">history</span>Journal d’audit</a>
            @endif
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
                <h2>Commence par créer tes sites</h2>
                <p>Ajoute un entrepôt par pays et tes boutiques. Les zones techniques de transit et de quarantaine seront préparées automatiquement, sans stock initial.</p>
            </div>
        </section>

        <div class="stock-control-layout">
            <section class="stock-control-card stock-control-locations">
                <div class="stock-control-section-head">
                    <div><span class="stock-control-kicker">ENTREPÔTS ET BOUTIQUES</span><h2>Sites de stockage et de vente</h2></div>
                    <span class="stock-control-count">{{ $locations->count() }} emplacement(s)</span>
                </div>

                <form method="POST" action="{{ route('stock-control.locations.store') }}" class="stock-control-create-form">
                    @csrf
                    <h3>Ajouter un entrepôt ou une boutique</h3>
                    <div class="stock-control-form-grid stock-location-create-fields">
                        <label class="stock-control-field"><span>Nom affiché</span><input name="name" value="{{ old('name') }}" placeholder="Boutique Djidjolé" maxlength="120" required></label>
                        <label class="stock-control-field"><span>Type d’emplacement</span><select name="type" required><option value="warehouse" @selected(old('type') === 'warehouse')>Entrepôt</option><option value="shop" @selected(old('type', 'shop') === 'shop')>Boutique</option></select><small>Un seul entrepôt peut être créé par pays.</small></label>
                        <label class="stock-control-field"><span>Pays de l’emplacement</span><select class="js-country-search" name="country_code"><option value="">Pays non précisé</option>@foreach($countries as $code => $country)<option value="{{ $code }}" @selected(old('country_code') === $code)>{{ $country['label'] }}</option>@endforeach</select></label>
                        <label class="stock-control-field"><span>Responsable</span><select name="responsible_user_id"><option value="">À désigner</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) old('responsible_user_id') === (string) $user->id)>{{ $user->name }} · {{ $user->user_type }}</option>@endforeach</select></label>
                    </div>
                    <div class="stock-control-form-footer"><small>Le code unique sera généré automatiquement. Réceptionne les achats dans un entrepôt, puis transfère les quantités vers les boutiques. Créer un site ne lui attribue aucun stock.</small><button class="stock-control-button stock-control-button-primary" type="submit"><span class="material-icons">add</span>Ajouter le site</button></div>
                </form>

                @if ($locations->isEmpty())
                    <div class="stock-control-empty"><span class="material-icons">warehouse</span><strong>Aucun site configuré</strong><p>Crée ton entrepôt et tes boutiques. Les emplacements techniques sont créés automatiquement avec le premier site.</p></div>
                @else
                    <div class="stock-location-list">
                        @foreach ($locations as $location)
                            <article class="stock-location-item">
                                <div class="stock-location-heading">
                                    <div class="stock-location-symbol"><span class="material-icons">{{ $location->type === 'shop' ? 'storefront' : 'warehouse' }}</span></div>
                                    <div class="stock-location-title"><div class="stock-location-name">{{ $location->name }} @if (in_array($location->code, $systemLocationCodes, true))<span class="stock-control-system-tag">Système</span>@endif</div><div class="stock-location-meta"><code>{{ $location->code }}</code><span>{{ $typeLabels[$location->type] ?? ($legacyTypeLabels[$location->type] ?? 'Site historique') }}</span>@if($location->country_code)<span>{{ $countries[$location->country_code]['label'] ?? $location->country_code }}</span>@endif</div></div>
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
                                            <label class="stock-control-field"><span>Pays de l’emplacement</span><select class="js-country-search" name="country_code"><option value="">Pays non précisé</option>@foreach($countries as $code => $country)<option value="{{ $code }}" @selected($location->country_code === $code)>{{ $country['label'] }}</option>@endforeach</select></label>
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
                                @if (in_array($location->type, ['warehouse', 'shop'], true))
                                    <form method="POST" action="{{ route('stock-control.locations.destroy', $location) }}" class="stock-location-delete-form" data-stock-confirm-title="Supprimer cet emplacement ?" data-stock-confirm-message="L’emplacement {{ $location->name }} sera supprimé s’il n’a aucune opération de stock liée. Cette action est définitive.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="stock-control-button stock-control-button-danger" type="submit"><span class="material-icons">delete</span>Supprimer l’emplacement</button>
                                    </form>
                                @endif
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
                        <div class="stock-rule-toggle"><span><strong>Approbations requises</strong><small>Une approbation est demandée uniquement pour les réceptions fournisseur et les transferts d’un entrepôt vers une boutique.</small></span></div>
                        <label class="stock-rule-toggle"><span><strong>Justificatif pour casse ou perte</strong><small>Demande une pièce jointe selon les règles activées.</small></span><input type="checkbox" name="loss_requires_attachment" value="1" @checked($rules['loss_requires_attachment'])></label>
                        <div class="stock-control-rule-grid">
                            <label class="stock-control-field"><span>Boutique utilisée pour les ventes</span><select name="default_sales_location_id"><option value="">Non configurée</option>@foreach($salesLocations as $salesLocation)<option value="{{ $salesLocation->id }}" @selected((string) old('default_sales_location_id', $rules['default_sales_location_id'] ?? '') === (string) $salesLocation->id)>{{ $salesLocation->name }} · {{ $salesLocation->code }}</option>@endforeach</select><small>À la livraison d’une commande, le stock sera déduit de cette boutique.</small></label>
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
                        <div><span>Approbations</span><strong>Réceptions fournisseur et transferts entrepôt → boutique</strong></div>
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
