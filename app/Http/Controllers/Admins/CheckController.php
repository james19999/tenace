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
         if ($auth->user_type=="ADMINUSER") {
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


                    $users =User::where('user_type','LVS')->get();

                    $constumer= Costumer::create([
                            'name'=>$request->name,
                            'phone'=>$request->phone,
                            'email'=>$request->email,
                            'adresse'=>$request->adresse,
                            'user_id'=>Auth::user()->id,

                      ]);
                      $code = $this->getName();
                      $order = Order::create([
                          'costumer_id'=>$constumer->id,
                          'subtotal'=>round($request->subtotal) ,
                          'tax'=>$request->tax,
                          'time'=>$request->time,
                          'remis'=>$request->remis,
                          'total'=>
                           intval($request->subtotal)+intval($request->tax),
                           'montant'=>intval($request->subtotal)+intval($request->tax)*intval($request->remis)/100,
                          'code' => $code,
                          'brouillon' =>$this->brouillons() ,
                          'created_user' =>Auth::user()->id,
                          'avis'=>$request->avis


                      ]);

                      foreach (Cart::instance('cart')->content() as $item) {
                          $orderItem = new OrderItem();
                          $orderItem->product_id = $item->id;
                          $orderItem->order_id = $order->id;
                          $orderItem->price = $item->price;
                          $orderItem->quantity = $item->qty;
                          $orderItem->save();
                      }

                        if (Auth::user()->user_type=="ADMINUSER") {
                            foreach ($users as $key => $user) {

                             Mail::to($user->email)->send(new TenaCos($order));

                            }
                            # code...
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
            $users =User::where('user_type','LVS')->get();

            $code = $this->getName();
            $order = Order::create([
                'costumer_id'=>$request->costumer_id,
                'subtotal'=>round($request->subtotal) ,
                'tax'=>$request->tax,
                'time'=>$request->time,
                'remis'=>$request->remis,
                'total'=>
                 intval($request->subtotal)+intval($request->tax),
                 'montant'=>(intval(($request->subtotal)+intval($request->tax))*intval($request->remis))/100,
                'code' => $code,
                'brouillon' =>$this->brouillons() ,
                'created_user' =>Auth::user()->id,
                'avis'=>$request->avis
            ]);

            foreach (Cart::instance('cart')->content() as $item) {
                $orderItem = new OrderItem();
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->price = $item->price;
                $orderItem->quantity = $item->qty;
                $orderItem->save();
            }


            if (Auth::user()->user_type=="ADMINUSER") {
                foreach ($users as $key => $user) {

                 Mail::to($user->email)->send(new TenaCos($order));

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
        $randomString = "TENACOS-" . '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}
