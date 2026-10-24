<?php

namespace App\Http\Controllers\Admins;

use App\Models\User;
use App\Mail\TenaCos;
use App\Mail\TenanCos;
use App\Models\Costumer;
use App\Models\CostumerContactPreference;
use App\Models\Product;
use App\Models\QuantityPromotionSetting;
use App\Mail\ParthnerMail;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Orders\OrderItem;
use App\Models\TransportCompany;
use App\Support\WestAfricaShippingCatalog;
use App\Services\StockModuleSettingsService;
use App\Models\StockLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Gloudemans\Shoppingcart\Facades\Cart;

class CheckController extends Controller
{

    public function shippingCities(Request $request, string $country): JsonResponse
    {
        $countryCode = strtoupper($country);
        abort_unless(array_key_exists($countryCode, WestAfricaShippingCatalog::countries()), 404);

        $query = trim((string) $request->query('q', ''));
        $normalize = static function (string $value): string {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            return mb_strtolower($ascii === false ? $value : $ascii);
        };
        $normalizedQuery = $normalize($query);
        $cities = collect(WestAfricaShippingCatalog::cities($countryCode))
            ->filter(fn (string $city): bool => $normalizedQuery === '' || str_contains($normalize($city), $normalizedQuery))
            ->take(50)
            ->map(fn (string $city): array => ['id' => $city, 'text' => $city])
            ->values();

        return response()->json(['results' => $cities, 'pagination' => ['more' => false]]);
    }

    public function brouillons () {
        $auth = Auth::user();

        if (in_array($auth->user_type, ['ADMINUSER', 'MNG', 'SCR', 'VDS', 'CSA'], true)) {
            return 1;
        }

        return 0;
    }

    public function palce_order(Request $request) {

         $cartItems = Cart::instance('cart')->content();
         if ($cartItems->isEmpty()) {
            return redirect()->route('productcart')->with('error', 'Ajoute au moins un produit avant de valider la commande.');
         }

         $request->validate([
             'tax' => ['required', 'integer', 'min:0'],
             'remis' => ['required', 'integer', 'between:0,100'],
             'is_wholesale' => ['nullable', 'boolean'],
         ], [
             'tax.required' => 'Entre les frais de livraison.',
             'tax.integer' => 'Les frais de livraison doivent être un montant entier.',
             'remis.required' => 'Entre le pourcentage de remise.',
             'remis.between' => 'La remise doit être comprise entre 0 et 100 %.',
         ]);

         $isWholesaleOrder = Auth::user()?->user_type !== User::ROLE_PARTNER
             && $cartItems->groupBy(fn ($item) => (int) $item->id)->contains(function ($items, $productId) {
                 $quantity = (int) $items->sum('qty');
                 $product = Product::query()->with('quantityPriceTiers')->find($productId);
                 return $product && $product->quantityPriceTiers->contains(fn ($tier) => $quantity >= (int) $tier->min_quantity);
             });
         $promotion = QuantityPromotionSetting::current();
         if ($isWholesaleOrder && Auth::user()?->user_type === User::ROLE_PARTNER) {
             return redirect()->route('productcart')->withErrors(['is_wholesale' => 'Le tarif grossiste de la boutique n’est pas disponible pour les commandes partenaires.'])->withInput();
         }
         $qualifiedWholesaleProduct = false;
         if ($isWholesaleOrder) {
             $wholesaleLocationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;
             $wholesaleLocation = $wholesaleLocationId
                 ? StockLocation::query()->whereKey($wholesaleLocationId)->where('type', 'shop')->where('is_active', true)->first()
                 : null;
             if (! $wholesaleLocation) {
                 return redirect()->route('productcart')->withErrors(['is_wholesale' => 'La boutique de stock grossiste n’est pas configurée. Contacte l’administration avant de passer cette commande.'])->withInput();
             }
             foreach ($cartItems->groupBy(fn ($item) => (int) $item->id) as $productId => $items) {
                 $quantity = (int) $items->sum('qty');
                 $product = Product::query()->with('quantityPriceTiers')->find($productId);
                 if (! $product) continue;
                 $available = (float) DB::table('stock_movement_lines as sml')
                     ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                     ->where('sm.status', 'posted')->where('sml.location_id', $wholesaleLocation->id)
                     ->where('sml.product_id', $productId)->sum('sml.quantity_delta');
                 if ($available < $quantity) {
                     return redirect()->route('productcart')->withErrors(['is_wholesale' => 'Stock insuffisant dans la boutique grossiste pour « '.$product->name.' » (disponible : '.max(0, (int) $available).').'])->withInput();
                 }
                 $qualifiedWholesaleProduct = $qualifiedWholesaleProduct || $product->quantityPriceTiers->contains(fn ($tier) => $quantity >= $tier->min_quantity);
                 $unitPrice = $product->unitPriceForQuantity($quantity);
                 $promotionPricing = $promotion->priceAfterPromotionForProductQuantity((int) $productId, $quantity, $unitPrice);
                 if ($promotionPricing) $unitPrice = $promotionPricing['unit_price'];
                 foreach ($items as $item) Cart::instance('cart')->remove($item->rowId);
                 Cart::instance('cart')->add($product->id, $items->first()->name, $quantity,
                     $unitPrice)->associate(Product::class);
             }
             if (! $qualifiedWholesaleProduct) {
                 return redirect()->route('productcart')->withErrors(['is_wholesale' => 'Aucun produit du panier n’atteint le seuil de commande grossiste configuré.'])->withInput();
             }
             $cartItems = Cart::instance('cart')->content();
         } elseif (Auth::user()?->user_type !== User::ROLE_PARTNER) {
             foreach ($cartItems->groupBy(fn ($item) => (int) $item->id) as $productId => $items) {
                 $quantity = (int) $items->sum('qty');
                 $product = Product::query()->find($productId);
                 if (! $product) continue;
                 $unitPrice = (int) $product->price;
                 $promotionPricing = $promotion->priceAfterPromotionForProductQuantity((int) $productId, $quantity, $unitPrice);
                 if ($promotionPricing) $unitPrice = $promotionPricing['unit_price'];
                 foreach ($items as $item) Cart::instance('cart')->remove($item->rowId);
                 Cart::instance('cart')->add($product->id, $items->first()->name, $quantity, $unitPrice)->associate(Product::class);
             }
             $cartItems = Cart::instance('cart')->content();
         }

         // Recalculate from the session cart. Never trust the subtotal submitted by the browser.
         $subtotal = (float) $cartItems->sum(fn ($item) => (float) $item->price * (int) $item->qty);
         $discountRate = (int) $request->input('remis');
         $itemsTotal = (int) round($subtotal - ($subtotal * $discountRate / 100));
         $amountDue = $itemsTotal + (int) $request->input('tax');
         $shippingCountries = WestAfricaShippingCatalog::countries();
         $deliveryMode = (string) $request->input('delivery_mode', 'local');
         $shippingCountry = (string) $request->input('shipping_country', '');
         $request->validate([
             'delivery_mode' => ['required', Rule::in(['local', 'expedition'])],
             'transport_company' => ['required_if:delivery_mode,expedition', 'nullable', 'string', 'max:120'],
             'shipping_country' => ['required_if:delivery_mode,expedition', 'nullable', Rule::in(array_keys($shippingCountries))],
             'shipping_city' => ['required_if:delivery_mode,expedition', 'nullable', 'string', 'max:120'],
         ], [
             'transport_company.required_if' => 'Indique le transporteur ou le moyen de transport.',
             'shipping_country.required_if' => 'Choisis le pays de destination.',
             'shipping_city.required_if' => 'Choisis la ville de destination.',
         ]);
         $transportCompany = null;
         if ($deliveryMode === 'expedition') {
             $transportCompany = TransportCompany::firstOrCreate([
                 'name' => trim((string) $request->input('transport_company')),
             ]);
         }
         $shippingAttributes = [
             'delivery_mode' => $deliveryMode,
             'transport_company_id' => $transportCompany?->id,
             'shipping_country' => $deliveryMode === 'expedition' ? $shippingCountry : null,
             'shipping_city' => $deliveryMode === 'expedition' ? $request->input('shipping_city') : null,
         ];
         $request->validate([
             'paid_amount' => ['nullable', 'integer', 'min:0', 'max:'.$amountDue],
         ], [
             'paid_amount.max' => 'Le montant déjà payé ne peut pas dépasser le total de la commande.',
         ]);
         $paidAmount = (int) $request->input('paid_amount', 0);
         $customerSelection = (string) $request->input('costumer_id', '');
         $isAnonymousCustomer = $customerSelection === 'anonymous';

         if ($isAnonymousCustomer) {
                $request->validate([
                    'phone'=>'required|string|max:50',
                    'adresse'=>'required|string|max:1000',
                    'tax'=>'required',
                    'time'=>'required',
                    'date_order'=>'required|date',
                    'remis'=>'required',
                    'follow_up_at'=>'required|date',
                ],
                [
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
                    $users =User::where('user_type','LVS')
                                   ->where('active',1)
                                  ->get();

                      $code = $this->getName();
                      $order = Order::create([
                          'costumer_id'=>$constumer->id,
                          'subtotal'=>round($subtotal),
                          'tax'=>$request->tax,
                          'date_order'=>$request->date_order,
                          'order_by'=>Auth::user()->id,
                          'time'=>$request->time,
                          'remis'=>$discountRate,
                          'total'=>$itemsTotal,
                          'montant'=>$amountDue,
                          'paid_amount'=>$paidAmount,
                          ...$shippingAttributes,
                          'code' => $code,
                          'brouillon' =>$this->brouillons() ,
                          'created_user' =>Auth::user()->id,
                          'avis'=>$request->avis,
                          'type'=>$request->type
                          ,'is_wholesale' => $isWholesaleOrder
                      ]);

                      if ($isWholesaleOrder && $paidAmount > 0) {
                          $order->payments()->create([
                              'stage' => 'at_order', 'method' => 'manual', 'amount' => $paidAmount,
                              'status' => 'collected', 'collected_by' => Auth::id(), 'collected_at' => now(),
                              'notes' => 'Montant déjà payé renseigné lors de la création de la commande.',
                          ]);
                      }

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
                    ->withErrors(['costumer_id' => 'Sélectionne un client existant ou choisis Client anonyme.'])
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
                'subtotal'=>round($subtotal),
                'tax'=>$request->tax,
                'time'=>$request->time,
                'remis'=>$discountRate,
                'date_order'=>$request->date_order,
                 'order_by'=>Auth::user()->id,
                'total'=>$itemsTotal,
                'montant'=>$amountDue,
                'paid_amount'=>$paidAmount,
                ...$shippingAttributes,
                'code' => $code,
                'brouillon' =>$this->brouillons() ,
                'created_user' =>Auth::user()->id,
                'avis'=>$request->avis,
                'type'=>$request->type
                ,'is_wholesale' => $isWholesaleOrder
            ]);

            if ($isWholesaleOrder && $paidAmount > 0) {
                $order->payments()->create([
                    'stage' => 'at_order', 'method' => 'manual', 'amount' => $paidAmount,
                    'status' => 'collected', 'collected_by' => Auth::id(), 'collected_at' => now(),
                    'notes' => 'Montant déjà payé renseigné lors de la création de la commande.',
                ]);
            }

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
