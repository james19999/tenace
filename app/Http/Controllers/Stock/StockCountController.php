<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAuditLog;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\StockControlNotification;
use App\Services\StockControlNotificationService;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockCountController extends Controller
{
    public function index(Request $request): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'counts');
        $counts = StockCount::query()
            ->with(['location:id,name,code', 'creator:id,name', 'counter:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('stocks.counts.index', compact('counts'));
    }

    public function create(Request $request): View
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'opening_balance' => ['nullable', 'boolean'],
            'warehouse_only' => ['nullable', 'boolean'],
        ]);
        $productId = $filters['product_id'] ?? null;
        $openingBalance = (bool) ($filters['opening_balance'] ?? false);
        $warehouseOnly = (bool) ($filters['warehouse_only'] ?? false);
        $locations = StockLocation::query()->where('is_active', true)
            ->whereIn('type', ($productId || $warehouseOnly) ? ['warehouse'] : ['warehouse', 'shop'])
            ->orderBy('name')->get(['id', 'name', 'code', 'type']);
        $locationId = $filters['location_id'] ?? (($warehouseOnly && $locations->count() === 1) ? $locations->first()->id : null);
        $users = User::query()->where('active', true)->whereIn('user_type', ['ADMINUSER', 'MNG'])
            ->orderBy('name')->get(['id', 'name', 'user_type']);
        $catalogueIsShop = ! empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop']);
        $locatedBalances = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')->where('sl.type', 'warehouse')
            ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as located_quantity')
            ->groupBy('sml.product_id');
        $stockReconciliationExamples = $catalogueIsShop ? collect() : DB::table('products as p')
            ->leftJoinSub($locatedBalances, 'lb', 'lb.product_id', '=', 'p.id')
            ->when($productId, fn ($query) => $query->where('p.id', $productId))
            ->whereRaw('ABS(p.qt_initial - COALESCE(lb.located_quantity, 0)) >= 0.0005')
            ->orderByRaw('ABS(p.qt_initial - COALESCE(lb.located_quantity, 0)) DESC')
            ->orderBy('p.name')
            ->limit(10)
            ->get([
                'p.id', 'p.name', 'p.qt_initial as global_quantity',
                DB::raw('COALESCE(lb.located_quantity, 0) as located_quantity'),
                DB::raw('p.qt_initial - COALESCE(lb.located_quantity, 0) as difference'),
            ]);

        return view('stocks.counts.create', compact('locations', 'users', 'stockReconciliationExamples', 'productId', 'openingBalance', 'locationId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'counter_id' => ['required', 'integer', 'exists:users,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'scheduled_at' => ['nullable', 'date'],
            'scope' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_opening_balance' => ['nullable', 'boolean'],
        ]);
        $location = StockLocation::query()->findOrFail($data['location_id']);
        if (! $location->is_active || ! in_array($location->type, ['warehouse', 'shop'], true)) {
            throw ValidationException::withMessages(['location_id' => 'Choisis un entrepôt ou une boutique active.']);
        }

        $count = DB::transaction(function () use ($request, $data, $location): StockCount {
            $count = StockCount::query()->create([
                'count_number' => 'TMP-'.Str::uuid(),
                'location_id' => $data['location_id'],
                'status' => 'in_progress',
                'blind_count' => true,
                'is_opening_balance' => $request->boolean('is_opening_balance'),
                'scheduled_at' => $data['scheduled_at'] ?? now(),
                'started_at' => now(),
                'created_by' => $request->user()->id,
                'counter_id' => $data['counter_id'],
                'scope' => $data['scope'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $count->update(['count_number' => sprintf('ST-IN-%s-%06d', now()->format('Y'), $count->id)]);

            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.count_created', 'subject_type' => StockCount::class,
                'subject_id' => $count->id, 'after_values' => [
                    'count_number' => $count->count_number,
                    'location_id' => $location->id, 'location_name' => $location->name,
                    'counter_id' => $count->counter_id,
                    'counter_name' => User::query()->whereKey($count->counter_id)->value('name'),
                    'scheduled_at' => $count->scheduled_at?->toDateTimeString(),
                    'scope' => $count->scope, 'opening_balance' => $count->is_opening_balance,
                ],
                'origin' => 'screen', 'result' => 'success',
            ]);

            return $count;
        });

        app(StockControlNotificationService::class)->toUsers([$count->counter_id], new StockControlNotification(
            'count_assigned',
            $count->is_opening_balance ? 'Reprise initiale à compter' : 'Inventaire à compter',
            'Le comptage '.$count->count_number.' à '.$location->name.' vous a été attribué.',
            route('stock-control.counts.show', $count),
            'fact_check',
            'counts',
        ), $request->user()->id);

        return redirect()->route('stock-control.counts.show', array_filter([
            'count' => $count, 'product_id' => $data['product_id'] ?? null,
        ]))->with('success', 'Inventaire créé. Le comptage est à saisir par le responsable désigné.');
    }

    public function searchProducts(Request $request, StockCount $count): JsonResponse
    {
        abort_unless($count->status === 'in_progress' && (int) $count->counter_id === (int) $request->user()->id, 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'min:2', 'max:120', 'required_without:product_id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id', 'required_without:search'],
        ]);

        $products = DB::table('products as p')
            ->leftJoin('stock_count_lines as scl', function ($join) use ($count): void {
                $join->on('scl.product_id', '=', 'p.id')->where('scl.count_id', '=', $count->id);
            })
            ->when(! empty($filters['product_id']), fn ($query) => $query->where('p.id', $filters['product_id']))
            ->when(! empty($filters['search']), fn ($query) => $query->where('p.name', 'like', '%'.trim($filters['search']).'%'))
            ->orderBy('p.name')
            ->limit(15)
            ->get(['scl.id as line_id', 'p.id as product_id', 'p.name']);

        return response()->json($products);
    }

    public function show(StockCount $count): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'counts');
        $count->load(['location:id,name,code,responsible_user_id', 'creator:id,name', 'counter:id,name']);
        $auditLogs = StockAuditLog::query()->with('user:id,name')
            ->where('subject_type', StockCount::class)->where('subject_id', $count->id)
            ->orderBy('created_at')->orderBy('id')->get();
        $lineCount = $count->lines()->whereNotNull('counted_quantity')->count();
        $count->setRelation('lines', $count->lines()->whereNotNull('counted_quantity')->with('product:id,name,price_market')->get());
        $canCount = $count->status === 'in_progress' && (int) $count->counter_id === (int) auth()->id();
        $canApprove = $count->status === 'submitted';
        $approvalMessage = 'Le comptage est prêt à être enregistré sans validation supplémentaire.';
        $canSecondCount = false;
        $requiresSecondCount = false;
        $differentLines = $count->lines->filter(fn ($line) => $line->counted_quantity !== null && abs((float) $line->counted_quantity - (float) $line->expected_quantity) >= 0.0005);
        $secondCountComplete = ! $requiresSecondCount || $differentLines->every(fn ($line) => $line->second_count_quantity !== null && (float) $line->second_count_quantity === (float) $line->counted_quantity);

        return view('stocks.counts.show', compact('count', 'lineCount', 'canCount', 'canApprove', 'approvalMessage', 'canSecondCount', 'requiresSecondCount', 'differentLines', 'secondCountComplete', 'auditLogs'));
    }

    public function printReceipt(StockCount $count): View
    {
        abort_unless($count->status === 'completed', 404);
        $count->load(['location:id,name,code', 'creator:id,name', 'counter:id,name']);
        $count->setRelation('lines', $count->lines()->whereNotNull('counted_quantity')->with(['product:id,name', 'lot:id,lot_code'])->get());
        $columns = ['Produit', 'Lot', 'Stock attendu', 'Quantité comptée', 'Écart appliqué'];
        $rows = $count->lines->map(function ($line) {
            $delta = (float) ($line->counted_quantity ?? 0) - (float) $line->expected_quantity;
            return [
                $line->product->name, $line->lot?->lot_code ?: 'Sans lot',
                number_format((float) $line->expected_quantity, 3, ',', ' '),
                $line->counted_quantity === null ? '—' : number_format((float) $line->counted_quantity, 3, ',', ' '),
                ($delta > 0 ? '+' : '').number_format($delta, 3, ',', ' '),
            ];
        })->all();
        return view('stocks.operation-receipt', [
            'title' => $count->is_opening_balance ? 'Bon d’inventaire initial' : 'Bon de comptage physique',
            'subtitle' => 'Comptage terminé et quantités appliquées', 'reference' => $count->count_number,
            'eventReference' => $count->completed_at?->format('d/m/Y à H:i'),
            'leftLabel' => 'Emplacement inventorié', 'leftName' => $count->location->name, 'leftInfo' => $count->location->code,
            'rightLabel' => 'Responsable du comptage', 'rightName' => $count->counter?->name ?? '—', 'rightInfo' => 'Créé par '.$count->creator?->name,
            'metadata' => ['Type d’inventaire' => $count->is_opening_balance ? 'Reprise initiale' : 'Comptage physique', 'Périmètre' => ucfirst(str_replace('_', ' ', $count->scope)), 'Enregistré par' => $count->counter?->name, 'Date d’enregistrement' => $count->completed_at?->format('d/m/Y à H:i')],
            'columns' => $columns, 'rows' => $rows, 'notes' => $count->notes,
            'leftSignature' => 'Compteur · '.$count->counter?->name, 'rightSignature' => 'Écarts appliqués au journal',
            'footnote' => 'Ce document présente les quantités relevées et les écarts inscrits dans le journal de stock.',
            'backUrl' => route('stock-control.counts.show', $count),
        ]);
    }

    public function submit(Request $request, StockCount $count): RedirectResponse
    {
        abort_unless((int) $count->counter_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $count, $data): void {
            $locked = StockCount::query()->lockForUpdate()->findOrFail($count->id);
            if ($locked->status !== 'in_progress') {
                throw ValidationException::withMessages(['count' => 'Cet inventaire a déjà été soumis. Recharge la page.']);
            }
            $products = Product::query()->whereIn('id', array_keys($data['quantities']))->lockForUpdate()->get()->keyBy('id');
            if (count($data['quantities']) !== $products->count()) {
                throw ValidationException::withMessages(['quantities' => 'Un produit sélectionné est introuvable.']);
            }
            $expectedQuantities = DB::table('stock_movement_lines as sml')
                ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                ->where('sm.status', 'posted')->where('sml.location_id', $locked->location_id)
                ->whereRaw('COALESCE(sm.posted_at, sm.occurred_at) <= ?', [$locked->started_at])
                ->whereIn('sml.product_id', $products->keys())
                ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as expected_quantity')
                ->groupBy('sml.product_id')->pluck('expected_quantity', 'product_id');
            $auditLines = [];
            foreach ($data['quantities'] as $productId => $quantity) {
                $line = $locked->lines()->firstOrNew(['product_id' => (int) $productId]);
                if (! $line->exists) {
                    $line->expected_quantity = $expectedQuantities[$productId] ?? 0;
                    $line->condition = 'sellable';
                }
                $line->counted_quantity = $quantity;
                $line->save();
                $auditLines[] = [
                    'product_id' => (int) $productId,
                    'product_name' => $products->get((int) $productId)->name,
                    'expected_quantity' => (float) $line->expected_quantity,
                    'counted_quantity' => (int) $quantity,
                    'difference' => (int) $quantity - (float) $line->expected_quantity,
                ];
            }
            $locked->status = 'submitted';
            $locked->submitted_at = now();
            $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '').($data['notes'] ?? '')) ?: null;
            $locked->save();
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.count_submitted', 'subject_type' => StockCount::class,
                'subject_id' => $locked->id,
                'before_values' => ['status' => 'in_progress'],
                'after_values' => ['status' => 'submitted', 'line_count' => count($auditLines), 'lines' => $auditLines],
                'reason' => $data['notes'] ?? $locked->notes,
                'origin' => 'screen', 'result' => 'success',
            ]);
        });

        return $this->approve($request, $count);
    }

    public function approve(Request $request, StockCount $count): RedirectResponse
    {
        DB::transaction(function () use ($request, $count): void {
            $locked = StockCount::query()->lockForUpdate()->with('lines')->findOrFail($count->id);
            $countedLines = $locked->lines->whereNotNull('counted_quantity')->values();
            if ($locked->status !== 'submitted' || $countedLines->isEmpty()) {
                throw ValidationException::withMessages(['count' => 'Cet inventaire ne contient aucun comptage à enregistrer.']);
            }
            $location = StockLocation::query()->findOrFail($locked->location_id);
            $adjustment = StockAdjustment::query()->create([
                'adjustment_number' => 'TMP-'.Str::uuid(), 'status' => 'posted',
                'reason_code' => $locked->is_opening_balance ? 'opening_balance' : 'inventory_variance',
                'reason' => $locked->is_opening_balance ? 'Reprise initiale des quantités par inventaire physique.' : 'Écart constaté lors de l’inventaire '.$locked->count_number,
                'count_id' => $locked->id, 'requested_by' => $locked->counter_id,
                'approved_by' => null, 'submitted_at' => $locked->submitted_at,
                'approved_at' => null, 'posted_at' => now(),
            ]);
            $adjustment->update(['adjustment_number' => sprintf('ST-AJ-%s-%06d', now()->format('Y'), $adjustment->id)]);

            $movement = StockMovement::query()->create([
                'movement_number' => 'TMP-'.Str::uuid(), 'type' => $locked->is_opening_balance ? 'opening_balance' : 'inventory_adjustment',
                'status' => 'posted', 'source_type' => 'stock_count', 'source_reference' => $locked->count_number,
                'occurred_at' => now(), 'origin' => 'screen', 'reason' => $adjustment->reason,
                'created_by' => $locked->counter_id, 'approved_by' => null,
                'posted_by' => $request->user()->id, 'approved_at' => null, 'posted_at' => now(),
            ]);
            $movement->update(['movement_number' => sprintf('ST-MVT-%s-%06d', now()->format('Y'), $movement->id)]);
            $adjustment->update(['movement_id' => $movement->id]);
            $hasDifferences = false;
            $auditLines = [];
            $catalogueIsShop = ! empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop']);

            foreach ($countedLines as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
                $before = (float) $line->expected_quantity;
                $counted = (float) $line->counted_quantity;
                $delta = $counted - $before;
                $line->update(['condition' => 'sellable']);
                $auditLines[] = [
                    'product_id' => $product->id, 'product_name' => $product->name,
                    'expected_quantity' => $before, 'counted_quantity' => $counted,
                    'difference' => $delta,
                ];

                if ($location->type === 'warehouse' && ! $catalogueIsShop) {
                    if ($locked->is_opening_balance) {
                        // The catalogue's legacy global quantity represents warehouse stock.
                        // Opening counts set it to the physical balance instead of adding it twice.
                        $product->qt_initial = (int) $counted;
                        $product->save();
                    } elseif (abs($delta) >= 0.0005) {
                        $product->qt_initial = (int) $product->qt_initial + (int) $delta;
                        $product->save();
                    }
                }

                if (abs($delta) < 0.0005) {
                    continue;
                }
                $hasDifferences = true;
                $movement->lines()->create([
                    'product_id' => $product->id, 'location_id' => $locked->location_id,
                    'quantity_delta' => $delta, 'unit_cost' => $product->price_market,
                    'unit' => 'piece', 'notes' => 'Écart enregistré par inventaire physique',
                ]);
                $adjustment->lines()->create([
                    'product_id' => $product->id, 'location_id' => $locked->location_id,
                    'quantity_before' => $before, 'quantity_counted' => $counted,
                    'quantity_delta' => $delta, 'unit_cost' => $product->price_market,
                ]);
            }

            if (! $hasDifferences) {
                $movement->delete();
                $adjustment->update(['movement_id' => null]);
            }
            $locked->status = 'completed';
            $locked->approved_at = null;
            $locked->completed_at = now();
            $locked->approved_by = null;
            $locked->reviewed_by = $request->user()->id;
            $locked->save();
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.count_completed', 'subject_type' => StockCount::class,
                'subject_id' => $locked->id,
                'before_values' => ['status' => 'submitted'],
                'after_values' => [
                    'status' => 'completed', 'adjustment_id' => $adjustment->id,
                    'adjustment_number' => $adjustment->adjustment_number,
                    'movement_id' => $movement->exists ? $movement->id : null,
                    'movement_number' => $movement->exists ? $movement->movement_number : null,
                    'line_count' => count($auditLines), 'lines' => $auditLines,
                ],
                'origin' => 'screen', 'result' => 'success',
            ]);
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'stock.adjustment_applied', 'subject_type' => StockAdjustment::class,
                'subject_id' => $adjustment->id,
                'before_values' => ['status' => 'created', 'movement_id' => null],
                'after_values' => [
                    'status' => 'posted', 'adjustment_number' => $adjustment->adjustment_number,
                    'count_id' => $locked->id, 'movement_id' => $movement->exists ? $movement->id : null,
                    'movement_number' => $movement->exists ? $movement->movement_number : null,
                    'line_count' => count($auditLines), 'lines' => $auditLines,
                ],
                'reason' => $adjustment->reason, 'origin' => 'screen', 'result' => 'success',
            ]);
        });

        app(StockControlNotificationService::class)->toUsers([$count->created_by, $count->counter_id], new StockControlNotification(
            'count_completed',
            'Inventaire enregistré',
            'Le comptage '.$count->count_number.' a été appliqué et inscrit au journal.',
            route('stock-control.counts.show', $count),
            'task_alt',
            'counts',
        ), $request->user()->id);

        return back()->with('success', 'Inventaire enregistré et écarts appliqués au journal de stock.');
    }

}
