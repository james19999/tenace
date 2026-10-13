<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Models\Product;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockControlController extends Controller
{
    private const LOCATION_TYPES = ['warehouse', 'shop', 'transit', 'blocked', 'sales_rep', 'wholesaler', 'other'];

    private const SYSTEM_LOCATIONS = [
        ['code' => 'MAG-CENTRAL', 'name' => 'Magasin central', 'type' => 'warehouse'],
        ['code' => 'TRANSIT', 'name' => 'Stock en transit', 'type' => 'transit'],
        ['code' => 'BLOQUE', 'name' => 'Stock bloqué / quarantaine', 'type' => 'blocked'],
    ];

    public function index(StockModuleSettingsService $settings): View
    {
        app(\App\Services\StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'settings');
        return view('stocks.control.index', [
            'locations' => StockLocation::query()
                ->with('responsible:id,name,user_type')
                ->orderByRaw("CASE type WHEN 'warehouse' THEN 1 WHEN 'shop' THEN 2 WHEN 'sales_rep' THEN 3 WHEN 'wholesaler' THEN 4 WHEN 'transit' THEN 5 WHEN 'blocked' THEN 6 ELSE 7 END")
                ->orderBy('name')
                ->get(),
            'salesLocations' => StockLocation::query()->where('is_active', true)->whereNotIn('type', ['transit', 'blocked'])->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'users' => \App\Models\User::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'user_type']),
            'rules' => $settings->rules(),
            'locationTypes' => self::LOCATION_TYPES,
            'systemLocationCodes' => array_column(self::SYSTEM_LOCATIONS, 'code'),
            'isAdmin' => auth()->user()?->user_type === 'ADMINUSER',
        ]);
    }

    public function balances(Request $request): View
    {
        app(\App\Services\StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'balances');
        $filters = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'stock_state' => ['nullable', Rule::in(['all', 'available', 'low', 'out'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $state = $filters['stock_state'] ?? 'all';
        $locationId = $filters['location_id'] ?? null;

        $locations = StockLocation::query()->orderBy('name')->get(['id', 'code', 'name', 'type', 'is_active']);
        $legacyTotal = (int) Product::query()->sum('qt_initial');

        $movementBase = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted');

        $locationTotals = (clone $movementBase)
            ->select('sl.type')
            ->selectRaw('SUM(sml.quantity_delta) as quantity')
            ->groupBy('sl.type')
            ->pluck('quantity', 'type');
        $locatedSellable = (int) (clone $movementBase)
            ->whereNotIn('sl.type', ['blocked', 'transit'])
            ->sum('sml.quantity_delta');
        $unallocated = $legacyTotal - $locatedSellable;

        $balances = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->join('products as p', 'p.id', '=', 'sml.product_id')
            ->where('sm.status', 'posted')
            ->when($locationId, fn ($query) => $query->where('sl.id', $locationId))
            ->when($search !== '', fn ($query) => $query->where('p.name', 'like', '%'.$search.'%'))
            ->select([
                'p.id as product_id', 'p.name as product_name', 'p.qt_initial as global_quantity',
                'p.qts_seuil as low_stock_threshold', 'sl.id as location_id', 'sl.name as location_name',
                'sl.code as location_code', 'sl.type as location_type',
            ])
            ->selectRaw('SUM(sml.quantity_delta) as quantity_on_hand')
            ->groupBy('p.id', 'p.name', 'p.qt_initial', 'p.qts_seuil', 'sl.id', 'sl.name', 'sl.code', 'sl.type')
            ->havingRaw('ABS(SUM(sml.quantity_delta)) > 0.0005')
            ->when($state === 'available', fn ($query) => $query->havingRaw('p.qt_initial > 0 AND p.qt_initial > COALESCE(p.qts_seuil, 0)'))
            ->when($state === 'low', fn ($query) => $query->havingRaw('p.qt_initial > 0 AND p.qt_initial <= COALESCE(p.qts_seuil, 0) AND COALESCE(p.qts_seuil, 0) > 0'))
            ->when($state === 'out', fn ($query) => $query->havingRaw('p.qt_initial <= 0'))
            ->orderBy('sl.name')
            ->orderBy('p.name')
            ->paginate(15)
            ->withQueryString();

        return view('stocks.control.balances', compact(
            'locations', 'balances', 'locationId', 'search', 'state', 'legacyTotal',
            'locatedSellable', 'unallocated', 'locationTotals'
        ));
    }

    public function initializeDefaults(): RedirectResponse
    {
        DB::transaction(function (): void {
            foreach (self::SYSTEM_LOCATIONS as $location) {
                $systemLocation = StockLocation::query()->firstOrCreate(['code' => $location['code']], [
                    'name' => $location['name'],
                    'type' => $location['type'],
                    'is_active' => true,
                ]);
                $systemLocation->update(['type' => $location['type'], 'is_active' => true]);
            }
        });

        return back()->with('success', 'Les emplacements de base sont prêts. Aucun stock n’a été déplacé.');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:40', 'unique:stock_locations,code'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(self::LOCATION_TYPES)],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (in_array(strtoupper($data['code']), array_column(self::SYSTEM_LOCATIONS, 'code'), true)) {
            return back()->withErrors(['code' => 'Ce code est réservé à un emplacement système.'])->withInput();
        }

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = true;
        StockLocation::query()->create($data);

        return back()->with('success', 'Emplacement créé. Aucun stock n’y a été affecté.');
    }

    public function updateLocation(Request $request, StockLocation $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (in_array($location->code, array_column(self::SYSTEM_LOCATIONS, 'code'), true)) {
            $data['is_active'] = true;
        }

        $location->update($data);

        return back()->with('success', 'Emplacement mis à jour.');
    }

    public function updateRules(Request $request, StockModuleSettingsService $settings): RedirectResponse
    {
        abort_unless($request->user()?->user_type === 'ADMINUSER', 403);

        $validated = $request->validate([
            'prevent_negative_stock' => ['sometimes', 'boolean'],
            'require_reason_for_outbound' => ['sometimes', 'boolean'],
            'adjustments_require_approval' => ['sometimes', 'boolean'],
            'allow_self_approval' => ['sometimes', 'boolean'],
            'second_count_on_difference' => ['sometimes', 'boolean'],
            'loss_requires_attachment' => ['sometimes', 'boolean'],
            'approval_threshold_quantity' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'approval_threshold_value' => ['required', 'numeric', 'min:0', 'max:1000000000000'],
            'transfer_overdue_days' => ['required', 'integer', 'min:1', 'max:365'],
            'expiry_alert_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'inventory_interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_sales_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
        ]);

        if (! empty($validated['default_sales_location_id'])) {
            $salesLocation = StockLocation::query()->findOrFail($validated['default_sales_location_id']);
            if (! $salesLocation->is_active || in_array($salesLocation->type, ['transit', 'blocked'], true)) {
                return back()->withErrors(['default_sales_location_id' => 'Choisis un emplacement actif de stock vendable.'])->withInput();
            }
        }

        foreach ([
            'prevent_negative_stock', 'require_reason_for_outbound', 'adjustments_require_approval',
            'allow_self_approval', 'second_count_on_difference', 'loss_requires_attachment',
        ] as $booleanRule) {
            $validated[$booleanRule] = $request->boolean($booleanRule);
        }

        $settings->save($validated);

        return back()->with('success', 'Règles de contrôle enregistrées. Elles seront appliquées aux opérations du module.');
    }
}
