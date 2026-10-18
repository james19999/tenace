<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\EnterStock;
use App\Models\StockAlert;
use App\Models\StockAttachment;
use App\Models\StockAuditLog;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptEvent;
use App\Notifications\StockControlNotification;
use App\Services\StockControlNotificationService;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockReceiptController extends Controller
{
    public function index(): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'receipts');
        $receipts = StockReceipt::query()
            ->with(['location:id,name,code', 'creator:id,name'])
            ->withCount('lines')
            ->latest('expected_at')
            ->paginate(15);

        return view('stocks.receipts.index', compact('receipts'));
    }

    public function create(): View
    {
        $locations = StockLocation::query()
            ->where('is_active', true)
            ->where('type', 'warehouse')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        return view('stocks.receipts.create', [
            'locations' => $locations,
            'defaultLocationId' => $locations->count() === 1 ? $locations->first()->id : null,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'price_market']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'origin_name' => ['nullable', 'string', 'max:160'],
            'expected_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.lot_code' => ['nullable', 'string', 'max:100'],
            'lines.*.manufactured_at' => ['nullable', 'date'],
            'lines.*.expires_at' => ['nullable', 'date', 'after_or_equal:lines.*.manufactured_at'],
            'lines.*.quantity_expected' => ['required', 'integer', 'gt:0', 'max:999999999'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $location = StockLocation::query()->findOrFail($data['location_id']);
        if (! $location->is_active || $location->type !== 'warehouse') {
            throw ValidationException::withMessages(['location_id' => 'Une réception fournisseur doit être dirigée vers un entrepôt actif.']);
        }

        $receipt = DB::transaction(function () use ($data, $request): StockReceipt {
            $receipt = StockReceipt::query()->create([
                'receipt_number' => 'TMP-'.Str::uuid(),
                'location_id' => $data['location_id'],
                'status' => 'draft',
                'origin_name' => $data['origin_name'] ?? null,
                'created_by' => $request->user()->id,
                'occurred_at' => now(),
                'expected_at' => $data['expected_at'],
                'notes' => $data['notes'] ?? null,
            ]);
            $receipt->receipt_number = sprintf('ST-REC-%s-%06d', now()->format('Y'), $receipt->id);
            $receipt->save();

            foreach ($data['lines'] as $line) {
                if (! empty($line['manufactured_at']) && ! empty($line['expires_at']) && $line['expires_at'] < $line['manufactured_at']) {
                    throw ValidationException::withMessages(['lines' => 'La date d’expiration doit être postérieure à la date de fabrication.']);
                }
                $product = Product::query()->findOrFail($line['product_id']);
                $lot = null;
                if (! empty($line['lot_code'])) {
                    $lot = \App\Models\StockLot::query()->firstOrCreate(
                        ['product_id' => $product->id, 'lot_code' => trim($line['lot_code'])],
                        [
                            'manufactured_at' => $line['manufactured_at'] ?? null,
                            'expires_at' => $line['expires_at'] ?? null,
                            'status' => 'active',
                        ]
                    );
                }
                $receipt->lines()->create([
                    'product_id' => $product->id,
                    'lot_id' => $lot?->id,
                    'lot_code' => $line['lot_code'] ?? null,
                    'manufactured_at' => $line['manufactured_at'] ?? null,
                    'expires_at' => $line['expires_at'] ?? null,
                    'quantity_expected' => $line['quantity_expected'],
                    'quantity_received' => 0,
                    'quantity_damaged' => 0,
                    'unit_cost' => $line['unit_cost'] ?? $product->price_market,
                ]);
            }

            $this->audit($request, 'receipt.created', $receipt, null, [
                'receipt_number' => $receipt->receipt_number,
                'location_id' => $receipt->location_id,
                'line_count' => count($data['lines']),
            ], 'Réception créée');

            return $receipt;
        });

        $this->storeAttachments($request, $receipt);

        return redirect()->route('stock-control.receipts.show', $receipt)->with('success', 'Réception créée en brouillon. Aucun stock n’a été augmenté.');
    }

    public function show(StockReceipt $receipt): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'receipts');
        $receipt->load([
            'location:id,name,code,type,responsible_user_id',
            'location.responsible:id,name',
            'creator:id,name',
            'validator:id,name',
            'lines.product:id,name,price_market',
            'lines.lot:id,product_id,lot_code,expires_at',
            'events.receiver:id,name',
            'events.approver:id,name',
            'events.lines.product:id,name',
            'events.lines.lot:id,lot_code',
            'events.movement.lines.location:id,name,code,type',
            'events.attachments',
            'attachments',
        ]);

        $systemLocations = StockLocation::query()->whereIn('code', ['BLOQUE'])->where('is_active', true)->get(['id', 'code', 'name']);
        $rules = app(StockModuleSettingsService::class)->rules();

        return view('stocks.receipts.show', compact('receipt', 'systemLocations', 'rules'));
    }

    public function deliveryReceipt(StockReceipt $receipt, StockReceiptEvent $event): View
    {
        abort_unless((int) $event->receipt_id === (int) $receipt->id && $event->status === 'approved', 404);
        $receipt->load(['location:id,name,code', 'creator:id,name']);
        $event->load(['receiver:id,name', 'approver:id,name', 'movement:id,movement_number', 'lines.product:id,name', 'lines.lot:id,lot_code']);
        $columns = ['Produit', 'Lot', 'Quantité reçue', 'Abîmée'];
        $rows = $event->lines->map(fn ($line) => [
            $line->product->name, $line->lot?->lot_code ?: 'Sans lot',
            number_format((float) $line->quantity_received, 3, ',', ' '),
            number_format((float) $line->quantity_damaged, 3, ',', ' '),
        ])->all();
        $metadata = [
            'Date de réception' => $event->occurred_at?->format('d/m/Y à H:i'),
            'Saisie par' => $event->receiver?->name,
            'Validée par' => $event->approver?->name,
            'Référence fournisseur' => $receipt->source_reference,
            'Mouvement stock' => $event->movement?->movement_number,
            'Réception créée par' => $receipt->creator?->name,
        ];
        $document = [
            'title' => 'Bon de réception fournisseur', 'subtitle' => 'Entrée de marchandises après validation',
            'reference' => $receipt->receipt_number, 'eventReference' => $event->event_number,
            'leftLabel' => 'Fournisseur / origine', 'leftName' => $receipt->origin_name ?: 'Origine non précisée', 'leftInfo' => $receipt->source_reference,
            'rightLabel' => 'Emplacement de réception', 'rightName' => $receipt->location->name, 'rightInfo' => $receipt->location->code,
            'metadata' => $metadata, 'columns' => $columns, 'rows' => $rows, 'notes' => $event->notes ?: $receipt->notes,
            'leftSignature' => 'Remis par le fournisseur', 'rightSignature' => 'Réceptionné par '.$event->receiver?->name,
            'footnote' => 'Ce bon reprend les quantités validées. Les quantités abîmées sont isolées et ne sont pas disponibles à la vente.',
            'backUrl' => route('stock-control.receipts.show', $receipt),
        ];

        return view('stocks.operation-receipt', $document);
    }

    public function recordDelivery(Request $request, StockReceipt $receipt): RedirectResponse
    {
        if (! in_array($receipt->status, ['draft', 'partial'], true)) {
            throw ValidationException::withMessages(['receipt' => 'Cette réception ne peut pas recevoir de nouvelle livraison dans son état actuel.']);
        }

        $expectedAt = $receipt->expected_at ?? $receipt->occurred_at;
        if ($expectedAt && now()->lt($expectedAt)) {
            throw ValidationException::withMessages(['receipt' => 'La saisie des quantités sera disponible à la date prévue de réception.']);
        }

        if ($receipt->events()->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages(['receipt' => 'Une livraison attend déjà sa validation.']);
        }

        $data = $request->validate([
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.receipt_line_id' => ['required', 'integer', 'distinct', 'exists:stock_receipt_lines,id'],
            'lines.*.quantity_received' => ['required', 'integer', 'min:0', 'max:999999999'],
            'lines.*.quantity_damaged' => ['required', 'integer', 'min:0', 'max:999999999'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'lines.*.discrepancy_note' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $receiptLines = $receipt->lines()->get()->keyBy('id');
        $normalizedLines = [];
        $hasQuantity = false;
        foreach ($data['lines'] as $line) {
            $receiptLine = $receiptLines->get((int) $line['receipt_line_id']);
            if (! $receiptLine) {
                throw ValidationException::withMessages(['lines' => 'Une ligne ne correspond pas à cette réception.']);
            }
            if ((float) $line['quantity_damaged'] > (float) $line['quantity_received']) {
                throw ValidationException::withMessages(['lines' => 'La quantité abîmée ne peut pas dépasser la quantité physiquement reçue.']);
            }
            $line['receipt_line'] = $receiptLine;
            $hasQuantity = $hasQuantity || (float) $line['quantity_received'] > 0;
            $normalizedLines[] = $line;
        }

        if (! $hasQuantity) {
            throw ValidationException::withMessages(['lines' => 'Saisis au moins une quantité reçue.']);
        }

        $hasDamagedQuantity = collect($normalizedLines)->contains(fn ($line) => (float) $line['quantity_damaged'] > 0);
        if ($hasDamagedQuantity && app(StockModuleSettingsService::class)->rules()['loss_requires_attachment'] && ! $request->hasFile('attachments')) {
            throw ValidationException::withMessages(['attachments' => 'Ajoute une photo ou un justificatif pour documenter les produits abîmés.']);
        }

        $event = DB::transaction(function () use ($data, $normalizedLines, $request, $receipt): StockReceiptEvent {
            $lockedReceipt = StockReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if (! in_array($lockedReceipt->status, ['draft', 'partial'], true)
                || $lockedReceipt->events()->where('status', 'submitted')->exists()) {
                throw ValidationException::withMessages(['receipt' => 'Une livraison a déjà été soumise ou cette réception ne peut plus être modifiée. Recharge la page.']);
            }
            $event = $lockedReceipt->events()->create([
                'event_number' => 'TMP-'.Str::uuid(),
                'status' => 'submitted',
                'received_by' => $request->user()->id,
                'occurred_at' => $data['occurred_at'],
                'notes' => $data['notes'] ?? null,
            ]);
            $event->event_number = sprintf('ST-RE-%s-%06d-%06d', now()->format('Y'), $lockedReceipt->id, $event->id);
            $event->save();

            foreach ($normalizedLines as $line) {
                if ((float) $line['quantity_received'] <= 0) {
                    continue;
                }
                $receiptLine = $line['receipt_line'];
                $event->lines()->create([
                    'receipt_line_id' => $receiptLine->id,
                    'product_id' => $receiptLine->product_id,
                    'lot_id' => $receiptLine->lot_id,
                    'quantity_received' => $line['quantity_received'],
                    'quantity_damaged' => $line['quantity_damaged'],
                    'unit_cost' => $line['unit_cost'] ?? $receiptLine->unit_cost,
                    'discrepancy_note' => $line['discrepancy_note'] ?? null,
                ]);
            }

            $lockedReceipt->update(['status' => 'pending']);
            $this->audit($request, 'receipt.delivery_submitted', $event, null, [
                'event_number' => $event->event_number,
                'receipt_id' => $lockedReceipt->id,
                'line_count' => $event->lines()->count(),
            ], 'Livraison de réception soumise pour validation');

            return $event;
        });

        $this->storeAttachments($request, $event);

        app(StockControlNotificationService::class)->toRoles(['ADMINUSER', 'MNG'], new StockControlNotification(
            'receipt_delivery_submitted',
            'Réception à vérifier',
            'La livraison '.$event->event_number.' attend une validation.',
            route('stock-control.receipts.show', $receipt),
            'move_to_inbox',
            'receipts',
        ), $request->user()->id);

        return redirect()->route('stock-control.receipts.show', $receipt)->with('success', 'Livraison saisie et envoyée en validation. Le stock n’a pas encore changé.');
    }

    public function reschedule(Request $request, StockReceipt $receipt): RedirectResponse
    {
        if (! in_array($receipt->status, ['draft', 'partial'], true)) {
            throw ValidationException::withMessages(['expected_at' => 'La date ne peut plus être modifiée pour cette réception.']);
        }

        if ($receipt->events()->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages(['expected_at' => 'Une livraison attend sa validation. Termine cette validation avant de modifier la date prévue.']);
        }

        $data = $request->validate(['expected_at' => ['required', 'date']]);
        $previousDate = ($receipt->expected_at ?? $receipt->occurred_at)?->toDateTimeString();
        $receipt->expected_at = $data['expected_at'];
        $receipt->save();

        $this->audit($request, 'receipt.rescheduled', $receipt,
            ['expected_at' => $previousDate],
            ['expected_at' => $receipt->expected_at?->toDateTimeString()],
            'Date prévue de réception modifiée');

        return redirect()->route('stock-control.receipts.show', $receipt)->with('success', 'La date prévue de réception a été mise à jour.');
    }

    public function approveDelivery(Request $request, StockReceipt $receipt, StockReceiptEvent $event, StockModuleSettingsService $settings): RedirectResponse
    {
        if ((int) $event->receipt_id !== (int) $receipt->id || $event->status !== 'submitted') {
            throw ValidationException::withMessages(['event' => 'Cette livraison a déjà été traitée ou ne correspond pas à cette réception.']);
        }

        $rules = $settings->rules();
        if (! $rules['allow_self_approval'] && (int) $event->received_by === (int) $request->user()->id) {
            throw ValidationException::withMessages(['event' => 'La personne qui a saisi cette livraison ne peut pas l’approuver.']);
        }

        $blockedLocation = StockLocation::query()->where('code', 'BLOQUE')->where('is_active', true)->first();
        if ($event->lines()->where('quantity_damaged', '>', 0)->exists() && ! $blockedLocation) {
            throw ValidationException::withMessages(['event' => 'Crée d’abord l’emplacement système BLOQUÉ pour isoler les produits abîmés.']);
        }
        if ($rules['loss_requires_attachment'] && $event->lines()->where('quantity_damaged', '>', 0)->exists() && ! $event->attachments()->exists()) {
            throw ValidationException::withMessages(['attachments' => 'Ajoute une photo ou un justificatif avant d’approuver les produits endommagés.']);
        }

        DB::transaction(function () use ($request, $receipt, $event, $blockedLocation): void {
            $lockedEvent = StockReceiptEvent::query()->lockForUpdate()->with('lines')->findOrFail($event->id);
            $lockedReceipt = StockReceipt::query()->lockForUpdate()->with('lines')->findOrFail($receipt->id);
            if ($lockedEvent->status !== 'submitted' || (int) $lockedEvent->receipt_id !== (int) $lockedReceipt->id) {
                throw ValidationException::withMessages(['event' => 'Cette livraison a été traitée entre-temps. Recharge la page.']);
            }

            $movement = StockMovement::query()->create([
                'movement_number' => 'TMP-'.Str::uuid(),
                'type' => 'receipt',
                'status' => 'posted',
                'source_type' => 'stock_receipt_event',
                'source_reference' => $lockedEvent->event_number,
                'occurred_at' => $lockedEvent->occurred_at,
                'origin' => 'screen',
                'reason' => 'Réception '.$lockedReceipt->receipt_number,
                'notes' => $lockedEvent->notes,
                'created_by' => $lockedEvent->received_by,
                'approved_by' => $request->user()->id,
                'posted_by' => $request->user()->id,
                'approved_at' => now(),
                'posted_at' => now(),
            ]);
            $movement->movement_number = sprintf('ST-MVT-%s-%06d', now()->format('Y'), $movement->id);
            $movement->save();

            $discrepancies = [];
            foreach ($lockedEvent->lines as $eventLine) {
                $receiptLine = $lockedReceipt->lines->firstWhere('id', $eventLine->receipt_line_id);
                if (! $receiptLine) {
                    throw ValidationException::withMessages(['event' => 'La ligne de réception source est introuvable.']);
                }

                $damaged = (float) $eventLine->quantity_damaged;
                $sellable = (float) $eventLine->quantity_received - $damaged;
                if ($sellable > 0) {
                    $movement->lines()->create([
                        'product_id' => $eventLine->product_id,
                        'location_id' => $lockedReceipt->location_id,
                        'lot_id' => $eventLine->lot_id,
                        'quantity_delta' => $sellable,
                        'unit_cost' => $eventLine->unit_cost,
                        'unit' => 'piece',
                    ]);

                    // Keep the legacy field synchronized in the original single-warehouse mode.
                    $product = Product::query()->lockForUpdate()->findOrFail($eventLine->product_id);
                    if (empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop'])) {
                        $product->qt_initial = (int) $product->qt_initial + (int) $sellable;
                    }
                    $receivedCost = (int) round($sellable * (float) ($eventLine->unit_cost ?? 0));
                    $product->price_by = (int) $product->price_by + $receivedCost;
                    $product->save();

                    EnterStock::query()->create([
                        'product_id' => $product->id,
                        'qt_stock' => (int) $sellable,
                        'amount' => $receivedCost,
                        'user_id' => $lockedEvent->received_by,
                    ]);
                }
                if ($damaged > 0) {
                    $movement->lines()->create([
                        'product_id' => $eventLine->product_id,
                        'location_id' => $blockedLocation->id,
                        'lot_id' => $eventLine->lot_id,
                        'quantity_delta' => $damaged,
                        'unit_cost' => $eventLine->unit_cost,
                        'unit' => 'piece',
                        'notes' => 'Produit endommagé à la réception '.$lockedReceipt->receipt_number,
                    ]);
                    $discrepancies[] = $eventLine->product->name.' : '.$damaged.' abîmé(s)';
                }

                $receiptLine->quantity_received = (float) $receiptLine->quantity_received + (float) $eventLine->quantity_received;
                $receiptLine->quantity_damaged = (float) $receiptLine->quantity_damaged + $damaged;
                $receiptLine->save();

                $expected = (float) $receiptLine->quantity_expected;
                $receivedTotal = (float) $receiptLine->quantity_received;
                if (abs($receivedTotal - $expected) > 0.0005) {
                    $discrepancies[] = $eventLine->product->name.' : attendu '.$expected.', reçu cumulé '.$receivedTotal;
                }
            }

            $lockedEvent->update([
                'status' => 'approved',
                'movement_id' => $movement->id,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $lockedReceipt->movement_id = $movement->id;
            $allReceived = $lockedReceipt->lines->every(fn ($line) => (float) $line->quantity_received >= (float) $line->quantity_expected);
            $lockedReceipt->status = $allReceived ? 'validated' : 'partial';
            $lockedReceipt->validated_by = $request->user()->id;
            $lockedReceipt->validated_at = $allReceived ? now() : null;
            $lockedReceipt->save();

            if ($discrepancies) {
                $alert = StockAlert::query()->create([
                    'type' => 'receipt_difference',
                    'severity' => 'warning',
                    'status' => 'open',
                    'deduplication_key' => 'receipt-difference:'.$lockedReceipt->id.':'.$lockedEvent->id,
                    'subject_type' => StockReceipt::class,
                    'subject_id' => $lockedReceipt->id,
                    'location_id' => $lockedReceipt->location_id,
                    'assigned_to' => $lockedReceipt->location->responsible_user_id,
                    'title' => 'Écart de réception à examiner',
                    'message' => implode(' · ', array_unique($discrepancies)),
                    'details' => ['receipt_number' => $lockedReceipt->receipt_number, 'event_number' => $lockedEvent->event_number],
                ]);
                $this->audit($request, 'receipt.difference_detected', $alert, null, $alert->only(['type', 'severity', 'message']), 'Écart détecté lors de la réception');
            }

            $this->audit($request, 'receipt.delivery_approved', $lockedEvent, ['status' => 'submitted'], [
                'status' => 'approved', 'movement_id' => $movement->id, 'receipt_status' => $lockedReceipt->status,
            ], 'Livraison de réception approuvée et mouvement enregistré');
        });

        $differenceAlert = StockAlert::query()
            ->where('deduplication_key', 'receipt-difference:'.$receipt->id.':'.$event->id)
            ->where('status', 'open')
            ->first();
        if ($differenceAlert) {
            app(StockControlNotificationService::class)->notifyAlert($differenceAlert);
        }

        app(StockControlNotificationService::class)->toUsers([$event->received_by, $receipt->created_by], new StockControlNotification(
            'receipt_delivery_approved',
            'Réception validée',
            'La livraison de la réception '.$receipt->receipt_number.' a été validée et inscrite au stock.',
            route('stock-control.receipts.show', $receipt),
            'task_alt',
            'receipts',
        ), $request->user()->id);

        return redirect()->route('stock-control.receipts.show', $receipt)->with('success', 'Livraison approuvée. Le stock a été augmenté et les articles abîmés sont isolés.');
    }

    public function downloadAttachment(StockAttachment $attachment)
    {
        abort_unless(str_starts_with($attachment->subject_type, 'App\\Models\\Stock'), 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function previewImage(StockAttachment $attachment)
    {
        abort_unless(str_starts_with($attachment->subject_type, 'App\\Models\\Stock'), 404);
        abort_unless(in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return response()->file(Storage::disk('local')->path($attachment->path), [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function storeAttachments(Request $request, StockReceipt|StockReceiptEvent $subject): void
    {
        foreach ($request->file('attachments', []) ?? [] as $file) {
            $path = $file->store('stock/receipts/'.$subject->getKey(), 'local');
            $subject->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function audit(Request $request, string $action, object $subject, ?array $before, ?array $after, ?string $reason): void
    {
        StockAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'actor_role' => $request->user()->user_type,
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'before_values' => $before,
            'after_values' => $after,
            'reason' => $reason,
            'origin' => 'screen',
            'result' => 'success',
        ]);
    }
}
