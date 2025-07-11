<?php

namespace App\Http\Controllers\Admins;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use App\Traits\MyTrait;
use App\Models\Expensive;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

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

        $orders = Order::orderby('created_at', 'DESC')
        ->whereDate('created_at',Carbon::today())
        ->where('brouillon',1)
        ->get();

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

        $chart1 = new LaravelChart($chart_options);



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

        $chart2 = new LaravelChart($settings1);





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
        $chart3 = new LaravelChart($charts);


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

        $chart4 = new LaravelChart($chart_options_annuel);

        $Ordered = Order::where('status', 'ordered')
        ->whereDate('created_at',Carbon::today())
        ->count();
        $Orderdelivered = Order::where('status', 'delivered')
        ->whereDate('created_at',Carbon::today())
        ->count();
        $Orderall = Order::
        whereDate('created_at',Carbon::today())->
        count();
        $OrderdeAmount = Order::where('status', 'delivered')
        ->whereDate('created_at',Carbon::today())
        ->sum('total');

        $startOfWeek = Carbon::now()->startOfWeek();

        $totalOrdersThisWeek = Order::where('created_at', '>=', $startOfWeek)
         ->
         where('status', 'delivered')
        ->sum('total');


        $anneeEnCours = now()->year;

        $expensive=Expensive::whereYear('created_at', $anneeEnCours)->sum('amount');

        $totalCommandes = Order::whereYear('created_at', $anneeEnCours)
            ->where('status', 'delivered')
            ->sum('total');
            $rupture = Product::where('qts_seuil', '>=', DB::raw('qt_initial'))
            ->orWhere(function ($query) {
                $query->where('qt_initial', 0);
            })
            ->count();
            $totalpubs=$this->totalpub();
            $totalimpre=$this->totalimprevu();
            $totalfond=$this->totalfond();
            $totalepargn=$this->totalepargne();

        return view('dashboard.dashboard',compact('rupture', 'totalepargn', 'totalpubs', 'totalimpre', 'totalfond', 'totalCommandes','expensive','totalOrdersThisWeek','chart1','chart2', 'chart3','chart4', 'orders','Ordered', 'Orderdelivered','Orderall', 'OrderdeAmount'));
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

    public function settings(){
        $settings=Setting::limit(1)->get();
        return view('dashboard.settings',compact('settings'));
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
