<?php

namespace App\Http\Controllers\Orders;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\TenaCos;
use App\Models\Product;
use App\Traits\MyTrait;
use App\Models\Costumer;
use App\Traits\NewTrait;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PartnerOrderController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\PourcentageCommission;
use App\Services\StockDeliveryService;
use App\Support\WestAfricaShippingCatalog;

class OrderController extends Controller
{
    use MyTrait;
    use NewTrait;
    // public function index(){

    //     $orders=Order::latest()
    //     ->whereDate('created_at',Carbon::today())
    //     ->where('brouillon',1)
    //     ->get();

    //     return view('orders.index',compact('orders'));
    // }

    public function invoice(Order $order)
    {
        $this->authorizeWholesalerOrder($order);
        $order->load('orderItems.product', 'costumer');

        return view('orders.invoice', compact('order'));
    }
    public function index(Request $request)
    {
        if (Auth::user()?->user_type === User::ROLE_PARTNER) {
            return app(PartnerOrderController::class)->index($request);
        }
        $wholesaleOnly = Auth::user()?->user_type === User::ROLE_COMMERCIAL_MANAGER;

        $filter = $request->get('filter', 'today');
        if (! in_array($filter, ['today', 'week', 'month', 'year', 'all'], true)) {
            $filter = 'today';
        }

        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $dashboardOrders = fn () => Order::with(['costumer', 'user'])
            ->where('brouillon', 1)
            ->when($wholesaleOnly, fn ($query) => $query->where('is_wholesale', true))
            ->orderByDesc('assigned_at')
            ->orderByDesc('updated_at');

        $createdTodayOrders = $dashboardOrders()
            ->where('created_at', '>=', $today)->where('created_at', '<', $tomorrow)
            ->paginate(10, ['*'], 'createdPage');
        $deliveryTodayOrders = $dashboardOrders()
            ->whereDate('date_order', $today->toDateString())
            ->paginate(10, ['*'], 'deliveryPage');
        $assignedTodayOrders = $dashboardOrders()
            ->where(function ($query) use ($today, $tomorrow) {
                $query->where(function ($assignedAt) use ($today, $tomorrow) {
                    $assignedAt->where('assigned_at', '>=', $today)->where('assigned_at', '<', $tomorrow);
                })->orWhere(function ($legacy) use ($today, $tomorrow) {
                    $legacy->whereNull('assigned_at')->where('status_order', true)
                        ->where('updated_at', '>=', $today)->where('updated_at', '<', $tomorrow);
                });
            })->paginate(10, ['*'], 'assignedPage');
        $overdueOrders = $dashboardOrders()
            ->where('status', 'ordered')->whereNotNull('date_order')
            ->where('date_order', '<', $today->toDateString())
            ->paginate(10, ['*'], 'overduePage');

        $historyOrdersQuery = Order::with(['costumer', 'user'])->where('brouillon', 1)
            ->when($wholesaleOnly, fn ($query) => $query->where('is_wholesale', true));
        switch ($filter) {
            case 'today':
                $historyOrdersQuery->where('created_at', '>=', $today)->where('created_at', '<', $tomorrow);
                break;
            case 'week':
                $historyOrdersQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $historyOrdersQuery->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
                break;
            case 'year':
                $historyOrdersQuery->whereYear('created_at', Carbon::now()->year);
                break;
        }
        $historyOrders = $historyOrdersQuery->latest()->paginate(10, ['*'], 'historyPage')->appends(['filter' => $filter, 'ordersTab' => 'history']);

        return view('orders.index', compact(
            'createdTodayOrders', 'deliveryTodayOrders', 'assignedTodayOrders',
            'overdueOrders', 'historyOrders', 'filter'
        ));
    }


    public function addnewproduct(Request $request, $id)
    {

        $Orders = Order::with(['costumer', 'user', 'createduser', 'transportCompany', 'deliveryPhotos.user', 'orderItems.product'])->findOrfail($id);

        $Orders->orderItems()->save([
            'product_id',
            'price',
            'quantity',
            'high_price',
        ]);
    }
    public function show($id)
    {

        $Orders = Order::with('user:id,name,user_type,country_code')->findOrFail($id);
        $this->authorizeWholesalerOrder($Orders);
        if (Auth::user()?->user_type === User::ROLE_PARTNER) {
            abort_unless((int) $Orders->partner_id === (int) Auth::id() || (int) $Orders->created_user === (int) Auth::id(), 404);
        }
        $isWholesalerOrder = (bool) $Orders->is_wholesale;
        $countryCode = $this->orderCountryCode($Orders);
        $livreurs = User::query()->where('user_type', User::ROLE_LIVREUR)
            ->where('active', true)
            ->when($isWholesalerOrder, fn ($query) => $query->whereNull('country_code'), fn ($query) => $query->where('country_code', $countryCode))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'country_code']);

        // $Orders->orderItems()->save([
        //     'product_id',
        //     'price',
        //     'quantity',
        //     'high_price',
        // ]);

        return  view('orders.show', compact('Orders', 'livreurs'));
    }

    public function updatePayment(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        $this->authorizeWholesalerOrder($order);
        $amountDue = (float) ($order->montant ?? ((float) $order->total + (float) $order->tax));
        $validated = $request->validate([
            'paid_amount' => ['required', 'integer', 'min:0', 'max:'.$amountDue],
        ], [
            'paid_amount.max' => 'Le montant déjà payé ne peut pas dépasser le total de la commande.',
        ]);

        $previousPaidAmount = (int) $order->paid_amount;
        $newPaidAmount = (int) $validated['paid_amount'];
        $order->paid_amount = $newPaidAmount;
        $order->save();
        if ($newPaidAmount > $previousPaidAmount) {
            $order->payments()->create([
                'stage' => 'manual_adjustment',
                'method' => 'manual',
                'amount' => $newPaidAmount - $previousPaidAmount,
                'status' => 'collected',
                'collected_by' => Auth::id(),
                'collected_at' => now(),
                'notes' => 'Encaissement saisi depuis la fiche de commande.',
            ]);
        }

        return redirect()->back()->with('success', 'Le paiement de la commande a été enregistré.');
    }

    public function change_order_status(Request $request, $id, StockDeliveryService $stockDelivery)
    {

        $orders = Order::findOrfail($id);
        $this->authorizeWholesalerOrder($orders);

        if ($orders) {
            if ($request->status == "canceled") {
                $request->validate(
                    ['motif' => 'required'],
                    ['motif.required' => 'Entrer le motif d\'annulation.']
                );


                $orders->status = $request->status;
                $orders->motif = $request->motif;
                $orders->user_id = Auth::user()->id;
                $orders->status_order = false;
                $orders->take = false;
                $this->RemoveCommission($orders);
                $this->RemoveEpargne($orders);
                $this->RemoveFond($orders);
                $this->RemovePub($orders);
                $this->RemoveImprevue($orders);
                $orders->save();
                return redirect()->back()->with('success', 'commande annuler');
            } else if ($request->status == "ordered") {
                $orders->status = $request->status;
                $orders->save();
                return redirect()->back()->with('success', 'commande remise en cours');
            } else if ($request->status == "delivered") {
                $order = Order::with('orderItems')->findOrFail($orders->id);
                if (! $stockDelivery->deliver($order, (int) Auth::id())) {
                    return redirect()->back()->with('success', 'Cette commande a déjà été validée comme livrée.');
                }

                $orders->status = $request->status;
                $orders->user_id = Auth::user()->id;
                $orders->status_order = false;
                $orders->take = false;

                $this->commission($order);

                $this->CalculCommissions($order);

                $this->CalculImprevu($order);
                $this->CalculTauxEpargne($order);
                $this->CalculTauxFond($order);
                $this->CalculTauxPub($order);
                $this->processCommissions($order);


                $orders->save();
                return redirect()->back()->with('success', 'commande valider');
            }
        } else {
            return redirect()->back()->with('success', 'error');
        }
    }


    public function refresh_order(Request $request, $id)
    {
        $validated = $request->validate([
            'user_take' => 'nullable|integer|exists:users,id',
            'time' => 'nullable|date_format:H:i',
            'adresse' => 'nullable|string|max:1000',
        ]);

        $orders = Order::findOrfail($id);
        $this->authorizeWholesalerOrder($orders);
        if (! empty($validated['user_take'])) {
            $createdByWholesaler = (bool) $orders->is_wholesale;
            $courier = User::query()->whereKey($validated['user_take'])
                ->where('user_type', User::ROLE_LIVREUR)
                ->where('active', true)
                ->when($createdByWholesaler, fn ($query) => $query->whereNull('country_code'), fn ($query) => $query->where('country_code', $this->orderCountryCode($orders)))
                ->exists();
            if (! $courier) {
                return redirect()->back()->withErrors([
                    'user_take' => $createdByWholesaler
                        ? 'Choisis un livreur standard actif, sans pays partenaire.'
                        : 'Choisis un livreur actif rattaché au pays de cette commande.',
                ])->withInput();
            }
        }
        if ($orders->status_order == 0 || $orders->status_order == 1) {
            $orders->status_order = true;
            $orders->status = 'ordered';
            $orders->take = true;
            $orders->time = $validated['time'] ?? $orders->time;

            if (!empty($validated['user_take'])) {
                $orders->user_id = $validated['user_take'];
                $orders->assigned_at = now();
            }

            if (array_key_exists('adresse', $validated)) {
                $customer = Costumer::findOrFail($orders->costumer_id);
                $customer->update(['adresse' => $validated['adresse']]);
            }

            $orders->save();

            $livreur = $orders->user_id ? User::find($orders->user_id) : null;
            if ($livreur && filter_var($livreur->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($livreur->email)->send(new TenaCos($orders));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Erreur d'envoi email livreur : " . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'commande relancé');
        }

        return redirect()->back()->with('success', 'commande déjà en cours');
    }

    private function orderCountryCode(Order $order): string
    {
        $order->loadMissing('user:id,user_type,country_code');
        $countryCode = strtoupper(trim((string) (
            $order->country_code
            ?: $order->shipping_country
            ?: ($order->user?->user_type === User::ROLE_LIVREUR ? $order->user->country_code : null)
            ?: config('services.fedapay.country_code', 'TG')
        )));

        return in_array($countryCode, array_keys(WestAfricaShippingCatalog::countries()), true)
            ? $countryCode
            : strtoupper((string) config('services.fedapay.country_code', 'TG'));
    }

    public function delete($id)
    {
        $order = Order::findOrFail($id);
        $this->authorizeWholesalerOrder($order);
        $order->delete();

        return  redirect()->back()->with('success', 'Commande supprimer');
    }

    public function edit_hours_order(Request $request, $id)
    {
        $isPartner = $request->user()?->user_type === User::ROLE_PARTNER;
        $validated = $request->validate([
            'time' => ['required', 'date_format:H:i'],
            'date_order' => $isPartner
                ? ['required', 'date_format:Y-m-d', 'after_or_equal:today']
                : ['nullable', 'date_format:Y-m-d'],
        ], [
            'time.required' => 'Choisis une heure de livraison.',
            'time.date_format' => 'Saisis une heure valide au format HH:MM.',
            'date_order.required' => 'Choisis une nouvelle date de livraison.',
            'date_order.after_or_equal' => 'La date de livraison doit être aujourd’hui ou plus tard.',
        ]);

        $order = Order::findOrFail($id);
        $this->authorizeWholesalerOrder($order);
        if ($isPartner) {
            abort_unless((int) $order->partner_id === (int) $request->user()->id || (int) $order->created_user === (int) $request->user()->id, 404);
            $order->date_order = $validated['date_order'];
        } elseif (! empty($validated['date_order'])) {
            $order->date_order = $validated['date_order'];
        }
        $order->time = $validated['time'];
        $order->save();

        return redirect()->back()->with('success', 'Heure de livraison mise à jour.');
    }

    private function authorizeWholesalerOrder(Order $order): void
    {
        if (Auth::user()?->user_type === User::ROLE_COMMERCIAL_MANAGER && ! $order->is_wholesale) {
            abort(404);
        }
    }




    public function updateProdSale($prod_id, $newQty)
    {
        $prod = Product::where('id', $prod_id)->first();
        if ($prod) {
            $qty_init = $prod->qt_initial;
            $qts_sell = $prod->qts_sell;

            $qty_init -= $newQty;
            //dd($qty_init);
            $prod->qt_initial = $qty_init;
            $qts_sell += intval($newQty);
            $prod->qts_sell = $qts_sell;
            $bnvendu = ($prod->price - $prod->price_market) * $qts_sell;
            $prod->benefice = $bnvendu;
            //dd($prod->qts_sell);
            $prod->update();
            return true; //$prod->qt_initial;
        } else {
            return false;
        }
    }


    public  function check_type($id)
    {

        $order = Order::findOrFail($id);
        if ($order->type === "PR") {
            $order->type = "PU";
        } elseif ($order->type === "PU") {
            $order->type = "PR";
        } else {
            return back()->with('error', 'Le type de cette commande ne peut pas être modifié.');
        }
        $order->save();
        return back();
    }

    public function reporte_order(Request $request, $id)
    {
        $orders = Order::findOrfail($id);


        if ($orders) {
            # code...
            if ($orders->status_order == 0 || $orders->status_order == 1) {
                $orders->status_order = true;
                $orders->user_id = $request->user_report;
                $orders->assigned_at = now();
                $orders->status = "ordered";
                $orders->time = $request->time;
                $orders->created_at = $request->date_report;
                $orders->take = true;
                $orders->save();

                return redirect()->back()->with('success', "Commande  programmée sur $request->date_report à $request->time ");
            } else {
                # code...
                return redirect()->back()->with('success', "Erreur de la reprogrammation");
            }
        }
    }

    public function order_cancel_list()
    {
        $orders = Order::where('status', 'canceled')
            ->whereNotNull('user_id')
            ->get();
        $livreurs = User::where('user_type', 'LVS')->get();
        return  view('orders.order_canceled', compact('orders', 'livreurs'));
    }
    public function order_report_list()
    {
        $orders = Order::whereDate('created_at', '>', Carbon::today())
            ->where('status', 'ordered')->get();

        return view('orders.order_report_list', compact('orders'));
    }
    public function ordered_list()
    {
        $livreurs = User::where('user_type', 'LVS')->get();

        $orders = Order::whereDate('created_at', '<', Carbon::today())
            ->where('status', 'ordered')->get();

        return view('orders.ordered_list', compact('orders', 'livreurs'));
    }

    public function refresh_specific_order(Request $request, $id)
    {

        $orders = Order::findOrfail($id);
        $this->authorizeWholesalerOrder($orders);


        if ($orders) {
            # code...
            if ($orders->status_order == 0 || $orders->status_order == 1) {
                $orders->status_order = false;
                $orders->user_id = null;
                $orders->status = "ordered";
                $orders->time = $request->time;
                $orders->take = false;
                $orders->created_at = Carbon::now();
                $orders->save();

                return redirect()->back()->with('success', "Succès");
            } else {
                # code...
                return redirect()->back()->with('success', "Erreur de la reprogrammation");
            }
        }
    }

    public function trie_order_parther(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $created_user = $request->input('costumer_id');

        $orders = Order::whereBetween('delivered_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where("status", "delivered")
            ->where('costumer_id', $created_user)
            ->get();

        $users = User::where('user_type', 'PT')->get();

        return view('orders.order_parthner_trie', compact("orders", "users"));
    }

    public function order_repport(Request $request)
    {

        $orders = Order::whereYear('delivered_at', $request->year)
            ->where('status', 'delivered')
            ->get();
        return view('orders.order_repport', compact('orders'));
    }


    public function countDeliveriesByDay()
    {
        $startOfWeek = Carbon::now()->startOfWeek(); // Début de la semaine (Lundi)
        $endOfWeek = Carbon::now()->endOfWeek();
        $query = DB::table('orders')
            ->join('users', function ($join) {
                $join->whereRaw('orders.user_id = CAST(users.id AS CHAR)');
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('DAYNAME(orders.delivered_at) as day'), // Jour de la livraison
                DB::raw('COUNT(orders.id) as delivered_count') // Nombre de commandes livrées par jour
            )
            ->where('users.user_type', '=', 'LVS') // Filtrer les utilisateurs de type LVS
            ->where('orders.status', '=', 'delivered')
            ->whereBetween('orders.delivered_at', [$startOfWeek, $endOfWeek])  // Filtrer les commandes livrées
            ->groupBy('users.id', 'users.name', 'day'); // Grouper par utilisateur et jour

        $this->scopeDeliveryRankingToCountry($query);

        $results = $query->get();

        return $results;
    }
    public function countTotalDeliveries()
    {
        $startOfWeek = Carbon::now()->startOfWeek(); // Début de la semaine (lundi)
        $endOfWeek = Carbon::now()->endOfWeek();    // Fin de la semaine (dimanche)

        $query = DB::table('orders')
            ->join('users', function ($join) {
                $join->whereRaw('orders.user_id = CAST(users.id AS CHAR)');
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(orders.id) as total_deliveries') // Total des livraisons par utilisateur
            )
            ->where('users.user_type', '=', 'LVS') // Filtrer les utilisateurs de type LVS
            ->where('orders.status', '=', 'delivered') // Filtrer les commandes livrées
            ->whereBetween('orders.delivered_at', [$startOfWeek, $endOfWeek]) // Filtrer les commandes de la semaine en cours
            ->groupBy('users.id', 'users.name') // Grouper par utilisateur
            ->orderByDesc('total_deliveries'); // Trier par total décroissant

        $this->scopeDeliveryRankingToCountry($query);

        return $query->get();
    }

    private function deliveryRankingCountryCode(): ?string
    {
        $viewer = auth()->user();
        $adminCanChooseAll = in_array($viewer?->user_type, ['ADMINUSER', 'MNG'], true);

        if ($viewer?->user_type === 'PT') {
            $countryCode = strtoupper(trim((string) $viewer->country_code));
            return in_array($countryCode, array_keys(WestAfricaShippingCatalog::countries()), true)
                ? $countryCode
                : '___NONE___';
        }

        $requestedCountry = strtoupper(trim((string) request()->query('country_code', '')));
        if ($adminCanChooseAll && $requestedCountry === 'ALL') {
            return null;
        }

        if ($adminCanChooseAll && in_array($requestedCountry, array_keys(WestAfricaShippingCatalog::countries()), true)) {
            return $requestedCountry;
        }

        $viewerCountry = strtoupper(trim((string) ($viewer?->country_code ?: config('services.fedapay.country_code', 'TG'))));
        return in_array($viewerCountry, array_keys(WestAfricaShippingCatalog::countries()), true)
            ? $viewerCountry
            : '___NONE___';
    }

    private function scopeDeliveryRankingToCountry($query): void
    {
        $countryCode = $this->deliveryRankingCountryCode();
        if ($countryCode !== null) {
            $query->where('users.country_code', $countryCode);
        }
    }


    public function formatDeliveriesByDay()
    {
        $dailyData = $this->countDeliveriesByDay();
        $totalData = $this->countTotalDeliveries();

        $formatted = [];

        // Ajout des données totales
        foreach ($totalData as $total) {
            $formatted[$total->id] = [
                'name' => $total->name,
                'total_deliveries' => $total->total_deliveries,
                'days' => []
            ];
        }

        // Ajout des livraisons par jour
        foreach ($dailyData as $item) {
            $formatted[$item->id]['days'][$item->day] = $item->delivered_count;
        }

        // Compléter les jours manquants avec des valeurs par défaut
        foreach ($formatted as &$user) {
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                $user['days'][$day] = $user['days'][$day] ?? 0;
            }
        }

        return $formatted;
    }


    public function showDeliveries()
    {
        $formatted = $this->formatDeliveriesByDay();
        $selectedCountry = $this->deliveryRankingCountryCode();
        $canSelectDeliveryCountry = in_array(auth()->user()?->user_type, ['ADMINUSER', 'MNG'], true);
        $countryOptions = WestAfricaShippingCatalog::countries();

        return view('orders.deliveries', compact('formatted', 'selectedCountry', 'canSelectDeliveryCountry', 'countryOptions'));
    }

    public function OrderFilter()
    {

        return view('orders.filter');
    }




    public function annualRanking($year = null)
    {
        $year = $year ?? now()->year;

        return DB::table('orders')
            ->join('users', function ($join) {
                $join->whereRaw('orders.user_id = CAST(users.id AS CHAR)');
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(orders.id) as total_deliveries'),
                DB::raw('SUM(orders.tax) as total_revenue')
            )
            ->where('users.user_type', 'LVS')
            ->where('orders.status', 'delivered')
            ->whereYear('orders.delivered_at', $year)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_deliveries')
            ->orderByDesc('total_revenue')
            ->get();
    }



    public function ranking(Request $request)
    {
        $year = $request->year ?? now()->year;

        $ranking = $this->annualRanking($year);

        return view('orders.ranking', compact('ranking', 'year'));
    }
}
