<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\QuantityPromotionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuantityPromotionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $promotion = QuantityPromotionSetting::current();
        $tierGroups = collect($promotion->tiers ?? [])->map(function ($tier) {
            $type = $tier['discount_type'] ?? (array_key_exists('unit_price', $tier) ? 'unit_price' : 'percent');
            return [
                'product_id' => (int) ($tier['product_id'] ?? 0),
                'min_quantity' => (int) ($tier['min_quantity'] ?? 0),
                'discount_type' => $type,
                'discount_value' => (int) ($tier['discount_value'] ?? ($type === 'unit_price'
                    ? ($tier['unit_price'] ?? 0)
                    : ($tier['discount_percent'] ?? 0))),
            ];
        })->groupBy('product_id');

        $products = Product::query()->with('quantityPriceTiers')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate(8)->withQueryString();

        $globalTiers = $tierGroups->get(0, collect());
        $tiersByProduct = $products->getCollection()->mapWithKeys(function ($product) use ($promotion, $globalTiers, $tierGroups) {
            $tiers = $promotion->application_mode === 'general'
                ? $globalTiers
                : $tierGroups->get((int) $product->id, collect());

            $tiers = $tiers->map(function ($tier) use ($product) {
                if ($tier['discount_type'] === 'percent') {
                    $regularPrice = (int) $product->unitPriceForQuantity($tier['min_quantity']);
                    $tier['discount_value'] = max(1, (int) round($regularPrice * (100 - $tier['discount_value']) / 100));
                    $tier['discount_type'] = 'unit_price';
                }

                return $tier;
            });

            return [$product->id => $tiers];
        });

        return view('products.promotions', compact('promotion', 'products', 'search', 'tiersByProduct'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'periods' => ['nullable', 'array', 'max:12'],
            'periods.*.starts_at' => ['nullable', 'date'],
            'periods.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $setting = QuantityPromotionSetting::current();
        $periods = [];
        foreach ($data['periods'] ?? [] as $period) {
            $startValue = $period['starts_at'] ?? null;
            $durationValue = $period['duration_days'] ?? null;
            if (! $startValue && ! $durationValue) continue;
            if (! $startValue || ! $durationValue) {
                throw ValidationException::withMessages(['periods' => 'Chaque période doit avoir une date de début et une durée en jours.']);
            }
            $periods[] = ['starts_at' => Carbon::parse($startValue)->toDateTimeString(), 'duration_days' => (int) $durationValue];
        }

        $isActive = $request->boolean('is_active');
        if ($isActive && collect($setting->tiers ?? [])->isEmpty()) {
            throw ValidationException::withMessages(['tiers' => 'Configure au moins un palier sur un produit avant d’activer les promotions.']);
        }
        if ($isActive && $periods === []) {
            throw ValidationException::withMessages(['periods' => 'Ajoute au moins une période avant d’activer la promotion.']);
        }

        $firstPeriod = $periods[0] ?? null;
        $startsAt = $firstPeriod ? Carbon::parse($firstPeriod['starts_at']) : null;
        $durationDays = $firstPeriod['duration_days'] ?? null;
        $setting->forceFill([
            'is_active' => $isActive,
            'periods' => $periods,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt && $durationDays ? $startsAt->copy()->addDays($durationDays) : null,
            'duration_days' => $durationDays,
        ])->save();

        return back()->with('success', 'L’activation et les périodes de promotion ont été enregistrées.');
    }

    public function saveProduct(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'min_quantity' => ['nullable', 'array', 'max:8'],
            'min_quantity.*' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'unit_price' => ['nullable', 'array', 'max:8'],
            'unit_price.*' => ['nullable', 'integer', 'min:1', 'max:999999999'],
        ]);

        $tiers = [];
        foreach ($data['min_quantity'] ?? [] as $index => $minimum) {
            $value = $data['unit_price'][$index] ?? null;
            if (($minimum === null || $minimum === '') && ($value === null || $value === '')) continue;
            if (! $minimum || ! $value) {
                throw ValidationException::withMessages(['tiers' => 'Chaque palier doit avoir une quantité minimale et un prix promotionnel valide.']);
            }
            $tiers[] = ['product_id' => (int) $product->id, 'min_quantity' => (int) $minimum, 'discount_type' => 'unit_price', 'discount_value' => (int) $value];
        }

        usort($tiers, fn ($left, $right) => $left['min_quantity'] <=> $right['min_quantity']);
        if (count(array_unique(array_column($tiers, 'min_quantity'))) !== count($tiers)) {
            throw ValidationException::withMessages(['tiers' => 'Chaque quantité minimale doit être unique pour ce produit.']);
        }
        $previousType = null;
        $previousValue = null;
        $previousPrice = (int) $product->price;
        foreach ($tiers as $tier) {
            if ($tier['discount_type'] === 'unit_price') {
                $regularPrice = (int) $product->unitPriceForQuantity($tier['min_quantity']);
                if ($tier['discount_value'] >= min($previousPrice, $regularPrice)) {
                    throw ValidationException::withMessages(['tiers' => 'Chaque prix promotionnel doit être inférieur au prix normal ou au palier précédent.']);
                }
                $previousPrice = $tier['discount_value'];
            } elseif ($previousType === 'percent' && $tier['discount_value'] < $previousValue) {
                throw ValidationException::withMessages(['tiers' => 'Le pourcentage de remise doit augmenter entre deux paliers successifs.']);
            }
            $previousType = $tier['discount_type'];
            $previousValue = $tier['discount_value'];
        }

        $setting = QuantityPromotionSetting::current();
        DB::transaction(function () use ($setting, $product, $tiers): void {
            $existing = collect($setting->tiers ?? []);
            if ($setting->application_mode !== 'product') {
                // Expand any legacy all-product rule before this product is customized.
                $legacyGlobalTiers = $existing->filter(fn ($tier) => empty($tier['product_id']))->values();
                if ($legacyGlobalTiers->isNotEmpty()) {
                    $existing = Product::query()->pluck('id')->flatMap(fn ($id) => $legacyGlobalTiers->map(fn ($tier) => array_merge($tier, ['product_id' => (int) $id])));
                }
            }
            $existing = $existing->reject(fn ($tier) => (int) ($tier['product_id'] ?? 0) === (int) $product->id);
            $allTiers = $existing->concat($tiers)->values()->all();
            $productIds = collect($allTiers)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            $setting->forceFill([
                'application_mode' => 'product',
                'product_ids' => $productIds,
                'tiers' => $allTiers,
                'is_active' => $allTiers !== [] && $setting->is_active,
            ])->save();
        });

        return back()->with('success', 'Les promotions de « '.$product->name.' » ont été enregistrées.');
    }

    public function deletePeriod(Request $request): JsonResponse
    {
        $data = $request->validate(['index' => ['required', 'integer', 'min:0', 'max:11']]);
        $setting = QuantityPromotionSetting::current();
        $periods = $setting->schedulePeriods();
        $index = (int) $data['index'];
        if (! array_key_exists($index, $periods)) {
            return response()->json(['message' => 'Cette période n’existe plus. Actualise la page puis réessaie.'], 404);
        }

        array_splice($periods, $index, 1);
        $periods = array_map(fn ($period) => ['starts_at' => $period['starts_at'], 'duration_days' => (int) $period['duration_days']], $periods);
        $firstPeriod = $periods[0] ?? null;
        $startsAt = $firstPeriod ? Carbon::parse($firstPeriod['starts_at']) : null;
        $durationDays = $firstPeriod['duration_days'] ?? null;
        $setting->forceFill([
            'periods' => $periods,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt && $durationDays ? $startsAt->copy()->addDays($durationDays) : null,
            'duration_days' => $durationDays,
            'is_active' => $periods !== [] && $setting->is_active,
        ])->save();

        return response()->json(['message' => 'La période a été supprimée.', 'remaining' => count($periods)]);
    }
}
