<?php

namespace App\Http\Controllers\Brouillons;

use App\Models\User;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class BrouillonController extends Controller
{
    
    public function index(){

        $orders=Order::latest()
        ->orderby('created_at','DESC')
         ->where('brouillon',0)
        ->get();

        return view('brouillons.index',compact('orders'));
    }


    public function show($id){

        $Orders=Order::findOrfail($id);
       return  view('brouillons.show',compact('Orders'));
    }


    public function unlock_brouillon($id){

        $order=Order::findOrfail($id);
        if ($order) {
           
            if($order->brouillon==0){
             $order->brouillon=1;
             $order->save();
             return back();
    
            }
        }
    
      }

      public function parthners()
      {
          $users=User::where('user_type','PT')->latest()->get();
  
          return view('parthners.index',compact('users'));
      }  
}
