<?php

namespace App\Http\Controllers\Admins;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use App\Traits\MyTrait;
use App\Models\Expensive;
use App\Models\Orders\Order;
use App\Support\DashboardChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DashboarController extends Controller
{
    use MyTrait;
    public function setpassword(Request $request, $id){

        $user= User::findOrfail($id);

        $user->password=Hash::make($request->password);
        $user->save();

        return redirect()->back()->with('messages','Mot de passe  modifié avec succès');


    }
     public function dashboard () {

        $todayStart = Carbon::today();
        $tomorrow = $todayStart->copy()->addDay();

        $orders = Order::with(['costumer', 'user'])
            ->where('brouillon', 1)
            ->where(function ($query) use ($todayStart, $tomorrow) {
                $query->where(function ($createdToday) use ($todayStart, $tomorrow) {
                    $createdToday->where('created_at', '>=', $todayStart)
                        ->where('created_at', '<', $tomorrow);
                })
                    // Show older orders on the dashboard only on the day they are assigned.
                    // Undelivered orders remain in order management for a later reassignment.
                    ->orWhere(function ($assignedToday) use ($todayStart, $tomorrow) {
                        $assignedToday->where('status', 'ordered')
                            ->where('status_order', true)
                            ->where(function ($date) use ($todayStart, $tomorrow) {
                                $date->where(function ($assignedAt) use ($todayStart, $tomorrow) {
                                    $assignedAt->where('assigned_at', '>=', $todayStart)
                                        ->where('assigned_at', '<', $tomorrow);
                                })->orWhere(function ($legacy) use ($todayStart, $tomorrow) {
                                        $legacy->whereNull('assigned_at')
                                            ->where('updated_at', '>=', $todayStart)
                                            ->where('updated_at', '<', $tomorrow);
                                    });
                            });
                    });
            })
            ->orderByDesc('assigned_at')
            ->orderByDesc('updated_at')
            ->get();

        $isAdmin = Auth::check() && Auth::user()->user_type === 'ADMINUSER';

        if ($isAdmin) {
            $chart_options = [
                'chart_title' => 'Rapport mensuels',
                'report_type' => 'group_by_date',
                'model' => 'App\Models\Orders\Order',
                'group_by_field' => 'created_at',
                'group_by_period' => 'month',
                'chart_type' => 'bar',
                'aggregate_function' => 'sum',
                'aggregate_field' => 'total',
                'where_raw' => "status='delivered' ",
            ];

            $chart1 = new DashboardChart($chart_options, $this->dashboardChartData(
                Order::class,
                'month',
                'sum',
                'total',
                null,
                'delivered'
            ));

            $settings1 = [
                'chart_title'           => 'Clients',
                'chart_type'            => 'pie',
                'report_type'           => 'group_by_date',
                'model'                 => 'App\Models\Costumer',
                'group_by_field'        => 'created_at',
                'group_by_period'       => 'day',
                'aggregate_function'    => 'count',
                'filter_field'          => 'created_at',
                'filter_days'           => '30',
                // 'where_raw' => "usertype='user' ",
            ];

            $chart2 = new DashboardChart($settings1, $this->dashboardChartData(
                \App\Models\Costumer::class,
                'day',
                'count',
                null,
                30
            ));

            $charts = [
                'chart_title' => 'Rapport périodique',
                'chart_type' => 'line',
                'report_type' => 'group_by_date',
                'model' => 'App\Models\Orders\Order',
                'group_by_field' => 'created_at',
                'group_by_period' => 'day',
                'aggregate_function' => 'sum',
                'aggregate_field' => 'total',
                'where_raw' => "status='delivered' ",
                'filter_field' => 'created_at',
                'filter_days' => 30, // show only transactions for last 30 days
                'filter_period' => 'week', // show only transactions for this week
            ];
            $chart3 = new DashboardChart($charts, $this->dashboardChartData(
                Order::class,
                'day',
                'sum',
                'total',
                30,
                'delivered'
            ));

            $chart_options_annuel = [
                'chart_title' => 'Rapport annuel',
                'report_type' => 'group_by_date',
                'model' => 'App\Models\Orders\Order',
                'group_by_field' => 'created_at',
                'group_by_period' => 'year',
                'chart_type' => 'bar',
                'aggregate_function' => 'sum',
                'aggregate_field' => 'total',
                'where_raw' => "status='delivered' ",
            ];

            $chart4 = new DashboardChart($chart_options_annuel, $this->dashboardChartData(
                Order::class,
                'year',
                'sum',
                'total',
                null,
                'delivered'
            ));

            $startOfWeek = Carbon::now()->startOfWeek();

            $totalOrdersThisWeek = Order::where('created_at', '>=', $startOfWeek)
                ->where('status', 'delivered')
                ->sum('total');

            $anneeEnCours = now()->startOfYear();
            $debutAnneeProchaine = $anneeEnCours->copy()->addYear();

            $totalCommandes = Order::where('created_at', '>=', $anneeEnCours)
                ->where('created_at', '<', $debutAnneeProchaine)
                ->where('status', 'delivered')
                ->sum('total');

            $totalpubs = $this->totalpub();
            $totalimpre = $this->totalimprevu();
            $totalfond = $this->totalfond();
            $totalepargn = $this->totalepargne();
        } else {
            $chart1 = null;
            $chart2 = null;
            $chart3 = null;
            $chart4 = null;
            $totalOrdersThisWeek = 0;
            $totalCommandes = 0;
            $totalpubs = 0;
            $totalimpre = 0;
            $totalfond = 0;
            $totalepargn = 0;
        }

        $createdTodaySql = '(created_at >= ? AND created_at < ?)';
        $assignedTodaySql = '(status_order = ? AND ((assigned_at >= ? AND assigned_at < ?) OR (assigned_at IS NULL AND updated_at >= ? AND updated_at < ?)))';
        $todayBindings = [$todayStart, $tomorrow];
        $assignedTodayBindings = [true, $todayStart, $tomorrow, $todayStart, $tomorrow];

        $todayOrderMetrics = Order::query()
            ->selectRaw(
                "SUM(CASE WHEN status = ? AND ({$createdTodaySql} OR {$assignedTodaySql}) THEN 1 ELSE 0 END) AS ordered_count",
                array_merge(['ordered'], $todayBindings, $assignedTodayBindings)
            )
            ->selectRaw(
                "SUM(CASE WHEN status = ? AND {$createdTodaySql} THEN 1 ELSE 0 END) AS delivered_count",
                array_merge(['delivered'], $todayBindings)
            )
            ->selectRaw(
                "SUM(CASE WHEN status = ? AND {$createdTodaySql} THEN 1 ELSE 0 END) AS canceled_count",
                array_merge(['canceled'], $todayBindings)
            )
            ->selectRaw(
                "SUM(CASE WHEN {$createdTodaySql} OR {$assignedTodaySql} THEN 1 ELSE 0 END) AS all_count",
                array_merge($todayBindings, $assignedTodayBindings)
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = ? AND {$createdTodaySql} THEN total ELSE 0 END), 0) AS delivered_amount",
                array_merge(['delivered'], $todayBindings)
            )
            ->whereRaw(
                "{$createdTodaySql} OR {$assignedTodaySql}",
                array_merge($todayBindings, $assignedTodayBindings)
            )
            ->first();

        $Ordered = (int) $todayOrderMetrics->ordered_count;
        $Orderdelivered = (int) $todayOrderMetrics->delivered_count;
        $Ordercanceled = (int) $todayOrderMetrics->canceled_count;
        $Orderall = (int) $todayOrderMetrics->all_count;
        $OrderdeAmount = $todayOrderMetrics->delivered_amount;

        $startOfYear = now()->startOfYear();
        $startOfNextYear = $startOfYear->copy()->addYear();
        $expensive = Expensive::where('created_at', '>=', $startOfYear)
            ->where('created_at', '<', $startOfNextYear)
            ->sum('amount');

        $rupture = Product::where('qts_seuil', '>=', DB::raw('qt_initial'))
            ->orWhere(function ($query) {
                $query->where('qt_initial', 0);
            })
            ->count();

        return view('dashboard.dashboard', compact(
            'rupture', 'totalepargn', 'totalpubs', 'totalimpre', 'totalfond',
            'totalCommandes', 'expensive', 'totalOrdersThisWeek', 'chart1',
            'chart2', 'chart3', 'chart4', 'orders', 'Ordered',
            'Orderdelivered', 'Ordercanceled', 'Orderall', 'OrderdeAmount'
        ));
     }

    /**
     * Aggregate dashboard chart rows in MySQL instead of loading every source
     * record into PHP as LaravelChart does.
     */
    private function dashboardChartData(
        string $model,
        string $period,
        string $aggregate,
        ?string $aggregateField = null,
        ?int $filterDays = null,
        ?string $status = null
    ): array {
        $formats = [
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            'year' => '%Y',
        ];

        if (!isset($formats[$period])) {
            throw new \InvalidArgumentException('Période de graphique non prise en charge.');
        }

        $query = $model::query()
            ->selectRaw('DATE_FORMAT(`created_at`, ?) AS chart_period', [$formats[$period]])
            ->whereNotNull('created_at');

        if ($filterDays !== null) {
            $query->where('created_at', '>=', now()->subDays($filterDays)->format('Y-m-d'));
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($aggregate === 'count') {
            $query->selectRaw('COUNT(*) AS aggregate_value');
        } elseif ($aggregate === 'sum' && $aggregateField !== null) {
            $query->selectRaw('COALESCE(SUM(`' . $aggregateField . '`), 0) AS aggregate_value');
        } else {
            throw new \InvalidArgumentException('Agrégat de graphique non pris en charge.');
        }

        return $query
            ->groupBy('chart_period')
            ->orderBy('chart_period')
            ->pluck('aggregate_value', 'chart_period')
            ->all();
    }




     public function edit($id){
         $product=Product::findOrfail($id);
         return view('dashboard.edit',compact('product'));
     }



     public function updates (Request $request,$id){

        $product=Product::findOrfail($id);
        // $image = $request->file('img');
        // $originalName = $image->getClientOriginalName();
        // $imageName = time() . '_' . $originalName;
        // $image->move(public_path('image'), $imageName);

        $product->update(['name'=>$request->name,'price'=>$request->price,
        'qts_seuil'=>$request->qts_seuil,
        'commission_amount'=>$request->commission_amount,
        'high_price'=>$request->high_price,
        'description'=>$request->description,
        // 'img'=>$imageName
    ]);

        return redirect()->route('product')->with('messages','produit modifié');

     }


     public function delete($id){
        Product::destroy($id);

        return redirect()->back()->with('messages','produit supprimé');
     }



     public function history(){

        $orders=Order::latest()
        ->whereDate('created_at',Carbon::today())
        ->where('brouillon',1)
        ->get();

        return view('dashboard.history',compact('orders'));
    }

     public function consultation(Request $request){

        $orders=Order::latest()
        ->whereMonth('created_at',8)
        ->where('brouillon',1)
        ->where('status', 'delivered')
        ->where('user_id',$request->user)
        ->get();
        $users =User::all();


        return view('dashboard.consutation',compact('orders','users'));
    }

    public function register() {

        return view('dashboard.register');
    }


    public function store_pathner(Request $request)
    {
         $request->validate([
                 'name' => ['required', 'string', 'max:255'],
                 'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                 'password' => ['required', 'string', 'min:8', 'confirmed'],
                 'adresse' => ['required', 'string'],
                 'phone' => ['required', 'integer' ,'min:8','unique:users'],
         ],
          [
            'phone.required'=>'Le numéro de téléphone ne doit pas dépasser huit chiffres.'
          ]

        );



               User::create([
                'name' => $request->name,
                'email' =>$request->email ,
                'password' =>Hash::make($request->password),
                'adresse' => $request->adresse,
                'phone' => $request->phone,
                'user_type' => "PT",
               ]);

         return redirect()->route('login')->with('messages','Votre compte a bien été créé.');
    }


    public function getProductOrders()
{
    $currentMonth = Carbon::now()->month;

    $productOrders = DB::table('order_items')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->select('products.name', 'order_items.product_id', DB::raw('count(*) as order_count'))
        ->whereMonth('order_items.created_at', $currentMonth)
        ->groupBy('products.name', 'order_items.product_id')
        ->get();

    return view('dashboard.product_orders', compact('productOrders'));
}

    public function rupture(){
        $Products = Product::where('qts_seuil', '>=', DB::raw('qt_initial'))
        ->orWhere(function ($query) {
            $query->where('qt_initial', 0);
        })->get();

        return view('dashboard.rupture',compact('Products'));
    }

    public function active($id){
     $user=User::findOrfail($id);

       if ($user->active==true) {
        # code...
          $user->active=false;
       } else if($user->active==false) {
        # code...
        $user->active=true;

       }
       $user->save();
       return back();


    }

    public function settings(Request $request){
        $settings = Setting::limit(1)->get();

        // Recherche utilisateurs pour la gestion des rôles
        $search = $request->input('user_search');
        $usersQuery = User::query();
        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $users = $usersQuery->orderBy('name')->paginate(5, ['*'], 'users_page');

        return view('dashboard.settings', compact('settings', 'users', 'search'));
    }

    public function updateUserRole(Request $request, $id)
    {
        // Double vérification : seul l'admin peut changer un rôle
        if (Auth::user()->user_type !== User::ROLE_ADMIN) {
            abort(403);
        }

        $request->validate([
            'user_type' => ['required', 'in:ADMINUSER,CALLCENTER,VDS,MNG,SCR,LVS,PT,CSA,User'],
        ]);

        $user = User::findOrFail($id);

        // Empêcher l'admin de changer son propre rôle par accident
        if ($user->id === Auth::id()) {
            return back()->with('role_error', 'Vous ne pouvez pas modifier votre propre rôle.');
        }

        $user->update(['user_type' => $request->user_type]);

        return back()->with('role_success', "Le rôle de {$user->name} a été mis à jour avec succès.");
    }

    public function settinginfo(Request $request) {

        if ($request->hasFile('img')) {

            $image = $request->file('img');
            $originalName = $image->getClientOriginalName();
            $imageName = time() . '_' . $originalName;
            $image->move(public_path('image'), $imageName);
        } else {
            $imageName = 'noimage.jpg';
        }
        $setting=Setting::count();
          if($setting==0){
            Setting::create(['name'=>$request->name,
            'address'=>$request->address,'phone'=>$request->phone,
            'email'=>$request->email,'img'=>$imageName]);
           return back()->with('messages',"Paramètre de l'entreprise configuré");

          }else{
           return back()->with('messages','Veillez modifier les informations de l\'entreprise');
          }

    }
    public function settinginfoupdate(Request $request ,$id) {

        if ($request->hasFile('img')) {

            $image = $request->file('img');
            $originalName = $image->getClientOriginalName();
            $imageName = time() . '_' . $originalName;
            $image->move(public_path('image'), $imageName);
        } else {
            $imageName = 'noimage.jpg';
        }
         $setting=Setting::findOrfail($id);

         $setting->update(['name'=>$request->name,
            'address'=>$request->address,'phone'=>$request->phone,
            'email'=>$request->email,'img'=>$imageName]);
           return back()->with('messages',"Paramètre de l'entreprise configuré");



    }
}
