<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockLot;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class StockAlertService
{
    public function refresh(): int
    {
        $rules = app(StockModuleSettingsService::class)->rules();
        $activeKeys = [];

        Product::query()->whereNotNull('qts_seuil')->where('qts_seuil', '>', 0)
            ->whereColumn('qt_initial', '<=', 'qts_seuil')
            ->orderBy('id')->chunkById(300, function ($products) use (&$activeKeys): void {
                foreach ($products as $product) {
                    $key = 'low-stock:product:'.$product->id;
                    $activeKeys[] = $key;
                    $quantity = (int) $product->qt_initial;
                    $this->syncAlert($key, [
                        'type' => 'low_stock', 'severity' => $quantity <= 0 ? 'critical' : 'warning',
                        'product_id' => $product->id, 'title' => $quantity <= 0 ? 'Produit en rupture' : 'Stock bas',
                        'message' => $product->name.' : '.$quantity.' unité(s) disponible(s), seuil '.$product->qts_seuil.'.',
                        'details' => ['quantity' => $quantity, 'threshold' => (int) $product->qts_seuil],
                    ]);
                }
            });

        $cutoff = now()->addDays((int) $rules['expiry_alert_days'])->toDateString();
        $lots = StockLot::query()->with('product:id,name')->where('status', 'active')
            ->whereNotNull('expires_at')->whereDate('expires_at', '<=', $cutoff)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')->from('stock_movement_lines as sml')
                    ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                    ->whereColumn('sml.lot_id', 'stock_lots.id')->where('sm.status', 'posted')
                    ->groupBy('sml.lot_id')->havingRaw('SUM(sml.quantity_delta) > 0');
            })->orderBy('id')->get();

        foreach ($lots as $lot) {
            $quantity = (float) DB::table('stock_movement_lines as sml')->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                ->where('sm.status', 'posted')->where('sml.lot_id', $lot->id)->sum('sml.quantity_delta');
            if ($quantity <= 0) {
                continue;
            }
            $key = 'expiry:lot:'.$lot->id.':'.$lot->expires_at->format('Ymd');
            $activeKeys[] = $key;
            $expired = $lot->expires_at->lt(today());
            $this->syncAlert($key, [
                'type' => 'expiry', 'severity' => $expired ? 'critical' : 'warning',
                'subject_type' => StockLot::class, 'subject_id' => $lot->id, 'product_id' => $lot->product_id,
                'title' => $expired ? 'Lot expiré en stock' : 'Lot proche de l’expiration',
                'message' => ($lot->product?->name ?? 'Produit').' · lot '.($lot->lot_code ?: '#'.$lot->id).' · expiration '.$lot->expires_at->format('d/m/Y').'.',
                'details' => ['lot_code' => $lot->lot_code, 'quantity' => $quantity, 'expires_at' => $lot->expires_at->toDateString()],
                'due_at' => $lot->expires_at->endOfDay(),
            ]);
        }

        $transferDelay = (int) $rules['transfer_overdue_days'];
        $overdueTransfers = StockTransfer::query()->with(['source:id,name', 'destination:id,name'])
            ->whereIn('status', ['authorized', 'in_transit', 'partial'])
            ->where(function ($query) use ($transferDelay): void {
                $query->where('due_at', '<=', now())
                    ->orWhere(function ($fallback) use ($transferDelay): void {
                        $fallback->whereNull('due_at')->where('created_at', '<=', now()->subDays($transferDelay));
                    });
            })->get();
        foreach ($overdueTransfers as $transfer) {
            $key = 'transfer-overdue:'.$transfer->id;
            $activeKeys[] = $key;
            $this->syncAlert($key, [
                'type' => 'transfer_overdue', 'severity' => 'warning',
                'subject_type' => StockTransfer::class, 'subject_id' => $transfer->id,
                'location_id' => $transfer->source_location_id,
                'title' => 'Transfert en retard',
                'message' => $transfer->transfer_number.' · '.$transfer->source?->name.' vers '.$transfer->destination?->name.'.',
                'details' => ['transfer_number' => $transfer->transfer_number, 'status' => $transfer->status],
                'due_at' => $transfer->due_at ?? $transfer->created_at->copy()->addDays($transferDelay),
            ]);
        }

        $generated = StockAlert::query()->whereIn('status', ['open', 'resolved'])->whereIn('type', ['low_stock', 'expiry', 'transfer_overdue'])->get();
        foreach ($generated as $alert) {
            if (! in_array($alert->deduplication_key, $activeKeys, true)) {
                $details = $alert->details ?? [];
                $details['condition_cleared'] = true;
                $alert->update([
                    'status' => 'resolved', 'resolved_at' => $alert->resolved_at ?? now(),
                    'resolved_by' => $alert->resolved_by ?? auth()->id(), 'details' => $details,
                ]);
            }
        }

        return count($activeKeys);
    }

    private function syncAlert(string $key, array $attributes): void
    {
        $alert = StockAlert::query()->where('deduplication_key', $key)->first();
        $details = $attributes['details'] ?? [];
        $signature = sha1(json_encode($details));
        if ($alert && $alert->status === 'resolved') {
            $oldDetails = $alert->details ?? [];
            if (! ($oldDetails['condition_cleared'] ?? false) && ($oldDetails['condition_signature'] ?? null) === $signature) {
                return;
            }
            $attributes['resolved_at'] = null;
            $attributes['resolved_by'] = null;
        }
        $details['condition_signature'] = $signature;
        $details['condition_cleared'] = false;
        $attributes['details'] = $details;
        $attributes['status'] = 'open';
        $attributes['deduplication_key'] = $key;
        StockAlert::query()->updateOrCreate(['deduplication_key' => $key], $attributes);
    }
}
