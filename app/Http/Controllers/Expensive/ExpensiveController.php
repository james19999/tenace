<?php

namespace App\Http\Controllers\Expensive;

use App\Models\Expensive;
use Illuminate\Http\Request;
use App\Models\TypeExpensive;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

class ExpensiveController extends Controller
{
    public function index()
    {
    # code...
    $expensives=Expensive::latest()->get();
    $typeexpensives=TypeExpensive::latest()->get();

    return view('expensive.index',compact('expensives','typeexpensives'));
    }

    public function create (Request $request){
       $request->validate([
        'description'=>'required',
        'date_create'=>'required',
        'type_expensive_id'=>'required',
        'amount'=>'required',

         ]);
       $auth=  Auth::user()->id;
        Expensive::create([
            'user_id'=>$auth,
            'description'=>$request->description,
            'date_create'=>$request->date_create,
            'type_expensive_id'=>$request->type_expensive_id,
            'amount'=>$request->amount,
        ]);

         return back()->with('messages',' dépense créer');
    }

       public function destroy($id)
       {
           $expense = Expensive::find($id);
           if ($expense) {
               $expense->delete();
               return back()->with('messages',' dépense supprimé');
           } else {
               return back()->with('messages','error');;
           }
     }



     public function update(Request $request, $id)
     {
         $item = Expensive::find($id);
         $auth=  Auth::user()->id;
         $item->update([
             'user_id'=>$auth,
             'description'=>$request->description,
             'date_create'=>$request->date_create,
             'type_expensive_id'=>$request->type_expensive_id,
             'amount'=>$request->amount,
         ]);

         return back()->with('messages',' dépense  modifier');

     }


     public function repport(){

        $chart_options = [
            'chart_title' => 'Rapport mensuels',
            'report_type' => 'group_by_date',
            'model' => 'App\Models\Expensive',
            'group_by_field' => 'created_at',
            'group_by_period' => 'month',
            'chart_type' => 'bar',
            'aggregate_function' => 'sum',
            'aggregate_field' => 'amount',
        ];

        $chart1 = new LaravelChart($chart_options);


        $charts = [
            'chart_title' => 'Rapport périodique',
            'chart_type' => 'line',
            'report_type' => 'group_by_date',
            'model' => 'App\Models\Expensive',


            'group_by_field' => 'created_at',
            'group_by_period' => 'day',

            'aggregate_function' => 'sum',
            'aggregate_field' => 'amount',

            'filter_field' => 'created_at',
            'filter_days' => 30, // show only transactions for last 30 days
            'filter_period' => 'week', // show only transactions for this week
        ];
        $chart3 = new LaravelChart($charts);


        $chart_options_annuel = [
            'chart_title' => 'Rapport annuel',
            'report_type' => 'group_by_date',
            'model' => 'App\Models\Expensive',
            'group_by_field' => 'created_at',
            'group_by_period' => 'year',
            'chart_type' => 'bar',
            'aggregate_function' => 'sum',
            'aggregate_field' => 'amount',
        ];

        $chart4 = new LaravelChart($chart_options_annuel);





        return view('expensive.rapport_expensive',compact('chart1','chart3','chart4'));
     }
}
