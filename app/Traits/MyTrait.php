<?php

namespace App\Traits;

use App\Models\AppCommission;
use App\Models\Epargne;
use App\Models\Fond;
use App\Models\Imprevu;
use App\Models\Orders\Order;
use App\Models\PourcentageCommission;
use App\Models\Pub;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

trait MyTrait
{
    public function CalculCommissions(Order $order)
    {
        // Trait method implementation
        if($order->costumer_id == $order->created_user){
            $s=  intval($order->total) * 1 / 100;
            AppCommission::create(['amount'=>$s ,'total'=>$order->total,'fixed'=>1]);
        }
    }

    public function CalculImprevu(Order $order){
        $percent =PourcentageCommission::where('percent','Im')->first();


        $imprevu=  intval($order->total) * intval($percent->amount)/100;
        Imprevu::create(['amount'=> $imprevu ,'total'=>$order->total,'fixed'=>$percent->amount]);

    }
    public function CalculTauxPub(Order $order){
        $percent =PourcentageCommission::where('percent','Pub')->first();

        $tauxpub=  intval($order->total) * intval($percent->amount) / 100;
        Pub::create(['amount'=> $tauxpub ,'total'=>$order->total,'fixed'=>$percent->amount]);


    }
    public function CalculTauxFond(Order $order){
        $percent =PourcentageCommission::where('percent','Fond')->first();

        $tauxfond=  intval($order->total) * intval($percent->amount)/ 100;
        Fond::create(['amount'=>  $tauxfond ,'total'=>$order->total,'fixed'=>$percent->amount]);


    }
    public function CalculTauxEpargne(Order $order){
        $percent =PourcentageCommission::where('percent','Epr')->first();

        $tauxepr=  intval($order->total) * intval($percent->amount)/ 100;

        Epargne::create(['amount'=> $tauxepr ,'total'=>$order->total,'fixed'=>$percent->amount]);


    }




    public function uploadimage(Request $request,String $string){
        if ($request->hasFile($string)) {

            $image = $request->file($string);
            $originalName = $image->getClientOriginalName();
            $imageName = time() . '_' . $originalName;
            $image->move(public_path('image'), $imageName);

        }
    }

   public function  totalpub(){
    $startOfWeek = Carbon::now()->startOfWeek();

    $totalpub =Pub::where('created_at', '>=', $startOfWeek)
    ->sum('amount');

    return $totalpub;
   }
   public function  totalimprevu(){
    $startOfWeek = Carbon::now()->startOfWeek();

    $totalimpre =Imprevu::where('created_at', '>=', $startOfWeek)
    ->sum('amount');

    return $totalimpre;
   }
   public function  totalfond(){
    $startOfWeek = Carbon::now()->startOfWeek();

    $totalfond=Fond::where('created_at', '>=', $startOfWeek)
    ->sum('amount');

    return $totalfond;
   }
   public function  totalepargne(){
    $startOfWeek = Carbon::now()->startOfWeek();

    $totalepargne=Epargne::where('created_at', '>=', $startOfWeek)
    ->sum('amount');

    return $totalepargne;
   }
}
