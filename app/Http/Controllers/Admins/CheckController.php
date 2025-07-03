<?php

namespace App\Http\Controllers\Admins;

use App\Models\User;
use App\Mail\TenaCos;
use App\Mail\TenanCos;
use App\Models\Costumer;
use App\Mail\ParthnerMail;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Gloudemans\Shoppingcart\Facades\Cart;

class CheckController extends Controller
{

    public function brouillons () {
        $auth =Auth::user();

         $brouillon ="";
         if ($auth->user_type=="ADMINUSER" || Auth::user()->user_type=="VDS" || Auth::user()->user_type=="MNG") {
             $brouillon=1;
         } else {
            # code...
            $brouillon=0;
         }

         return $brouillon;

    }

    public function palce_order(Request $request) {


         if ($request->costumer_id=="default") {
            # code...
                $request->validate([
                    'name'=>'required',
                    'phone'=>'required',
                    'adresse'=>'required',
                    'tax'=>'required',
                    'time'=>'required',
                    'remis'=>'required',
                ],
                [
                    'name.required'=>'Le nom du client',
                    'phone.required'=>'Le numéro de téléphone',
                    'adresse.required'=>'L\'adresse de livraison',
                    'tax.required'=>'Entrer les frais de livraison',
                    'time.required'=>'Entrer la date de livraison',

                ]

                );
                $exist =Costumer::where('phone' ,$request->phone)->first();
                  if ($exist) {
                    # code...
                return redirect()->route('productcart')->with('error','Le client existe dèjà sélectionné  le dans la liste des clients');

                  } else {
                    # code...

                    $constumer= Costumer::create([
                            'name'=>$request->name,
                            'phone'=>str_replace(' ', '',$request->phone),
                            'email'=>$request->email,
                            'adresse'=>$request->adresse,
                            'user_id'=>Auth::user()->id,

                      ]);
                    $users =User::where('user_type','LVS')
                                   ->where('active',1)
                                  ->get();

                      $code = $this->getName();
                      $order = Order::create([
                          'costumer_id'=>$constumer->id,
                          'subtotal'=>round($request->subtotal) ,
                          'tax'=>$request->tax,
                          'time'=>$request->time,
                          'remis'=>$request->remis,
                          'total'=>intval($request->remis)==0?($request->subtotal):
                          intval(($request->subtotal))-
                          (intval(($request->subtotal))*intval($request->remis))/100,
                           'montant'=>intval($request->subtotal)+intval($request->tax),
                          'code' => $code,
                          'brouillon' =>$this->brouillons() ,
                          'created_user' =>Auth::user()->id,
                          'avis'=>$request->avis,
                          'type'=>$request->type
                      ]);

                      foreach (Cart::instance('cart')->content() as $item) {
                          $orderItem = new OrderItem();
                          $orderItem->product_id = $item->id;
                          $orderItem->order_id = $order->id;
                          $orderItem->price = $item->price;
                          $orderItem->quantity = $item->qty;
                          $item->high_price ? $orderItem->high_price = $item->high_price : $orderItem->high_price=0 ;

                          $orderItem->save();
                      }

                        if (Auth::user()->user_type=="ADMINUSER" || Auth::user()->user_type=="MNG") {
                              if ($request->type=="PU") {
                                # code...

                                foreach ($users as $key => $user) {

                                    try {
                            // Mail::to($user->email)->send(new TenaCos($order));
                        } catch (\Exception $e) {
                            Log::error("Erreur d’envoi à {$user->email} : " . $e->getMessage());
                        }

                                //  Mail::to($user->email)->send(new TenaCos($order));

                                }
                                # code...
                              }
                        } else {
                            Mail::to('crepinawity@gmail.com')->send(new ParthnerMail(URL::signedRoute('brouillons')));
                        }

                     Cart::instance('cart')->destroy();
                     return redirect()->route('order')->with('messages','Commande effectuée');
                  }

         } else {
            $request->validate([
                'tax'=>'required',
                'time'=>'required',
            ],
            [
                'tax.required'=>'Entrer les frais de livraison',
                'time.required'=>'Entrer l \' heure de livraison',

            ]);
            $users =User::where('user_type','LVS')
                ->where('active',1)
               ->get();

            $code = $this->getName();
            $order = Order::create([
                'costumer_id'=>$request->costumer_id,
                'subtotal'=>round($request->subtotal) ,
                'tax'=>$request->tax,
                'time'=>$request->time,
                'remis'=>$request->remis,
                'total'=>intval($request->remis)==0?($request->subtotal):
                intval(($request->subtotal))-
                (intval(($request->subtotal))*intval($request->remis))/100,
                 'montant'=>intval($request->subtotal)+intval($request->tax),
                'code' => $code,
                'brouillon' =>$this->brouillons() ,
                'created_user' =>Auth::user()->id,
                'avis'=>$request->avis,
                'type'=>$request->type
            ]);

            foreach (Cart::instance('cart')->content() as $item) {
                $orderItem = new OrderItem();
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->price = $item->price ;
                $item->high_price ? $orderItem->high_price = $item->high_price : $orderItem->high_price=0 ;
                $orderItem->quantity = $item->qty;
                $orderItem->save();
            }


            if (Auth::user()->user_type=="ADMINUSER" || Auth::user()->user_type=="MNG") {
                 if($request->type=="PU"){

                     foreach ($users as $key => $user) {

                      Mail::to($user->email)->send(new TenaCos($order));

                     }
                 }
                # code...
            } else {
                Mail::to('crepinawity@gmail.com')->send(new ParthnerMail(URL::signedRoute('brouillons')));
            }

           Cart::instance('cart')->destroy();
           return redirect()->route('order')->with('messages','Commande effectuée');
         }


    }

    public  function getName($n = 3)
    {
        $characters = "0123456789";
        $randomString = "TENACE"."-" . '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }



    // public function update_order($id){
    //   $order=Order::findOrfail($id);
    //  $restor= Cart::instance('cart')->restore($order);

    // }
}
