<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use App\Models\StockAuditLog;
use App\Models\StockLot;
use App\Models\StockReceipt;
use App\Models\StockTransfer;
use App\Services\StockAlertService;
use App\Services\StockControlNotificationService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAlertController extends Controller
{
    public function index(Request $request): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'alerts');
        $status = $request->validate(['status' => ['nullable', 'in:open,resolved,all']])['status'] ?? 'open';
        $alerts = StockAlert::query()->with([
            'product:id,name', 'location:id,name,code', 'resolver:id,name',
            'subject' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    StockLot::class => ['product:id,name'],
                    StockReceipt::class => [
                        'location:id,name,code',
                        'events.receiver:id,name', 'events.approver:id,name',
                        'events.lines.product:id,name', 'events.lines.lot:id,lot_code', 'events.attachments',
                    ],
                    StockTransfer::class => ['source:id,name', 'destination:id,name'],
                ]);
            },
        ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
            ->latest('created_at')->paginate(20)->withQueryString();
        $openCount = StockAlert::query()->where('status', 'open')->count();

        return view('stocks.alerts.index', compact('alerts', 'status', 'openCount'));
    }

    public function refresh(StockAlertService $alerts): RedirectResponse
    {
        $before = StockAlert::query()->where('status', 'open')->count();
        $previousOpenKeys = StockAlert::query()->where('status', 'open')->pluck('deduplication_key')->all();
        $count = $alerts->refresh();
        $newAlerts = StockAlert::query()->where('status', 'open')
            ->whereIn('type', ['low_stock', 'expiry', 'transfer_overdue'])
            ->whereNotIn('deduplication_key', $previousOpenKeys ?: ['__none__'])
            ->get();
        foreach ($newAlerts as $alert) {
            app(StockControlNotificationService::class)->notifyAlert($alert);
        }
        StockAuditLog::query()->create([
            'user_id' => auth()->id(), 'actor_role' => auth()->user()?->user_type,
            'action' => 'stock.alerts_refreshed', 'subject_type' => StockAlert::class,
            'after_values' => ['open_before' => $before, 'active_detected' => $count],
            'origin' => 'screen', 'result' => 'success',
        ]);

        return back()->with('success', $count.' alerte(s) active(s) détectée(s).');
    }

    public function resolve(Request $request, StockAlert $alert): RedirectResponse
    {
        if ($alert->status === 'open') {
            $details = $alert->details ?? [];
            $details['condition_cleared'] = false;
            $alert->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => $request->user()->id, 'details' => $details]);
            StockAuditLog::query()->create([
                'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
                'action' => 'stock.alert_resolved', 'subject_type' => StockAlert::class, 'subject_id' => $alert->id,
                'after_values' => ['type' => $alert->type, 'title' => $alert->title],
                'origin' => 'screen', 'result' => 'success',
            ]);
        }

        return back()->with('success', 'Alerte marquée comme traitée.');
    }
}
