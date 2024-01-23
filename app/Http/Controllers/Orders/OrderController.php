<?php

namespace App\Http\Controllers\Orders;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Costumer;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    public function index(){

        $orders=Order::latest()
        ->whereDate('created_at',Carbon::today())
        ->where('brouillon',1)
        ->get();

        return view('orders.index',compact('orders'));
    }


    public function show($id){

         $Orders=Order::findOrfail($id);
        return  view('orders.show',compact('Orders'));
    }

    public function change_order_status(Request $request,$id){

          $orders=Order::findOrfail($id);

           if($orders){
               if($request->status=="canceled"){
                 $request->validate(['motif'=>'required'],
                 ['motif.required' => 'Entrer le motif d\'annulation.']
                );


                 $orders->status=$request->status;
                 $orders->motif=$request->motif;
                 $orders->user_id=Auth::user()->id;
                 $orders->status_order=false;
                 $orders->take=false;
                 $orders->save();
                return redirect()->back()->with('success','commande annuler');

               }else if($request->status=="ordered"){
                $orders->status=$request->status;
                $orders->save();
                return redirect()->back()->with('success','commande remise en cours');

               }else if($request->status=="delivered"){
                   $order=Order::where('id',$orders->id)->first();
                      foreach ($order->orderItems as $key => $value) {
                        # code...
                        $this->updateProdSale($value->product_id,$value->quantity);
                      }
                      $orders->status=$request->status;
                      $orders->user_id=Auth::user()->id;
                      $orders->status_order=false;
                      $orders->take=false;

                     $orders->save();
                return redirect()->back()->with('success','commande valider');
               }

           }else{
            return redirect()->back()->with('success','error');

           }
    }


     public function refresh_order(Request $request, $id){
        $orders=Order::findOrfail($id);

        if($orders){

             if($orders->status_order==0 || $orders->status_order==1){
               $orders->status_order=false;
               $orders->user_id=null;
               $orders->status="ordered";
               $orders->time=$request->time;
               $orders->take=false;

              $constumer=Costumer::findOrfail($orders->costumer_id);

              $constumer->update(['adresse'=>$request->adresse]);

               $orders->save();

               return redirect()->back()->with('success','commande relancé');

             }else{
               return redirect()->back()->with('success','commande déjà en cours');

             }
         }
      }

    public function delete($id){

           Order::destroy($id);

           return  redirect()->back()->with('success','Commande supprimer');
    }

    public function edit_hours_order (Request $request, $id){
       $order =Order::findOrfail($id);

        if($order){
            $order->time=$request->time;

        //    $constumer=Costumer::findOrfail($order->costumer_id);

        //    $constumer->update(['adresse'=>$request->adresse]);

            $order->save();

           return redirect()->back()->with('succes','heure de livraison mise à jour');


        }
    }




    public function updateProdSale($prod_id,$newQty)
    {
        $prod = Product::where('id',$prod_id)->first();
        if ($prod) {
            $qty_init = $prod->qt_initial;
            $qts_sell = $prod->qts_sell;

            $qty_init -=$newQty;
            //dd($qty_init);
            $prod->qt_initial = $qty_init;
            $qts_sell +=intval($newQty);
            $prod->qts_sell = $qts_sell;
            $bnvendu=($prod->price - $prod->price_market) *$qts_sell;
            $prod->benefice =$bnvendu;
            //dd($prod->qts_sell);
            $prod->update();
            return true;//$prod->qt_initial;
        }else{
            return false;
        }
    }


    public  function check_type ($id){

      $order=Order::where('id',$id)->first();

       if($order->type=="PR"){
         $order->type="PU";
       }else if($order->type="PU"){
        $order->type="PR";
       }
      $order->save();
      return back();
    }

    public function reporte_order (Request $request, $id) {
        $orders=Order::findOrfail($id);


           if ($orders) {
            # code...
            if($orders->status_order==0 || $orders->status_order==1){
                $orders->status_order=true;
                $orders->user_id=$request->user_report;
                $orders->status="ordered";
                $orders->time=$request->time;
                $orders->created_at=$request->date_report;
                $orders->take=true;
                $orders->save();

                return redirect()->back()->with('success',"Commande  programmée sur $request->date_report à $request->time ");


           } else {
            # code...
            return redirect()->back()->with('success',"Erreur de la reprogrammation");

           }
        }

    }

    public function order_cancel_list(){
        $orders=Order::where('status','canceled')->get();
        $livreurs=User::where('user_type','LVS')->get();
        return  view('orders.order_canceled',compact('orders','livreurs'));
    }
    public function order_report_list(){
    $orders=Order::whereDate('created_at','>', Carbon::today())
     ->where('status','ordered')->get();

     return view('orders.order_report_list',compact('orders'));

    }
    public function ordered_list(){
    $livreurs=User::where('user_type','LVS')->get();

    $orders=Order::whereDate('created_at','<', Carbon::today())
     ->where('status','ordered')->get();

     return view('orders.ordered_list',compact('orders','livreurs'));

    }

     public function refresh_specific_order (Request $request ,$id) {

        $orders=Order::findOrfail($id);


        if ($orders) {
         # code...
         if($orders->status_order==0 || $orders->status_order==1){
            $orders->status_order=false;
            $orders->user_id=null;
            $orders->status="ordered";
            $orders->time=$request->time;
            $orders->take=false;
             $orders->created_at=Carbon::now();
             $orders->save();

             return redirect()->back()->with('success',"Succès");


        } else {
         # code...
         return redirect()->back()->with('success',"Erreur de la reprogrammation");

        }
     }
    }
}
