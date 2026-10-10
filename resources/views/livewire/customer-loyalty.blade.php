<div class="loyalty-page">
    <div class="loyalty-header">
        <div class="loyalty-heading-copy"><span class="loyalty-eyebrow">SERVICE CLIENT <i></i> CRM</span><h1>Fidélité des clientes</h1><p>Repère les clientes à valoriser et celles à accompagner pour les faire revenir.</p></div>
        <div class="loyalty-header-actions">
            @if($canRecordReferral)<button type="button" class="btn loyalty-secondary-button" wire:click="toggleReferralForm"><span class="material-icons">diversity_3</span>Ajouter une recommandation</button>@endif
            @if($isAdmin)<a href="{{ route('customer-loyalty.rules') }}" class="btn loyalty-settings-button"><span class="material-icons">tune</span>Règles de fidélité</a>@endif
        </div>
    </div>

    @if (session()->has('loyaltyMessage'))<div class="alert alert-success">{{ session('loyaltyMessage') }}</div>@endif

    @if($showReferralForm)
        <section class="loyalty-rules-card">
            <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">BOUCHE-À-OREILLE</span><h2>Enregistrer une recommandation</h2><p>Recherche les deux clientes par leur nom, leur téléphone ou leur e-mail.</p></div><button class="btn btn-light" type="button" wire:click="toggleReferralForm">Fermer</button></div>
            <form wire:submit.prevent="recordReferral" class="loyalty-form-grid loyalty-referral-form">
                <div class="loyalty-customer-picker">
                    <label for="referrer-customer-search">Cliente référente</label>
                    <div class="loyalty-customer-searchbox"><span class="material-icons">search</span><input id="referrer-customer-search" class="form-control" type="search" autocomplete="off" wire:model.debounce.300ms="referrerSearch" placeholder="Nom, téléphone ou e-mail">@if($referrerCustomerId)<button type="button" class="loyalty-clear-selection" wire:click="clearReferralCustomer('referrer')" aria-label="Changer la cliente référente"><span class="material-icons">close</span></button>@endif</div>
                    @if($referrerCustomerId)<small class="loyalty-selected-customer"><span class="material-icons">check_circle</span>Cliente sélectionnée</small>@endif
                    @if($referrerMatches->isNotEmpty())<div class="loyalty-customer-suggestions">@foreach($referrerMatches as $customer)<button type="button" wire:click="selectReferralCustomer('referrer', {{ $customer->id }})"><strong>{{ $customer->name }}</strong><small>{{ $customer->phone ?: $customer->email ?: 'ID '.$customer->id }}</small></button>@endforeach</div>@elseif(mb_strlen(trim($referrerSearch)) >= 2 && ! $referrerCustomerId)<small class="loyalty-search-hint">Aucune cliente trouvée.</small>@endif
                    @error('referrerCustomerId')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="loyalty-customer-picker">
                    <label for="referred-customer-search">Cliente recommandée</label>
                    <div class="loyalty-customer-searchbox"><span class="material-icons">person_add</span><input id="referred-customer-search" class="form-control" type="search" autocomplete="off" wire:model.debounce.300ms="referredSearch" placeholder="Nom, téléphone ou e-mail">@if($referredCustomerId)<button type="button" class="loyalty-clear-selection" wire:click="clearReferralCustomer('referred')" aria-label="Changer la cliente recommandée"><span class="material-icons">close</span></button>@endif</div>
                    @if($referredCustomerId)<small class="loyalty-selected-customer"><span class="material-icons">check_circle</span>Cliente sélectionnée</small>@endif
                    @if($referredMatches->isNotEmpty())<div class="loyalty-customer-suggestions">@foreach($referredMatches as $customer)<button type="button" wire:click="selectReferralCustomer('referred', {{ $customer->id }})"><strong>{{ $customer->name }}</strong><small>{{ $customer->phone ?: $customer->email ?: 'ID '.$customer->id }}</small></button>@endforeach</div>@elseif(mb_strlen(trim($referredSearch)) >= 2 && ! $referredCustomerId)<small class="loyalty-search-hint">Aucune cliente trouvée.</small>@endif
                    @error('referredCustomerId')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="loyalty-referral-submit"><button class="btn loyalty-primary-button" type="submit" wire:loading.attr="disabled" wire:target="recordReferral"><span class="material-icons">save</span>Enregistrer la recommandation</button></div>
            </form>
        </section>
    @endif

    <section class="loyalty-summary-grid">
        <article class="loyalty-summary-card summary-customers"><span class="loyalty-summary-icon material-icons">groups</span><small>Portefeuille analysé</small><strong>{{ number_format($summary->customers_count ?? 0, 0, ',', ' ') }}</strong><em>clientes suivies</em></article>
        <article class="loyalty-summary-card summary-faithful"><span class="loyalty-summary-icon material-icons">workspace_premium</span><small>Fidèles et VIP</small><strong>{{ number_format(($categoryCounts['faithful'] ?? 0) + ($categoryCounts['vip'] ?? 0), 0, ',', ' ') }}</strong><em>clientes à valoriser</em></article>
        <article class="loyalty-summary-card summary-score"><span class="loyalty-summary-icon material-icons">query_stats</span><small>Score moyen</small><strong>{{ number_format($summary->average_score ?? 0, 1, ',', ' ') }}<sup>/100</sup></strong><em>sur l’ensemble du portefeuille</em></article>
        <article class="loyalty-summary-card summary-alert"><span class="loyalty-summary-icon material-icons">autorenew</span><small>À réactiver ou à risque</small><strong>{{ number_format(($segmentCounts['to_reactivate'] ?? 0) + ($segmentCounts['at_risk'] ?? 0), 0, ',', ' ') }}</strong><em>actions de fidélisation à prévoir</em></article>
    </section>

    <section class="loyalty-category-strip" aria-label="Répartition des catégories clientes">
        @foreach($categories as $key => $label)
            @php $categoryTotal = max(1, (int) ($summary->customers_count ?? 0)); $categoryValue = (int) ($categoryCounts[$key] ?? 0); @endphp
            <button type="button" class="loyalty-category-tile category-tile-{{ $key }} {{ $categoryFilter === $key ? 'is-selected' : '' }}" wire:click="$set('categoryFilter', '{{ $categoryFilter === $key ? '' : $key }}')">
                <span class="category-tile-top"><span class="category-dot"></span><span>{{ $label }}</span></span><strong>{{ number_format($categoryValue, 0, ',', ' ') }}</strong>
                <span class="category-tile-share">{{ number_format($categoryValue / $categoryTotal * 100, 1, ',', ' ') }} % du portefeuille</span>
            </button>
        @endforeach
    </section>

    <section class="loyalty-list-card loyalty-performance-card">
        @php $maxCategoryRevenue = max(1, (float) $categoryPerformance->max('revenue')); @endphp
        <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">PERFORMANCE COMMERCIALE</span><h2>Chiffre d’affaires par catégorie</h2><p>Compare les ventes générées par chaque profil cliente.</p></div><div class="loyalty-performance-tools"><label class="loyalty-year-filter"><span>Année</span><select class="form-control" wire:model="revenueYear" aria-label="Filtrer le chiffre d’affaires par année"><option value="">Globalement</option>@foreach($revenueYears as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select></label><div class="loyalty-repurchase"><span class="material-icons">repeat</span><span><small>Taux de réachat global</small><strong>{{ number_format($summary->repurchase_rate ?? 0, 1, ',', ' ') }} %</strong></span></div></div></div>
        <div class="loyalty-revenue-chart" role="group" aria-label="Graphique du chiffre d’affaires par catégorie cliente">
            @foreach($categories as $key => $label)
                @php $performance = $categoryPerformance[$key] ?? null; $revenue = (float) ($performance->revenue ?? 0); $barHeight = $revenue > 0 ? max(8, round($revenue / $maxCategoryRevenue * 100)) : 2; @endphp
                    <button type="button" class="loyalty-chart-column {{ $categoryFilter === $key ? 'is-selected' : '' }}" wire:click="$set('categoryFilter', '{{ $categoryFilter === $key ? '' : $key }}')" title="{{ $label }} : {{ number_format($revenue, 0, ',', ' ') }} F CFA, {{ number_format($performance->customers_count ?? 0, 0, ',', ' ') }} cliente(s)">
                        <span class="loyalty-chart-value">{{ number_format($revenue, 0, ',', ' ') }} <small>F</small></span>
                        <span class="loyalty-chart-stage"><i class="chart-{{ $key }}" style="height:{{ $barHeight }}%"></i></span>
                        <span class="loyalty-chart-label"><i class="category-dot dot-{{ $key }}"></i>{{ $label }}</span>
                        <small class="loyalty-chart-count">{{ number_format($performance->customers_count ?? 0, 0, ',', ' ') }} cliente(s)</small>
                    </button>
            @endforeach
        </div>
        <div class="loyalty-chart-caption"><span><i></i> Chiffre d’affaires des commandes prises en compte par les règles de fidélité{{ $selectedRevenueYear ? ' · '.$selectedRevenueYear : '' }}</span><span>Clique sur une colonne pour filtrer les clientes</span></div>
    </section>

    <section class="loyalty-list-card">
        <div class="loyalty-section-heading"><div><span class="loyalty-eyebrow">SEGMENTATION</span><h2>{{ $categoryFilter !== '' ? ($categories[$categoryFilter] ?? 'Portefeuille clientes') : 'Portefeuille clientes' }}</h2><p>Recherche et filtres pour retrouver rapidement les bons profils.</p></div><span class="loyalty-list-count"><strong>{{ number_format($profiles->total(), 0, ',', ' ') }}</strong> résultat(s)</span></div>
        <div class="loyalty-filters">
            <label class="loyalty-search"><span class="material-icons">search</span><input type="search" wire:model.debounce.400ms="search" placeholder="Nom, téléphone, e-mail ou identifiant"></label>
            <label class="loyalty-filter-field"><span>Catégorie</span><select class="form-control" wire:model="categoryFilter"><option value="">Toutes</option>@foreach($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label class="loyalty-filter-field"><span>Segment</span><select class="form-control" wire:model="segmentFilter"><option value="">Tous</option>@foreach($segmentLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label class="loyalty-filter-field"><span>Score minimum</span><input class="form-control" type="number" min="0" max="100" wire:model.debounce.300ms="minScore" placeholder="Ex. 70"></label>
            <label class="loyalty-filter-field"><span>Commandes min.</span><input class="form-control" type="number" min="0" wire:model.debounce.300ms="minOrders" placeholder="Ex. 3"></label>
            <label class="loyalty-filter-field"><span>Dépenses min. (FCFA)</span><input class="form-control" type="number" min="0" wire:model.debounce.300ms="minSpend" placeholder="Ex. 100 000"></label>
            <label class="loyalty-filter-field"><span>Dernier achat depuis</span><input class="form-control" type="date" wire:model="purchaseFrom"></label>
            <label class="loyalty-filter-field"><span>Dernier achat jusqu’au</span><input class="form-control" type="date" wire:model="purchaseTo"></label>
            <label class="loyalty-filter-field"><span>Produit acheté</span><select class="form-control" wire:model="productFilter"><option value="">Tous les produits</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
        </div>
        @if($search || $categoryFilter || $segmentFilter || $minScore !== '' || $minOrders !== '' || $minSpend !== '' || $purchaseFrom || $purchaseTo || $productFilter)
            <div class="loyalty-filter-footer"><span><span class="material-icons">filter_alt</span>Filtres actifs</span><button type="button" wire:click="clearFilters">Effacer les filtres <span class="material-icons">close</span></button></div>
        @endif
        <div wire:loading.flex class="loyalty-loading"><span></span>Actualisation des clientes…</div>
        <div class="table-responsive loyalty-desktop-list">
            <table class="table loyalty-table mb-0">
                <thead><tr><th>Cliente</th><th>Score</th><th>Catégorie</th><th>Commandes</th><th>Total dépensé</th><th>Dernier achat</th><th>Fréquence</th><th>Recommandations</th><th>Segments</th><th></th></tr></thead>
                <tbody>
                @forelse($profiles as $profile)
                    @php $data = $profile->metrics ?? []; @endphp
                    <tr>
                        <td><a href="{{ route('view-costumers', $profile->costumer_id) }}"><strong>{{ $profile->customer?->name ?? 'Cliente supprimée' }}</strong></a><small>{{ $profile->customer?->phone }}</small></td>
                        <td><span class="loyalty-score"><strong>{{ $profile->score }}</strong><small>/100</small></span><span class="loyalty-score-track"><i style="width:{{ $profile->score }}%"></i></span></td>
                        <td><span class="loyalty-category category-{{ $profile->category }}">{{ $categories[$profile->category] ?? $profile->category }}</span></td>
                        <td>{{ number_format($data['orders_count'] ?? 0) }}</td>
                        <td>{{ number_format($data['total_spent'] ?? 0, 0, ',', ' ') }} F</td>
                        <td>@if(!empty($data['last_purchase_at'])){{ \Carbon\Carbon::parse($data['last_purchase_at'])->format('d/m/Y') }}<small>Il y a {{ $data['days_since_last_purchase'] }} j</small>@else—@endif</td>
                        <td>{{ isset($data['average_purchase_gap_days']) ? number_format($data['average_purchase_gap_days'], 0, ',', ' ').' j' : '—' }}<small>{{ ucfirst($data['regularity'] ?? '—') }}</small></td>
                        @php $referral = $referralDetails[$profile->costumer_id] ?? ['count' => 0, 'names' => [], 'referred_by' => null]; $referralLabel = $referral['names'] ? implode(', ', array_slice($referral['names'], 0, 2)).(count($referral['names']) > 2 ? ' +'.(count($referral['names']) - 2) : '') : ($referral['referred_by'] ? 'Recommandée par '.$referral['referred_by'] : 'Aucune recommandation'); @endphp
                        <td><div class="loyalty-referral-info"><strong>{{ $referral['count'] }} recommandée(s)</strong><small>{{ $referralLabel }}</small></div></td>
                        <td><div class="loyalty-segments">@foreach($data['segments'] ?? [] as $segment)<span>{{ $segmentLabels[$segment] ?? $segment }}</span>@endforeach @if(($data['open_service_cases_count'] ?? 0)>0)<small>{{ $data['open_service_cases_count'] }} dossier(s) SAV ouvert(s)</small>@endif</div></td>
                        <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('view-costumers', $profile->costumer_id) }}" aria-label="Ouvrir la fiche"><span class="material-icons">arrow_forward</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="loyalty-empty"><span class="material-icons">person_search</span><strong>Aucun profil calculé</strong><small>Après installation, lance le recalcul initial pour analyser les clientes existantes.</small></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="loyalty-mobile-list">
            @forelse($profiles as $profile)
                @php $data = $profile->metrics ?? []; @endphp
                <article class="loyalty-mobile-card">
                    <div class="loyalty-mobile-card-head">
                        <div class="loyalty-avatar">{{ mb_substr($profile->customer?->name ?? '?', 0, 1) }}</div>
                        <div class="loyalty-mobile-name"><a href="{{ route('view-costumers', $profile->costumer_id) }}"><strong>{{ $profile->customer?->name ?? 'Cliente supprimée' }}</strong></a><small>{{ $profile->customer?->phone }}</small></div>
                        <span class="loyalty-mobile-score">{{ $profile->score }}<small>/100</small></span>
                    </div>
                    <div class="loyalty-mobile-badges"><span class="loyalty-category category-{{ $profile->category }}">{{ $categories[$profile->category] ?? $profile->category }}</span>@foreach($data['segments'] ?? [] as $segment)<span class="loyalty-mobile-segment">{{ $segmentLabels[$segment] ?? $segment }}</span>@endforeach</div>
                    <div class="loyalty-mobile-stats"><span><small>Commandes</small><strong>{{ number_format($data['orders_count'] ?? 0) }}</strong></span><span><small>Total dépensé</small><strong>{{ number_format($data['total_spent'] ?? 0, 0, ',', ' ') }} F</strong></span><span><small>Dernier achat</small><strong>@if(!empty($data['last_purchase_at'])){{ \Carbon\Carbon::parse($data['last_purchase_at'])->format('d/m/Y') }}@else—@endif</strong></span></div>
                    @php $referral = $referralDetails[$profile->costumer_id] ?? ['count' => 0, 'names' => [], 'referred_by' => null]; $referralLabel = $referral['names'] ? implode(', ', array_slice($referral['names'], 0, 2)).(count($referral['names']) > 2 ? ' +'.(count($referral['names']) - 2) : '') : ($referral['referred_by'] ? 'Recommandée par '.$referral['referred_by'] : 'Aucune recommandation enregistrée'); @endphp
                    <div class="loyalty-mobile-referral"><small>{{ $referral['count'] }} cliente(s) recommandée(s)</small><strong>{{ $referralLabel }}</strong></div>
                    <a class="loyalty-mobile-link" href="{{ route('view-costumers', $profile->costumer_id) }}">Voir la fiche cliente <span class="material-icons">arrow_forward</span></a>
                </article>
            @empty
                <div class="loyalty-empty"><span class="material-icons">person_search</span><strong>Aucun profil trouvé</strong><small>Essaie d’élargir les filtres ou la recherche.</small></div>
            @endforelse
        </div>
        <div class="loyalty-pagination">{{ $profiles->links() }}</div>
    </section>
</div>
