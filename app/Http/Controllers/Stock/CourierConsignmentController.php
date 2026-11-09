<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\CourierConsignmentRun;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\CourierConsignmentService;
use App\Services\FinanceCashLedgerService;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourierConsignmentController extends Controller
{
    public function index(Request $request, CourierConsignmentService $service, StockModuleSettingsService $settings): View
    {
        $actor = $request->user();
        $isCourier = $actor->user_type === User::ROLE_LIVREUR;
        $shop = $isCourier ? null : $this->shopFor($actor, $settings);
        $query = CourierConsignmentRun::query()->with(['shop', 'courier', 'lines.product', 'events.actor'])->latest('run_date')->latest('id');
        if ($isCourier) $query->where('courier_user_id', $actor->id);
        elseif ($shop) $query->where('shop_location_id', $shop->id);
        else $query->whereRaw('1 = 0');
        $totalRunCount = (clone $query)->count();
        $runs = (clone $query)->setEagerLoads([])->reorder()->select('courier_user_id')->distinct()->orderBy('courier_user_id')
            ->paginate(10, ['courier_user_id'], 'courier_page')->withQueryString();
        $courierIds = collect($runs->items())->pluck('courier_user_id');
        $groupedRuns = $courierIds->isEmpty()
            ? collect()
            : (clone $query)->whereIn('courier_user_id', $courierIds)->latest('run_date')->latest('id')->get();
        $groupedRuns->each(function (CourierConsignmentRun $run) use ($service): void {
            $run->setAttribute('cash_expected', $service->expectedCash($run));
        });
        $runs->setCollection($groupedRuns->groupBy('courier_user_id')->values());

        $couriers = collect();
        $products = collect();
        if (! $isCourier && $shop) {
            $couriers = User::query()->where('active', true)->where('user_type', User::ROLE_LIVREUR)->orderBy('name')->get(['id', 'name', 'phone']);
            $shopBalances = $service->balances($shop->id);
            $products = Product::query()->orderBy('name')->get(['id', 'name', 'price', 'price_market'])
                ->map(function (Product $product) use ($shopBalances) {
                    $product->shop_quantity = $shopBalances[$product->id] ?? 0;
                    return $product;
                })->filter(fn (Product $product) => $product->shop_quantity > 0)->values();
        }

        return view('stocks.courier-consignments.index', compact('actor', 'isCourier', 'shop', 'runs', 'totalRunCount', 'couriers', 'products'));
    }

    public function assign(Request $request, CourierConsignmentService $service, StockModuleSettingsService $settings): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->user_type, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_SECRETARY], true), 403);
        $shop = $this->shopFor($actor, $settings);
        abort_unless($shop, 403, 'Aucune boutique n’est affectée à ce responsable.');
        $data = $request->validate([
            'courier_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('active', true)->where('user_type', User::ROLE_LIVREUR))],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['lines'] = collect($data['lines'])
            ->filter(fn (array $line): bool => (int) $line['quantity'] > 0)
            ->values()
            ->all();
        if ($data['lines'] === []) {
            throw ValidationException::withMessages(['lines' => 'Choisis au moins un produit et indique une quantité supérieure à zéro.']);
        }
        $courier = User::query()->findOrFail($data['courier_id']);
        $run = DB::transaction(function () use ($service, $shop, $courier, $data, $actor) {
            $run = $service->assign($shop, $courier, $data['lines'], $actor);
            if ($data['notes'] ?? null) $run->update(['notes' => trim(($run->notes ? $run->notes."\n" : '').$data['notes'])]);
            return $run;
        });
        return redirect()->route('courier-consignments.index')->with('success', 'Produits consignés au livreur. Référence : '.$run->reference);
    }

    public function settle(Request $request, CourierConsignmentRun $run, CourierConsignmentService $service, FinanceCashLedgerService $cashLedger, StockModuleSettingsService $settings): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->user_type, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_SECRETARY], true)
            && (int) $run->shop_location_id === (int) $this->shopFor($actor, $settings)?->id, 403);
        $data = $request->validate([
            'returned' => ['sometimes', 'array'], 'returned.*.product_id' => ['required_with:returned', 'integer'], 'returned.*.quantity' => ['required_with:returned', 'integer', 'min:0'],
            'cash_amount' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'close_run' => ['nullable', 'boolean'], 'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'returned.*.quantity.integer' => 'La quantité rendue doit être un nombre entier de pièces (ex. 0, 1 ou 2).',
            'returned.*.quantity.min' => 'La quantité rendue ne peut pas être négative.',
            'cash_amount.integer' => 'Le montant remis doit être un nombre entier en F CFA.',
        ]);
        $close = $request->boolean('close_run');
        $updated = $service->settle($run, $data['returned'] ?? [], (int) $data['cash_amount'], $close, $actor, $cashLedger);
        if (! empty($data['notes'])) {
            $updated->update(['notes' => trim(($updated->notes ? $updated->notes."\n" : '').$data['notes'])]);
        }
        if ($close) return redirect()->route('courier-consignments.receipt', $updated)->with('success', 'Tournée clôturée : stock, ventes et versements sont rapprochés.');
        return redirect()->route('courier-consignments.index')->with('success', 'Point de tournée enregistré. Le reliquat de ventes peut être reversé plus tard.');
    }

    public function receipt(Request $request, CourierConsignmentRun $run, StockModuleSettingsService $settings): View
    {
        abort_unless($run->status === 'closed', 404);
        $actor = $request->user();
        abort_unless(($actor->user_type === User::ROLE_LIVREUR && (int) $run->courier_user_id === (int) $actor->id)
            || (in_array($actor->user_type, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_SECRETARY], true) && (int) $run->shop_location_id === (int) $this->shopFor($actor, $settings)?->id), 403);
        $run->load(['shop', 'courier', 'lines.product', 'events.actor']);
        return view('stocks.courier-consignments.receipt', compact('run'));
    }

    private function shopFor(User $actor, StockModuleSettingsService $settings): ?StockLocation
    {
        if ($actor->primary_stock_location_id) {
            $assigned = StockLocation::query()->whereKey($actor->primary_stock_location_id)->where('type', 'shop')->where('is_active', true)->first();
            if ($assigned) return $assigned;
        }
        $responsible = StockLocation::query()->where('responsible_user_id', $actor->id)->where('type', 'shop')->where('is_active', true)->first();
        if ($responsible) return $responsible;
        if ($actor->isAdmin()) {
            $locationId = $settings->rules()['default_sales_location_id'] ?? null;
            return $locationId ? StockLocation::query()->whereKey($locationId)->where('type', 'shop')->where('is_active', true)->first() : null;
        }
        return null;
    }
}
