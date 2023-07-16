<?php

namespace App\Http\Controllers\Admins;

use App\Models\Product;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

class DashboarController extends Controller
{
     public function dashboard () {

        $orders = Order::orderby('created_at', 'DESC')->get();

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

        $Ordered = Order::where('status', 'ordered')->count();
        $Orderdelivered = Order::where('status', 'delivered')->count();
        $Orderall = Order::count();
        $OrderdeAmount = Order::where('status', 'delivered')->sum('total');

        return view('dashboard.dashboard',compact('chart1','chart2', 'chart3','chart4', 'orders','Ordered', 'Orderdelivered','Orderall', 'OrderdeAmount'));
     }




     public function edit($id){
         $product=Product::findOrfail($id);
         return view('dashboard.edit',compact('product'));
     }



     public function updates (Request $request,$id){

        $product=Product::findOrfail($id);

        $product->update(['name'=>$request->name,'price'=>$request->price]);

        return redirect()->route('product')->with('messages','produit modifié');

     }


     public function delete($id){
        Product::destroy($id);

        return redirect()->back()->with('messages','produit supprimé');
     }
}
