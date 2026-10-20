<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCountryPrice;
use App\Models\PartnerCountryDeliverySetting;
use App\Models\PublicOrderLink;
use App\Models\User;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PartnerOrderingSetupController extends Controller
{
    public function prices(Request $request, string $country): View
    {
        $country = strtoupper($country);
        $countries = WestAfricaShippingCatalog::countries();
        $this->authorizeCountry($request, $country);
        abort_unless(isset($countries[$country]), 404);
        if ($request->user()->user_type === User::ROLE_PARTNER) {
            $countries = [$country => $countries[$country]];
        }

        $products = Product::query()
            ->with(['countryPrices' => fn ($query) => $query->where('country_code', $country)])
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();
        $deliverySetting = PartnerCountryDeliverySetting::query()->firstOrNew(['country_code' => $country]);

        return view('partner-ordering.prices', compact('country', 'countries', 'products', 'deliverySetting'));
    }

    public function savePrices(Request $request, string $country): RedirectResponse
    {
        $country = strtoupper($country);
        $this->authorizeCountry($request, $country);
        abort_unless(isset(WestAfricaShippingCatalog::countries()[$country]), 404);

        $validated = $request->validate([
            'order_delivery_fee' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'available' => ['sometimes', 'array'],
            'available.*' => ['boolean'],
            'delivery_fees' => ['sometimes', 'array'],
            'delivery_fees.*' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ]);

        $productIds = array_map('intval', array_unique(array_merge(
            array_keys($validated['available'] ?? []),
            array_keys($validated['delivery_fees'] ?? [])
        )));
        $productsById = Product::query()->whereIn('id', $productIds)->get(['id', 'price'])->keyBy('id');
        $knownIds = $productsById->keys()->map(fn ($id) => (int) $id)->all();
        abort_unless(count($knownIds) === count(array_unique($productIds)), 422, 'Un produit sélectionné n’existe plus.');

        foreach ($productIds as $productId) {
            $record = ProductCountryPrice::query()->firstOrNew([
                'product_id' => $productId,
                'country_code' => $country,
            ]);

            if (! $record->exists) {
                // A country price row uses the sale price already defined on the product.
                $record->price = (int) $productsById[$productId]->price;
            }

            $specificFee = $validated['delivery_fees'][$productId] ?? null;
            $record->has_delivery_fee_override = $specificFee !== null && $specificFee !== '';
            $record->delivery_fee = $record->has_delivery_fee_override ? (int) $specificFee : 0;
            $record->is_available = (bool) ($validated['available'][$productId] ?? false);
            $record->save();
        }

        PartnerCountryDeliverySetting::query()->updateOrCreate(
            ['country_code' => $country],
            [
                'delivery_mode' => 'per_order',
                'order_delivery_fee' => (int) ($validated['order_delivery_fee'] ?? 0),
            ],
        );

        return back()->with('success', 'La disponibilité et les frais de livraison ont été enregistrés.');
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

    private function authorizeCountry(Request $request, string $country): void
    {
        $user = $request->user();
        abort_unless($user?->user_type === User::ROLE_PARTNER, 403);
        abort_unless(strtoupper((string) $user->country_code) === $country, 403, 'Tu peux modifier uniquement les prix de ton pays.');
    }
}
