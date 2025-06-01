<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Pub;
use App\Models\Fond;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Epargne;
use App\Models\Imprevu;
use App\Models\TotalFond;
use App\Models\Orders\Order;
use App\Models\TolalFondPub;
use App\Models\AppCommission;
use App\Models\TolalFondImprevu;
use App\Models\PourcentageCommission;
use Illuminate\Support\Facades\Request;

trait MyTrait
{

    public function commission(Order $order){
        $users=User::all();
        foreach ($users as  $user) {
          if ($user->user_type=="MNG") {
              # code...
           $manager=PourcentageCommission::where('percent','Big')->first();
              if ($manager) {
                  # code...
                  $d=  intval($order->total) * (intval($manager->amount) / 100);
                  $user->commissions()->create(['amount'=>$d ,'total'=>$order->total,'fixed'=>$manager->amount,'order_id'=>$order->id]);

              } else {
                  # code...
                  $d=  intval($order->total) * 0;
                  $user->commissions()->create(['amount'=>$d ,'total'=>0,'fixed'=>0,'order_id'=>$order->id]);


              }

          }else if($user->user_type=="ADMINUSER"){
          $manager=PourcentageCommission::where('percent','Resp')->first();
            if ($manager) {
              # code...
              $s=  intval($order->total) * (intval($manager->amount) / 100);
              $user->commissions()->create(['amount'=>$s ,'total'=>$order->total,'fixed'=>$manager->amount]);

            } else {
              # code...
              $s=  intval($order->total) * 0 ;
              $user->commissions()->create(['amount'=>$s ,'total'=>0,'fixed'=>0]);

            }



          }
        }
       }
    public function CalculCommissions(Order $order)
    {
        // Trait method implementation
        if($order->costumer_id == $order->created_user){
            $s=  intval($order->total) * 1 / 100;
            AppCommission::create(['amount'=>$s ,'total'=>$order->total,'fixed'=>1]);
        }
    }

    public function CalculImprevu(Order $order){
         try {
            //code...
            $percent =PourcentageCommission::where('percent','Im')->first();
            $somme=Imprevu::sum('amount');

            $imprevu=  intval($order->total) * (intval($percent->amount)/100);
            $tote=$somme+$imprevu;
            Imprevu::create(['amount'=> $imprevu ,'total'=>$order->total,'fixed'=>$percent->amount,'order_id'=>$order->id]);
            $totalfonds=TolalFondImprevu::where('etat',0)->first();
            if ($totalfonds==null ) {
                TolalFondImprevu::create(['totals'=>$tote]);
            }else{
               $totalfonds->totals =intval($totalfonds->totals)+ $imprevu;
               $totalfonds->save();
            }
         } catch (\Throwable $th) {
            //throw $th;
         }

    }
    public function CalculTauxPub(Order $order){
          try {
            //code...
            $percent =PourcentageCommission::where('percent','Pub')->first();
            $somme=Pub::sum('amount');

            $tauxpub=  intval($order->total) * (intval($percent->amount) / 100);
            $tote=$somme+$tauxpub;
            Pub::create(['amount'=> $tauxpub ,'total'=>$order->total,'fixed'=>$percent->amount,'order_id'=>$order->id]);
            $totalfonds=TolalFondPub::where('etat',0)->first();
            if ($totalfonds==null ) {
                TolalFondPub::create(['totals'=>$tote]);
            }else{
               $totalfonds->totals =intval($totalfonds->totals)+ $tauxpub;
               $totalfonds->save();
            }
          } catch (\Throwable $th) {
            //throw $th;
          }


    }
    public function CalculTauxFond(Order $order){
          try {
              //code...
              $percent =PourcentageCommission::where('percent','Fond')->first();
              $somme=Fond::sum('amount');
              $tauxfond=  intval($order->total) * (intval($percent->amount)/ 100);
              $tote=$somme+$tauxfond;
              Fond::create(['amount'=>  $tauxfond ,'total'=>$order->total,'fixed'=>$percent->amount,'order_id'=>$order->id]);
              $totalfonds=TotalFond::where('etat',0)->first();
                 if ($totalfonds==null ) {
                     TotalFond::create(['totals'=>$tote]);
                 }else{
                    $totalfonds->totals =intval($totalfonds->totals)+ $tauxfond;
                    $totalfonds->save();
                 }
          } catch (\Throwable $th) {
            //throw $th;
          }


    }
    public function CalculTauxEpargne(Order $order){
        try {
            $percent =PourcentageCommission::where('percent','Epr')->first();

            $tauxepr=  intval($order->total) * (intval($percent->amount)/ 100);

            Epargne::create(['amount'=> $tauxepr ,'total'=>$order->total,'fixed'=>$percent->amount,'order_id'=>$order->id]);
        } catch (\Throwable $th) {
            //throw $th;
        }

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

   public function processCommissions(Order $order)
{
    $totalCommission = 0;

    foreach ($order->orderItems as $item) {
        $product = $item->product;
        $commission = $product->commission_amount * $item->quantity;
        $totalCommission += $commission;
    }

    // Mettre à jour le portefeuille du vendeur
    $wallet = Wallet::firstOrCreate(['user_id' => $order->created_user]);
    $wallet->balance += $totalCommission;
    $wallet->save();

    // (Optionnel) enregistrer un historique
    // CommissionHistory::create([
    //     'user_id' => $order->seller_id,
    //     'order_id' => $order->id,
    //     'amount' => $totalCommission,
    // ]);
}


}


