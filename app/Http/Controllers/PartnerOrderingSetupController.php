<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCountryPrice;
use App\Models\PartnerProductStock;
use App\Models\PartnerCourierStockIssueLine;
use App\Models\PartnerCountryDeliverySetting;
use App\Models\PublicOrderLink;
use App\Models\User;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $canSelectPartnerStock = in_array($request->user()->user_type, [User::ROLE_ADMIN, User::ROLE_MANAGER], true);
        $countryPartners = $canSelectPartnerStock
            ? User::query()->where('user_type', User::ROLE_PARTNER)->where('country_code', $country)->orderBy('name')->get(['id', 'name', 'country_code'])
            : collect();
        $selectedPartnerId = $isPartner
            ? (int) $request->user()->id
            : (int) ($request->query('partner_id') ?: ($countryPartners->count() === 1 ? $countryPartners->first()->id : 0));
        $stockPartner = $isPartner
            ? $request->user()
            : $countryPartners->firstWhere('id', $selectedPartnerId);
        $canReplenishPartnerStock = $canSelectPartnerStock && $stockPartner !== null;
        $showPartnerStock = $isPartner || $canReplenishPartnerStock;
        $products = Product::query()
            ->with(['countryPrices' => fn ($query) => $query->where('country_code', $country)->with('quantityTiers')])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.trim((string) $request->query('search')).'%'))
            ->when($isPartner, fn ($query) => $query->whereHas('countryPrices', fn ($prices) => $prices
                ->where('country_code', $country)
                ->where('is_available', true)))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();
        $deliverySetting = PartnerCountryDeliverySetting::query()->firstOrNew(['country_code' => $country]);
        $partnerStocks = $showPartnerStock
            ? PartnerProductStock::query()->where('partner_id', $stockPartner->id)->where('country_code', $country)->get()->keyBy('product_id')
            : collect();
        $productIds = $products->getCollection()->modelKeys();
        $warehouseSnapshot = $canReplenishPartnerStock
            ? ['warehouse' => app(\App\Services\PartnerCourierStockService::class)->hasWarehouseForCountry($country)]
            : ['warehouse' => false];
        $suppliedStocks = $showPartnerStock
            ? app(\App\Services\PartnerCourierStockService::class)->suppliedQuantities($stockPartner, $productIds)
            : collect();
        $courierStocks = $showPartnerStock
            ? PartnerCourierStockIssueLine::query()
                ->join('partner_courier_stock_issues as issues', 'issues.id', '=', 'partner_courier_stock_issue_lines.issue_id')
                ->where('issues.partner_id', $stockPartner->id)
                ->where('issues.country_code', $country)
                ->whereIn('partner_courier_stock_issue_lines.product_id', $productIds)
                ->groupBy('partner_courier_stock_issue_lines.product_id')
                ->selectRaw('partner_courier_stock_issue_lines.product_id, SUM(partner_courier_stock_issue_lines.quantity_remaining) as quantity')
                ->pluck('quantity', 'product_id')
            : collect();

        return view('partner-ordering.prices', compact('country', 'countries', 'products', 'deliverySetting', 'isPartner', 'partnerStocks', 'warehouseSnapshot', 'suppliedStocks', 'courierStocks', 'countryPartners', 'stockPartner', 'canReplenishPartnerStock', 'showPartnerStock'));
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
                'tier_configured' => ['sometimes', 'array'],
                'tier_configured.*' => ['boolean'],
                'quantity_prices' => ['sometimes', 'array'],
                'quantity_prices.*' => ['array', 'max:8'],
                'quantity_prices.*.*.min_quantity' => ['nullable', 'integer', 'min:2', 'max:500'],
                'quantity_prices.*.*.unit_price' => ['nullable', 'integer', 'min:1', 'max:999999999'],
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
            array_keys($validated['prices'] ?? []),
            array_keys($validated['tier_configured'] ?? [])
        )));
        $productsById = Product::query()->whereIn('id', $productIds)->get(['id', 'price'])->keyBy('id');
        $knownIds = $productsById->keys()->map(fn ($id) => (int) $id)->all();
        abort_unless(count($knownIds) === count(array_unique($productIds)), 422, 'Un produit sélectionné n’existe plus.');
        if ($isAdmin) {
            $existingCountryPrices = ProductCountryPrice::query()->where('country_code', $country)
                ->whereIn('product_id', $productIds)->get()->keyBy('product_id');
            foreach (($validated['tier_configured'] ?? []) as $productId => $_configured) {
                $rows = array_values($validated['quantity_prices'][$productId] ?? []);
                $tiers = [];
                foreach ($rows as $row) {
                    $hasQuantity = isset($row['min_quantity']) && $row['min_quantity'] !== '';
                    $hasPrice = isset($row['unit_price']) && $row['unit_price'] !== '';
                    if ($hasQuantity !== $hasPrice) {
                        throw ValidationException::withMessages([
                            'quantity_prices' => 'Renseigne à la fois la quantité minimale et le prix unitaire de chaque palier.',
                        ]);
                    }
                    if (! $hasQuantity) continue;
                    $tiers[] = ['min_quantity' => (int) $row['min_quantity'], 'unit_price' => (int) $row['unit_price']];
                }
                usort($tiers, fn (array $left, array $right) => $left['min_quantity'] <=> $right['min_quantity']);
                $thresholds = array_column($tiers, 'min_quantity');
                if (count($thresholds) !== count(array_unique($thresholds))) {
                    throw ValidationException::withMessages(['quantity_prices' => 'Chaque palier doit avoir une quantité minimale différente.']);
                }
                $basePrice = (int) ($validated['prices'][$productId] ?? $existingCountryPrices[$productId]?->price ?? $productsById[$productId]->price);
                $previousPrice = $basePrice;
                foreach ($tiers as $tier) {
                    if ($tier['unit_price'] >= $previousPrice) {
                        throw ValidationException::withMessages([
                            'quantity_prices' => 'Le prix unitaire d’un palier doit être inférieur au prix précédent (tarif de base ou palier antérieur).',
                        ]);
                    }
                    $previousPrice = $tier['unit_price'];
                }
            }
        }
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($productIds, $productsById, $validated, $isAdmin, $isPartner, $country, $request): void {
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

                    if ($isPartner && array_key_exists($productId, $validated['delivery_fees'] ?? [])) {
                        $specificFee = $validated['delivery_fees'][$productId] ?? null;
                        $record->has_delivery_fee_override = $specificFee !== null && $specificFee !== '';
                        $record->delivery_fee = $record->has_delivery_fee_override ? (int) $specificFee : 0;
                    }
                    $record->is_available = (bool) ($validated['available'][$productId] ?? false);
                    $record->save();

                    if ($isAdmin && array_key_exists($productId, $validated['tier_configured'] ?? [])) {
                        $tiers = collect($validated['quantity_prices'][$productId] ?? [])
                            ->filter(fn (array $tier) => isset($tier['min_quantity'], $tier['unit_price']) && $tier['min_quantity'] !== '' && $tier['unit_price'] !== '')
                            ->map(fn (array $tier) => [
                                'min_quantity' => (int) $tier['min_quantity'],
                                'unit_price' => (int) $tier['unit_price'],
                            ])->sortBy('min_quantity')->values()->all();
                        $record->quantityTiers()->delete();
                        if ($tiers) $record->quantityTiers()->createMany($tiers);
                    }
                }

            });
        } catch (\DomainException $exception) {
            return back()->withErrors(['stock' => $exception->getMessage()])->withInput();
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

    public function replenishStock(Request $request, string $country): RedirectResponse
    {
        $country = strtoupper($country);
        $this->authorizeCountry($request, $country);
        $canSelectPartnerStock = in_array($request->user()->user_type, [User::ROLE_ADMIN, User::ROLE_MANAGER], true);
        abort_unless($canSelectPartnerStock, 403);
        abort_unless(isset(WestAfricaShippingCatalog::countries()[$country]), 404);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999999'],
            'partner_id' => ['required', 'integer'],
        ]);
        $partner = User::query()->whereKey($validated['partner_id'])->where('user_type', User::ROLE_PARTNER)->where('country_code', $country)->firstOrFail();
        $isAvailable = ProductCountryPrice::query()->where('product_id', $validated['product_id'])
            ->where('country_code', $country)->where('is_available', true)->exists();
        abort_unless($isAvailable, 422, 'Ce produit n’est pas disponible dans le catalogue de ton pays.');

        try {
            app(\App\Services\PartnerCourierStockService::class)->replenish(
                $partner, $request->user(), [(int) $validated['product_id'] => (int) $validated['quantity']],
            );
        } catch (\DomainException $exception) {
            return back()->withErrors(['stock' => $exception->getMessage()])->withInput($validated);
        }

        return back()->with('success', 'Le réapprovisionnement de '.$partner->name.' a été enregistré. Le stock de l’entrepôt et sa réserve ont été mis à jour.');
    }

    public function links(): View
    {
        $links = PublicOrderLink::query()
            ->with('creator:id,name')
            ->withCount(['products', 'orders'])
            ->latest()
            ->paginate(20);

        $products = Product::query()
            ->with(['countryPrices' => fn ($query) => $query->where('is_available', true)])
            ->whereHas('countryPrices', fn ($query) => $query->where('is_available', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $countries = WestAfricaShippingCatalog::countries();

        return view('partner-ordering.links', compact('links', 'products', 'countries'));
    }

    public function createLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2', 'in:'.implode(',', array_keys(WestAfricaShippingCatalog::countries()))],
            'title' => ['nullable', 'string', 'max:160'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
        ]);

        if (PublicOrderLink::query()->where('country_code', strtoupper($validated['country_code']))->exists()) {
            return back()->withErrors(['country_code' => 'Un lien de commande existe déjà pour ce pays. Tu peux le copier ou le réactiver.'])->withInput();
        }

        $pricedProductIds = Product::query()
            ->whereIn('id', $validated['product_ids'])
            ->whereHas('countryPrices', fn ($query) => $query
                ->where('country_code', strtoupper($validated['country_code']))
                ->where('is_available', true))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($pricedProductIds) !== count($validated['product_ids'])) {
            return back()->withErrors(['product_ids' => 'Chaque produit du lien doit être disponible dans le pays choisi.'])->withInput();
        }

        $link = PublicOrderLink::create([
            'token' => (string) Str::uuid(),
            'title' => $validated['title'] ?: 'Catalogue '.(WestAfricaShippingCatalog::countries()[strtoupper($validated['country_code'])]['label'] ?? strtoupper($validated['country_code'])),
            'country_code' => strtoupper($validated['country_code']),
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

    public function deleteLink(PublicOrderLink $link): RedirectResponse
    {
        if ($link->is_active) {
            return back()->withErrors(['link' => 'Désactive le lien avant de pouvoir le supprimer.']);
        }

        $link->delete();

        return back()->with('success', 'Le lien désactivé a été supprimé. Les commandes déjà créées sont conservées.');
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
