<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCountryPrice;
use App\Models\PublicOrderLink;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PartnerOrderingSetupController extends Controller
{
    public function prices(string $country): View
    {
        $country = strtoupper($country);
        $countries = WestAfricaShippingCatalog::countries();
        abort_unless(isset($countries[$country]), 404);

        $products = Product::query()
            ->with(['countryPrices' => fn ($query) => $query->where('country_code', $country)])
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('partner-ordering.prices', compact('country', 'countries', 'products'));
    }

    public function savePrices(Request $request, string $country): RedirectResponse
    {
        $country = strtoupper($country);
        abort_unless(isset(WestAfricaShippingCatalog::countries()[$country]), 404);

        $validated = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*' => ['nullable', 'integer', 'min:1', 'max:999999999'],
            'available' => ['sometimes', 'array'],
            'available.*' => ['boolean'],
        ]);

        $productIds = array_map('intval', array_keys($validated['prices']));
        $knownIds = Product::query()->whereIn('id', $productIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(count($knownIds) === count(array_unique($productIds)), 422, 'Un produit sélectionné n’existe plus.');

        foreach ($validated['prices'] as $productId => $price) {
            $record = ProductCountryPrice::query()->firstOrNew([
                'product_id' => (int) $productId,
                'country_code' => $country,
            ]);

            if ($price === null || $price === '') {
                if ($record->exists) {
                    $record->delete();
                }
                continue;
            }

            $record->price = (int) $price;
            $record->is_available = (bool) ($validated['available'][$productId] ?? false);
            $record->save();
        }

        return back()->with('success', 'Les prix du catalogue ont été enregistrés.');
    }

    public function links(): View
    {
        $links = PublicOrderLink::query()
            ->with('creator:id,name')
            ->withCount(['products', 'orders'])
            ->latest()
            ->paginate(20);

        $products = Product::query()
            ->whereHas('countryPrices', fn ($query) => $query->where('is_available', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('partner-ordering.links', compact('links', 'products'));
    }

    public function createLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
        ]);

        $pricedProductIds = Product::query()
            ->whereIn('id', $validated['product_ids'])
            ->whereHas('countryPrices', fn ($query) => $query->where('is_available', true))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($pricedProductIds) !== count($validated['product_ids'])) {
            return back()->withErrors(['product_ids' => 'Chaque produit du lien doit avoir un prix actif dans au moins un pays.'])->withInput();
        }

        $link = PublicOrderLink::create([
            'token' => (string) Str::uuid(),
            'title' => $validated['title'] ?: 'Sélection de produits',
            'expires_at' => $validated['expires_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $link->products()->sync(collect($validated['product_ids'])
            ->mapWithKeys(fn ($id, $index) => [(int) $id => ['sort_order' => $index]])
            ->all());

        return redirect()->route('partner-ordering.links')
            ->with('success', 'Le lien de commande a été créé. Tu peux le copier et le partager.');
    }

    public function toggleLink(PublicOrderLink $link): RedirectResponse
    {
        $link->is_active = ! $link->is_active;
        $link->save();

        return back()->with('success', $link->is_active ? 'Le lien est actif.' : 'Le lien a été désactivé.');
    }
}
