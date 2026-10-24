<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductQuantityPriceTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductQuantityPriceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $products = Product::query()->with('quantityPriceTiers')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate(8)->withQueryString();

        return view('products.quantity-prices', compact('products', 'search'));
    }

    public function save(Request $request, Product $product)
    {
        $data = $request->validate([
            'min_quantity' => ['array', 'max:8'],
            'min_quantity.*' => ['nullable', 'integer', 'min:2', 'max:999999'],
            'unit_price' => ['array', 'max:8'],
            'unit_price.*' => ['nullable', 'integer', 'min:1', 'max:999999999'],
        ]);

        $tiers = [];
        foreach ($data['min_quantity'] ?? [] as $index => $minimum) {
            $price = $data['unit_price'][$index] ?? null;
            if (($minimum === null || $minimum === '') && ($price === null || $price === '')) continue;
            if (! $minimum || ! $price) {
                throw ValidationException::withMessages(['tiers' => 'Chaque palier doit avoir une quantité minimale et un prix unitaire.']);
            }
            $tiers[] = ['min_quantity' => (int) $minimum, 'unit_price' => (int) $price];
        }
        usort($tiers, fn ($left, $right) => $left['min_quantity'] <=> $right['min_quantity']);
        if (count(array_unique(array_column($tiers, 'min_quantity'))) !== count($tiers)) {
            throw ValidationException::withMessages(['tiers' => 'Chaque quantité minimale doit être unique.']);
        }
        $previousPrice = (int) $product->price;
        foreach ($tiers as $tier) {
            if ($tier['unit_price'] >= $previousPrice) {
                throw ValidationException::withMessages(['tiers' => 'Chaque palier doit proposer un prix inférieur au prix précédent.']);
            }
            $previousPrice = $tier['unit_price'];
        }

        DB::transaction(function () use ($product, $tiers): void {
            $product->quantityPriceTiers()->delete();
            if ($tiers) $product->quantityPriceTiers()->createMany($tiers);
        });

        return back()->with('success', 'Les tarifs dégressifs de '.$product->name.' ont été enregistrés.');
    }
}
