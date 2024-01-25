<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\Product;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class EcomController extends Controller
{
    //
  public function register_costumer(Request $request){
      $validate=Validator::make($request->all(),[
        'name'=>'required',
        'phone'=>'required|unique:costumers,phone',
        'adresse'=>'required',
      ]);
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
                    return Response::json(['token'=>$token,'status'=>true]);
                }else{
                    return Response::json(['messages'=>'error','status'=>false]);

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

    public function place_order (Request $request ) {
        try {
            //code...
            $code = $this->getName();
            $order = Order::create([
                'costumer_id'=>1,
                'subtotal'=>round($request->subtotal),
                'tax'=>0,
                'time'=>Carbon::now(),
                'remis'=>0,
                'total'=>intval($request->subtotal),
                'montant'=>intval($request->subtotal),
                'code' => $code,
                'brouillon' =>0 ,
                'created_user' =>1,
                'avis'=>"hhdhd",
                'type'=>"PR"
            ]);
                 $order->orderItems()->create([
                    'product_id' => $request->product_id,
                    'price' =>$request->price,
                    'quantity' =>$request->quantity,
                    'high_price' =>0,
                ]);


            return Response::json(['status'=>true,'order'=>$order]);
        } catch (\Throwable $th) {
            //throw $th;
            return Response::json(['status'=>false,'message'=>$th->getMessage()]);

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
