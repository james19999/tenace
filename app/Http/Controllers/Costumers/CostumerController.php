<?php

namespace App\Http\Controllers\Costumers;

use Carbon\Carbon;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\CustomerLoyaltyProfile;
use App\Models\CustomerLoyaltyScoreHistory;
use App\Services\CustomerLoyaltyService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;


class CostumerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('costumer.index');
    }

    public function datatable(Request $request)
    {
        $draw = max((int) $request->input('draw', 0), 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $search = trim((string) $request->input('search.value', ''));

        $columns = ['id', 'name', 'phone', 'email', 'adresse', 'id'];
        $orderColumn = (int) $request->input('order.0.column', 1);
        $orderBy = $columns[$orderColumn] ?? 'name';
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Costumer::query();
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($customers) use ($term) {
                $customers->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('adresse', 'like', $term);
            });
        }

        $recordsFiltered = (clone $query)->count();
        $customers = $query->select(['id', 'name', 'phone', 'email', 'adresse'])
            ->orderBy($orderBy, $direction)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $customers->values()->map(function (Costumer $customer, int $index) use ($start) {
            $editUrl = route('costumer.edit', $customer->id);
            $deleteUrl = route('costumer.destroy', $customer->id);
            $actions = '<div class="btn-group btn-group-sm">'
                .'<a href="'.$editUrl.'" class="btn btn-warning text-white"><i class="material-icons">edit</i> Modifier</a>'
                .'<form method="POST" action="'.$deleteUrl.'" onsubmit="return confirm(\'Supprimer ce client ?\')">'
                .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
                .'<input type="hidden" name="_method" value="DELETE">'
                .'<button type="submit" class="btn btn-danger"><i class="material-icons">delete</i> Supprimer</button>'
                .'</form></div>';

            return [
                'number' => $start + $index + 1,
                'name' => e($customer->name),
                'phone' => e($customer->phone),
                'email' => e($customer->email ?? '@'),
                'address' => e($customer->adresse ?? '-'),
                'actions' => $actions,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => Costumer::count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $term = trim($validated['q'] ?? '');
        if (mb_strlen($term) < 2) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $costumers = Costumer::query()
            ->where(function ($customers) use ($term) {
                $prefix = $term.'%';
                $customers->where('name', 'like', $prefix)
                    ->orWhere('phone', 'like', $prefix)
                    ->orWhere('email', 'like', $prefix);
            })
            ->orderBy('name')
            ->paginate(20, ['id', 'name', 'phone'], 'page', (int) ($validated['page'] ?? 1));

        return response()->json([
            'results' => $costumers->getCollection()->map(fn (Costumer $costumer) => [
                'id' => $costumer->id,
                'text' => $costumer->name.' | '.$costumer->phone,
            ]),
            'pagination' => ['more' => $costumers->hasMorePages()],
        ]);
    }

    public function topcostumer(Request $request)
    {

        $montharray = [
            1 => "Janvier",
            2 => "Février",
            3 => "Mars",
            4 => "Avril",
            5 => "Mai",
            6 => "Juin",
            7 => "Juillet",
            8 => "Août",
            9 => "Septembre",
            10 => "Octobre",
            11 => "Novembre",
            12 => "Décembre"
         ];



                # code...
        $limit = min(max((int) $request->input('limit', 10), 1), 500);
        $month = (int) $request->input('month');
        $month = $month >= 1 && $month <= 12 ? $month : null;

        $topOrders = Order::query()
            ->select('costumer_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(total) as orders_sum_total')
            ->whereNotNull('costumer_id')
            ->where('costumer_id', '!=', '')
            ->whereYear(DB::raw('COALESCE(orders.date_order, orders.created_at)'), Carbon::now()->year)
            ->when($month, function ($q) use ($month) {
                return $q->whereMonth(DB::raw('COALESCE(orders.date_order, orders.created_at)'), $month);
            })
            ->groupBy('costumer_id')
            ->orderByDesc('orders_sum_total')
            ->limit($limit)
            ->get();

        $customerIds = $topOrders->pluck('costumer_id')->map(fn ($id) => (int) $id)->filter()->values();
        $customersById = Costumer::whereIn('id', $customerIds)->get()->keyBy('id');

        $costumers = $topOrders->map(function ($top) use ($customersById) {
            $c = $customersById->get((int) $top->costumer_id);
            if (! $c) {
                return null;
            }
            $c->orders_count = (int) $top->orders_count;
            $c->orders_sum_total = (float) $top->orders_sum_total;
            return $c;
        })->filter()->values();

        return view('costumer.top_costumer', compact('costumers', 'montharray', 'limit', 'month'));

    }
    public function viewcostumer($id, CustomerLoyaltyService $loyaltyService)
    {
        $costumers = Costumer::where('id', $id)
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->with([
                'orders' => fn ($q) => $q->orderByDesc('id'),
                'orders.orderItems.product',
            ])
            ->firstOrFail();

        $purchasedProducts = collect();
        foreach ($costumers->orders as $order) {
            foreach ($order->orderItems as $item) {
                $productId = $item->product_id ?? ('item_'.$item->id);
                $productName = $item->product->name ?? 'Produit personnalisé';
                $productImg = $item->product->img ?? null;
                $unitPrice = $item->price ?? ($item->product->price ?? 0);
                $lineTotal = $unitPrice * $item->quantity;

                if (!$purchasedProducts->has($productId)) {
                    $purchasedProducts->put($productId, [
                        'product_id' => $item->product_id,
                        'name' => $productName,
                        'img' => $productImg,
                        'unit_price' => $unitPrice,
                        'total_quantity' => 0,
                        'total_amount' => 0,
                        'orders_count' => 0,
                        'last_purchased_at' => $order->created_at,
                    ]);
                }

                $curr = $purchasedProducts->get($productId);
                $curr['total_quantity'] += (int) $item->quantity;
                $curr['total_amount'] += (float) $lineTotal;
                $curr['orders_count'] += 1;
                if ($order->created_at && (! $curr['last_purchased_at'] || $order->created_at > $curr['last_purchased_at'])) {
                    $curr['last_purchased_at'] = $order->created_at;
                }
                $purchasedProducts->put($productId, $curr);
            }
        }

        $purchasedProducts = $purchasedProducts->sortByDesc('total_quantity')->values();

        $loyaltyProfile = null;
        $loyaltyHistory = collect();
        $loyaltyCategoryLabels = CustomerLoyaltyService::defaults()['category_labels'];
        try {
            $loyaltyProfile = CustomerLoyaltyProfile::where('costumer_id', $costumers->id)->first();
            if (! $loyaltyProfile || ! $loyaltyProfile->calculated_at || $loyaltyProfile->calculated_at->lt(now()->subDay())) {
                $loyaltyProfile = $loyaltyService->refreshCustomer((int) $costumers->id);
            }
            $loyaltyHistory = CustomerLoyaltyScoreHistory::where('costumer_id', $costumers->id)
                ->latest('snapshot_date')->limit(12)->get()->sortBy('snapshot_date')->values();
            $loyaltyCategoryLabels = $loyaltyService->rules()['category_labels'];
        } catch (Throwable $exception) {
            report($exception);
        }

        return view('costumer.view_costomer', compact('costumers', 'purchasedProducts', 'loyaltyProfile', 'loyaltyHistory', 'loyaltyCategoryLabels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('costumer.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
             'name'=>'required',
             'phone'=>'required',
             'adresse'=>'required',
        ]);


            Costumer::create([
                 'name'=>$request->name,
                 'phone'=>str_replace(' ', '',$request->phone)  ,
                 'email'=>$request->email,
                 'adresse'=>$request->adresse,
                 'user_id'=>Auth::user()->id,
            ]);

            return  redirect()->route('costumer.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $costumers=Costumer::findOrfail($id);

        return view('costumer.edit',compact('costumers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $costumers=Costumer::findOrfail($id);

        $costumers->update([
            'name'=>$request->name,
            'phone'=>str_replace(' ', '',$request->phone),
            'email'=>$request->email,
            'adresse'=>$request->adresse,
        ]);


        return redirect()->route('costumer.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        Costumer::destroy($id);

        return back();
    }
}
