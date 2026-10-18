<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAuditLog;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Notifications\StockControlNotification;
use App\Services\StockControlNotificationService;
use App\Services\StockModuleSettingsService;
use App\Services\StockLocationApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'adjustments');
        $adjustments = StockAdjustment::query()
            ->with(['requester:id,name', 'approver:id,name', 'lines.product:id,name', 'lines.location:id,name,code'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('stocks.adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        return view('stocks.adjustments.create', [
            'locations' => StockLocation::query()->where('is_active', true)->whereIn('type', ['warehouse', 'shop'])->orderBy('name')->get(['id', 'name', 'code']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'qt_initial', 'price_market']),
        ]);
    }

    public function store(Request $request, StockModuleSettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity_delta' => ['required', 'integer', 'not_in:0', 'between:-999999999,999999999'],
            'reason_code' => ['required', 'in:found,loss,damage,correction,other'],
            'reason' => ['required', 'string', 'min:5', 'max:3000'],
        ]);
        $location = StockLocation::query()->findOrFail($data['location_id']);
        if (! $location->is_active || ! in_array($location->type, ['warehouse', 'shop'], true)) {
            throw ValidationException::withMessages(['location_id' => 'Choisis un entrepôt ou une boutique active.']);
        }

        $rules = $settings->rules();
        $quantity = (int) $data['quantity_delta'];
        $product = Product::query()->findOrFail($data['product_id']);
        $warehouseBalance = $this->warehouseBalance((int) $product->id);
        if ($location->type === 'warehouse' && empty($rules['legacy_catalogue_stock_migrated_to_shop'])
            && abs((float) $product->qt_initial - $warehouseBalance) >= 0.0005) {
            throw ValidationException::withMessages([
                'product_id' => 'Le stock historique de ce produit n’est pas encore réparti dans les emplacements. Enregistre d’abord un inventaire initial pour affecter les quantités réelles, puis crée cet ajustement.',
            ]);
        }
        $approvalRequired = false;

        $adjustment = DB::transaction(function () use ($request, $data, $quantity, $approvalRequired, $rules, $location): StockAdjustment {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $before = $this->balance((int) $data['location_id'], (int) $product->id, true);
            $after = $before + $quantity;
            if ($after < 0 && $rules['prevent_negative_stock']) {
                throw ValidationException::withMessages(['quantity_delta' => "Stock insuffisant sur cet emplacement (disponible : {$before})."]);
            }
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity_delta' => 'La quantité après correction ne peut pas être négative.']);
            }
            if ($location->type === 'warehouse' && empty($rules['legacy_catalogue_stock_migrated_to_shop'])
                && (int) $product->qt_initial + $quantity < 0) {
                throw ValidationException::withMessages(['quantity_delta' => 'Cette correction rendrait le stock global négatif. Vérifie les quantités avant de continuer.']);
            }

            $adjustment = StockAdjustment::query()->create([
                'adjustment_number' => 'TMP-'.Str::uuid(),
                'status' => 'posted',
                'reason_code' => $data['reason_code'], 'reason' => trim($data['reason']),
                'requested_by' => $request->user()->id,
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'posted_at' => now(),
            ]);
            $adjustment->update(['adjustment_number' => sprintf('ST-AJ-%s-%06d', now()->format('Y'), $adjustment->id)]);
            $adjustment->lines()->create([
                'product_id' => $product->id, 'location_id' => $data['location_id'],
                'quantity_before' => $before, 'quantity_counted' => $after,
                'quantity_delta' => $quantity, 'unit_cost' => $product->price_market,
            ]);

            if (! $approvalRequired) {
                $this->postAdjustment($adjustment, $product, $quantity, $request->user()->id);
            }
            $this->audit($request, $adjustment, 'stock.adjustment_applied', [
                'status' => $adjustment->status, 'adjustment_number' => $adjustment->adjustment_number,
                'product_id' => $product->id, 'product_name' => $product->name,
                'location_id' => $data['location_id'], 'location_name' => $location->name,
                'quantity_before' => $before, 'quantity_delta' => $quantity, 'quantity_after' => $after,
                'reason_code' => $data['reason_code'], 'movement_id' => $adjustment->movement_id,
                'approval_required' => $approvalRequired,
            ], ['status' => 'new', 'product_quantity' => $before]);

            return $adjustment;
        });

        return redirect()->route('stock-control.adjustments.show', $adjustment)
            ->with('success', 'Ajustement enregistré et appliqué au stock.');
    }

    public function show(StockAdjustment $adjustment): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'adjustments');
        $adjustment->load(['requester:id,name', 'approver:id,name', 'movement', 'count:id,count_number', 'lines.product:id,name,price_market,qt_initial', 'lines.location:id,name,code,responsible_user_id']);
        $auditLogs = StockAuditLog::query()->with('user:id,name')
            ->where('subject_type', StockAdjustment::class)->where('subject_id', $adjustment->id)
            ->orderBy('created_at')->orderBy('id')->get();
        $line = $adjustment->lines->first();
        $locatedQuantity = $line ? $this->warehouseBalance((int) $line->product_id) : 0;
        $catalogueIsShop = ! empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop']);
        $globalQuantity = $catalogueIsShop ? (int) $locatedQuantity : (int) ($line?->product?->qt_initial ?? 0);
        $unallocatedQuantity = $globalQuantity - $locatedQuantity;
        $approvalBlockedByReconciliation = $line?->location?->type === 'warehouse'
            && ! $catalogueIsShop
            && abs((float) $unallocatedQuantity) >= 0.0005;
        $canApprove = $adjustment->status === 'submitted'
            && $line !== null
            && app(StockLocationApprovalService::class)->canApprove(auth()->user(), $line->location, $adjustment->requested_by)
            && ! $approvalBlockedByReconciliation;
        $approvalMessage = $line
            ? app(StockLocationApprovalService::class)->waitingMessage($line->location, $adjustment->requested_by)
            : 'Cette demande ne contient aucune ligne à traiter.';

        return view('stocks.adjustments.show', compact('adjustment', 'canApprove', 'approvalMessage', 'globalQuantity', 'locatedQuantity', 'unallocatedQuantity', 'approvalBlockedByReconciliation', 'auditLogs'));
    }

    public function printReceipt(StockAdjustment $adjustment): View
    {
        abort_unless($adjustment->status === 'posted', 404);
        $adjustment->load(['requester:id,name', 'approver:id,name', 'movement:id,movement_number', 'lines.product:id,name', 'lines.location:id,name,code', 'lines.lot:id,lot_code']);
        $columns = ['Produit', 'Emplacement', 'Lot', 'Avant', 'Variation', 'Après'];
        $rows = $adjustment->lines->map(fn ($line) => [
            $line->product->name, $line->location->name, $line->lot?->lot_code ?: 'Sans lot',
            number_format((float) $line->quantity_before, 3, ',', ' '),
            ((float) $line->quantity_delta > 0 ? '+' : '').number_format((float) $line->quantity_delta, 3, ',', ' '),
            number_format((float) $line->quantity_counted, 3, ',', ' '),
        ])->all();
        return view('stocks.operation-receipt', [
            'title' => 'Bon de correction de stock', 'subtitle' => 'Ajustement appliqué au stock',
            'reference' => $adjustment->adjustment_number, 'eventReference' => $adjustment->movement?->movement_number,
            'leftLabel' => 'Demandé par', 'leftName' => $adjustment->requester?->name ?? '—', 'leftInfo' => $adjustment->submitted_at?->format('d/m/Y à H:i'),
            'rightLabel' => 'Emplacement concerné', 'rightName' => $adjustment->lines->first()?->location?->name ?? '—', 'rightInfo' => $adjustment->lines->first()?->location?->code,
            'metadata' => ['Motif' => ucfirst(str_replace('_', ' ', $adjustment->reason_code)), 'Approbation' => $adjustment->approved_at ? 'Validé par '.$adjustment->approver?->name : 'Non requise', 'Mouvement stock' => $adjustment->movement?->movement_number],
            'columns' => $columns, 'rows' => $rows, 'notes' => $adjustment->reason,
            'leftSignature' => 'Enregistré par · '.$adjustment->requester?->name, 'rightSignature' => 'Correction inscrite au journal',
            'footnote' => 'Ce bon atteste une correction inscrite dans le journal des mouvements de stock.',
            'backUrl' => route('stock-control.adjustments.show', $adjustment),
        ]);
    }

    public function approve(Request $request, StockAdjustment $adjustment, StockLocationApprovalService $approvals): RedirectResponse
    {
        DB::transaction(function () use ($request, $adjustment, $approvals): void {
            $locked = StockAdjustment::query()->lockForUpdate()->with('lines')->findOrFail($adjustment->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['adjustment' => 'Cette demande n’est plus en attente. Recharge la page.']);
            }
            $line = $locked->lines->first();
            if (! $line) {
                throw ValidationException::withMessages(['adjustment' => 'Cette demande ne contient aucune ligne à valider.']);
            }
            $approvals->assertCanApprove($request->user(), StockLocation::query()->findOrFail($line->location_id), $locked->requested_by);
            $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
            $location = StockLocation::query()->findOrFail($line->location_id);
            $warehouseBalance = $this->warehouseBalance((int) $product->id);
            $catalogueIsShop = ! empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop']);
            if ($location->type === 'warehouse' && ! $catalogueIsShop
                && abs((float) $product->qt_initial - $warehouseBalance) >= 0.0005) {
                throw ValidationException::withMessages(['adjustment' => 'Le stock historique de ce produit n’est pas encore entièrement réparti dans les emplacements. Cette demande ne peut pas être approuvée. Enregistre un inventaire initial, puis crée une nouvelle demande.']);
            }
            $current = $this->balance((int) $line->location_id, (int) $product->id, true);
            if ((float) $current !== (float) $line->quantity_before) {
                throw ValidationException::withMessages(['adjustment' => 'Le stock de cet emplacement a changé depuis la demande. Refuse cette demande et crée une correction à jour.']);
            }
            if ($current + (float) $line->quantity_delta < 0) {
                throw ValidationException::withMessages(['adjustment' => 'Cette correction rendrait le stock négatif.']);
            }
            if ($location->type === 'warehouse' && ! $catalogueIsShop
                && (int) $product->qt_initial + (int) $line->quantity_delta < 0) {
                throw ValidationException::withMessages(['adjustment' => 'Cette correction rendrait le stock global négatif.']);
            }
            $locked->update(['status' => 'posted', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'posted_at' => now()]);
            $this->postAdjustment($locked, $product, (int) $line->quantity_delta, $request->user()->id);
            $this->audit($request, $locked, 'stock.adjustment_approved', [
                'status' => 'posted', 'movement_id' => $locked->movement_id,
                'quantity_delta' => (int) $line->quantity_delta,
                'quantity_before' => (float) $line->quantity_before,
                'quantity_after' => (float) $line->quantity_counted,
            ], ['status' => 'submitted']);
        });

        $adjustment->loadMissing('requester');
        app(StockControlNotificationService::class)->toUsers([$adjustment->requested_by], new StockControlNotification(
            'adjustment_approved',
            'Ajustement approuvé',
            'La demande '.$adjustment->adjustment_number.' a été approuvée et appliquée.',
            route('stock-control.adjustments.show', $adjustment),
            'task_alt',
            'adjustments',
        ), $request->user()->id);

        return back()->with('success', 'Correction approuvée et appliquée au stock.');
    }

    public function reject(Request $request, StockAdjustment $adjustment, StockLocationApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:3', 'max:2000']]);
        DB::transaction(function () use ($request, $adjustment, $data, $approvals): void {
            $locked = StockAdjustment::query()->lockForUpdate()->with('lines')->findOrFail($adjustment->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['adjustment' => 'Cette demande n’est plus en attente.']);
            }
            $line = $locked->lines->first();
            if (! $line) {
                throw ValidationException::withMessages(['adjustment' => 'Cette demande ne contient aucune ligne à traiter.']);
            }
            $approvals->assertCanApprove($request->user(), StockLocation::query()->findOrFail($line->location_id), $locked->requested_by);
            $locked->update(['status' => 'rejected', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'reason' => $locked->reason."\n\nRefus : ".trim($data['rejection_reason'])]);
            $this->audit($request, $locked, 'stock.adjustment_rejected', [
                'status' => 'rejected', 'rejection_reason' => trim($data['rejection_reason']),
            ], ['status' => 'submitted']);
        });

        app(StockControlNotificationService::class)->toUsers([$adjustment->requested_by], new StockControlNotification(
            'adjustment_rejected',
            'Ajustement refusé',
            'La demande '.$adjustment->adjustment_number.' a été refusée. Consulte son motif.',
            route('stock-control.adjustments.show', $adjustment),
            'block',
            'adjustments',
        ), $request->user()->id);

        return back()->with('success', 'Demande refusée. Le stock n’a pas été modifié.');
    }

    private function postAdjustment(StockAdjustment $adjustment, Product $product, int $quantity, int $userId): void
    {
        $line = $adjustment->lines()->firstOrFail();
        $movement = StockMovement::query()->create([
            'movement_number' => 'TMP-'.Str::uuid(), 'type' => 'manual_adjustment', 'status' => 'posted',
            'source_type' => 'stock_adjustment', 'source_reference' => $adjustment->adjustment_number,
            'occurred_at' => now(), 'origin' => 'screen', 'reason' => $adjustment->reason,
            'created_by' => $adjustment->requested_by, 'approved_by' => null,
            'posted_by' => $userId, 'approved_at' => null, 'posted_at' => now(),
        ]);
        $movement->update(['movement_number' => sprintf('ST-MVT-%s-%06d', now()->format('Y'), $movement->id)]);
        $movement->lines()->create([
            'product_id' => $product->id, 'location_id' => $line->location_id,
            'quantity_delta' => $quantity, 'unit_cost' => $product->price_market,
            'unit' => 'piece', 'notes' => $adjustment->reason,
        ]);
        $location = StockLocation::query()->findOrFail($line->location_id);
        if ($location->type === 'warehouse' && empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop'])) {
            $product->qt_initial = max(0, (int) $product->qt_initial + $quantity);
            $product->save();
        }
        $adjustment->update(['movement_id' => $movement->id]);
    }

    private function balance(int $locationId, int $productId, bool $lock = false): float
    {
        $query = DB::table('stock_movement_lines as sml')->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sml.location_id', $locationId)->where('sml.product_id', $productId);
        if ($lock) {
            DB::table('stock_locations')->where('id', $locationId)->lockForUpdate()->first();
        }

        return (float) ($query->sum('sml.quantity_delta') ?? 0);
    }

    private function sellableLocatedBalance(int $productId): float
    {
        return (float) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')->where('sml.product_id', $productId)
            ->whereNotIn('sl.type', ['blocked', 'transit'])
            ->sum('sml.quantity_delta');
    }

    private function warehouseBalance(int $productId): float
    {
        return (float) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')->where('sml.product_id', $productId)
            ->where('sl.type', 'warehouse')->sum('sml.quantity_delta');
    }

    private function audit(Request $request, StockAdjustment $adjustment, string $action, array $after, ?array $before = null): void
    {
        StockAuditLog::query()->create([
            'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
            'action' => $action, 'subject_type' => StockAdjustment::class, 'subject_id' => $adjustment->id,
            'before_values' => $before, 'after_values' => $after, 'reason' => $adjustment->reason,
            'origin' => 'screen', 'result' => 'success',
        ]);
    }
}
