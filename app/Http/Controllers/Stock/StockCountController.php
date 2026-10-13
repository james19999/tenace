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

    public function create(): View
    {
        $locations = StockLocation::query()->where('is_active', true)
            ->whereNotIn('type', ['transit', 'blocked'])->orderBy('name')->get(['id', 'name', 'code']);
        $users = User::query()->where('active', true)->whereIn('user_type', ['ADMINUSER', 'MNG'])
            ->orderBy('name')->get(['id', 'name', 'user_type']);
        $locatedBalances = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')->whereNotIn('sl.type', ['blocked', 'transit'])
            ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as located_quantity')
            ->groupBy('sml.product_id');
        $stockReconciliationExamples = DB::table('products as p')
            ->leftJoinSub($locatedBalances, 'lb', 'lb.product_id', '=', 'p.id')
            ->whereRaw('ABS(p.qt_initial - COALESCE(lb.located_quantity, 0)) >= 0.0005')
            ->orderByRaw('ABS(p.qt_initial - COALESCE(lb.located_quantity, 0)) DESC')
            ->orderBy('p.name')
            ->limit(10)
            ->get([
                'p.id', 'p.name', 'p.qt_initial as global_quantity',
                DB::raw('COALESCE(lb.located_quantity, 0) as located_quantity'),
                DB::raw('p.qt_initial - COALESCE(lb.located_quantity, 0) as difference'),
            ]);

        return view('stocks.counts.create', compact('locations', 'users', 'stockReconciliationExamples'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'counter_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
            'scope' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_opening_balance' => ['nullable', 'boolean'],
        ]);
        $location = StockLocation::query()->findOrFail($data['location_id']);
        if (! $location->is_active || in_array($location->type, ['transit', 'blocked'], true)) {
            throw ValidationException::withMessages(['location_id' => 'Choisis un emplacement actif de stock vendable.']);
        }

        $count = DB::transaction(function () use ($request, $data): StockCount {
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

            $products = Product::query()->orderBy('name')->get(['id']);
            $expectedByProduct = DB::table('stock_movement_lines as sml')
                ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                ->where('sm.status', 'posted')->where('sml.location_id', $data['location_id'])
                ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as expected_quantity')
                ->groupBy('sml.product_id')->pluck('expected_quantity', 'product_id');
            foreach ($products->chunk(500) as $productChunk) {
                $now = now();
                $rows = $productChunk->map(fn ($product) => [
                    'count_id' => $count->id,
                    'product_id' => $product->id,
                    'expected_quantity' => $expectedByProduct[$product->id] ?? 0,
                    'condition' => 'sellable',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                DB::table('stock_count_lines')->insert($rows);
            }

            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.count_created', 'subject_type' => StockCount::class,
                'subject_id' => $count->id, 'after_values' => ['location_id' => $data['location_id'], 'lines' => $products->count()],
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

        return redirect()->route('stock-control.counts.show', $count)->with('success', 'Inventaire créé. Le comptage est à saisir par le responsable désigné.');
    }

    public function show(StockCount $count): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'counts');
        $count->load(['location:id,name,code', 'creator:id,name', 'counter:id,name', 'approver:id,name', 'lines.product:id,name,price_market']);
        $canCount = $count->status === 'in_progress' && (int) $count->counter_id === (int) auth()->id();
        $allowSelfApproval = app(StockModuleSettingsService::class)->rules()['allow_self_approval'];
        $canApprove = $count->status === 'submitted' && ($allowSelfApproval || (int) $count->counter_id !== (int) auth()->id());
        $canSecondCount = $count->status === 'submitted' && (int) $count->counter_id !== (int) auth()->id();
        $requiresSecondCount = app(StockModuleSettingsService::class)->rules()['second_count_on_difference'];
        $differentLines = $count->lines->filter(fn ($line) => $line->counted_quantity !== null && abs((float) $line->counted_quantity - (float) $line->expected_quantity) >= 0.0005);
        $secondCountComplete = ! $requiresSecondCount || $differentLines->every(fn ($line) => $line->second_count_quantity !== null && (float) $line->second_count_quantity === (float) $line->counted_quantity);

        return view('stocks.counts.show', compact('count', 'canCount', 'canApprove', 'canSecondCount', 'requiresSecondCount', 'differentLines', 'secondCountComplete'));
    }

    public function printReceipt(StockCount $count): View
    {
        abort_unless($count->status === 'completed', 404);
        $count->load(['location:id,name,code', 'creator:id,name', 'counter:id,name', 'approver:id,name', 'lines.product:id,name', 'lines.lot:id,lot_code']);
        $columns = ['Produit', 'Lot', 'Stock attendu', '1er comptage', 'Recomptage', 'Écart validé'];
        $rows = $count->lines->map(function ($line) {
            $delta = (float) ($line->counted_quantity ?? 0) - (float) $line->expected_quantity;
            return [
                $line->product->name, $line->lot?->lot_code ?: 'Sans lot',
                number_format((float) $line->expected_quantity, 3, ',', ' '),
                $line->counted_quantity === null ? '—' : number_format((float) $line->counted_quantity, 3, ',', ' '),
                $line->second_count_quantity === null ? '—' : number_format((float) $line->second_count_quantity, 3, ',', ' '),
                ($delta > 0 ? '+' : '').number_format($delta, 3, ',', ' '),
            ];
        })->all();
        return view('stocks.operation-receipt', [
            'title' => $count->is_opening_balance ? 'Bon d’inventaire initial' : 'Bon de comptage physique',
            'subtitle' => 'Comptage terminé et validé', 'reference' => $count->count_number,
            'eventReference' => $count->completed_at?->format('d/m/Y à H:i'),
            'leftLabel' => 'Emplacement inventorié', 'leftName' => $count->location->name, 'leftInfo' => $count->location->code,
            'rightLabel' => 'Responsable du comptage', 'rightName' => $count->counter?->name ?? '—', 'rightInfo' => 'Créé par '.$count->creator?->name,
            'metadata' => ['Type d’inventaire' => $count->is_opening_balance ? 'Reprise initiale' : 'Comptage physique', 'Périmètre' => ucfirst(str_replace('_', ' ', $count->scope)), 'Validé par' => $count->approver?->name, 'Date de validation' => $count->approved_at?->format('d/m/Y à H:i')],
            'columns' => $columns, 'rows' => $rows, 'notes' => $count->notes,
            'leftSignature' => 'Compteur · '.$count->counter?->name, 'rightSignature' => 'Validateur · '.$count->approver?->name,
            'footnote' => 'Ce document présente les quantités relevées et les écarts validés dans le journal de stock.',
            'backUrl' => route('stock-control.counts.show', $count),
        ]);
    }

    public function submit(Request $request, StockCount $count): RedirectResponse
    {
        abort_unless((int) $count->counter_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $count, $data): void {
            $locked = StockCount::query()->lockForUpdate()->findOrFail($count->id);
            if ($locked->status !== 'in_progress') {
                throw ValidationException::withMessages(['count' => 'Cet inventaire a déjà été soumis. Recharge la page.']);
            }
            $lines = $locked->lines()->get()->keyBy('id');
            if (count($data['quantities']) !== $lines->count()) {
                throw ValidationException::withMessages(['quantities' => 'Renseigne la quantité de chaque produit de l’inventaire.']);
            }
            foreach ($data['quantities'] as $lineId => $quantity) {
                $line = $lines->get((int) $lineId);
                if (! $line) {
                    throw ValidationException::withMessages(['quantities' => 'Une ligne envoyée ne correspond pas à cet inventaire.']);
                }
                $line->counted_quantity = $quantity;
                $line->save();
            }
            $locked->status = 'submitted';
            $locked->submitted_at = now();
            $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '').($data['notes'] ?? '')) ?: null;
            $locked->save();
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.count_submitted', 'subject_type' => StockCount::class,
                'subject_id' => $locked->id, 'after_values' => ['line_count' => $lines->count()],
                'origin' => 'screen', 'result' => 'success',
            ]);
        });

        app(StockControlNotificationService::class)->toRoles(['ADMINUSER', 'MNG'], new StockControlNotification(
            'count_submitted',
            'Inventaire à vérifier',
            'Le comptage '.$count->count_number.' a été soumis pour contrôle indépendant.',
            route('stock-control.counts.show', $count),
            'fact_check',
            'counts',
        ), $request->user()->id);

        return back()->with('success', 'Comptage soumis pour vérification indépendante.');
    }

    public function approve(Request $request, StockCount $count, StockModuleSettingsService $settings): RedirectResponse
    {
        $rules = $settings->rules();
        if (! $rules['allow_self_approval'] && (int) $count->counter_id === (int) $request->user()->id) {
            throw ValidationException::withMessages(['count' => 'Le responsable du comptage ne peut pas approuver son propre inventaire.']);
        }

        DB::transaction(function () use ($request, $count, $rules): void {
            $locked = StockCount::query()->lockForUpdate()->with('lines')->findOrFail($count->id);
            if ($locked->status !== 'submitted' || $locked->lines->contains(fn ($line) => $line->counted_quantity === null)) {
                throw ValidationException::withMessages(['count' => 'Cet inventaire n’est pas prêt à être approuvé.']);
            }
            if ($rules['second_count_on_difference'] && $locked->lines->contains(fn ($line) => abs((float) $line->counted_quantity - (float) $line->expected_quantity) >= 0.0005
                && ($line->second_count_quantity === null || (float) $line->second_count_quantity !== (float) $line->counted_quantity))) {
                throw ValidationException::withMessages(['count' => 'Les écarts doivent être recomptés et confirmés avant l’approbation.']);
            }
            $adjustment = StockAdjustment::query()->create([
                'adjustment_number' => 'TMP-'.Str::uuid(), 'status' => 'posted',
                'reason_code' => $locked->is_opening_balance ? 'opening_balance' : 'inventory_variance',
                'reason' => $locked->is_opening_balance ? 'Reprise initiale des quantités par inventaire physique.' : 'Écart constaté lors de l’inventaire '.$locked->count_number,
                'count_id' => $locked->id, 'requested_by' => $locked->counter_id,
                'approved_by' => $request->user()->id, 'submitted_at' => $locked->submitted_at,
                'approved_at' => now(), 'posted_at' => now(),
            ]);
            $adjustment->update(['adjustment_number' => sprintf('ST-AJ-%s-%06d', now()->format('Y'), $adjustment->id)]);

            $movement = StockMovement::query()->create([
                'movement_number' => 'TMP-'.Str::uuid(), 'type' => $locked->is_opening_balance ? 'opening_balance' : 'inventory_adjustment',
                'status' => 'posted', 'source_type' => 'stock_count', 'source_reference' => $locked->count_number,
                'occurred_at' => now(), 'origin' => 'screen', 'reason' => $adjustment->reason,
                'created_by' => $locked->counter_id, 'approved_by' => $request->user()->id,
                'posted_by' => $request->user()->id, 'approved_at' => now(), 'posted_at' => now(),
            ]);
            $movement->update(['movement_number' => sprintf('ST-MVT-%s-%06d', now()->format('Y'), $movement->id)]);
            $adjustment->update(['movement_id' => $movement->id]);
            $hasDifferences = false;

            foreach ($locked->lines as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
                $before = (float) $line->expected_quantity;
                $counted = (float) $line->counted_quantity;
                $delta = $counted - $before;
                $line->update(['condition' => 'sellable']);
                if (abs($delta) < 0.0005) {
                    continue;
                }
                $hasDifferences = true;
                $movement->lines()->create([
                    'product_id' => $product->id, 'location_id' => $locked->location_id,
                    'quantity_delta' => $delta, 'unit_cost' => $product->price_market,
                    'unit' => 'piece', 'notes' => 'Écart validé par inventaire physique',
                ]);
                $adjustment->lines()->create([
                    'product_id' => $product->id, 'location_id' => $locked->location_id,
                    'quantity_before' => $before, 'quantity_counted' => $counted,
                    'quantity_delta' => $delta, 'unit_cost' => $product->price_market,
                ]);
                if (! $locked->is_opening_balance) {
                    $product->qt_initial = (int) $product->qt_initial + (int) $delta;
                    $product->save();
                }
            }

            if (! $hasDifferences) {
                $movement->delete();
                $adjustment->update(['movement_id' => null]);
            }
            $locked->status = 'completed';
            $locked->approved_at = now();
            $locked->completed_at = now();
            $locked->approved_by = $request->user()->id;
            $locked->reviewed_by = $request->user()->id;
            $locked->save();
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.count_approved', 'subject_type' => StockCount::class,
                'subject_id' => $locked->id, 'after_values' => ['adjustment_id' => $adjustment->id, 'movement_id' => $movement->exists ? $movement->id : null],
                'origin' => 'screen', 'result' => 'success',
            ]);
        });

        app(StockControlNotificationService::class)->toUsers([$count->created_by, $count->counter_id], new StockControlNotification(
            'count_approved',
            'Inventaire approuvé',
            'Le comptage '.$count->count_number.' a été approuvé et inscrit au journal.',
            route('stock-control.counts.show', $count),
            'task_alt',
            'counts',
        ), $request->user()->id);

        return back()->with('success', 'Inventaire approuvé et écarts enregistrés dans le journal de stock.');
    }

    public function secondCount(Request $request, StockCount $count): RedirectResponse
    {
        abort_unless((int) $count->counter_id !== (int) $request->user()->id, 403);
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:999999999'],
        ]);

        DB::transaction(function () use ($request, $count, $data): void {
            $locked = StockCount::query()->lockForUpdate()->with('lines')->findOrFail($count->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['count' => 'Le recomptage est disponible après la soumission du premier comptage.']);
            }
            $lines = $locked->lines->keyBy('id');
            foreach ($data['quantities'] as $lineId => $quantity) {
                $line = $lines->get((int) $lineId);
                if (! $line || abs((float) $line->counted_quantity - (float) $line->expected_quantity) < 0.0005) {
                    throw ValidationException::withMessages(['quantities' => 'Le recomptage doit porter uniquement sur les lignes présentant un écart.']);
                }
                $line->second_count_quantity = $quantity;
                $line->save();
            }
            $expectedIds = $lines->filter(fn ($line) => abs((float) $line->counted_quantity - (float) $line->expected_quantity) >= 0.0005)->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();
            $providedIds = collect(array_keys($data['quantities']))->map(fn ($id) => (string) $id)->sort()->values()->all();
            if ($expectedIds !== $providedIds) {
                throw ValidationException::withMessages(['quantities' => 'Recompte chaque produit présentant un écart.']);
            }
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'inventory.second_count', 'subject_type' => StockCount::class,
                'subject_id' => $locked->id, 'after_values' => ['line_ids' => $providedIds],
                'origin' => 'screen', 'result' => 'success',
            ]);
        });

        app(StockControlNotificationService::class)->toRoles(['ADMINUSER', 'MNG'], new StockControlNotification(
            'count_recounted',
            'Second comptage terminé',
            'Le second comptage du dossier '.$count->count_number.' est saisi et peut être contrôlé.',
            route('stock-control.counts.show', $count),
            'fact_check',
            'counts',
        ), $request->user()->id);

        return back()->with('success', 'Second comptage enregistré. Les écarts confirmés peuvent être approuvés.');
    }
}
