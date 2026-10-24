<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\Orders\Order;
use App\Services\StockModuleSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WholesaleCustomerMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'products' ? 'products' : 'customers';
        $search = trim((string) $request->query('search', ''));
        $productSearch = trim((string) $request->query('product_search', ''));
        $wholesaleOrders = fn ($query) => $query->where('is_wholesale', true);
        $customers = Costumer::query()
            ->whereHas('orders', $wholesaleOrders)
            ->when($search !== '', fn ($query) => $query->where(function ($customerQuery) use ($search) {
                $customerQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->withCount(['orders as wholesale_orders_count' => $wholesaleOrders])
            ->withSum(['orders as wholesale_revenue' => $wholesaleOrders], 'subtotal')
            ->withMax(['orders as last_wholesale_order_at' => $wholesaleOrders], 'date_order')
            ->orderByDesc('last_wholesale_order_at')
            ->paginate(15)->withQueryString();

        $stats = Order::query()->where('is_wholesale', true)
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COUNT(DISTINCT costumer_id) as customers_count')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as revenue')
            ->selectRaw("SUM(CASE WHEN status = 'ordered' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered")
            ->selectRaw("SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled")
            ->first();

        $rules = app(StockModuleSettingsService::class)->rules();
        $wholesaleShop = ! empty($rules['wholesale_sales_location_id'])
            ? StockLocation::query()->whereKey($rules['wholesale_sales_location_id'])->first()
            : null;
        $since = now()->subDays(30)->startOfDay();

        $movementEvolution = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')
            ->where('sml.location_id', $wholesaleShop?->id ?? 0)
            ->select('sml.product_id')
            ->selectRaw('COALESCE(SUM(sml.quantity_delta), 0) as current_quantity')
            ->selectRaw("COALESCE(SUM(CASE WHEN sm.occurred_at >= ? AND sm.type IN ('receipt', 'transfer_receipt') THEN sml.quantity_delta ELSE 0 END), 0) as received_30d", [$since])
            ->selectRaw("COALESCE(SUM(CASE WHEN sm.occurred_at >= ? AND sm.type IN ('sale', 'transfer_shipment') THEN -sml.quantity_delta ELSE 0 END), 0) as outflow_30d", [$since])
            ->selectRaw('COALESCE(SUM(CASE WHEN sm.occurred_at >= ? THEN sml.quantity_delta ELSE 0 END), 0) as net_change_30d', [$since])
            ->groupBy('sml.product_id');

        $products = Product::query()
            ->joinSub($movementEvolution, 'evolution', 'evolution.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', 'products.img')
            ->addSelect('evolution.current_quantity', 'evolution.received_30d', 'evolution.outflow_30d', 'evolution.net_change_30d')
            ->when($productSearch !== '', fn ($query) => $query->where('products.name', 'like', '%'.$productSearch.'%'))
            ->orderBy('products.name')
            ->paginate(15, ['*'], 'productsPage')->withQueryString();

        $productStats = (clone $movementEvolution)->reorder();
        $productStats = DB::query()->fromSub($productStats, 'product_evolution')
            ->selectRaw('COUNT(*) as products_count')
            ->selectRaw('COALESCE(SUM(current_quantity), 0) as current_stock')
            ->selectRaw('COALESCE(SUM(received_30d), 0) as received_30d')
            ->selectRaw('COALESCE(SUM(outflow_30d), 0) as outflow_30d')
            ->first();

        return view('wholesale-monitoring.index', compact(
            'customers', 'search', 'stats', 'tab', 'productSearch', 'products', 'productStats', 'wholesaleShop'
        ));
    }

    public function show(Costumer $customer): View
    {
        $orders = $customer->orders()->where('is_wholesale', true)
            ->with(['createduser', 'orderItems.product'])
            ->latest('date_order')->latest('id')->paginate(15);
        abort_if($orders->total() === 0, 404);

        $summary = $customer->orders()->where('is_wholesale', true)
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid')
            ->selectRaw("SUM(CASE WHEN status = 'ordered' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered")
            ->selectRaw("SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled")
            ->first();

        return view('wholesale-monitoring.show', compact('customer', 'orders', 'summary'));
    }
}
