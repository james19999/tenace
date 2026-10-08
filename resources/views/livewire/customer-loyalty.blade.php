<div class="loyalty-page">
    <div class="loyalty-header">
        <div><span class="loyalty-eyebrow">RELATION CLIENT</span><h1>Fidélité des clientes</h1><p>Comprendre les habitudes d’achat et choisir la bonne action au bon moment.</p></div>
        <div class="loyalty-header-actions">
            @if($canRecordReferral)<button type="button" class="btn btn-outline-secondary" wire:click="toggleReferralForm"><span class="material-icons">diversity_3</span>Enregistrer une recommandation</button>@endif
            @if($isAdmin)<button type="button" class="btn loyalty-settings-button" wire:click="toggleRules"><span class="material-icons">tune</span>Règles de fidélité</button>@endif
        </div>
    </div>

    @if (session()->has('loyaltyMessage'))<div class="alert alert-success">{{ session('loyaltyMessage') }}</div>@endif

    @if($showReferralForm)
        <section class="loyalty-rules-card">
            <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">BOUCHE-À-OREILLE</span><h2>Enregistrer une recommandation</h2><p>Choisis les deux fiches déjà présentes dans TENACOS pour éviter les doublons.</p></div><button class="btn btn-light" type="button" wire:click="toggleReferralForm">Fermer</button></div>
            <form wire:submit.prevent="recordReferral" class="loyalty-form-grid">
                <label>Identifiant de la cliente référente<input class="form-control" type="number" min="1" wire:model.defer="referrerCustomerId" placeholder="ID visible sur la fiche cliente">@error('referrerCustomerId')<small class="text-danger">{{ $message }}</small>@enderror</label>
                <label>Identifiant de la nouvelle cliente<input class="form-control" type="number" min="1" wire:model.defer="referredCustomerId" placeholder="ID de la fiche cliente">@error('referredCustomerId')<small class="text-danger">{{ $message }}</small>@enderror</label>
                <div class="align-self-end"><button class="btn loyalty-primary-button" type="submit"><span class="material-icons">save</span>Enregistrer</button></div>
            </form>
        </section>
    @endif

    <section class="loyalty-summary-grid">
        <article class="loyalty-summary-card"><span class="material-icons">groups</span><small>Clientes analysées</small><strong>{{ number_format($summary->customers_count ?? 0, 0, ',', ' ') }}</strong></article>
        <article class="loyalty-summary-card"><span class="material-icons">loyalty</span><small>Fidèles et VIP</small><strong>{{ number_format(($categoryCounts['faithful'] ?? 0) + ($categoryCounts['vip'] ?? 0), 0, ',', ' ') }}</strong></article>
        <article class="loyalty-summary-card"><span class="material-icons">query_stats</span><small>Score moyen</small><strong>{{ number_format($summary->average_score ?? 0, 1, ',', ' ') }}<em>/100</em></strong></article>
        <article class="loyalty-summary-card loyalty-alert-card"><span class="material-icons">notifications_active</span><small>À réactiver / à risque</small><strong>{{ number_format(($segmentCounts['to_reactivate'] ?? 0) + ($segmentCounts['at_risk'] ?? 0), 0, ',', ' ') }}</strong></article>
    </section>

    <section class="loyalty-list-card loyalty-performance-card">
        <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">PERFORMANCE COMMERCIALE</span><h2>Valeur par catégorie</h2></div><span class="loyalty-list-count">Réachat global : {{ number_format($summary->repurchase_rate ?? 0, 1, ',', ' ') }} %</span></div>
        <div class="table-responsive"><table class="table loyalty-table mb-0"><thead><tr><th>Catégorie</th><th>Clientes</th><th>Chiffre d’affaires</th><th>Panier moyen</th><th>Commandes moyennes</th></tr></thead><tbody>
            @foreach($categories as $key => $label)
                @php $performance = $categoryPerformance[$key] ?? null; @endphp
                @if($performance)
                    <tr><td><button type="button" class="loyalty-text-filter" wire:click="$set('categoryFilter', '{{ $key }}')">{{ $label }}</button></td><td>{{ number_format($performance->customers_count, 0, ',', ' ') }}</td><td>{{ number_format($performance->revenue, 0, ',', ' ') }} F</td><td>{{ number_format($performance->average_basket, 0, ',', ' ') }} F</td><td>{{ number_format($performance->average_orders, 1, ',', ' ') }}</td></tr>
                @endif
            @endforeach
        </tbody></table></div>
    </section>

    <section class="loyalty-category-strip">
        @foreach($categories as $key => $label)
            <button type="button" class="{{ $categoryFilter === $key ? 'is-selected' : '' }}" wire:click="$set('categoryFilter', '{{ $categoryFilter === $key ? '' : $key }}')">
                <span>{{ $label }}</span><strong>{{ number_format($categoryCounts[$key] ?? 0) }}</strong>
            </button>
        @endforeach
    </section>

    @if($isAdmin && $showRules)
        <section class="loyalty-rules-card">
            <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">ADMINISTRATION</span><h2>Règles de calcul</h2><p>Les changements modifient le classement automatiquement. Les commandes livrées sont prises en compte par défaut.</p></div><button class="btn btn-light" type="button" wire:click="toggleRules">Fermer</button></div>
            <form wire:submit.prevent="saveRules">
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
    @endif

    <section class="loyalty-list-card">
        <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">SEGMENTATION</span><h2>Portefeuille clientes</h2></div><span class="loyalty-list-count">{{ number_format($profiles->total(), 0, ',', ' ') }} résultat(s)</span></div>
        <div class="loyalty-filters">
            <label class="loyalty-search"><span class="material-icons">search</span><input type="search" wire:model.debounce.400ms="search" placeholder="Nom, téléphone, e-mail ou identifiant"></label>
            <select class="form-control" wire:model="categoryFilter"><option value="">Toutes les catégories</option>@foreach($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
            <select class="form-control" wire:model="segmentFilter"><option value="">Tous les segments</option>@foreach($segmentLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
            <input class="form-control" type="number" min="0" max="100" wire:model.debounce.300ms="minScore" placeholder="Score minimum">
            <input class="form-control" type="number" min="0" wire:model.debounce.300ms="minOrders" placeholder="Commandes minimum">
            <input class="form-control" type="number" min="0" wire:model.debounce.300ms="minSpend" placeholder="Dépenses minimum (FCFA)">
            <input class="form-control" type="date" wire:model="purchaseFrom" aria-label="Dernier achat à partir du">
            <input class="form-control" type="date" wire:model="purchaseTo" aria-label="Dernier achat jusqu’au">
            <select class="form-control" wire:model="productFilter"><option value="">Tous les produits</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
        </div>
        <div class="table-responsive">
            <table class="table loyalty-table mb-0">
                <thead><tr><th>Cliente</th><th>Score</th><th>Catégorie</th><th>Commandes</th><th>Total dépensé</th><th>Dernier achat</th><th>Fréquence</th><th>Segments</th><th></th></tr></thead>
                <tbody>
                @forelse($profiles as $profile)
                    @php $data = $profile->metrics ?? []; @endphp
                    <tr>
                        <td><a href="{{ route('view-costumers', $profile->costumer_id) }}"><strong>{{ $profile->customer?->name ?? 'Cliente supprimée' }}</strong></a><small>{{ $profile->customer?->phone }}</small></td>
                        <td><span class="loyalty-score"><strong>{{ $profile->score }}</strong><small>/100</small></span></td>
                        <td><span class="loyalty-category category-{{ $profile->category }}">{{ $categories[$profile->category] ?? $profile->category }}</span></td>
                        <td>{{ number_format($data['orders_count'] ?? 0) }}</td>
                        <td>{{ number_format($data['total_spent'] ?? 0, 0, ',', ' ') }} F</td>
                        <td>@if(!empty($data['last_purchase_at'])){{ \Carbon\Carbon::parse($data['last_purchase_at'])->format('d/m/Y') }}<small>Il y a {{ $data['days_since_last_purchase'] }} j</small>@else—@endif</td>
                        <td>{{ isset($data['average_purchase_gap_days']) ? number_format($data['average_purchase_gap_days'], 0, ',', ' ').' j' : '—' }}<small>{{ ucfirst($data['regularity'] ?? '—') }}</small></td>
                        <td><div class="loyalty-segments">@foreach($data['segments'] ?? [] as $segment)<span>{{ $segmentLabels[$segment] ?? $segment }}</span>@endforeach @if(($data['open_service_cases_count'] ?? 0)>0)<small>{{ $data['open_service_cases_count'] }} dossier(s) SAV ouvert(s)</small>@endif</div></td>
                        <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('view-costumers', $profile->costumer_id) }}" aria-label="Ouvrir la fiche"><span class="material-icons">arrow_forward</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="loyalty-empty"><span class="material-icons">person_search</span><strong>Aucun profil calculé</strong><small>Après installation, lance le recalcul initial pour analyser les clientes existantes.</small></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="loyalty-pagination">{{ $profiles->links() }}</div>
    </section>
</div>
