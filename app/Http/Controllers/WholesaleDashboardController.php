<?php

namespace App\Http\Controllers;

use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WholesaleDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $base = fn () => Order::query()
            ->where('is_wholesale', true)
            ->where('created_user', $request->user()->id);
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

        $recentOrders = $base()->with('costumer')
            ->latest('date_order')->latest('id')->limit(8)->get();

        return view('wholesale.dashboard', compact('summary', 'recentOrders'));
    }
}
