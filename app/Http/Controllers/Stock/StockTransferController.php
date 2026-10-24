<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockAuditLog;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferEvent;
use App\Models\User;
use App\Notifications\StockControlNotification;
use App\Services\StockControlNotificationService;
use App\Services\StockModuleSettingsService;
use App\Services\StockLegacyWarehouseReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        app(StockControlNotificationService::class)->markTabAsRead($user, 'transfers');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'submitted', 'authorized', 'in_transit', 'partial', 'closed'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'all';

        $transfers = StockTransfer::query()
            ->with(['source:id,name,code', 'destination:id,name,code', 'requester:id,name', 'lines'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('transfer_number', 'like', '%'.$search.'%')
                    ->orWhere('reason', 'like', '%'.$search.'%')
                    ->orWhereHas('source', fn ($source) => $source->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('destination', fn ($destination) => $destination->where('name', 'like', '%'.$search.'%'));
            }))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $canCreate = true;
        return view('stocks.transfers.index', compact('transfers', 'search', 'status', 'canCreate'));
    }

    public function create(Request $request, StockLegacyWarehouseReconciliationService $legacyStock): View
    {
        $legacyStock->reconcileSingleWarehouse();
        $locations = StockLocation::query()->where('is_active', true)->whereIn('type', ['warehouse', 'shop'])->orderBy('name')->get(['id', 'name', 'code', 'type']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'price_market', 'qt_initial']);
        $locationQuantities = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')
            ->whereIn('sl.type', ['warehouse', 'shop'])
            ->select('sml.product_id', 'sml.location_id')
            ->selectRaw('SUM(sml.quantity_delta) as quantity')
            ->groupBy('sml.product_id', 'sml.location_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($balances) => $balances->mapWithKeys(fn ($balance) => [(string) $balance->location_id => (float) $balance->quantity])->all())
            ->all();
        $requestedProductId = $request->integer('product_id');
        $selectedProductId = $products->contains('id', $requestedProductId) ? $requestedProductId : null;
        $productReplenishment = $selectedProductId !== null || $request->boolean('replenishment');
        $sourceLocations = $productReplenishment ? $locations->where('type', 'warehouse')->values() : $locations;
        $destinationLocations = $productReplenishment ? $locations->where('type', 'shop')->values() : $locations;
        $defaultWarehouseId = $sourceLocations->count() === 1 ? $sourceLocations->first()->id : null;
        $defaultShopId = $destinationLocations->count() === 1 ? $destinationLocations->first()->id : null;

        return view('stocks.transfers.create', compact('locations', 'sourceLocations', 'destinationLocations', 'products', 'locationQuantities', 'selectedProductId', 'productReplenishment', 'defaultWarehouseId', 'defaultShopId'));
    }

    public function store(Request $request, StockLegacyWarehouseReconciliationService $legacyStock): RedirectResponse
    {
        $data = $request->validate([
            'source_location_id' => ['required', 'integer', 'exists:stock_locations,id', 'different:destination_location_id'],
            'destination_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'due_at' => ['nullable', 'date', 'after_or_equal:now'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'lines.*.quantity_requested' => ['required', 'integer', 'gt:0', 'max:999999999'],
        ]);

        $source = StockLocation::query()->findOrFail($data['source_location_id']);
        $destination = StockLocation::query()->findOrFail($data['destination_location_id']);
        if (! $source->is_active || ! $destination->is_active || ! in_array($source->type, ['warehouse', 'shop'], true) || ! in_array($destination->type, ['warehouse', 'shop'], true)) {
            throw ValidationException::withMessages(['source_location_id' => 'Choisis un entrepôt ou une boutique active comme départ et destination.']);
        }
        if ($source->type === 'warehouse') {
            $legacyStock->reconcileSingleWarehouse();
        }
        $isWarehouseToShop = $source->type === 'warehouse' && $destination->type === 'shop';
        $requiresAuthorization = $isWarehouseToShop && $request->user()->user_type !== 'ADMINUSER';
        $shipImmediately = $isWarehouseToShop && $request->user()->user_type === 'ADMINUSER';
        $transit = $shipImmediately
            ? StockLocation::query()->where('code', 'TRANSIT')->where('is_active', true)->first()
            : null;
        if ($shipImmediately && ! $transit) {
            throw ValidationException::withMessages(['transfer' => 'L’emplacement système TRANSIT doit être initialisé avant l’expédition.']);
        }

        $transfer = DB::transaction(function () use ($data, $request, $requiresAuthorization, $shipImmediately, $transit): StockTransfer {
            $transfer = StockTransfer::query()->create([
                'transfer_number' => 'TMP-'.Str::uuid(),
                'source_location_id' => $data['source_location_id'],
                'destination_location_id' => $data['destination_location_id'],
                'status' => $requiresAuthorization ? 'submitted' : 'authorized',
                'due_at' => $data['due_at'] ?? null,
                'requested_by' => $request->user()->id,
                'authorized_by' => $requiresAuthorization ? null : $request->user()->id,
                'submitted_at' => now(),
                'authorized_at' => $requiresAuthorization ? null : now(),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);
            $transfer->transfer_number = sprintf('ST-TRF-%s-%06d', now()->format('Y'), $transfer->id);
            $transfer->save();

            foreach ($data['lines'] as $line) {
                $transfer->lines()->create([
                    'product_id' => $line['product_id'],
                    'quantity_requested' => $line['quantity_requested'],
                ]);
            }

            if ($shipImmediately) {
                $transfer->load('lines');
                $this->postShipment($transfer, $request, now()->toDateTimeString(), $transfer->notes, $transit);
            }

            $action = $requiresAuthorization ? 'transfer.submitted' : ($shipImmediately ? 'transfer.admin_created_and_shipped' : 'transfer.auto_authorized');
            $this->audit($request, $action, $transfer, null, [
                'transfer_number' => $transfer->transfer_number,
                'status' => $transfer->status,
                'source_location_id' => $transfer->source_location_id,
                'destination_location_id' => $transfer->destination_location_id,
                'line_count' => count($data['lines']),
            ], $transfer->reason);

            return $transfer;
        });

        if ($requiresAuthorization) {
            app(StockControlNotificationService::class)->toRoles(['ADMINUSER'], new StockControlNotification(
                'transfer_submitted',
                'Transfert à autoriser',
                'La demande '.$transfer->transfer_number.' attend une autorisation.',
                route('stock-control.transfers.show', $transfer),
                'swap_horiz',
                'transfers',
            ), $request->user()->id);
        } elseif ($shipImmediately) {
            $this->notifyDestinationOfShipment($transfer, $request->user()->id);
        }

        return redirect()->route('stock-control.transfers.show', $transfer)->with('success', $requiresAuthorization
            ? 'Demande envoyée à un Admin pour autorisation. Le stock partira en transit après sa validation.'
            : ($shipImmediately
                ? 'Transfert expédié directement. Les quantités sont maintenant en transit vers la boutique.'
                : 'Transfert préparé. Tu peux enregistrer l’expédition.'));
    }

    public function show(StockTransfer $transfer): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'transfers');
        $transfer->load([
            'source:id,name,code,type,responsible_user_id', 'source.responsible:id,name',
            'destination:id,name,code,type,responsible_user_id', 'destination.responsible:id,name',
            'requester:id,name', 'authorizer:id,name', 'shipper:id,name', 'receiver:id,name',
            'lines.product:id,name,price_market', 'lines.lot:id,lot_code,expires_at',
            'events.user:id,name', 'events.lines.product:id,name', 'events.lines.lot:id,lot_code',
            'events.movement.lines.location:id,name,code,type', 'events.attachments',
        ]);
        $transit = StockLocation::query()->where('code', 'TRANSIT')->where('is_active', true)->first(['id', 'name', 'code']);
        $blocked = StockLocation::query()->where('code', 'BLOQUE')->where('is_active', true)->first(['id', 'name', 'code']);
        $canAuthorize = $transfer->status === 'submitted'
            && auth()->user()->user_type === 'ADMINUSER';
        $canShip = $transfer->status === 'authorized';
        $canReceive = in_array($transfer->status, ['in_transit', 'partial'], true);

        return view('stocks.transfers.show', compact('transfer', 'transit', 'blocked', 'canAuthorize', 'canShip', 'canReceive'));
    }

    public function shipmentReceipt(StockTransfer $transfer): View
    {
        $transfer->load([
            'source:id,name,code,responsible_user_id', 'source.responsible:id,name',
            'destination:id,name,code,responsible_user_id', 'destination.responsible:id,name',
            'requester:id,name', 'authorizer:id,name', 'shipper:id,name',
            'lines.product:id,name', 'lines.lot:id,lot_code',
            'events' => fn ($query) => $query->where('type', 'shipment')->with([
                'user:id,name', 'movement:id,movement_number', 'lines.product:id,name', 'lines.lot:id,lot_code',
            ])->orderByDesc('occurred_at'),
        ]);
        $shipment = $transfer->events->first();
        abort_unless($shipment, 404);

        return view('stocks.transfers.shipment-receipt', compact('transfer', 'shipment'));
    }

    public function eventReceipt(StockTransfer $transfer, StockTransferEvent $event): View
    {
        abort_unless((int) $event->transfer_id === (int) $transfer->id && in_array($event->type, ['shipment', 'receipt'], true), 404);
        $transfer->load(['source:id,name,code', 'destination:id,name,code', 'requester:id,name']);
        $event->load(['user:id,name', 'movement:id,movement_number', 'lines.product:id,name', 'lines.lot:id,lot_code']);
        $isShipment = $event->type === 'shipment';
        $columns = $isShipment ? ['Produit', 'Lot', 'Quantité expédiée'] : ['Produit', 'Lot', 'Quantité reçue', 'Abîmée', 'Disponible'];
        $rows = $event->lines->map(function ($line) use ($isShipment) {
            $row = [$line->product->name, $line->lot?->lot_code ?: 'Sans lot', number_format((float) $line->quantity, 3, ',', ' ')];
            if (! $isShipment) {
                $row[] = number_format((float) $line->quantity_damaged, 3, ',', ' ');
                $row[] = number_format((float) $line->quantity - (float) $line->quantity_damaged, 3, ',', ' ');
            }
            return $row;
        })->all();
        $metadata = [
            ($isShipment ? 'Date de départ' : 'Date d’arrivée') => $event->occurred_at?->format('d/m/Y à H:i'),
            ($isShipment ? 'Expédié par' : 'Réceptionné par') => $event->user?->name,
            'Référence du mouvement' => $event->movement?->movement_number,
            'Demandeur du transfert' => $transfer->requester?->name,
            'Motif du transfert' => $transfer->reason,
        ];
        $document = [
            'title' => $isShipment ? 'Bon d’expédition' : 'Bon de réception du transfert',
            'subtitle' => $isShipment ? 'Articles remis au transport vers le site destinataire' : 'Articles reçus sur le site destinataire',
            'reference' => $transfer->transfer_number, 'eventReference' => $event->event_number,
            'leftLabel' => 'Emplacement source', 'leftName' => $transfer->source->name, 'leftInfo' => $transfer->source->code,
            'rightLabel' => 'Destination', 'rightName' => $transfer->destination->name, 'rightInfo' => $transfer->destination->code,
            'metadata' => $metadata, 'columns' => $columns, 'rows' => $rows, 'notes' => $event->notes ?: $transfer->notes,
            'leftSignature' => $isShipment ? 'Remis au départ par' : 'Expédié par',
            'rightSignature' => $isShipment ? 'Reçu par le destinataire' : 'Réception confirmé par '.$event->user?->name,
            'footnote' => $isShipment ? 'Ce bon confirme le départ des articles et leur mise en transit. La réception à destination doit être enregistrée séparément.' : 'Ce bon confirme les quantités constatées à destination. Les quantités abîmées sont isolées et ne sont pas disponibles à la vente.',
            'backUrl' => route('stock-control.transfers.show', $transfer),
        ];

        return view('stocks.operation-receipt', $document);
    }

    public function authorizeTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        abort_unless($request->user()->user_type === 'ADMINUSER', 403);

        DB::transaction(function () use ($request, $transfer): void {
            $locked = StockTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['transfer' => 'Cette demande a déjà été traitée. Recharge la page.']);
            }
            $locked->update(['status' => 'authorized', 'authorized_by' => $request->user()->id, 'authorized_at' => now()]);
            $this->audit($request, 'transfer.authorized', $locked, ['status' => 'submitted'], ['status' => 'authorized'], null);
        });

        $notification = new StockControlNotification(
            'transfer_authorized',
            'Transfert prêt à expédier',
            'Le transfert '.$transfer->transfer_number.' a été autorisé et attend son expédition.',
            route('stock-control.transfers.show', $transfer),
            'local_shipping',
            'transfers',
        );
        app(StockControlNotificationService::class)->toUsers([(int) $transfer->requested_by], $notification, $request->user()->id);

        return back()->with('success', 'Transfert autorisé. Le stock sera déplacé au moment de l’expédition.');
    }

    public function ship(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $data = $request->validate([
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $transit = StockLocation::query()->where('code', 'TRANSIT')->where('is_active', true)->first();
        if (! $transit) {
            throw ValidationException::withMessages(['transfer' => 'L’emplacement système TRANSIT doit être initialisé avant l’expédition.']);
        }

        DB::transaction(function () use ($request, $transfer, $data, $transit): void {
            $locked = StockTransfer::query()->lockForUpdate()->with('lines')->findOrFail($transfer->id);
            if ($locked->status !== 'authorized') {
                throw ValidationException::withMessages(['transfer' => 'Seul un transfert autorisé peut être expédié.']);
            }
            $this->postShipment($locked, $request, $data['occurred_at'], $data['notes'] ?? null, $transit);
        });

        $this->notifyDestinationOfShipment($transfer, $request->user()->id);

        return back()->with('success', 'Expédition enregistrée. Les quantités sont maintenant en transit vers la destination.');
    }

    public function receive(Request $request, StockTransfer $transfer, StockModuleSettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.transfer_line_id' => ['required', 'integer', 'distinct', 'exists:stock_transfer_lines,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:0', 'max:999999999'],
            'lines.*.quantity_damaged' => ['required', 'integer', 'min:0', 'max:999999999'],
            'lines.*.notes' => ['nullable', 'string', 'max:1500'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $transferLines = $transfer->lines()->get()->keyBy('id');
        $normalized = [];
        $hasQty = false;
        foreach ($data['lines'] as $line) {
            $source = $transferLines->get((int) $line['transfer_line_id']);
            if (! $source) {
                throw ValidationException::withMessages(['lines' => 'Une ligne ne correspond pas à ce transfert.']);
            }
            if ((float) $line['quantity_damaged'] > (float) $line['quantity']) {
                throw ValidationException::withMessages(['lines' => 'La quantité abîmée ne peut pas dépasser la quantité reçue.']);
            }
            if ((float) $line['quantity'] > (float) $source->quantity_shipped - (float) $source->quantity_received) {
                throw ValidationException::withMessages(['lines' => 'La quantité reçue dépasse le reliquat expédié.']);
            }
            $line['source_line'] = $source;
            $hasQty = $hasQty || (int) $line['quantity'] > 0;
            $normalized[] = $line;
        }
        if (! $hasQty) {
            throw ValidationException::withMessages(['lines' => 'Saisis au moins une quantité reçue.']);
        }

        $hasDamage = collect($normalized)->contains(fn ($line) => (int) $line['quantity_damaged'] > 0);
        if ($hasDamage && $settings->rules()['loss_requires_attachment'] && ! $request->hasFile('attachments')) {
            throw ValidationException::withMessages(['attachments' => 'Ajoute une photo ou un justificatif pour documenter les articles abîmés.']);
        }

        $transit = StockLocation::query()->where('code', 'TRANSIT')->where('is_active', true)->first();
        $blocked = StockLocation::query()->where('code', 'BLOQUE')->where('is_active', true)->first();
        if (! $transit) {
            throw ValidationException::withMessages(['transfer' => 'L’emplacement système TRANSIT est introuvable.']);
        }
        if ($hasDamage && ! $blocked) {
            throw ValidationException::withMessages(['transfer' => 'L’emplacement système BLOQUÉ doit être initialisé pour isoler les articles abîmés.']);
        }

        $event = DB::transaction(function () use ($request, $transfer, $data, $normalized, $transit, $blocked, $hasDamage): StockTransferEvent {
            $locked = StockTransfer::query()->lockForUpdate()->with('lines')->findOrFail($transfer->id);
            if (! in_array($locked->status, ['in_transit', 'partial'], true)) {
                throw ValidationException::withMessages(['transfer' => 'Ce transfert n’attend pas de réception. Recharge la page.']);
            }
            $destinationLocation = StockLocation::query()->findOrFail($locked->destination_location_id);

            $event = $this->createTransferEvent($locked, $request, 'receipt', $data['occurred_at'], $data['notes'] ?? null);
            $movement = $this->createMovement($locked, $event, $request, 'transfer_receipt', 'Réception du transfert '.$locked->transfer_number);
            $receivedAny = false;

            foreach ($normalized as $lineData) {
                $line = $locked->lines->firstWhere('id', $lineData['transfer_line_id']);
                if (! $line) {
                    throw ValidationException::withMessages(['lines' => 'Une ligne du transfert est introuvable.']);
                }
                $quantity = (float) $lineData['quantity'];
                $damaged = (float) $lineData['quantity_damaged'];
                if ($quantity <= 0) {
                    continue;
                }
                $remaining = (float) $line->quantity_shipped - (float) $line->quantity_received;
                if ($quantity > $remaining) {
                    throw ValidationException::withMessages(['lines' => 'La quantité reçue a changé entre-temps. Recharge la page.']);
                }

                $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
                $this->movementLine($movement, $product, $transit->id, $line->lot_id, -$quantity, 'Sortie du transit');
                if ($quantity - $damaged > 0) {
                    $this->movementLine($movement, $product, $locked->destination_location_id, $line->lot_id, $quantity - $damaged, 'Entrée disponible à destination');
                    if ($destinationLocation->type === 'warehouse' && empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop'])) {
                        $product->qt_initial = (int) $product->qt_initial + (int) ($quantity - $damaged);
                        $product->save();
                    }
                }
                if ($damaged > 0) {
                    $this->movementLine($movement, $product, $blocked->id, $line->lot_id, $damaged, 'Article abîmé à l’arrivée');
                }

                $event->lines()->create([
                    'transfer_line_id' => $line->id, 'product_id' => $product->id, 'lot_id' => $line->lot_id,
                    'quantity' => $quantity, 'quantity_damaged' => $damaged, 'notes' => $lineData['notes'] ?? null,
                ]);
                $line->quantity_received = (float) $line->quantity_received + $quantity;
                $line->quantity_damaged = (float) $line->quantity_damaged + $damaged;
                $line->save();
                $receivedAny = true;
            }

            if (! $receivedAny) {
                throw ValidationException::withMessages(['lines' => 'Saisis une quantité strictement positive.']);
            }

            $fullyReceived = $locked->lines->every(fn ($line) => (float) $line->quantity_received >= (float) $line->quantity_shipped);
            $locked->received_by = $request->user()->id;
            $locked->received_at = $data['occurred_at'];
            $locked->status = $fullyReceived ? 'closed' : 'partial';
            $locked->closed_at = $fullyReceived ? now() : null;
            $locked->save();

            if ($hasDamage) {
                StockAlert::query()->create([
                    'type' => 'transfer_damage', 'severity' => 'warning', 'status' => 'open',
                    'deduplication_key' => 'transfer-damage:'.$locked->id.':'.$event->id,
                    'subject_type' => StockTransfer::class, 'subject_id' => $locked->id,
                    'location_id' => $locked->destination_location_id, 'assigned_to' => $locked->destination->responsible_user_id,
                    'title' => 'Articles abîmés lors d’un transfert',
                    'message' => 'Des articles ont été isolés en zone BLOQUÉ à la réception.',
                    'details' => ['transfer_number' => $locked->transfer_number, 'event_number' => $event->event_number],
                ]);
            }

            $this->audit($request, 'transfer.received', $locked, ['status' => 'in_transit'], [
                'status' => $locked->status, 'event_number' => $event->event_number,
                'movement_number' => $movement->movement_number, 'fully_received' => $fullyReceived,
            ], $data['notes'] ?? null);

            return $event;
        });

        $this->storeAttachments($request, $event);

        $damageAlert = StockAlert::query()
            ->where('deduplication_key', 'transfer-damage:'.$transfer->id.':'.$event->id)
            ->where('status', 'open')
            ->first();
        if ($damageAlert) {
            app(StockControlNotificationService::class)->notifyAlert($damageAlert);
        }

        app(StockControlNotificationService::class)->toUsers([$transfer->requested_by], new StockControlNotification(
            'transfer_received',
            $transfer->fresh()->status === 'closed' ? 'Transfert terminé' : 'Réception partielle du transfert',
            $transfer->fresh()->status === 'closed'
                ? 'Le transfert '.$transfer->transfer_number.' a été entièrement réceptionné.'
                : 'Une réception partielle a été enregistrée pour le transfert '.$transfer->transfer_number.'.',
            route('stock-control.transfers.show', $transfer),
            'inventory_2',
            'transfers',
        ), $request->user()->id);

        return back()->with('success', $transfer->fresh()->status === 'closed'
            ? 'Transfert entièrement réceptionné et clôturé.'
            : 'Réception partielle enregistrée. Le reliquat reste en transit.');
    }

    public function downloadAttachment(\App\Models\StockAttachment $attachment)
    {
        abort_unless(str_starts_with($attachment->subject_type, StockTransferEvent::class), 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    private function postShipment(StockTransfer $transfer, Request $request, string $occurredAt, ?string $notes, StockLocation $transit): StockTransferEvent
    {
        if ($transfer->status !== 'authorized') {
            throw ValidationException::withMessages(['transfer' => 'Seul un transfert autorisé peut être expédié.']);
        }

        $sourceLocation = StockLocation::query()->lockForUpdate()->findOrFail($transfer->source_location_id);
        $productModels = [];
        foreach ($transfer->lines as $line) {
            $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
            $balance = $this->locationBalance($product->id, $transfer->source_location_id);
            if ($balance < (float) $line->quantity_requested) {
                throw ValidationException::withMessages([
                    'transfer' => 'Le journal de « '.$sourceLocation->name.' » indique '.number_format($balance, 3, ',', ' ').' unité(s) pour '.$product->name.', mais '.number_format((float) $line->quantity_requested, 3, ',', ' ').' sont demandées. Le stock catalogue n’est pas automatiquement affecté à cet emplacement : vérifie la source ou enregistre les quantités physiques avec un inventaire initial.',
                ]);
            }
            if ($sourceLocation->type === 'warehouse'
                && empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop'])
                && (int) $product->qt_initial < (int) $line->quantity_requested) {
                throw ValidationException::withMessages(['transfer' => 'Le stock global historique est insuffisant pour '.$product->name.'. Rapproche le stock avant l’expédition.']);
            }
            $productModels[$product->id] = $product;
        }

        $event = $this->createTransferEvent($transfer, $request, 'shipment', $occurredAt, $notes);
        $movement = $this->createMovement($transfer, $event, $request, 'transfer_shipment', 'Expédition du transfert '.$transfer->transfer_number);

        foreach ($transfer->lines as $line) {
            $product = $productModels[$line->product_id];
            $quantity = (float) $line->quantity_requested;
            $this->movementLine($movement, $product, $transfer->source_location_id, $line->lot_id, -$quantity, 'Sortie vers transit');
            $this->movementLine($movement, $product, $transit->id, $line->lot_id, $quantity, 'Entrée en transit');
            $event->lines()->create([
                'transfer_line_id' => $line->id, 'product_id' => $product->id, 'lot_id' => $line->lot_id,
                'quantity' => $quantity, 'quantity_damaged' => 0,
            ]);
            $line->quantity_shipped = $quantity;
            $line->save();

            if ($sourceLocation->type === 'warehouse' && empty(app(StockModuleSettingsService::class)->rules()['legacy_catalogue_stock_migrated_to_shop'])) {
                $product->qt_initial = (int) $product->qt_initial - (int) $quantity;
                $product->save();
            }
        }

        $transfer->update(['status' => 'in_transit', 'shipped_by' => $request->user()->id, 'shipped_at' => $occurredAt]);
        $this->audit($request, 'transfer.shipped', $transfer, ['status' => 'authorized'], [
            'status' => 'in_transit', 'event_number' => $event->event_number, 'movement_number' => $movement->movement_number,
        ], $notes);

        return $event;
    }

    private function notifyDestinationOfShipment(StockTransfer $transfer, int $exceptUserId): void
    {
        $destinationManagerId = StockLocation::query()->whereKey($transfer->destination_location_id)->value('responsible_user_id');
        $recipientIds = [];
        if ($destinationManagerId) $recipientIds[] = (int) $destinationManagerId;
        $notification = new StockControlNotification(
            'transfer_shipped',
            'Transfert à réceptionner',
            'Le transfert '.$transfer->transfer_number.' est en route vers sa destination.',
            route('stock-control.transfers.show', $transfer),
            'local_shipping',
            'transfers',
        );
        if ($recipientIds) {
            app(StockControlNotificationService::class)->toUsers(array_values(array_unique($recipientIds)), $notification, $exceptUserId);
        } else {
            app(StockControlNotificationService::class)->toRoles(['ADMINUSER', 'MNG'], $notification, $exceptUserId);
        }
    }

    private function locationBalance(int $productId, int $locationId): float
    {
        return (float) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')
            ->where('sml.product_id', $productId)
            ->where('sml.location_id', $locationId)
            ->sum('sml.quantity_delta');
    }

    private function createTransferEvent(StockTransfer $transfer, Request $request, string $type, string $occurredAt, ?string $notes): StockTransferEvent
    {
        $event = $transfer->events()->create([
            'event_number' => 'TMP-'.Str::uuid(), 'type' => $type,
            'user_id' => $request->user()->id, 'occurred_at' => $occurredAt, 'notes' => $notes,
        ]);
        $event->event_number = sprintf('ST-TEV-%s-%06d-%06d', now()->format('Y'), $transfer->id, $event->id);
        $event->save();

        return $event;
    }

    private function createMovement(StockTransfer $transfer, StockTransferEvent $event, Request $request, string $type, string $reason): StockMovement
    {
        $movement = StockMovement::query()->create([
            'movement_number' => 'TMP-'.Str::uuid(), 'type' => $type, 'status' => 'posted',
            'transfer_id' => $transfer->id, 'source_type' => 'stock_transfer_event',
            'source_reference' => $event->event_number, 'occurred_at' => $event->occurred_at,
            'origin' => 'screen', 'reason' => $reason, 'notes' => $event->notes,
            'created_by' => $request->user()->id, 'approved_by' => $request->user()->id,
            'posted_by' => $request->user()->id, 'approved_at' => now(), 'posted_at' => now(),
        ]);
        $movement->movement_number = sprintf('ST-MVT-%s-%06d', now()->format('Y'), $movement->id);
        $movement->save();
        $event->update(['movement_id' => $movement->id]);

        return $movement;
    }

    private function movementLine(StockMovement $movement, Product $product, int $locationId, ?int $lotId, float $quantity, string $notes): void
    {
        $movement->lines()->create([
            'product_id' => $product->id, 'location_id' => $locationId, 'lot_id' => $lotId,
            'quantity_delta' => $quantity, 'unit_cost' => $product->price_market, 'unit' => 'piece', 'notes' => $notes,
        ]);
    }

    private function storeAttachments(Request $request, StockTransferEvent $event): void
    {
        foreach ($request->file('attachments', []) ?? [] as $file) {
            $path = $file->store('stock/transfers/'.$event->id, 'local');
            $event->attachments()->create([
                'uploaded_by' => $request->user()->id, 'path' => $path,
                'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
            ]);
        }
    }

    private function audit(Request $request, string $action, object $subject, ?array $before, ?array $after, ?string $reason): void
    {
        StockAuditLog::query()->create([
            'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
            'action' => $action, 'subject_type' => get_class($subject), 'subject_id' => $subject->getKey(),
            'before_values' => $before, 'after_values' => $after, 'reason' => $reason,
            'origin' => 'screen', 'result' => 'success',
        ]);
    }
}
