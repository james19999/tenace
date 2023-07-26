<?php

namespace App\Http\Controllers\Admins;

use App\Models\User;
use App\Mail\TenaCos;
use App\Mail\TenanCos;
use App\Models\Costumer;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Orders\OrderItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Gloudemans\Shoppingcart\Facades\Cart;

class CheckController extends Controller
{
    public function palce_order(Request $request) {
         if ($request->costumer_id=="default") {
            # code...
                $request->validate([
                    'name'=>'required',
                    'phone'=>'required',
                    'adresse'=>'required',
                    'tax'=>'required',
                    'time'=>'required',
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


                    $users =User::all();

                    $constumer= Costumer::create([
                            'name'=>$request->name,
                            'phone'=>$request->phone,
                            'email'=>$request->email,
                            'adresse'=>$request->adresse,
                      ]);
                      $code = $this->getName();
                      $order = Order::create([
                          'costumer_id'=>$constumer->id,
                          'subtotal'=>round($request->subtotal) ,
                          'tax'=>$request->tax,
                          'time'=>$request->time,
                          'total'=>intval($request->subtotal)+ intval($request->tax),
                          'code' => $code,

                      ]);

                      foreach (Cart::instance('cart')->content() as $item) {
                          $orderItem = new OrderItem();
                          $orderItem->product_id = $item->id;
                          $orderItem->order_id = $order->id;
                          $orderItem->price = $item->price;
                          $orderItem->quantity = $item->qty;
                          $orderItem->save();
                      }

                      foreach ($users as $key => $user) {

                       Mail::to($user->email)->send(new TenaCos($order));

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
            $users =User::all();

            $code = $this->getName();
            $order = Order::create([
                'costumer_id'=>$request->costumer_id,
                'subtotal'=>round($request->subtotal) ,
                'tax'=>$request->tax,
                'time'=>$request->time,
                'total'=>intval($request->subtotal)+ intval($request->tax),
                'code' => $code,

            ]);

            foreach (Cart::instance('cart')->content() as $item) {
                $orderItem = new OrderItem();
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->price = $item->price;
                $orderItem->quantity = $item->qty;
                $orderItem->save();
            }

            foreach ($users as $key => $user) {

             Mail::to($user->email)->send(new TenaCos($order));

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
