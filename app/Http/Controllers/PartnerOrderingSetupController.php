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

        $isPartner = $request->user()->user_type === User::ROLE_PARTNER;
        $products = Product::query()
            ->with(['countryPrices' => fn ($query) => $query->where('country_code', $country)])
            ->when($isPartner, fn ($query) => $query->whereHas('countryPrices', fn ($prices) => $prices
                ->where('country_code', $country)
                ->where('is_available', true)))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();
        $deliverySetting = PartnerCountryDeliverySetting::query()->firstOrNew(['country_code' => $country]);

        return view('partner-ordering.prices', compact('country', 'countries', 'products', 'deliverySetting', 'isPartner'));
    }

    public function savePrices(Request $request, string $country): RedirectResponse
    {
        $country = strtoupper($country);
        $this->authorizeCountry($request, $country);
        abort_unless(isset(WestAfricaShippingCatalog::countries()[$country]), 404);

        $isPartner = $request->user()->user_type === User::ROLE_PARTNER;
        $isAdmin = $request->user()->user_type === User::ROLE_ADMIN;
        $rules = [
            'available' => ['sometimes', 'array'],
            'available.*' => ['boolean'],
        ];
        if ($isAdmin) {
            $rules += [
                'prices' => ['sometimes', 'array'],
                'prices.*' => ['required', 'integer', 'min:0', 'max:999999999'],
            ];
        }
        if ($isPartner) {
            $rules += [
                'order_delivery_fee' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'delivery_fees' => ['sometimes', 'array'],
            'delivery_fees.*' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            ];
        }
        $validated = $request->validate($rules);

        $productIds = array_map('intval', array_unique(array_merge(
            array_keys($validated['available'] ?? []),
            array_keys($validated['delivery_fees'] ?? []),
            array_keys($validated['prices'] ?? [])
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

            if ($isAdmin && array_key_exists($productId, $validated['prices'] ?? [])) {
                $record->price = (int) $validated['prices'][$productId];
            }

            if ($isPartner) {
                $specificFee = $validated['delivery_fees'][$productId] ?? null;
                $record->has_delivery_fee_override = $specificFee !== null && $specificFee !== '';
                $record->delivery_fee = $record->has_delivery_fee_override ? (int) $specificFee : 0;
            }
            $record->is_available = (bool) ($validated['available'][$productId] ?? false);
            $record->save();
        }

        if ($isPartner) {
            PartnerCountryDeliverySetting::query()->updateOrCreate(
                ['country_code' => $country],
                [
                    'delivery_mode' => 'per_order',
                    'order_delivery_fee' => (int) ($validated['order_delivery_fee'] ?? 0),
                ],
            );
        }

        return back()->with('success', $isPartner
            ? 'La disponibilité et les frais de livraison ont été enregistrés.'
            : ($isAdmin ? 'Les prix et disponibilités de ce pays ont été enregistrés.' : 'Les produits disponibles dans ce pays ont été enregistrés.'));
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

        $countries = WestAfricaShippingCatalog::countries();

        return view('partner-ordering.links', compact('links', 'products', 'countries'));
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
        abort_unless(in_array($user?->user_type, [User::ROLE_PARTNER, User::ROLE_ADMIN, User::ROLE_MANAGER], true), 403);
        if ($user->user_type === User::ROLE_PARTNER) {
            abort_unless(strtoupper((string) $user->country_code) === $country, 403, 'Tu peux modifier uniquement les prix de ton pays.');
        }
    }
}
