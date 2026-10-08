<section class="loyalty-rules-card">
            <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">ADMINISTRATION</span><h2>Règles de fidélité</h2><p>Configure les critères de classement. Les commandes et la satisfaction alimentent automatiquement le score des clientes.</p></div><a class="btn btn-light" href="{{ route('customer-loyalty.index') }}">Retour à la fidélité</a></div>
            <form id="loyalty-rules-form" wire:submit.prevent="saveRules">
                <h3>Pondération du score <small>La somme doit faire 100.</small></h3>
                <div class="loyalty-form-grid loyalty-weight-grid">
                    @foreach(['recency'=>'Récence','frequency'=>'Fréquence','orders'=>'Nombre d’achats','spend'=>'Montant dépensé','regularity'=>'Régularité','engagement'=>'Engagement'] as $key => $label)
                        <label>{{ $label }}<div class="input-group"><input class="form-control" type="number" min="0" max="100" wire:model.defer="weights.{{ $key }}"><span class="input-group-text">pts</span></div></label>
                    @endforeach
                </div>
                @error('weights')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                <h3 class="mt-4">Activité et catégories</h3>
                <div class="loyalty-form-grid">
                    <label>Très active jusqu’à (jours)<input class="form-control" type="number" wire:model.defer="recencyDays.very_active"></label>
                    <label>Active jusqu’à (jours)<input class="form-control" type="number" wire:model.defer="recencyDays.active"></label>
                    <label>À surveiller jusqu’à (jours)<input class="form-control" type="number" wire:model.defer="recencyDays.watch"></label>
                    <label>Inactive après (jours)<input class="form-control" type="number" wire:model.defer="recencyDays.inactive"></label>
                    <label>Nouvelle cliente, achat récent sous (jours)<input class="form-control" type="number" wire:model.defer="newCustomerDays"></label>
                    <label>Maximum de commandes pour « nouvelle »<input class="form-control" type="number" wire:model.defer="newCustomerMaxOrders"></label>
                    <label>Commandes minimum pour « régulière »<input class="form-control" type="number" wire:model.defer="regularMinOrders"></label>
                    <label>Écart moyen maximum pour « régulière » (jours)<input class="form-control" type="number" wire:model.defer="regularMaxGapDays"></label>
                    <label>Segment récemment acquise sous (jours)<input class="form-control" type="number" wire:model.defer="recentCustomerDays"></label>
                    <label>Seuil du score fidèle<input class="form-control" type="number" wire:model.defer="faithfulScore"></label>
                    <label>Commandes minimum pour être fidèle<input class="form-control" type="number" wire:model.defer="faithfulMinOrders"></label>
                    <label>Seuil du score VIP<input class="form-control" type="number" wire:model.defer="vipScore"></label>
                    <label>Commandes minimum pour être VIP<input class="form-control" type="number" wire:model.defer="vipMinOrders"></label>
                    <label>Dépenses minimum VIP (FCFA)<input class="form-control" type="number" wire:model.defer="vipMinSpend"></label>
                    <label>Risque si retard supérieur à la fréquence ×<input class="form-control" type="number" step="0.1" wire:model.defer="riskFrequencyMultiplier"></label>
                    <label>Retrait de points si réclamation ouverte<input class="form-control" type="number" wire:model.defer="unresolvedCasePenalty"></label>
                    <label>Score minimum « fort potentiel »<input class="form-control" type="number" wire:model.defer="highPotentialMinScore"></label>
                    <label>Commandes minimum « fort potentiel »<input class="form-control" type="number" wire:model.defer="highPotentialMinOrders"></label>
                </div>
                @error('recencyDays')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                <h3 class="mt-4">Commandes prises en compte</h3>
                <div class="loyalty-checks"><label><input type="checkbox" value="delivered" wire:model.defer="qualifyingStatuses"> Livrée</label><label><input type="checkbox" value="ordered" wire:model.defer="qualifyingStatuses"> En cours (à activer seulement si ces commandes correspondent à des achats confirmés)</label></div>
                @error('qualifyingStatuses')<div class="text-danger small">{{ $message }}</div>@enderror

                <h3 class="mt-4">Critères de récence <small>Points maximum accordés pour la récence.</small></h3>
                <div class="loyalty-form-grid loyalty-weight-grid">
                    @foreach(['very_active'=>'Très active','active'=>'Active','watch'=>'À surveiller','inactive'=>'Inactive'] as $key => $label)
                        <label>{{ $label }}<div class="input-group"><input class="form-control" type="number" min="0" max="100" wire:model.defer="recencyPoints.{{ $key }}"><span class="input-group-text">pts</span></div></label>
                    @endforeach
                </div>
                <h3 class="mt-4">Fréquence d’achat <small>Délai moyen entre deux achats : seuils et points.</small></h3>
                <div class="loyalty-form-grid loyalty-weight-grid">
                    @foreach([0,1,2,3] as $index)
                        <label>Intervalle ≤ (jours)<input class="form-control" type="number" min="1" wire:model.defer="frequencyDays.{{ $index }}"></label>
                        <label>Points à ce seuil<div class="input-group"><input class="form-control" type="number" min="0" max="100" wire:model.defer="frequencyPoints.{{ $index }}"><span class="input-group-text">pts</span></div></label>
                    @endforeach
                </div>
                <h3 class="mt-4">Commandes et valeur cumulée <small>Les paliers et les points sont modifiables.</small></h3>
                <div class="loyalty-form-grid loyalty-tier-grid">
                    @foreach($orderTiers as $index => $tier)
                        <label>Commandes à partir de<input class="form-control" type="number" min="1" wire:model.defer="orderTiers.{{ $index }}.threshold"></label>
                        <label>Points accordés<input class="form-control" type="number" min="0" max="100" wire:model.defer="orderTiers.{{ $index }}.points"></label>
                    @endforeach
                    @foreach($spendTiers as $index => $tier)
                        <label>Dépenses à partir de (FCFA)<input class="form-control" type="number" min="0" wire:model.defer="spendTiers.{{ $index }}.threshold"></label>
                        <label>Points accordés<input class="form-control" type="number" min="0" max="100" wire:model.defer="spendTiers.{{ $index }}.points"></label>
                    @endforeach
                </div>
                <h3 class="mt-4">Segments disponibles</h3>
                <div class="loyalty-checks">
                    @foreach(['vip'=>'VIP','recent'=>'Récemment acquise','high_potential'=>'Fort potentiel','at_risk'=>'À risque','to_reactivate'=>'À réactiver','high_spend'=>'Fort montant dépensé','service_attention'=>'Suivi SAV requis'] as $key => $label)
                        <label><input type="checkbox" wire:model.defer="segments.{{ $key }}"> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="d-flex align-items-center justify-content-between flex-wrap mt-4 mb-2"><h3 class="mb-0">Segments personnalisés</h3><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addCustomSegment">Ajouter un segment</button></div>
                @error('customSegments')<small class="text-danger d-block mb-2">{{ $message }}</small>@enderror
                @foreach($customSegments as $index => $segment)
                    <div class="loyalty-custom-segment">
                        <div class="loyalty-form-grid">
                            <label>Nom du segment<input class="form-control" type="text" maxlength="50" wire:model.defer="customSegments.{{ $index }}.name" placeholder="Ex. Acheteuses gamme AKA"></label>
                            <label>Commandes minimum<input class="form-control" type="number" min="0" wire:model.defer="customSegments.{{ $index }}.min_orders"></label>
                            <label>Dépenses minimum (FCFA)<input class="form-control" type="number" min="0" wire:model.defer="customSegments.{{ $index }}.min_spend"></label>
                            <label>Score minimum<input class="form-control" type="number" min="0" max="100" wire:model.defer="customSegments.{{ $index }}.min_score"></label>
                            <label>Achat datant de moins de (jours)<input class="form-control" type="number" min="0" wire:model.defer="customSegments.{{ $index }}.max_days_since_last_purchase"></label>
                            <label>Recommandations minimum<input class="form-control" type="number" min="0" wire:model.defer="customSegments.{{ $index }}.min_referrals"></label>
                            <label>Dossiers SAV ouverts minimum<input class="form-control" type="number" min="0" wire:model.defer="customSegments.{{ $index }}.min_open_cases"></label>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger mt-2" wire:click="removeCustomSegment({{ $index }})">Supprimer ce segment</button>
                        @foreach(['name','min_orders','min_spend','min_score','max_days_since_last_purchase','min_referrals','min_open_cases'] as $field)@error('customSegments.'.$index.'.'.$field)<small class="text-danger d-block">{{ $message }}</small>@enderror @endforeach
                    </div>
                @endforeach
                <h3 class="mt-4">Conditions du statut VIP <small>Les conditions cochées doivent toutes être remplies.</small></h3>
                <div class="loyalty-checks">
                    <label><input type="checkbox" wire:model.defer="vipRequirements.score"> Score minimum VIP</label>
                    <label><input type="checkbox" wire:model.defer="vipRequirements.orders"> Nombre minimum de commandes</label>
                    <label><input type="checkbox" wire:model.defer="vipRequirements.spend"> Montant dépensé minimum</label>
                    <label><input type="checkbox" wire:model.defer="vipRequirements.regularity"> Achats réguliers</label>
                </div>
                <h3 class="mt-4">Noms des catégories</h3>
                <div class="loyalty-form-grid">
                    @foreach($categoryNames as $key => $name)
                        <label>{{ ['new'=>'Nouvelle','occasional'=>'Occasionnelle','regular'=>'Régulière','faithful'=>'Fidèle','vip'=>'VIP','inactive'=>'Inactive','no_purchase'=>'Sans achat'][$key] ?? $key }}<input class="form-control" type="text" maxlength="40" wire:model.defer="categoryNames.{{ $key }}"></label>
                    @endforeach
                </div>
                <div class="d-flex justify-content-end mt-4"><button class="btn loyalty-primary-button" type="submit" wire:loading.attr="disabled" wire:target="saveRules"><span class="material-icons">save</span>Enregistrer les règles</button></div>
            </form>
        </section>
