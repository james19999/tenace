<?php

namespace App\Http\Controllers;

use App\Models\Orders\Order;
use App\Models\PublicOrderLink;
use App\Models\User;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PartnerMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        return view('partners.monitoring.index', $this->report($request->string('search')->toString()));
    }

    public function snapshot(Request $request): JsonResponse
    {
        $report = $this->report($request->string('search')->toString());

        return response()->json([
            'updated_at' => now()->format('H:i:s'),
            'summary' => $report['summary'],
            'order_statuses' => $report['orderStatuses'],
            'links' => $report['links']->map(fn (array $link) => $link)->values(),
            'orders' => $report['orders']->getCollection()->map(fn (Order $order) => [
                'code' => $order->code,
                'partner' => $order->partner?->name ?? '—',
                'link' => $order->publicOrderLink?->title ?? 'Catalogue direct',
                'customer' => $order->costumer?->name ?? 'Client non renseigné',
                'courier' => $order->user?->name ?? 'Non affecté',
                'status' => $this->statusLabel($order->partner_order_status),
                'status_code' => $order->partner_order_status,
                'amount' => (int) ($order->total ?: $order->montant),
                'paid_amount' => (int) $order->paid_amount,
                'delivery_fee' => (int) $order->tax,
                'payment_status' => $order->payments->pluck('status')->unique()->implode(', ') ?: 'Aucun paiement enregistré',
                'reason' => $order->motif,
                'updated_at' => $order->updated_at?->format('d/m/Y H:i'),
            ])->values(),
            'partners' => $report['partners']->map(fn (User $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'country' => $partner->monitor_country,
                'active' => (bool) $partner->active,
                'revenue_today' => (int) $partner->revenue_today,
                'revenue_week' => (int) $partner->revenue_week,
                'revenue_year' => (int) $partner->revenue_year,
                'orders_total' => (int) $partner->orders_total,
                'orders_active' => (int) $partner->orders_active,
                'orders_completed' => (int) $partner->orders_completed,
                'orders_canceled' => (int) $partner->orders_canceled,
                'couriers_active' => (int) $partner->couriers_active,
                'link_clicks' => (int) $partner->link_clicks,
                'link_visitors' => (int) $partner->link_visitors,
            ])->values(),
            'monthly_labels' => $report['monthlyLabels'],
            'monthly_values' => $report['monthlyValues'],
            'activities' => $report['activities']->map(fn (Order $order) => [
                'code' => $order->code,
                'partner' => $order->partner?->name ?? 'Partenaire supprimé',
                'country' => WestAfricaShippingCatalog::countries()[strtoupper((string) $order->country_code)]['label'] ?? $order->country_code ?? '—',
                'amount' => (int) ($order->total ?: $order->montant),
                'status' => $this->statusLabel($order->partner_order_status),
                'updated_at' => $order->updated_at?->format('d/m/Y H:i'),
                'reason' => $order->motif,
            ])->values(),
        ]);
    }

    private function report(string $search = ''): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $yearStart = $now->copy()->startOfYear();
        $partnerIds = User::query()->where('user_type', User::ROLE_PARTNER)->pluck('id');

        $orderAggregates = DB::table('orders')->whereIn('partner_id', $partnerIds)
            ->select('partner_id')
            ->selectRaw('COUNT(*) AS orders_total')
            ->selectRaw("SUM(CASE WHEN partner_order_status NOT IN ('delivered', 'canceled') THEN 1 ELSE 0 END) AS orders_active")
            ->selectRaw("SUM(CASE WHEN partner_order_status = 'delivered' THEN 1 ELSE 0 END) AS orders_completed")
            ->selectRaw("SUM(CASE WHEN partner_order_status = 'canceled' THEN 1 ELSE 0 END) AS orders_canceled")
            ->selectRaw("SUM(CASE WHEN partner_order_status = 'delivered' AND delivered_at >= ? THEN CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2)) ELSE 0 END) AS revenue_today", [$todayStart])
            ->selectRaw("SUM(CASE WHEN partner_order_status = 'delivered' AND delivered_at >= ? THEN CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2)) ELSE 0 END) AS revenue_week", [$weekStart])
            ->selectRaw("SUM(CASE WHEN partner_order_status = 'delivered' AND delivered_at >= ? THEN CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2)) ELSE 0 END) AS revenue_year", [$yearStart])
            ->groupBy('partner_id')->get()->keyBy('partner_id');

        $courierCounts = User::query()->where('user_type', User::ROLE_LIVREUR)->where('active', true)
            ->select('country_code')->selectRaw('COUNT(*) AS couriers_active')->groupBy('country_code')->get()->keyBy('country_code');
        $linkTraffic = DB::table('public_order_links')
            ->leftJoin('public_order_link_visits', 'public_order_link_visits.public_order_link_id', '=', 'public_order_links.id')
            ->select('public_order_links.country_code')
            ->selectRaw('COALESCE(SUM(public_order_link_visits.visits), 0) AS link_clicks')
            ->selectRaw('COUNT(DISTINCT public_order_link_visits.visitor_hash) AS link_visitors')
            ->groupBy('public_order_links.country_code')->get()->keyBy('country_code');
        $linkCountryOwners = User::query()->where('user_type', User::ROLE_PARTNER)->where('active', true)
            ->orderBy('id')->get(['id', 'country_code'])->groupBy(fn (User $partner) => strtoupper((string) $partner->country_code)
            )->map(fn ($partners) => (int) $partners->first()->id);

        $partners = User::query()->where('user_type', User::ROLE_PARTNER)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('country_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')->get()->map(function (User $partner) use ($orderAggregates, $courierCounts) {
                $countryCode = strtoupper((string) $partner->country_code);
                $countryLabel = WestAfricaShippingCatalog::countries()[$countryCode]['label'] ?? ($countryCode ?: 'Pays non défini');
                $metrics = $orderAggregates[$partner->id] ?? null;
                $partner->monitor_country = $countryLabel;
                $partner->orders_total = (int) ($metrics->orders_total ?? 0);
                $partner->orders_active = (int) ($metrics->orders_active ?? 0);
                $partner->orders_completed = (int) ($metrics->orders_completed ?? 0);
                $partner->orders_canceled = (int) ($metrics->orders_canceled ?? 0);
                $partner->revenue_today = (int) ($metrics->revenue_today ?? 0);
                $partner->revenue_week = (int) ($metrics->revenue_week ?? 0);
                $partner->revenue_year = (int) ($metrics->revenue_year ?? 0);
                $partner->couriers_active = (int) ($courierCounts[$countryCode]->couriers_active ?? 0);
                $ownsCountryLink = ($linkCountryOwners[$countryCode] ?? null) === (int) $partner->id;
                $partner->link_clicks = $ownsCountryLink ? (int) ($linkTraffic[$countryCode]->link_clicks ?? 0) : 0;
                $partner->link_visitors = $ownsCountryLink ? (int) ($linkTraffic[$countryCode]->link_visitors ?? 0) : 0;

                return $partner;
            })->sortByDesc('revenue_year')->values();

        $allOrders = Order::query()->whereIn('partner_id', $partnerIds);
        $statusRows = (clone $allOrders)->selectRaw('partner_order_status, COUNT(*) AS orders_count')
            ->groupBy('partner_order_status')->get()->keyBy('partner_order_status');
        $linkSummary = DB::table('public_order_links')
            ->leftJoin('public_order_link_visits', 'public_order_link_visits.public_order_link_id', '=', 'public_order_links.id')
            ->selectRaw('COALESCE(SUM(public_order_link_visits.visits), 0) AS clicks_total')
            ->selectRaw('COUNT(DISTINCT public_order_link_visits.visitor_hash) AS visitors_unique')
            ->first();
        $summary = [
            'partners_total' => User::query()->where('user_type', User::ROLE_PARTNER)->count(),
            'partners_active' => User::query()->where('user_type', User::ROLE_PARTNER)->where('active', true)->count(),
            'orders_active' => (clone $allOrders)->whereNotIn('partner_order_status', ['delivered', 'canceled'])->count(),
            'orders_total' => (clone $allOrders)->count(),
            'orders_completed' => (clone $allOrders)->where('partner_order_status', 'delivered')->count(),
            'orders_canceled' => (clone $allOrders)->where('partner_order_status', 'canceled')->count(),
            'orders_today' => (clone $allOrders)->where('created_at', '>=', $todayStart)->count(),
            'clicks_total' => (int) ($linkSummary->clicks_total ?? 0),
            'visitors_unique' => (int) ($linkSummary->visitors_unique ?? 0),
            'revenue_today' => (int) (clone $allOrders)->where('partner_order_status', 'delivered')->where('delivered_at', '>=', $todayStart)->sum(DB::raw("CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2))")),
            'revenue_week' => (int) (clone $allOrders)->where('partner_order_status', 'delivered')->where('delivered_at', '>=', $weekStart)->sum(DB::raw("CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2))")),
            'revenue_year' => (int) (clone $allOrders)->where('partner_order_status', 'delivered')->where('delivered_at', '>=', $yearStart)->sum(DB::raw("CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2))")),
        ];

        $monthStart = $now->copy()->startOfMonth()->subMonths(11);
        $monthlyRows = (clone $allOrders)->where('partner_order_status', 'delivered')->where('delivered_at', '>=', $monthStart)
            ->selectRaw("DATE_FORMAT(delivered_at, '%Y-%m') AS month_key, SUM(CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2))) AS amount")
            ->groupBy('month_key')->get()->keyBy('month_key');
        $monthlyLabels = $monthlyValues = [];
        for ($month = $monthStart->copy(); $month <= $now->copy()->startOfMonth(); $month->addMonth()) {
            $key = $month->format('Y-m');
            $monthlyLabels[] = $month->translatedFormat('M y');
            $monthlyValues[] = (int) ($monthlyRows[$key]->amount ?? 0);
        }

        $activities = (clone $allOrders)->with('partner:id,name,country_code')->latest('updated_at')->limit(12)->get();

        $links = PublicOrderLink::query()->withCount('orders')->latest()->get()->map(function (PublicOrderLink $link): array {
            $traffic = DB::table('public_order_link_visits')->where('public_order_link_id', $link->id)
                ->selectRaw('COALESCE(SUM(visits), 0) AS clicks, COUNT(DISTINCT visitor_hash) AS visitors')->first();
            $orders = $link->orders();
            $countryCode = strtoupper((string) $link->country_code);
            $partner = User::query()->where('user_type', User::ROLE_PARTNER)->where('country_code', $countryCode)->where('active', true)->orderBy('id')->first();

            return [
                'id' => $link->id,
                'title' => $link->title ?: 'Catalogue '.$countryCode,
                'country' => WestAfricaShippingCatalog::countries()[$countryCode]['label'] ?? $countryCode,
                'partner' => $partner?->name ?? 'Aucun partenaire actif',
                'clicks' => (int) ($traffic->clicks ?? 0),
                'visitors' => (int) ($traffic->visitors ?? 0),
                'orders' => (int) $link->orders_count,
                'active_orders' => (clone $orders)->whereNotIn('partner_order_status', ['delivered', 'canceled'])->count(),
                'completed_orders' => (clone $orders)->where('partner_order_status', 'delivered')->count(),
                'canceled_orders' => (clone $orders)->where('partner_order_status', 'canceled')->count(),
                'is_active' => $link->isAvailable(),
            ];
        })->values();

        $orderStatuses = $statusRows->mapWithKeys(fn ($row, $status) => [$status ?: 'unknown' => (int) $row->orders_count]);
        $orders = (clone $allOrders)->with(['partner:id,name', 'publicOrderLink:id,title,country_code', 'costumer:id,name', 'user:id,name', 'payments:id,order_id,status,amount,method'])
            ->latest('updated_at')->paginate(25, ['*'], 'orders_page')->withQueryString();

        return compact('summary', 'partners', 'monthlyLabels', 'monthlyValues', 'activities', 'links', 'orderStatuses', 'orders');
    }

    private function statusLabel(?string $status): string
    {
        return [
            'awaiting_delivery_quote' => 'Frais à définir',
            'awaiting_customer_confirmation' => 'Attente du client',
            'awaiting_prepayment' => 'Paiement attendu',
            'preparing' => 'En préparation',
            'ready_for_courier' => 'Prête au retrait',
            'assigned_to_courier' => 'Livreur affecté',
            'handed_to_courier' => 'Colis remis',
            'out_for_delivery' => 'En livraison',
            'delivered' => 'Livrée',
            'canceled' => 'Annulée',
        ][$status ?? ''] ?? 'En attente';
    }
}
