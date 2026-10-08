<?php

namespace App\Http\Livewire;

use App\Models\Costumer;
use App\Models\CustomerLoyaltyProfile;
use App\Models\CustomerLoyaltyReferral;
use App\Models\CustomerLoyaltyScoreHistory;
use App\Models\CustomerLoyaltySettings;
use App\Models\Product;
use App\Services\CustomerLoyaltyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerLoyalty extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'categoryFilter', 'segmentFilter', 'minScore', 'minOrders', 'minSpend', 'purchaseFrom', 'purchaseTo', 'productFilter'];

    public string $search = '';
    public string $categoryFilter = '';
    public string $segmentFilter = '';
    public string $minScore = '';
    public string $minOrders = '';
    public string $minSpend = '';
    public string $purchaseFrom = '';
    public string $purchaseTo = '';
    public string $productFilter = '';
    public bool $showRules = false;
    public bool $showReferralForm = false;
    public string $referrerCustomerId = '';
    public string $referredCustomerId = '';

    public array $weights = [];
    public array $recencyDays = [];
    public array $recencyPoints = [];
    public array $frequencyPoints = [];
    public array $frequencyDays = [];
    public array $orderTiers = [];
    public array $spendTiers = [];
    public array $segments = [];
    public array $categoryNames = [];
    public array $customSegments = [];
    public array $qualifyingStatuses = [];
    public array $vipRequirements = [];
    public $newCustomerDays = 30;
    public $newCustomerMaxOrders = 1;
    public $regularMinOrders = 2;
    public $regularMaxGapDays = 90;
    public $recentCustomerDays = 30;
    public $faithfulScore = 70;
    public $faithfulMinOrders = 3;
    public $vipScore = 82;
    public $vipMinOrders = 5;
    public $vipMinSpend = 250000;
    public $riskFrequencyMultiplier = 1.75;
    public $unresolvedCasePenalty = 10;
    public $highPotentialMinScore = 55;
    public $highPotentialMinOrders = 2;

    public function mount(CustomerLoyaltyService $loyalty): void
    {
        $this->fillRules($loyalty->rules());
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingSegmentFilter(): void { $this->resetPage(); }
    public function updatingMinScore(): void { $this->resetPage(); }
    public function updatingMinOrders(): void { $this->resetPage(); }
    public function updatingMinSpend(): void { $this->resetPage(); }
    public function updatingPurchaseFrom(): void { $this->resetPage(); }
    public function updatingPurchaseTo(): void { $this->resetPage(); }
    public function updatingProductFilter(): void { $this->resetPage(); }

    protected function isAdmin(): bool
    {
        return Auth::user()->hasRole(['ADMINUSER']);
    }

    public function toggleRules(): void
    {
        abort_unless($this->isAdmin(), 403);
        $this->showRules = ! $this->showRules;
    }

    public function addCustomSegment(): void
    {
        abort_unless($this->isAdmin(), 403);
        if (count($this->customSegments) >= 12) {
            $this->addError('customSegments', 'Tu peux définir jusqu’à 12 segments personnalisés.');
            return;
        }
        $this->customSegments[] = ['name' => '', 'min_orders' => null, 'min_spend' => null, 'min_score' => null, 'max_days_since_last_purchase' => null, 'min_referrals' => null, 'min_open_cases' => null];
    }

    public function removeCustomSegment(int $index): void
    {
        abort_unless($this->isAdmin(), 403);
        unset($this->customSegments[$index]);
        $this->customSegments = array_values($this->customSegments);
    }

    public function toggleReferralForm(): void
    {
        abort_unless($this->isAdmin() || Auth::user()->hasRole(['MNG', 'SCR']), 403);
        $this->showReferralForm = ! $this->showReferralForm;
    }

    protected function fillRules(array $rules): void
    {
        $this->weights = $rules['weights'];
        $this->recencyDays = $rules['recency_days'];
        $this->recencyPoints = $rules['recency_points'];
        $this->frequencyPoints = array_values($rules['frequency_points']);
        $this->frequencyDays = $rules['frequency_days'];
        $this->orderTiers = $this->tierRows($rules['order_tiers']);
        $this->spendTiers = $this->tierRows($rules['spend_tiers']);
        $this->segments = $rules['segments'];
        $this->categoryNames = $rules['category_labels'];
        $this->customSegments = array_values($rules['custom_segments'] ?? []);
        $this->qualifyingStatuses = $rules['qualifying_statuses'];
        $this->vipRequirements = [
            'score' => $rules['vip_require_score'], 'orders' => $rules['vip_require_orders'],
            'spend' => $rules['vip_require_spend'], 'regularity' => $rules['vip_require_regularity'],
        ];
        $this->newCustomerDays = $rules['new_customer_days'];
        $this->newCustomerMaxOrders = $rules['new_customer_max_orders'];
        $this->regularMinOrders = $rules['regular_min_orders'];
        $this->regularMaxGapDays = $rules['regular_max_gap_days'];
        $this->recentCustomerDays = $rules['recent_customer_days'];
        $this->faithfulScore = $rules['faithful_score'];
        $this->faithfulMinOrders = $rules['faithful_min_orders'];
        $this->vipScore = $rules['vip_score'];
        $this->vipMinOrders = $rules['vip_min_orders'];
        $this->vipMinSpend = $rules['vip_min_spend'];
        $this->riskFrequencyMultiplier = $rules['risk_frequency_multiplier'];
        $this->unresolvedCasePenalty = $rules['unresolved_case_penalty'];
        $this->highPotentialMinScore = $rules['high_potential_min_score'];
        $this->highPotentialMinOrders = $rules['high_potential_min_orders'];
    }

    public function saveRules(CustomerLoyaltyService $loyalty): void
    {
        abort_unless($this->isAdmin(), 403);
        $this->validate([
            'weights.recency' => ['required', 'integer', 'min:0', 'max:100'],
            'weights.frequency' => ['required', 'integer', 'min:0', 'max:100'],
            'weights.orders' => ['required', 'integer', 'min:0', 'max:100'],
            'weights.spend' => ['required', 'integer', 'min:0', 'max:100'],
            'weights.regularity' => ['required', 'integer', 'min:0', 'max:100'],
            'weights.engagement' => ['required', 'integer', 'min:0', 'max:100'],
            'newCustomerDays' => ['required', 'integer', 'min:1', 'max:365'],
            'newCustomerMaxOrders' => ['required', 'integer', 'min:1', 'max:20'],
            'regularMinOrders' => ['required', 'integer', 'min:2', 'max:100'],
            'regularMaxGapDays' => ['required', 'integer', 'min:1', 'max:1825'],
            'recentCustomerDays' => ['required', 'integer', 'min:1', 'max:365'],
            'faithfulScore' => ['required', 'integer', 'min:1', 'max:100'],
            'faithfulMinOrders' => ['required', 'integer', 'min:2', 'max:100'],
            'vipScore' => ['required', 'integer', 'min:1', 'max:100'],
            'vipMinOrders' => ['required', 'integer', 'min:2', 'max:100'],
            'vipMinSpend' => ['required', 'numeric', 'min:0'],
            'riskFrequencyMultiplier' => ['required', 'numeric', 'min:1', 'max:10'],
            'unresolvedCasePenalty' => ['required', 'integer', 'min:0', 'max:50'],
            'highPotentialMinScore' => ['required', 'integer', 'min:0', 'max:100'],
            'highPotentialMinOrders' => ['required', 'integer', 'min:1', 'max:100'],
            'vipRequirements.*' => ['boolean'],
            'recencyDays.very_active' => ['required', 'integer', 'min:1', 'max:365'],
            'recencyDays.active' => ['required', 'integer', 'min:1', 'max:730'],
            'recencyDays.watch' => ['required', 'integer', 'min:1', 'max:1095'],
            'recencyDays.inactive' => ['required', 'integer', 'min:1', 'max:1825'],
            'frequencyDays.*' => ['required', 'integer', 'min:1', 'max:1825'],
            'frequencyPoints.*' => ['required', 'integer', 'min:0', 'max:100'],
            'orderTiers.*.threshold' => ['required', 'integer', 'min:1', 'max:1000'],
            'orderTiers.*.points' => ['required', 'integer', 'min:0', 'max:100'],
            'spendTiers.*.threshold' => ['required', 'numeric', 'min:0'],
            'spendTiers.*.points' => ['required', 'integer', 'min:0', 'max:100'],
            'qualifyingStatuses' => ['required', 'array', 'min:1'],
            'qualifyingStatuses.*' => ['in:ordered,delivered'],
            'categoryNames.*' => ['required', 'string', 'max:40'],
            'customSegments.*.name' => ['required', 'string', 'max:50'],
            'customSegments.*.min_orders' => ['nullable', 'integer', 'min:0'],
            'customSegments.*.min_spend' => ['nullable', 'numeric', 'min:0'],
            'customSegments.*.min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'customSegments.*.max_days_since_last_purchase' => ['nullable', 'integer', 'min:0'],
            'customSegments.*.min_referrals' => ['nullable', 'integer', 'min:0'],
            'customSegments.*.min_open_cases' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_sum(array_map('intval', $this->weights)) !== 100) {
            $this->addError('weights', 'La somme des pondérations doit être égale à 100.');
            return;
        }
        $thresholds = array_map('intval', array_values($this->recencyDays));
        if ($thresholds !== array_values($thresholds) || $thresholds !== collect($thresholds)->sort()->values()->all()) {
            $this->addError('recencyDays', 'Les seuils doivent aller du plus court au plus long.');
            return;
        }
        if (! count($this->qualifyingStatuses)) {
            $this->addError('qualifyingStatuses', 'Sélectionne au moins un statut de commande pris en compte.');
            return;
        }
        if (! collect($this->vipRequirements)->contains(true)) {
            $this->addError('vipRequirements', 'Active au moins une condition pour définir le statut VIP.');
            return;
        }
        foreach ($this->customSegments as $index => $segment) {
            $hasCriteria = collect(['min_orders', 'min_spend', 'min_score', 'max_days_since_last_purchase', 'min_referrals', 'min_open_cases'])
                ->contains(fn ($field) => ($segment[$field] ?? '') !== '' && ($segment[$field] ?? null) !== null);
            if (trim((string) ($segment['name'] ?? '')) !== '' && ! $hasCriteria) {
                $this->addError('customSegments.'.$index.'.name', 'Définis au moins un critère pour ce segment.');
                return;
            }
        }
        $frequencyThresholds = array_map('intval', array_values($this->frequencyDays));
        if ($frequencyThresholds !== collect($frequencyThresholds)->sort()->values()->all()) {
            $this->addError('frequencyDays', 'Les seuils de fréquence doivent aller du plus court au plus long.');
            return;
        }

        $rules = array_replace_recursive($loyalty->rules(), [
            'weights' => array_map('intval', $this->weights),
            'recency_days' => array_map('intval', $this->recencyDays),
            'recency_points' => array_map('intval', $this->recencyPoints),
            'frequency_points' => array_map('intval', $this->frequencyPoints),
            'frequency_days' => array_map('intval', $this->frequencyDays),
            'order_tiers' => $this->normalizeTiers($this->orderTiers),
            'spend_tiers' => $this->normalizeTiers($this->spendTiers),
            'segments' => array_map(fn ($value) => (bool) $value, $this->segments),
            'category_labels' => $this->categoryNames,
            'custom_segments' => $this->normalizeCustomSegments(),
            'qualifying_statuses' => array_values(array_intersect($this->qualifyingStatuses, ['ordered', 'delivered'])),
            'new_customer_days' => (int) $this->newCustomerDays,
            'new_customer_max_orders' => (int) $this->newCustomerMaxOrders,
            'regular_min_orders' => (int) $this->regularMinOrders,
            'regular_max_gap_days' => (int) $this->regularMaxGapDays,
            'recent_customer_days' => (int) $this->recentCustomerDays,
            'faithful_score' => (int) $this->faithfulScore,
            'faithful_min_orders' => (int) $this->faithfulMinOrders,
            'vip_score' => (int) $this->vipScore,
            'vip_min_orders' => (int) $this->vipMinOrders,
            'vip_min_spend' => (float) $this->vipMinSpend,
            'vip_require_score' => (bool) ($this->vipRequirements['score'] ?? false),
            'vip_require_orders' => (bool) ($this->vipRequirements['orders'] ?? false),
            'vip_require_spend' => (bool) ($this->vipRequirements['spend'] ?? false),
            'vip_require_regularity' => (bool) ($this->vipRequirements['regularity'] ?? false),
            'high_potential_min_score' => (int) $this->highPotentialMinScore,
            'high_potential_min_orders' => (int) $this->highPotentialMinOrders,
            'risk_frequency_multiplier' => (float) $this->riskFrequencyMultiplier,
            'unresolved_case_penalty' => (int) $this->unresolvedCasePenalty,
        ]);
        CustomerLoyaltySettings::create(['rules' => $rules, 'updated_by' => Auth::id()]);
        $loyalty->forgetRulesCache();
        Cache::forget('customer-loyalty-dashboard-summary');
        $this->dispatchBrowserEvent('customer-loyalty-rules-saved');
        try {
            \Artisan::queue('customer-loyalty:recalculate');
            session()->flash('loyaltyMessage', 'Les règles sont enregistrées et le recalcul des clientes a démarré.');
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('loyaltyMessage', 'Les règles sont enregistrées. Le recalcul automatique est prévu cette nuit ; tu peux aussi lancer la commande de recalcul.');
        }
    }

    protected function tierRows(array $tiers): array
    {
        $rows = [];
        foreach ($tiers as $threshold => $points) $rows[] = ['threshold' => $threshold, 'points' => $points];
        return $rows;
    }

    protected function normalizeTiers(array $tiers): array
    {
        $normalized = [];
        foreach ($tiers as $tier) {
            $threshold = filter_var($tier['threshold'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($threshold !== false && $threshold >= 0) $normalized[$threshold] = max(0, (int) ($tier['points'] ?? 0));
        }
        ksort($normalized, SORT_NUMERIC);
        return $normalized;
    }

    protected function normalizeCustomSegments(): array
    {
        $normalized = [];
        foreach ($this->customSegments as $index => $segment) {
            $name = trim((string) ($segment['name'] ?? ''));
            if ($name === '') continue;
            $hasCriteria = collect(['min_orders', 'min_spend', 'min_score', 'max_days_since_last_purchase', 'min_referrals', 'min_open_cases'])
                ->contains(fn ($field) => ($segment[$field] ?? '') !== '' && ($segment[$field] ?? null) !== null);
            if (! $hasCriteria) {
                $this->addError('customSegments.'.$index.'.name', 'Définis au moins un critère pour ce segment.');
                return [];
            }
            $normalized['custom_'.$index] = [
                'name' => $name,
                'min_orders' => ($segment['min_orders'] ?? '') === '' ? null : (int) $segment['min_orders'],
                'min_spend' => ($segment['min_spend'] ?? '') === '' ? null : (float) $segment['min_spend'],
                'min_score' => ($segment['min_score'] ?? '') === '' ? null : (int) $segment['min_score'],
                'max_days_since_last_purchase' => ($segment['max_days_since_last_purchase'] ?? '') === '' ? null : (int) $segment['max_days_since_last_purchase'],
                'min_referrals' => ($segment['min_referrals'] ?? '') === '' ? null : (int) $segment['min_referrals'],
                'min_open_cases' => ($segment['min_open_cases'] ?? '') === '' ? null : (int) $segment['min_open_cases'],
            ];
        }
        return $normalized;
    }

    public function recordReferral(CustomerLoyaltyService $loyalty): void
    {
        abort_unless($this->isAdmin() || Auth::user()->hasRole(['MNG', 'SCR']), 403);
        $this->validate([
            'referrerCustomerId' => ['required', 'integer', 'exists:costumers,id'],
            'referredCustomerId' => ['required', 'integer', 'exists:costumers,id', 'different:referrerCustomerId', 'unique:customer_loyalty_referrals,referred_costumer_id'],
        ]);
        CustomerLoyaltyReferral::create([
            'referrer_costumer_id' => $this->referrerCustomerId,
            'referred_costumer_id' => $this->referredCustomerId,
            'recorded_by' => Auth::id(),
            'referred_at' => today(),
        ]);
        $loyalty->refreshCustomer((int) $this->referrerCustomerId);
        $loyalty->refreshCustomer((int) $this->referredCustomerId);
        $this->reset(['referrerCustomerId', 'referredCustomerId']);
        $this->showReferralForm = false;
        session()->flash('loyaltyMessage', 'La recommandation a été enregistrée.');
    }

    public function render()
    {
        $query = CustomerLoyaltyProfile::query()->with('customer:id,name,phone,email');
        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->whereHas('customer', fn ($customer) => $customer->where('name', 'like', $term)
                ->orWhere('phone', 'like', $term)->orWhere('email', 'like', $term)
                ->when(ctype_digit(trim($this->search)), fn ($customer) => $customer->orWhere('id', (int) trim($this->search))));
        }
        if ($this->categoryFilter !== '') $query->where('category', $this->categoryFilter);
        if ($this->segmentFilter !== '') $query->whereJsonContains('metrics->segments', $this->segmentFilter);
        if ($this->minScore !== '') $query->where('score', '>=', (int) $this->minScore);
        if ($this->minOrders !== '') $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(metrics, '$.orders_count')) AS UNSIGNED) >= ?", [(int) $this->minOrders]);
        if ($this->minSpend !== '') $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(metrics, '$.total_spent')) AS DECIMAL(14,2)) >= ?", [(float) $this->minSpend]);
        if ($this->purchaseFrom !== '') $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metrics, '$.last_purchase_at')) >= ?", [$this->purchaseFrom]);
        if ($this->purchaseTo !== '') $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metrics, '$.last_purchase_at')) <= ?", [$this->purchaseTo]);
        if ($this->productFilter !== '') $query->whereHas('customer.orders.orderItems', fn ($items) => $items->where('product_id', $this->productFilter)->whereHas('order', fn ($orders) => $orders->where('status', 'delivered')));
        $profiles = $query->orderByDesc('score')->paginate(15);

        [$categoryCounts, $segmentCounts, $metrics, $categoryPerformance] = Cache::remember('customer-loyalty-dashboard-summary', 60, function () {
            $categoryCounts = CustomerLoyaltyProfile::query()->select('category')->selectRaw('COUNT(*) as aggregate')->groupBy('category')->pluck('aggregate', 'category');
            $segmentCounts = collect(['at_risk', 'to_reactivate', 'high_potential', 'service_attention'])->mapWithKeys(fn ($segment) => [
                $segment => CustomerLoyaltyProfile::whereJsonContains('metrics->segments', $segment)->count(),
            ]);
            $metrics = CustomerLoyaltyProfile::query()->selectRaw('COUNT(*) as customers_count, AVG(score) as average_score, AVG(JSON_EXTRACT(metrics, "$.orders_count")) as average_orders, AVG(JSON_EXTRACT(metrics, "$.total_spent")) as average_spend, 100 * SUM(CASE WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(metrics, "$.orders_count")) AS UNSIGNED) >= 2 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) as repurchase_rate')->first();
            $categoryPerformance = CustomerLoyaltyProfile::query()
                ->select('category')->selectRaw('COUNT(*) as customers_count')
                ->selectRaw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(metrics, "$.total_spent")) AS DECIMAL(14,2))) as revenue')
                ->selectRaw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(metrics, "$.average_basket")) AS DECIMAL(14,2))) as average_basket')
                ->selectRaw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(metrics, "$.orders_count")) AS UNSIGNED)) as average_orders')
                ->groupBy('category')->get()->keyBy('category');
            return [$categoryCounts, $segmentCounts, $metrics, $categoryPerformance];
        });

        return view('livewire.customer-loyalty', [
            'profiles' => $profiles,
            'categoryCounts' => $categoryCounts,
            'segmentCounts' => $segmentCounts,
            'summary' => $metrics,
            'categoryPerformance' => $categoryPerformance,
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'isAdmin' => $this->isAdmin(),
            'canRecordReferral' => $this->isAdmin() || Auth::user()->hasRole(['MNG', 'SCR']),
            'categories' => $this->categoryNames,
            'segmentLabels' => array_merge([
                'recent' => 'Récemment acquise', 'high_potential' => 'Fort potentiel', 'at_risk' => 'À risque',
                'to_reactivate' => 'À réactiver', 'high_spend' => 'Fort montant dépensé', 'service_attention' => 'Suivi SAV requis', 'vip' => 'VIP',
            ], collect($this->customSegments)->mapWithKeys(fn ($segment, $index) => ['custom_'.$index => $segment['name']])->all()),
        ])->extends('layouts.admin')->section('content');
    }
}
