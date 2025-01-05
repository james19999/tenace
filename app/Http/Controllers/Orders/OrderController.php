<?php

namespace App\Http\Controllers\Orders;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\TenaCos;
use App\Models\Product;
use App\Traits\MyTrait;
use App\Models\Costumer;
use App\Traits\NewTrait;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\PourcentageCommission;

class OrderController extends Controller
{
   use MyTrait;
   use NewTrait;
    public function index(){

        $orders=Order::latest()
        ->whereDate('created_at',Carbon::today())
        ->where('brouillon',1)
        ->get();

        return view('orders.index',compact('orders'));
    }


      public function addnewproduct (Request $request ,$id){

        $Orders=Order::findOrfail($id);

        $Orders->orderItems()->save([
            'product_id',
            'price',
            'quantity',
            'high_price',
        ]);
      }
    public function show($id){

         $Orders=Order::findOrfail($id);
        $livreurs=User::where('user_type','LVS')->get();

        // $Orders->orderItems()->save([
        //     'product_id',
        //     'price',
        //     'quantity',
        //     'high_price',
        // ]);

        return  view('orders.show',compact('Orders' ,'livreurs'));
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
                 $this->RemoveCommission($orders);
                 $this->RemoveEpargne($orders);
                 $this->RemoveFond($orders);
                 $this->RemovePub($orders);
                 $this->RemoveImprevue( $orders);
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

                      $this->commission($order);

                      $this->CalculCommissions($order);

                      $this->CalculImprevu($order);
                      $this->CalculTauxEpargne($order);
                      $this->CalculTauxFond($order);
                      $this->CalculTauxPub($order);


                     $orders->save();
                return redirect()->back()->with('success','commande valider');
               }

           }else{
            return redirect()->back()->with('success','error');

           }
    }


     public function refresh_order(Request $request, $id){
        $orders=Order::findOrfail($id);
        $livreurs=User::where('id',$request->user_take)->first();


        if($orders){

             if($orders->status_order==0 || $orders->status_order==1){
               $orders->status_order=true;
               $orders->user_id=$request->user_take;
               $orders->status="ordered";
               $orders->time=$request->time;
               $orders->take=true;

              $constumer=Costumer::findOrfail($orders->costumer_id);

              $constumer->update(['adresse'=>$request->adresse]);
              Mail::to($livreurs->email)->send(new TenaCos($orders));

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
        $orders=Order::where('status','canceled')
         ->whereNotNull('user_id')
        ->get();
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

     public function trie_order_parther(Request $request){
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $created_user= $request->input('costumer_id');

        $orders = Order::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
           ->where("status","delivered")
           ->where('costumer_id',$created_user)
          ->get();

          $users=User::where('user_type','PT')->get();

         return view('orders.order_parthner_trie',compact( "orders","users"));
     }

     public function order_repport(Request $request){

        $orders = Order::whereYear('created_at', $request->year)
                        ->where('status','delivered')
                         ->get();
        return view('orders.order_repport',compact('orders'));
     }


     public function countDeliveriesByDay()
     {
         $startOfWeek = Carbon::now()->startOfWeek(); // Début de la semaine (Lundi)
         $endOfWeek = Carbon::now()->endOfWeek();
         $results = DB::table('orders')
             ->join('users', 'orders.user_id', '=', 'users.id')
             ->select(
                 'users.id',
                 'users.name',
                 DB::raw('DAYNAME(orders.updated_at) as day'), // Jour de la livraison
                 DB::raw('COUNT(orders.id) as delivered_count') // Nombre de commandes livrées par jour
             )
             ->where('users.user_type', '=', 'LVS') // Filtrer les utilisateurs de type LVS
             ->where('orders.status', '=', 'delivered')
             ->whereBetween('orders.updated_at', [$startOfWeek, $endOfWeek])  // Filtrer les commandes livrées
             ->groupBy('users.id', 'users.name', 'day') // Grouper par utilisateur et jour
             ->get();

         return $results;
     }
     public function countTotalDeliveries()
     {
         $startOfWeek = Carbon::now()->startOfWeek(); // Début de la semaine (lundi)
         $endOfWeek = Carbon::now()->endOfWeek();    // Fin de la semaine (dimanche)

         return DB::table('orders')
             ->join('users', 'orders.user_id', '=', 'users.id')
             ->select(
                 'users.id',
                 'users.name',
                 DB::raw('COUNT(orders.id) as total_deliveries') // Total des livraisons par utilisateur
             )
             ->where('users.user_type', '=', 'LVS') // Filtrer les utilisateurs de type LVS
             ->where('orders.status', '=', 'delivered') // Filtrer les commandes livrées
             ->whereBetween('orders.updated_at', [$startOfWeek, $endOfWeek]) // Filtrer les commandes de la semaine en cours
             ->groupBy('users.id', 'users.name') // Grouper par utilisateur
             ->orderByDesc('total_deliveries') // Trier par total décroissant
             ->get();
     }


          public function formatDeliveriesByDay()
          {
              $dailyData = $this->countDeliveriesByDay();
              $totalData = $this->countTotalDeliveries();

              $formatted = [];

              // Ajout des données totales
              foreach ($totalData as $total) {
                  $formatted[$total->id] = [
                      'name' => $total->name,
                      'total_deliveries' => $total->total_deliveries,
                      'days' => []
                  ];
              }

              // Ajout des livraisons par jour
              foreach ($dailyData as $item) {
                  $formatted[$item->id]['days'][$item->day] = $item->delivered_count;
              }

              // Compléter les jours manquants avec des valeurs par défaut
              foreach ($formatted as &$user) {
                  foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                      $user['days'][$day] = $user['days'][$day] ?? 0;
                  }
              }

              return $formatted;
          }


     public function showDeliveries()
     {
         $formatted = $this->formatDeliveriesByDay();
         return view('orders.deliveries', compact('formatted'));
     }

}
