<?php
namespace App\Traits;

use App\Models\Commission;
use App\Models\Epargne;
use App\Models\Fond;
use App\Models\Imprevu;
use App\Models\Orders\Order;
use App\Models\Pub;

trait NewTrait{

    public function RemoveFond (Order $order){
         try {
            //code...
            return   Fond::where('order_id',$order->id)->delete();
         } catch (\Throwable $th) {
            //throw $th;
         }

    }
    public function RemovePub (Order $order){
         try {
            //code...
            return   Pub::where('order_id',$order->id)->delete();
         } catch (\Throwable $th) {
            //throw $th;
         }

    }
    public function RemoveImprevue (Order $order){
        try {
            //code...
            return   Imprevu::where('order_id',$order->id)->delete();
        } catch (\Throwable $th) {
            //throw $th;
        }

    }
    public function RemoveCommission (Order $order){
        try {
            //code...
            return   Commission::where('order_id',$order->id)->delete();
        } catch (\Throwable $th) {
            //throw $th;
        }

    }
    public function RemoveEpargne (Order $order){
        try {
            //code...
            return   Epargne::where('order_id',$order->id)->delete();
        } catch (\Throwable $th) {
            //throw $th;
        }

    }



}
