<?php

namespace App\Services;

use App\Models\Costumer;
use App\Models\CostumerContactHistory;
use App\Models\CustomerLoyaltyProfile;
use App\Models\CustomerLoyaltyScoreHistory;
use App\Models\CustomerLoyaltySettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerLoyaltyService
{
    public static function defaults(): array
    {
        return [
            'qualifying_statuses' => ['delivered'],
            'category_labels' => ['new' => 'Nouvelle cliente', 'occasional' => 'Occasionnelle', 'regular' => 'Régulière', 'faithful' => 'Fidèle', 'vip' => 'VIP', 'inactive' => 'Inactive', 'no_purchase' => 'Sans achat'],
            'weights' => ['recency' => 25, 'frequency' => 25, 'orders' => 15, 'spend' => 20, 'regularity' => 10, 'engagement' => 5],
            'recency_days' => ['very_active' => 30, 'active' => 60, 'watch' => 90, 'inactive' => 180],
            'frequency_days' => [30, 60, 90, 180],
            'new_customer_days' => 30,
            'new_customer_max_orders' => 1,
            'regular_min_orders' => 2,
            'regular_max_gap_days' => 90,
            'recent_customer_days' => 30,
            'faithful_score' => 70,
            'faithful_min_orders' => 3,
            'vip_score' => 82,
            'vip_min_orders' => 5,
            'vip_min_spend' => 250000,
            'vip_require_score' => true,
            'vip_require_orders' => true,
            'vip_require_spend' => true,
            'vip_require_regularity' => true,
            'risk_frequency_multiplier' => 1.75,
            'high_potential_min_score' => 55,
            'high_potential_min_orders' => 2,
            'unresolved_case_penalty' => 10,
            'order_tiers' => [1 => 3, 2 => 6, 5 => 10, 10 => 15],
            'spend_tiers' => [50000 => 5, 150000 => 10, 300000 => 15, 500000 => 20],
            'recency_points' => ['very_active' => 25, 'active' => 18, 'watch' => 10, 'inactive' => 3],
            'frequency_points' => [30 => 25, 60 => 19, 90 => 12, 180 => 5],
            'segments' => [
                'vip' => true,
                'recent' => true,
                'high_potential' => true,
                'at_risk' => true,
                'to_reactivate' => true,
                'high_spend' => true,
                'service_attention' => true,
            ],
            'custom_segments' => [],
        ];
    }

    public function rules(): array
    {
        return array_replace_recursive(self::defaults(), Cache::remember('customer-loyalty-rules', 300, function () {
            return CustomerLoyaltySettings::query()->latest('id')->first()?->rules ?: [];
        }));
    }

    public function forgetRulesCache(): void
    {
        Cache::forget('customer-loyalty-rules');
    }

    public function refreshCustomer(int $customerId): ?CustomerLoyaltyProfile
    {
        if (! $this->tablesReady()) return null;
        $customer = Costumer::whereKey($customerId)->first();
        if (! $customer) {
            return null;
        }
        $this->refreshCustomers(collect([$customer]));
        return CustomerLoyaltyProfile::where('costumer_id', $customerId)->first();
    }

    public function initializeCustomer(int $customerId): void
    {
        if (! $this->tablesReady()) return;
        $timestamp = now();
        CustomerLoyaltyProfile::upsert([[
            'costumer_id' => $customerId,
            'score' => 0,
            'category' => 'no_purchase',
            'metrics' => json_encode([
                'orders_count' => 0, 'total_spent' => 0, 'average_basket' => 0,
                'first_purchase_at' => null, 'last_purchase_at' => null,
                'days_since_last_purchase' => null, 'customer_age_days' => 0,
                'average_purchase_gap_days' => null, 'purchase_interval_variation' => null,
                'regularity' => 'insuffisante', 'referrals_count' => 0, 'contact_count' => 0,
                'positive_feedback_count' => 0, 'negative_feedback_count' => 0,
                'service_cases_count' => 0, 'open_service_cases_count' => 0,
                'average_satisfaction' => null, 'segments' => [],
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'calculated_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]], ['costumer_id'], ['updated_at']);
        Cache::forget('customer-loyalty-dashboard-summary');
    }

    public function refreshCustomers($customers): void
    {
        if (! $this->tablesReady()) return;
        $customers = collect($customers)->keyBy('id');
        if ($customers->isEmpty()) return;

        $rules = $this->rules();
        $customerIds = $customers->keys()->map(fn ($id) => (string) $id)->all();
        $ordersByCustomer = DB::table('orders')
            ->select('costumer_id', 'total')
            ->selectRaw('COALESCE(date_order, DATE(created_at)) AS purchase_date')
            ->whereIn('costumer_id', $customerIds)
            ->whereIn('status', $rules['qualifying_statuses'])
            ->orderBy('costumer_id')->orderByRaw('COALESCE(date_order, DATE(created_at))')
            ->get()->groupBy(fn ($order) => (string) $order->costumer_id);

        $casesByCustomer = DB::table('customer_service_cases')
            ->select('costumer_id')
            ->selectRaw('COUNT(*) AS total_cases')
            ->selectRaw("SUM(CASE WHEN status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS open_cases")
            ->selectRaw('AVG(customer_satisfaction) AS average_satisfaction')
            ->whereIn('costumer_id', $customers->keys())
            ->groupBy('costumer_id')->get()->keyBy('costumer_id');

        $contactsByCustomer = CostumerContactHistory::query()
            ->whereIn('costumer_id', $customers->keys())
            ->select('costumer_id')
            ->selectRaw('COUNT(*) AS contact_count')
            ->selectRaw("SUM(CASE WHEN sentiment = 'positive' THEN 1 ELSE 0 END) AS positive_count")
            ->selectRaw("SUM(CASE WHEN sentiment = 'negative' THEN 1 ELSE 0 END) AS negative_count")
            ->groupBy('costumer_id')->get()->keyBy('costumer_id');

        $referralsByCustomer = DB::table('customer_loyalty_referrals')
            ->whereIn('referrer_costumer_id', $customers->keys())
            ->select('referrer_costumer_id')->selectRaw('COUNT(*) AS referral_count')
            ->groupBy('referrer_costumer_id')->get()->keyBy('referrer_costumer_id');

        $profileRows = [];
        $historyRows = [];
        $existingProfiles = CustomerLoyaltyProfile::query()
            ->whereIn('costumer_id', $customers->keys())
            ->get(['costumer_id', 'score', 'category', 'metrics'])
            ->keyBy('costumer_id');
        $timestamp = now();
        $snapshotDate = today()->toDateString();

        foreach ($customers as $id => $customer) {
            $orders = $ordersByCustomer->get((string) $id, collect());
            $dates = $orders->pluck('purchase_date')->filter()->map(fn ($date) => \Carbon\Carbon::parse($date)->startOfDay())->values();
            $now = now()->startOfDay();
            $lastPurchase = $dates->last();
            $firstPurchase = $dates->first();
            $daysSinceLast = $lastPurchase ? $lastPurchase->diffInDays($now) : null;
            $intervals = [];
            for ($i = 1; $i < $dates->count(); $i++) {
                $intervals[] = $dates[$i - 1]->diffInDays($dates[$i]);
            }
            $averageGap = count($intervals) ? array_sum($intervals) / count($intervals) : null;
            $coefficientOfVariation = null;
            if (count($intervals) > 1 && $averageGap > 0) {
                $variance = array_sum(array_map(fn ($gap) => ($gap - $averageGap) ** 2, $intervals)) / count($intervals);
                $coefficientOfVariation = sqrt($variance) / $averageGap;
            }

            $caseStats = $casesByCustomer->get($id);
            $contactStats = $contactsByCustomer->get($id);
            $referralCount = (int) ($referralsByCustomer->get($id)->referral_count ?? 0);
            $spent = (float) $orders->sum(fn ($order) => (float) $order->total);
            $metrics = [
                'orders_count' => $orders->count(),
                'total_spent' => round($spent, 2),
                'average_basket' => $orders->count() ? round($spent / $orders->count(), 2) : 0,
                'first_purchase_at' => $firstPurchase?->toDateString(),
                'last_purchase_at' => $lastPurchase?->toDateString(),
                'days_since_last_purchase' => $daysSinceLast,
                'customer_age_days' => $firstPurchase ? $firstPurchase->diffInDays($now) : ($customer->created_at ? $customer->created_at->startOfDay()->diffInDays($now) : 0),
                'average_purchase_gap_days' => $averageGap ? round($averageGap, 1) : null,
                'purchase_interval_variation' => $coefficientOfVariation !== null ? round($coefficientOfVariation, 3) : null,
                'regularity' => $this->regularityLabel($coefficientOfVariation, count($intervals)),
                'referrals_count' => $referralCount,
                'contact_count' => (int) ($contactStats->contact_count ?? 0),
                'positive_feedback_count' => (int) ($contactStats->positive_count ?? 0),
                'negative_feedback_count' => (int) ($contactStats->negative_count ?? 0),
                'service_cases_count' => (int) ($caseStats->total_cases ?? 0),
                'open_service_cases_count' => (int) ($caseStats->open_cases ?? 0),
                'average_satisfaction' => $caseStats?->average_satisfaction !== null ? round((float) $caseStats->average_satisfaction, 2) : null,
            ];

            [$score, $category, $segments] = $this->scoreAndCategory($metrics, $rules);
            $metrics['segments'] = $segments;
            $encodedMetrics = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $existing = $existingProfiles->get($id);
            $profileRows[] = [
                'costumer_id' => $id, 'score' => $score, 'category' => $category,
                'metrics' => $encodedMetrics, 'calculated_at' => $timestamp,
                'created_at' => $timestamp, 'updated_at' => $timestamp,
            ];
            $previousMetrics = $existing?->metrics ?? [];
            if (! $existing || (int) $existing->score !== $score || $existing->category !== $category
                || ($previousMetrics['segments'] ?? []) !== $segments
                || (int) ($previousMetrics['orders_count'] ?? -1) !== $metrics['orders_count']) {
                $historyRows[] = [
                    'costumer_id' => $id, 'score' => $score, 'category' => $category,
                    'metrics' => $encodedMetrics, 'snapshot_date' => $snapshotDate,
                    'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
        }

        CustomerLoyaltyProfile::upsert($profileRows, ['costumer_id'], ['score', 'category', 'metrics', 'calculated_at', 'updated_at']);
        if ($historyRows) {
            CustomerLoyaltyScoreHistory::upsert($historyRows, ['costumer_id', 'snapshot_date'], ['score', 'category', 'metrics', 'updated_at']);
        }
        Cache::forget('customer-loyalty-dashboard-summary');
    }

    protected function scoreAndCategory(array $metrics, array $rules): array
    {
        $weights = $rules['weights'];
        $points = [
            'recency' => $this->recencyPoints($metrics['days_since_last_purchase'], $rules),
            'frequency' => $this->bandPoints($metrics['average_purchase_gap_days'], $rules['frequency_days'], array_values($rules['frequency_points']), (int) $weights['frequency']),
            'orders' => $this->tierPoints($metrics['orders_count'], $rules['order_tiers'], (int) $weights['orders']),
            'spend' => $this->tierPoints($metrics['total_spent'], $rules['spend_tiers'], (int) $weights['spend']),
            'regularity' => $this->regularityPoints($metrics['purchase_interval_variation'], $metrics['orders_count'], (int) $weights['regularity']),
            'engagement' => $this->engagementPoints($metrics, (int) $weights['engagement']),
        ];
        $weightTotal = max(1, array_sum(array_map('intval', $weights)));
        $score = (int) round(min(100, array_sum($points) * 100 / $weightTotal));
        if ($metrics['open_service_cases_count'] > 0) {
            $score = max(0, $score - min($score, (int) $rules['unresolved_case_penalty']));
        }

        $days = $metrics['days_since_last_purchase'];
        $orders = (int) $metrics['orders_count'];
        $inactive = $days !== null && $days > (int) $rules['recency_days']['inactive'];
        $vip = (! $rules['vip_require_score'] || $score >= (int) $rules['vip_score'])
            && (! $rules['vip_require_orders'] || $orders >= (int) $rules['vip_min_orders'])
            && (! $rules['vip_require_spend'] || $metrics['total_spent'] >= (float) $rules['vip_min_spend'])
            && (! $rules['vip_require_regularity'] || $metrics['regularity'] === 'régulière');
        if ($orders === 0) {
            $category = 'no_purchase';
        } elseif ($inactive) {
            $category = 'inactive';
        } elseif ($days <= (int) $rules['new_customer_days'] && $orders <= (int) $rules['new_customer_max_orders']) {
            $category = 'new';
        } elseif ($vip) {
            $category = 'vip';
        } elseif ($score >= (int) $rules['faithful_score'] && $orders >= (int) $rules['faithful_min_orders']) {
            $category = 'faithful';
        } elseif ($orders >= (int) $rules['regular_min_orders']
            && $metrics['average_purchase_gap_days'] !== null
            && $metrics['average_purchase_gap_days'] <= (int) $rules['regular_max_gap_days']) {
            $category = 'regular';
        } else {
            $category = 'occasional';
        }

        $risk = $days !== null && $metrics['average_purchase_gap_days'] !== null
            && $days > $metrics['average_purchase_gap_days'] * (float) $rules['risk_frequency_multiplier']
            && ! $inactive;
        $segments = [];
        if ($vip) $segments[] = 'vip';
        if ($metrics['customer_age_days'] <= (int) $rules['recent_customer_days']) $segments[] = 'recent';
        if ($orders >= (int) $rules['high_potential_min_orders'] && $score >= (int) $rules['high_potential_min_score'] && ! $vip) $segments[] = 'high_potential';
        if ($risk) $segments[] = 'at_risk';
        if ($inactive) $segments[] = 'to_reactivate';
        if ($metrics['total_spent'] >= (float) $rules['vip_min_spend']) $segments[] = 'high_spend';
        if ($metrics['open_service_cases_count'] > 0) $segments[] = 'service_attention';

        foreach ($rules['custom_segments'] ?? [] as $key => $segmentRule) {
            $conditions = [
                'orders_count' => ['min_orders', 'min'],
                'total_spent' => ['min_spend', 'min'],
                'score' => ['min_score', 'min'],
                'days_since_last_purchase' => ['max_days_since_last_purchase', 'max'],
                'referrals_count' => ['min_referrals', 'min'],
                'open_service_cases_count' => ['min_open_cases', 'min'],
            ];
            $matches = true;
            foreach ($conditions as $metric => [$ruleKey, $operator]) {
                if (! array_key_exists($ruleKey, $segmentRule) || $segmentRule[$ruleKey] === null || $segmentRule[$ruleKey] === '') continue;
                $value = $metric === 'score' ? $score : $metrics[$metric];
                if ($value === null || ($operator === 'min' ? $value < $segmentRule[$ruleKey] : $value > $segmentRule[$ruleKey])) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) $segments[] = $key;
        }

        return [$score, $category, array_values(array_filter($segments, fn ($segment) => $rules['segments'][$segment] ?? str_starts_with($segment, 'custom_')))];
    }

    protected function tablesReady(): bool
    {
        foreach ([
            'customer_loyalty_settings', 'customer_loyalty_profiles',
            'customer_loyalty_score_histories', 'customer_loyalty_referrals',
            'customer_service_cases', 'costumer_contact_histories',
        ] as $table) {
            if (! Schema::hasTable($table)) return false;
        }
        return true;
    }

    protected function thresholdPoints($value, array $bands, int $maximum): int
    {
        if ($value === null) return 0;
        ksort($bands, SORT_NUMERIC);
        foreach ($bands as $threshold => $points) {
            if ($value <= (float) $threshold) return min($maximum, max(0, (int) $points));
        }
        return 0;
    }

    protected function recencyPoints(?int $days, array $rules): int
    {
        if ($days === null) return 0;
        foreach (['very_active', 'active', 'watch', 'inactive'] as $band) {
            if ($days <= (int) $rules['recency_days'][$band]) {
                return min((int) $rules['weights']['recency'], max(0, (int) $rules['recency_points'][$band]));
            }
        }
        return 0;
    }

    protected function bandPoints($value, array $thresholds, array $points, int $maximum): int
    {
        if ($value === null) return 0;
        foreach ($thresholds as $index => $threshold) {
            if ($value <= (float) $threshold) return min($maximum, max(0, (int) ($points[$index] ?? 0)));
        }
        return 0;
    }

    protected function tierPoints(float|int $value, array $tiers, int $maximum): int
    {
        ksort($tiers, SORT_NUMERIC);
        $points = 0;
        foreach ($tiers as $threshold => $tierPoints) {
            if ($value < (float) $threshold) break;
            $points = (int) $tierPoints;
        }
        return min($maximum, max(0, $points));
    }

    protected function regularityPoints(?float $variation, int $orders, int $maximum): int
    {
        if ($orders < 3) return 0;
        if ($variation === null) return 0;
        if ($variation <= 0.25) return $maximum;
        if ($variation <= 0.5) return (int) round($maximum * 0.75);
        if ($variation <= 0.8) return (int) round($maximum * 0.4);
        return 0;
    }

    protected function engagementPoints(array $metrics, int $maximum): int
    {
        $referralAndFeedback = max(0, min($maximum, (int) $metrics['referrals_count'] * 2
            + (int) $metrics['positive_feedback_count'] * 2 - (int) $metrics['negative_feedback_count'] * 2));
        $satisfaction = $metrics['average_satisfaction'];
        if ($satisfaction === null) return $referralAndFeedback;

        $satisfactionPoints = (int) round(max(0, min(1, ((float) $satisfaction - 1) / 4)) * $maximum);
        return (int) round(($referralAndFeedback + $satisfactionPoints) / 2);
    }

    protected function regularityLabel(?float $variation, int $intervalCount): string
    {
        if ($intervalCount < 2 || $variation === null) return 'insuffisante';
        if ($variation <= 0.5) return 'régulière';
        return $variation <= 1 ? 'irrégulière' : 'occasionnelle';
    }
}
