<?php

namespace App\Http\Controllers\Admins;

use App\Models\User;
use App\Mail\TenaCos;
use App\Mail\TenanCos;
use App\Models\Costumer;
use App\Models\CostumerContactPreference;
use App\Mail\ParthnerMail;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Gloudemans\Shoppingcart\Facades\Cart;

class CheckController extends Controller
{

    public function brouillons () {
        $auth = Auth::user();

        if (in_array($auth->user_type, ['ADMINUSER', 'MNG', 'SCR', 'VDS', 'CSA'], true)) {
            return 1;
        }

        return 0;
    }

    public function palce_order(Request $request) {


         $customerSelection = (string) $request->input('costumer_id', 'default');
         $isAnonymousCustomer = $customerSelection === 'anonymous';

         if (in_array($customerSelection, ['default', 'anonymous'], true)) {
            # code...
                $request->validate([
                    'name'=>$isAnonymousCustomer ? 'nullable|string|max:255' : 'required|string|max:255',
                    'phone'=>'required|string|max:50',
                    'adresse'=>'required|string|max:1000',
                    'tax'=>'required',
                    'time'=>'required',
                    'date_order'=>'required|date',
                    'remis'=>'required',
                    'follow_up_at'=>'required|date',
                ],
                [
                    'name.required'=>'Le nom du client',
                    'date_order.required'=>'Mettez une date de livraison',
                    'phone.required'=>'Le numéro de téléphone',
                    'adresse.required'=>'L\'adresse de livraison',
                    'tax.required'=>'Entrer les frais de livraison',
                    'time.required'=>'Entrer la date de livraison',

                ]

                );
                $normalizedPhone = preg_replace('/\s+/u', '', trim((string) $request->phone));
                $customersWithPhone = Costumer::query()
                    ->whereRaw("REPLACE(phone, ' ', '') = ?", [$normalizedPhone])
                    ->get();
                $exist = $customersWithPhone->first();

                if ($isAnonymousCustomer) {
                    $anonymousCustomer = $customersWithPhone->first(
                        fn (Costumer $customer) => $customer->isAnonymous()
                    );

                    if ($anonymousCustomer) {
                        $constumer = $anonymousCustomer;
                        $constumer->adresse = $request->adresse;
                        $constumer->save();
                    } elseif ($exist) {
                        return redirect()->route('productcart')
                            ->withErrors(['phone' => 'Ce numéro appartient déjà à une fiche client. Sélectionne cette fiche dans la liste.'])
                            ->withInput();
                    } else {
                        $constumer = Costumer::create([
                            'name' => $this->generateAnonymousCustomerName(),
                            'phone' => $normalizedPhone,
                            'email' => null,
                            'adresse' => $request->adresse,
                            'user_id' => Auth::id(),
                        ]);
                    }
                } elseif ($exist) {
                    return redirect()->route('productcart')
                        ->with('error', 'Le client existe déjà. Sélectionne-le dans la liste des clients.')
                        ->withInput();
                } else {
                    $constumer = Costumer::create([
                        'name' => $request->name,
                        'phone' => $normalizedPhone,
                        'email' => $request->email,
                        'adresse' => $request->adresse,
                        'user_id' => Auth::id(),
                    ]);
                }
                    $users =User::where('user_type','LVS')
                                   ->where('active',1)
                                  ->get();

                      $code = $this->getName();
                      $order = Order::create([
                          'costumer_id'=>$constumer->id,
                          'subtotal'=>round($request->subtotal) ,
                          'tax'=>$request->tax,
                          'date_order'=>$request->date_order,
                          'order_by'=>Auth::user()->id,
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

                      $this->updateCustomerFollowUp($request, $constumer->id);

                      foreach (Cart::instance('cart')->content() as $item) {
                          $orderItem = new OrderItem();
                          $orderItem->product_id = $item->id;
                          $orderItem->order_id = $order->id;
                          $orderItem->price = $item->price;
                          $orderItem->quantity = $item->qty;
                          $item->high_price ? $orderItem->high_price = $item->high_price : $orderItem->high_price=0 ;

                          $orderItem->save();
                      }

                        if (in_array(Auth::user()->user_type, ['ADMINUSER', 'MNG', 'SCR', 'VDS', 'CSA'], true)) {
                              if ($request->type=="PU") {
                                 foreach ($users as $key => $user) {
                                     try {
                                         Mail::to($user->email)->send(new TenaCos($order));
                                     } catch (\Exception $e) {
                                         Log::error("Erreur d’envoi à {$user->email} : " . $e->getMessage());
                                     }
                                 }
                              }
                        } else {
                            try {
                                Mail::to('crepinawity@gmail.com')->send(new ParthnerMail(URL::signedRoute('brouillons')));
                            } catch (\Throwable $e) {
                                Log::error("Erreur d'envoi email partenaire : " . $e->getMessage());
                            }
                        }

                     Cart::instance('cart')->destroy();
                     return redirect()->route('order')->with('messages','Commande effectuée');
         } else {
            if (! Costumer::whereKey($customerSelection)->exists()) {
                return redirect()->route('productcart')
                    ->withErrors(['costumer_id' => 'Sélectionne un client existant ou choisis une option de création.'])
                    ->withInput();
            }
            $request->validate([
                'tax'=>'required',
                'time'=>'required',
                'date_order'=>'required|date',
                'follow_up_at'=>'required|date',
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
                'date_order'=>$request->date_order,
                 'order_by'=>Auth::user()->id,
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

            $this->updateCustomerFollowUp($request, (int) $request->costumer_id);

            foreach (Cart::instance('cart')->content() as $item) {
                $orderItem = new OrderItem();
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->price = $item->price ;
                $item->high_price ? $orderItem->high_price = $item->high_price : $orderItem->high_price=0 ;
                $orderItem->quantity = $item->qty;
                $orderItem->save();
            }



                        if (in_array(Auth::user()->user_type, ['ADMINUSER', 'MNG', 'SCR', 'VDS', 'CSA'], true)) {
                              if ($request->type=="PU") {
                                # code...

                                foreach ($users as $key => $user) {

                                    try {
                            Mail::to($user->email)->send(new TenaCos($order));
                        } catch (\Exception $e) {
                            Log::error("Erreur d’envoi à {$user->email} : " . $e->getMessage());
                        }

                                //  Mail::to($user->email)->send(new TenaCos($order));

                                }
                                # code...
                              }
                        } else {
                            try {
                                Mail::to('crepinawity@gmail.com')->send(new ParthnerMail(URL::signedRoute('brouillons')));
                            } catch (\Throwable $e) {
                                Log::error("Erreur d'envoi email partenaire : " . $e->getMessage());
                            }
                        }

           Cart::instance('cart')->destroy();
           return redirect()->route('order')->with('messages','Commande effectuée');
         }


    }

    private function generateAnonymousCustomerName(): string
    {
        do {
            $name = 'CL-'.Str::upper(Str::random(6));
        } while (Costumer::where('name', $name)->exists());

        return $name;
    }

    private function updateCustomerFollowUp(Request $request, int $costumerId): void
    {
        $preference = CostumerContactPreference::firstOrNew(['costumer_id' => $costumerId]);
        $preference->next_follow_up_at = $preference->do_not_contact_at
            ? null
            : $request->input('follow_up_at');
        $preference->updated_by = Auth::id();
        $preference->save();
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
