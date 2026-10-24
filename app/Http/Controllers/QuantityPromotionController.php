<?php

namespace App\Http\Controllers;

use App\Models\QuantityPromotionSetting;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuantityPromotionController extends Controller
{
    public function index(): View
    {
        return view('products.promotions', [
            'promotion' => QuantityPromotionSetting::current(),
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
            'min_quantity' => ['array', 'max:8'],
            'min_quantity.*' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'discount_percent' => ['array', 'max:8'],
            'discount_percent.*' => ['nullable', 'integer', 'min:1', 'max:90'],
            'starts_at' => ['nullable', 'required_if:is_active,1', 'date'],
            'duration_days' => ['nullable', 'required_if:is_active,1', 'integer', 'min:1', 'max:365'],
        ]);

        $tiers = [];
        foreach ($data['min_quantity'] ?? [] as $index => $minimum) {
            $discount = $data['discount_percent'][$index] ?? null;
            if (($minimum === null || $minimum === '') && ($discount === null || $discount === '')) {
                continue;
            }
            if (! $minimum || ! $discount) {
                throw ValidationException::withMessages([
                    'tiers' => 'Chaque palier doit avoir une quantité minimale et un pourcentage de réduction.',
                ]);
            }
            $tiers[] = ['min_quantity' => (int) $minimum, 'discount_percent' => (int) $discount];
        }

        usort($tiers, fn ($left, $right) => $left['min_quantity'] <=> $right['min_quantity']);
        if (count(array_unique(array_column($tiers, 'min_quantity'))) !== count($tiers)) {
            throw ValidationException::withMessages(['tiers' => 'Chaque quantité minimale doit être unique.']);
        }
        $previousDiscount = 0;
        foreach ($tiers as $tier) {
            if ($tier['discount_percent'] < $previousDiscount) {
                throw ValidationException::withMessages([
                    'tiers' => 'La réduction doit rester identique ou augmenter à chaque palier.',
                ]);
            }
            $previousDiscount = $tier['discount_percent'];
        }
        if ($request->boolean('is_active') && $tiers === []) {
            throw ValidationException::withMessages(['tiers' => 'Ajoute au moins un palier avant d’activer la promotion.']);
        }
        $startsAt = ! empty($data['starts_at']) ? Carbon::parse($data['starts_at']) : null;
        $durationDays = isset($data['duration_days']) ? (int) $data['duration_days'] : null;
        $endsAt = $startsAt && $durationDays ? $startsAt->copy()->addDays($durationDays) : null;
        $applicationMode = $request->boolean('apply_to_all') ? 'general' : 'product';
        if ($request->boolean('is_active') && $applicationMode === 'product' && empty($data['product_ids'])) {
            throw ValidationException::withMessages(['product_ids' => 'Sélectionne au moins un produit pour activer les promotions par produit.']);
        }

        DB::transaction(function () use ($request, $data, $tiers, $applicationMode, $startsAt, $endsAt, $durationDays): void {
            QuantityPromotionSetting::query()->updateOrCreate(['id' => 1], [
                'is_active' => $request->boolean('is_active'),
                'application_mode' => $applicationMode,
                'product_ids' => array_map('intval', $data['product_ids'] ?? []),
                'tiers' => $tiers,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_days' => $durationDays,
            ]);
        });

        return redirect()->route('quantity-promotions.index')->with('success', 'Les paramètres de promotion ont été enregistrés.');
    }
}
