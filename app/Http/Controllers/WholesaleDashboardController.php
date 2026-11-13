<?php

namespace App\Http\Controllers;

use App\Models\Orders\Order;
use App\Models\FinanceCashMovement;
use App\Services\FinanceCashLedgerService;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WholesaleDashboardController extends Controller
{
    public function index(Request $request, StockModuleSettingsService $settings, FinanceCashLedgerService $ledger): View
    {
        $base = fn () => Order::query()
            ->where('is_wholesale', true)
            ->when(! $request->user()->isWholesaleShopResponsible(), fn ($query) => $query->where('created_user', $request->user()->id));
        $notCancelled = static fn ($query) => $query
            ->where('status', '!=', 'canceled')
            ->where(fn ($status) => $status->whereNull('partner_order_status')->orWhere('partner_order_status', '!=', 'canceled'));
        $delivered = static fn ($query) => $query
            ->where(fn ($status) => $status->where('status', 'delivered')->orWhere('partner_order_status', 'delivered'));

        $summary = [
            'orders' => $base()->count(),
            'in_progress' => $base()->where('status', 'ordered')
                ->where(fn ($status) => $status->whereNull('partner_order_status')->orWhereNotIn('partner_order_status', ['delivered', 'canceled']))
                ->count(),
            'delivered' => $delivered($base())->where($notCancelled)->count(),
            'canceled' => $base()->where(fn ($status) => $status->where('status', 'canceled')->orWhere('partner_order_status', 'canceled'))->count(),
            'revenue' => (int) $delivered($base())->where($notCancelled)->sum('subtotal'),
        ];

        $wholesaleLocationId = $request->user()->wholesaleResponsibilityLocationId()
            ?? ($settings->rules()['wholesale_sales_location_id'] ?? null);
        $cashSummary = [
            'remitted' => 0,
            'in_register' => 0,
        ];
        if ($wholesaleLocationId) {
            $cashSummary['remitted'] = (int) FinanceCashMovement::query()
                ->where('stock_location_id', $wholesaleLocationId)
                ->where('movement_type', 'remittance_handover')
                ->where('from_custody', 'shop')
                ->where('to_custody', 'transit')
                ->sum('amount');
            $cashSummary['in_register'] = $ledger->balance('shop', (int) $wholesaleLocationId);
        }

        $recentOrders = $base()->with('costumer')
            ->latest('date_order')->latest('id')->limit(8)->get();

        return view('wholesale.dashboard', compact('summary', 'cashSummary', 'recentOrders'));
    }
}
