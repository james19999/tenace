<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\URL;

use App\Http\Controllers\Controller;
use App\Mail\ParthnerMail;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\Product;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class EcomController extends Controller
{
    //
  public function register_costumer(Request $request){
     try {
        //code...
        $validate=Validator::make($request->all(),[
          'name'=>'required',
          'phone'=>'required|unique:costumers,phone',
          'adresse'=>'required',
        ],
        ['phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',]
    );
          if($validate->fails()){
              return Response::json(['status'=>false, 'messages'=>$validate->getMessageBag()]);
          }else{
              Costumer::create([
                   'name'=>$request->name,
                   'phone'=>$request->phone,
                   'email'=>$request->email,
                   'adresse'=>$request->adresse,
                   'user_id'=>0,
              ]);
              return Response::json(['status'=>true, 'messages'=>"Votre compte à été bien créer"]);

          }
     } catch (\Throwable $th) {
        return Response::json(['status'=>false, 'messages'=>$th->getMessage()]);

     }

  }
  public function login_costomer(Request $request){
    try {
        //code...
         $validate=Validator::make($request->all(),[
              'phone'=>'required',

         ]);
          if($validate->fails()){
              return  Response::json([$validate->getMessageBag(),'status'=>false]);
          }else{
              $costumercompany =Costumer::
              where('phone',$request->phone)->
              first();

                if ($costumercompany){
                    $token= $costumercompany->createToken("costumer",['companiecostumer'])->plainTextToken;
                    return Response::json(['token'=>$token,'status'=>true ,'user'=>$costumercompany]);
                }else{
                    return Response::json(['messages'=>'Numéro de téléphone incorrect','status'=>false]);

                }
             }
            } catch (\Throwable $th) {
                //throw $th;
                return Response::json(['messages'=>'error','status'=>false]);

            }
  }

    public function all_products(){
        // $product=Product::with('category')->get()->sortByDesc('created_at');
        $product=Product::latest()->get();

        return Response::json(['status'=>true,'product'=>$product]);
    }


    public function auth_costumer_list(){

        try {
            $startOfWeek = Carbon::now()->startOfWeek();
          //code...
          $orders =Order::where('costumer_id',Auth::user()->id)
          ->where('created_at', '>=', $startOfWeek)
          ->with(['costumer','user' ,'orderItems.product'])
          ->latest()->get();
           return Response::json(['status'=>true,'order'=>$orders]);
        } catch (\Throwable $th) {
          //throw $th;
        }
    }

    public function place_order(Request $request) {
        try {
            $code = $this->getName(); // Assurez-vous que getName() génère un code unique
            $orderData = [
                'costumer_id' => Auth::user()->id,
                'subtotal' => round($request->subtotal),
                'tax' => 0,
                'time' => Carbon::now(),
                'remis' => 0,
                'total' => intval($request->total),
                'montant' => intval($request->montant),
                'code' => $code,
                'brouillon' => 0,
                'created_user' => Auth::user()->id,
                'avis' => " ",
                'type' => "PR"
            ];

            // Création de la commande
            $order = Order::create($orderData);

            // Associer les éléments de commande à la commande
            foreach ($request->order_items as $item) {
                $orderItem = [
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'high_price' => 0,
                ];

                $order->orderItems()->create($orderItem);
                Mail::to(env('Mail_User'))->send(new ParthnerMail(URL::signedRoute('brouillons')));

            }

            return response()->json(['status' => true, 'order' => $order]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()]);
        }
    }


   public function setcostomerinfo(Request $request, $id){

    try {
        //code...
       $order=Order::where('id',$id)->first();
      if ($order) {
        # code...
        // $costumer =Costumer::where('id',$order->id)->first();
         $order->time=$request->time;

         $order->costumer()->update([
            'adresse'=>$request->adresse
             ]) ;
          $order->save();

         return Response::json(['status'=>true,'order'=>$order]);

      }
    } catch (\Throwable $th) {
        //throw $th;
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


}
