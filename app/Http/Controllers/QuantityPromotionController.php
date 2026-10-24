<?php

namespace App\Http\Controllers;

use App\Models\QuantityPromotionSetting;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuantityPromotionController extends Controller
{
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
        $periods = array_map(fn ($period) => [
            'starts_at' => $period['starts_at'],
            'duration_days' => (int) $period['duration_days'],
        ], $periods);
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

    public function index(): View
    {
        $promotion = QuantityPromotionSetting::current();
        $tiers = collect($promotion->tiers ?? [])->map(function ($tier) {
            $discountType = $tier['discount_type'] ?? (array_key_exists('unit_price', $tier) ? 'unit_price' : 'percent');
            return [
                'product_id' => $tier['product_id'] ?? 'all',
                'min_quantity' => $tier['min_quantity'] ?? null,
                'discount_type' => $discountType,
                'discount_value' => $tier['discount_value'] ?? ($discountType === 'unit_price'
                    ? ($tier['unit_price'] ?? null)
                    : ($tier['discount_percent'] ?? null)),
            ];
        })->unique(fn ($tier) => ($tier['product_id'] ?? 'all').':'.$tier['min_quantity'])->values()->all();

        return view('products.promotions', [
            'promotion' => $promotion,
            'promotionTiers' => $tiers,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'apply_to_all' => ['nullable', 'boolean'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'promotion_tiers' => ['nullable', 'array', 'max:8'],
            'promotion_tiers.*.product_id' => ['nullable', 'string'],
            'promotion_tiers.*.min_quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'promotion_tiers.*.discount_type' => ['nullable', 'in:percent,unit_price'],
            'promotion_tiers.*.discount_value' => ['nullable', 'integer', 'min:1', 'max:999999999'],
            'periods' => ['nullable', 'array', 'max:12'],
            'periods.*.starts_at' => ['nullable', 'date'],
            'periods.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $applyToAll = $request->boolean('apply_to_all');
        $tiers = [];
        foreach ($data['promotion_tiers'] ?? [] as $tier) {
            $minimum = $tier['min_quantity'] ?? null;
            // A global fixed unit price cannot fit products with different prices.
            $discountType = $applyToAll ? 'percent' : ($tier['discount_type'] ?? 'percent');
            $discountValue = $tier['discount_value'] ?? null;
            $productId = $tier['product_id'] ?? 'all';
            if (($minimum === null || $minimum === '') && ($discountValue === null || $discountValue === '')) {
                continue;
            }
            if (! $minimum || ! $discountValue) {
                throw ValidationException::withMessages([
                    'tiers' => 'Chaque palier doit avoir une quantité minimale et une valeur de réduction valide.',
                ]);
            }
            if ($discountType === 'percent' && (int) $discountValue > 90) {
                throw ValidationException::withMessages(['tiers' => 'La réduction en pourcentage doit être comprise entre 1 et 90 %.']);
            }
            if ($productId !== 'all' && (! ctype_digit((string) $productId) || ! Product::query()->whereKey((int) $productId)->exists())) {
                throw ValidationException::withMessages(['tiers' => 'Choisis un produit inclus dans cette promotion pour chaque palier.']);
            }
            $tiers[] = ['product_id' => $productId === 'all' ? null : (int) $productId, 'min_quantity' => (int) $minimum, 'discount_type' => $discountType, 'discount_value' => (int) $discountValue];
        }

        $tiers = collect($tiers)->groupBy(fn ($tier) => $tier['product_id'] ?? 'all')->flatMap(function ($group) {
            $group = $group->sortBy('min_quantity')->values();
            if ($group->pluck('min_quantity')->unique()->count() !== $group->count()) {
                throw ValidationException::withMessages(['tiers' => 'Chaque quantité minimale doit être unique pour un même produit.']);
            }
            $previousValue = null;
            $discountType = null;
            foreach ($group as $tier) {
                if ($discountType === $tier['discount_type'] && $previousValue !== null && (($tier['discount_type'] === 'percent' && $tier['discount_value'] < $previousValue)
                    || ($tier['discount_type'] === 'unit_price' && $tier['discount_value'] > $previousValue))) {
                    throw ValidationException::withMessages(['tiers' => 'Le pourcentage doit augmenter, ou le prix unitaire diminuer, entre deux paliers successifs du même type.']);
                }
                $previousValue = $discountType === $tier['discount_type'] ? $tier['discount_value'] : null;
                $discountType = $tier['discount_type'];
            }
            return $group;
        })->values()->all();
        if (collect($tiers)->contains(fn ($tier) => $tier['product_id'] !== null)
            && collect($tiers)->contains(fn ($tier) => $tier['product_id'] === null)) {
            throw ValidationException::withMessages(['tiers' => 'Utilise soit des paliers pour tous les produits concernés, soit des paliers par produit.']);
        }
        if ($request->boolean('is_active') && $tiers === []) {
            throw ValidationException::withMessages(['tiers' => 'Ajoute au moins un palier avant d’activer la promotion.']);
        }
        $periods = [];
        foreach ($data['periods'] ?? [] as $period) {
            $startValue = $period['starts_at'] ?? null;
            $durationValue = $period['duration_days'] ?? null;
            if (! $startValue && ! $durationValue) continue;
            if (! $startValue || ! $durationValue) {
                throw ValidationException::withMessages(['periods' => 'Chaque période doit avoir une date de début et une durée en jours.']);
            }
            $start = Carbon::parse($startValue);
            $periods[] = ['starts_at' => $start->toDateTimeString(), 'duration_days' => (int) $durationValue];
        }
        if ($request->boolean('is_active') && $periods === []) {
            throw ValidationException::withMessages(['periods' => 'Ajoute au moins une période avant d’activer la promotion.']);
        }
        $firstPeriod = $periods[0] ?? null;
        $startsAt = $firstPeriod ? Carbon::parse($firstPeriod['starts_at']) : null;
        $durationDays = $firstPeriod['duration_days'] ?? null;
        $endsAt = $startsAt && $durationDays ? $startsAt->copy()->addDays($durationDays) : null;
        $applicationMode = $applyToAll ? 'general' : 'product';
        $productIds = $applicationMode === 'product' ? array_map('intval', $data['product_ids'] ?? []) : [];
        $unitPriceTiers = collect($tiers)->filter(fn ($tier) => $tier['discount_type'] === 'unit_price');
        if ($unitPriceTiers->isNotEmpty()) {
            $scopedProductIds = collect($tiers)->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique();
            $productsForPriceCheck = Product::query()->with('quantityPriceTiers')
                ->when($scopedProductIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $scopedProductIds))
                ->when($scopedProductIds->isEmpty() && $applicationMode === 'product', fn ($query) => $query->whereIn('id', $productIds))
                ->get();
            foreach ($unitPriceTiers as $tier) {
                $affectedProducts = $tier['product_id'] !== null
                    ? $productsForPriceCheck->where('id', $tier['product_id'])
                    : $productsForPriceCheck;
                foreach ($affectedProducts as $product) {
                    $regularPrice = $product->unitPriceForQuantity($tier['min_quantity']);
                    if ($tier['discount_value'] >= $regularPrice) {
                        throw ValidationException::withMessages(['tiers' => 'Le prix promotionnel par unité doit être inférieur au prix normal de chaque produit concerné ('.$product->name.').']);
                    }
                }
            }
        }
        foreach ($tiers as $tier) {
            if ($tier['product_id'] !== null && $applicationMode === 'product' && ! in_array($tier['product_id'], $productIds, true)) {
                throw ValidationException::withMessages(['tiers' => 'Chaque palier doit cibler un produit sélectionné dans la promotion.']);
            }
        }
        if ($request->boolean('is_active') && $applicationMode === 'product' && $productIds === []) {
            throw ValidationException::withMessages(['product_ids' => 'Sélectionne au moins un produit ou active l’option « Appliquer à tous les produits ».']);
        }

        DB::transaction(function () use ($request, $tiers, $applicationMode, $productIds, $startsAt, $endsAt, $durationDays, $periods): void {
            QuantityPromotionSetting::query()->updateOrCreate(['id' => 1], [
                'is_active' => $request->boolean('is_active'),
                'application_mode' => $applicationMode,
                'product_ids' => $productIds,
                'tiers' => $tiers,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_days' => $durationDays,
                'periods' => $periods,
            ]);
        });

        return redirect()->route('quantity-promotions.index')->with('success', 'Les paramètres de promotion ont été enregistrés.');
    }
}
