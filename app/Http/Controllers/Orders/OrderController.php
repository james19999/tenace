<?php

namespace App\Http\Controllers\Orders;

use App\Models\Costumer;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{

    public function index(){

        $orders=Order::latest()->get();

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
                 $orders->save();
                return redirect()->back()->with('success','commande annuler');

               }else if($request->status=="ordered"){
                $orders->status=$request->status;
                $orders->save();
                return redirect()->back()->with('success','commande remise en cours');

               }else if($request->status=="delivered"){
                 $orders->status=$request->status;
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

             if($orders->status_order==0){
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
}
