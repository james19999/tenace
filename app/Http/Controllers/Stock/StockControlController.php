<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Models\Product;
use App\Models\StockAuditLog;
use App\Services\StockModuleSettingsService;
use App\Services\StockLegacyWarehouseReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockControlController extends Controller
{
    // Only warehouse and shop are user-managed. Transit and quarantine remain system locations.
    private const LOCATION_TYPES = ['warehouse', 'shop'];

    private const SYSTEM_LOCATIONS = [
        ['code' => 'TRANSIT', 'name' => 'Stock en transit', 'type' => 'transit'],
        ['code' => 'BLOQUE', 'name' => 'Stock bloqué / quarantaine', 'type' => 'blocked'],
    ];

    public function index(StockModuleSettingsService $settings): View
    {
        app(\App\Services\StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'settings');
        return view('stocks.control.index', [
            'locations' => StockLocation::query()
                ->where('is_active', true)
                ->whereNotIn('code', array_column(self::SYSTEM_LOCATIONS, 'code'))
                ->with('responsible:id,name,user_type')
                ->orderByRaw("CASE type WHEN 'warehouse' THEN 1 WHEN 'shop' THEN 2 WHEN 'transit' THEN 3 WHEN 'blocked' THEN 4 ELSE 5 END")
                ->orderBy('name')
                ->get(),
            'salesLocations' => StockLocation::query()->where('is_active', true)->where('type', 'shop')->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'users' => \App\Models\User::query()->where('active', true)->whereIn('user_type', ['ADMINUSER', 'MNG'])->orderBy('name')->get(['id', 'name', 'user_type']),
            'rules' => $settings->rules(),
            'locationTypes' => self::LOCATION_TYPES,
            'systemLocationCodes' => array_column(self::SYSTEM_LOCATIONS, 'code'),
            'isAdmin' => auth()->user()?->user_type === 'ADMINUSER',
        ]);
    }

    public function balances(Request $request, StockLegacyWarehouseReconciliationService $legacyStock, StockModuleSettingsService $settings): View
    {
        app(\App\Services\StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'balances');
        $legacyStock->reconcileSingleWarehouse();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'stock_state' => ['nullable', Rule::in(['all', 'available', 'low', 'out'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $locationId = $filters['location_id'] ?? null;
        $state = $filters['stock_state'] ?? 'all';

        $movementBase = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted');

        $locationTotals = (clone $movementBase)
            ->select('sl.type')
            ->selectRaw('SUM(sml.quantity_delta) as quantity')
            ->groupBy('sl.type')
            ->pluck('quantity', 'type');
        $warehouseTotal = (int) ($locationTotals['warehouse'] ?? 0);
        $shopTotal = (int) ($locationTotals['shop'] ?? 0);
        $blockedTotal = (int) ($locationTotals['blocked'] ?? 0);
        $transitTotal = (int) ($locationTotals['transit'] ?? 0);
        $catalogueTotal = (int) Product::query()->sum(DB::raw('COALESCE(qt_initial, 0)'));
        $productionCatalogueInShop = ! empty($settings->rules()['legacy_catalogue_stock_migrated_to_shop']);
        $unallocated = $productionCatalogueInShop ? 0 : $catalogueTotal - $warehouseTotal;
        $locations = StockLocation::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'type', 'is_active']);

        $productLocationTotals = (clone $movementBase)
            ->select('sml.product_id')
            ->selectRaw("SUM(CASE WHEN sl.type = 'warehouse' THEN sml.quantity_delta ELSE 0 END) as warehouse_quantity")
            ->selectRaw("SUM(CASE WHEN sl.type = 'shop' THEN sml.quantity_delta ELSE 0 END) as shop_quantity")
            ->selectRaw("SUM(CASE WHEN sl.type = 'transit' THEN sml.quantity_delta ELSE 0 END) as transit_quantity")
            ->selectRaw("SUM(CASE WHEN sl.type = 'blocked' THEN sml.quantity_delta ELSE 0 END) as blocked_quantity")
            ->groupBy('sml.product_id');

        $balances = Product::query()
            ->leftJoinSub($productLocationTotals, 'plt', 'plt.product_id', '=', 'products.id')
            ->when($search !== '', fn ($query) => $query->where('products.name', 'like', '%'.$search.'%'))
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereExists(function ($exists) use ($locationId) {
                    $exists->selectRaw('1')
                        ->from('stock_movement_lines as filter_sml')
                        ->join('stock_movements as filter_sm', 'filter_sm.id', '=', 'filter_sml.movement_id')
                        ->whereColumn('filter_sml.product_id', 'products.id')
                        ->where('filter_sml.location_id', $locationId)
                        ->where('filter_sm.status', 'posted');
                });
            })
            ->when($state === 'available', fn ($query) => $query->whereRaw('(COALESCE(plt.warehouse_quantity, 0) + COALESCE(plt.shop_quantity, 0)) > 0')->whereRaw('COALESCE(products.qts_seuil, 0) < (COALESCE(plt.warehouse_quantity, 0) + COALESCE(plt.shop_quantity, 0))'))
            ->when($state === 'low', fn ($query) => $query->whereRaw('(COALESCE(plt.warehouse_quantity, 0) + COALESCE(plt.shop_quantity, 0)) > 0')->whereRaw('COALESCE(products.qts_seuil, 0) > 0 AND (COALESCE(plt.warehouse_quantity, 0) + COALESCE(plt.shop_quantity, 0)) <= products.qts_seuil'))
            ->when($state === 'out', fn ($query) => $query->whereRaw('(COALESCE(plt.warehouse_quantity, 0) + COALESCE(plt.shop_quantity, 0)) <= 0'))
            ->select([
                'products.id as product_id', 'products.name as product_name',
                'products.qts_seuil as low_stock_threshold',
            ])
            ->selectRaw('COALESCE(plt.warehouse_quantity, 0) as warehouse_quantity')
            ->selectRaw('COALESCE(plt.warehouse_quantity, 0) as global_quantity')
            ->selectRaw('COALESCE(plt.shop_quantity, 0) as shop_quantity')
            ->selectRaw('COALESCE(plt.transit_quantity, 0) as transit_quantity')
            ->selectRaw('COALESCE(plt.blocked_quantity, 0) as blocked_quantity')
            ->orderBy('products.name')
            ->paginate(10)
            ->withQueryString();
        $productIds = $balances->getCollection()->pluck('product_id');
        $locationBreakdowns = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')->whereIn('sml.product_id', $productIds)
            ->select('sml.product_id', 'sl.name as location_name', 'sl.code as location_code', 'sl.type as location_type')
            ->selectRaw('SUM(sml.quantity_delta) as quantity_on_hand')
            ->groupBy('sml.product_id', 'sl.id', 'sl.name', 'sl.code', 'sl.type')
            ->havingRaw('ABS(SUM(sml.quantity_delta)) > 0.0005')
            ->orderBy('sl.name')->get()->groupBy('product_id');

        return view('stocks.control.balances', compact(
            'balances', 'search', 'state', 'warehouseTotal', 'shopTotal', 'locationTotals', 'catalogueTotal',
            'unallocated', 'blockedTotal', 'transitTotal', 'locations', 'locationId', 'locationBreakdowns', 'productionCatalogueInShop'
        ));
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(self::LOCATION_TYPES)],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->validateLocationResponsible($data['responsible_user_id'] ?? null);

        $data['code'] = $this->generateLocationCode($data['type'], $data['name']);
        $data['is_active'] = true;
        $this->ensureTechnicalLocations();
        $location = StockLocation::query()->create($data);
        $this->audit($request, 'location.created', $location, null, $location->only(['name', 'code', 'type', 'responsible_user_id', 'is_active', 'notes']));

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

        $this->validateLocationResponsible($data['responsible_user_id'] ?? null);

        if (in_array($location->code, array_column(self::SYSTEM_LOCATIONS, 'code'), true)) {
            $data['is_active'] = true;
        }

        $before = $location->only(['name', 'code', 'type', 'responsible_user_id', 'is_active', 'notes']);
        $location->update($data);
        $this->audit($request, 'location.updated', $location, $before, $location->fresh()->only(['name', 'code', 'type', 'responsible_user_id', 'is_active', 'notes']));

        return back()->with('success', 'Emplacement mis à jour.');
    }

    public function destroyLocation(Request $request, StockLocation $location, StockModuleSettingsService $settings): RedirectResponse
    {
        abort_unless(in_array($location->type, ['warehouse', 'shop'], true), 403);

        $references = [
            ['table' => 'stock_transfers', 'query' => fn ($query) => $query->where('source_location_id', $location->id)->orWhere('destination_location_id', $location->id)],
            ['table' => 'stock_receipts', 'query' => fn ($query) => $query->where('location_id', $location->id)],
            ['table' => 'stock_counts', 'query' => fn ($query) => $query->where('location_id', $location->id)],
            ['table' => 'stock_movement_lines', 'query' => fn ($query) => $query->where('location_id', $location->id)],
            ['table' => 'stock_adjustment_lines', 'query' => fn ($query) => $query->where('location_id', $location->id)],
        ];

        foreach ($references as $reference) {
            $query = DB::table($reference['table']);
            $reference['query']($query);
            if ($query->exists()) {
                return back()->withErrors([
                    'location' => 'Suppression impossible : cet emplacement est déjà lié à une réception, un inventaire, un transfert ou un mouvement de stock. Désactive-le pour le conserver dans l’historique.',
                ]);
            }
        }

        $before = $location->only(['name', 'code', 'type', 'responsible_user_id', 'is_active', 'notes']);
        $rules = $settings->rules();
        $location->delete();
        $this->audit($request, 'location.deleted', $location, $before, null);
        if ((int) ($rules['default_sales_location_id'] ?? 0) === (int) $location->id) {
            $settings->save(array_replace($rules, ['default_sales_location_id' => null]));
            $this->auditSettings($request, ['default_sales_location_id' => $location->id], ['default_sales_location_id' => null]);
        }

        return back()->with('success', 'L’emplacement '.$location->name.' a été supprimé.');
    }

    public function updateRules(Request $request, StockModuleSettingsService $settings): RedirectResponse
    {
        abort_unless($request->user()?->user_type === 'ADMINUSER', 403);

        $validated = $request->validate([
            'prevent_negative_stock' => ['sometimes', 'boolean'],
            'require_reason_for_outbound' => ['sometimes', 'boolean'],
            'loss_requires_attachment' => ['sometimes', 'boolean'],
            'transfer_overdue_days' => ['required', 'integer', 'min:1', 'max:365'],
            'expiry_alert_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'inventory_interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_sales_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
        ]);

        if (! empty($validated['default_sales_location_id'])) {
            $salesLocation = StockLocation::query()->findOrFail($validated['default_sales_location_id']);
            if (! $salesLocation->is_active || $salesLocation->type !== 'shop') {
                return back()->withErrors(['default_sales_location_id' => 'Choisis une boutique active pour enregistrer les sorties des commandes.'])->withInput();
            }
        }

        foreach ([
            'prevent_negative_stock', 'require_reason_for_outbound',
            'loss_requires_attachment',
        ] as $booleanRule) {
            $validated[$booleanRule] = $request->boolean($booleanRule);
        }

        $before = $settings->rules();
        $settings->save($validated);
        $this->auditSettings($request, $before, $settings->rules());

        return back()->with('success', 'Règles de contrôle enregistrées. Elles seront appliquées aux opérations du module.');
    }

    private function validateLocationResponsible(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $eligible = \App\Models\User::query()
            ->whereKey($userId)
            ->where('active', true)
            ->whereIn('user_type', ['ADMINUSER', 'MNG'])
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'responsible_user_id' => 'Le responsable doit être un Admin ou un Manager actif, autorisé à valider les opérations de stock.',
            ]);
        }
    }

    private function ensureTechnicalLocations(): void
    {
        foreach (self::SYSTEM_LOCATIONS as $location) {
            StockLocation::query()->firstOrCreate(['code' => $location['code']], [
                'name' => $location['name'],
                'type' => $location['type'],
                'is_active' => true,
            ]);
        }
    }

    private function generateLocationCode(string $type, string $name): string
    {
        $prefix = $type === 'warehouse' ? 'ENT' : 'BOU';
        $namePart = Str::upper(Str::slug($name));
        $namePart = trim(Str::limit($namePart, 24, ''), '-');

        do {
            $code = $prefix.'-'.($namePart !== '' ? $namePart.'-' : '').Str::upper(Str::random(6));
        } while (StockLocation::query()->where('code', $code)->exists());

        return $code;
    }

    private function audit(Request $request, string $action, object $subject, ?array $before, ?array $after): void
    {
        StockAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'actor_role' => $request->user()->user_type,
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'before_values' => $before,
            'after_values' => $after,
            'reason' => null,
            'origin' => 'screen',
            'result' => 'success',
            'request_id' => $request->headers->get('X-Request-Id'),
        ]);
    }

    private function auditSettings(Request $request, array $before, array $after): void
    {
        StockAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'actor_role' => $request->user()->user_type,
            'action' => 'stock.rules_updated',
            'subject_type' => 'stock_module_settings',
            'subject_id' => 1,
            'before_values' => $before,
            'after_values' => $after,
            'origin' => 'screen',
            'result' => 'success',
            'request_id' => $request->headers->get('X-Request-Id'),
        ]);
    }
}
