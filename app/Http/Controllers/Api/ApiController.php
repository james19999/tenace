<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    //login système

    public function login (Request $request) {

       try {
         $validator=Validator::make($request->all(),[
              'email'=>'required|email',
              'password'=>'required',
         ]);

          if ($validator->fails()) {
            # code...
            return Response::json([
                 'status'=>false,
                 'message'=>$validator->getMessageBag()
            ]);
          } else {
            # code...

        $user = User::where('email', $request->email)
                          ->
                          where('active',true)
                           ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

         $token= $user->createToken($request->password)->plainTextToken;
         return Response::json([
            'token'=>$token,
            'user'=>$user,
            'status'=>true,
         ]);
          }

       } catch (\Throwable $th) {
        //throw $th;
        return Response::json(['message'=>$th->getMessage()]);
       }
    }

    public  function logout_user(Request $request){
        $logout=  $request->user()->currentAccessToken()->delete();

        try {
                if($logout){
                    return Response::json([
                        'status'=>true,
                        "message"=>"success"
                    ]);

                }else{
                    return Response::json([
                        'status'=>false,
                        "message"=>"errors"
                ]);
                }
        } catch (\Throwable $th) {
            return Response::json([
                'status'=>false,
                "message"=>"$th"
        ]);
        }

     }

    //end login système

    //ORDER Manage
     public function get_order_livrable(){
        try {
            //code...
            $orders=Order::where('status_order',0)
            ->where('status','ordered')
            ->latest()
            ->whereDate('created_at',Carbon::today())
            ->where('brouillon',1)
            ->where('type','PU')
            ->with(['costumer','user' ,'orderItems.product'])
            ->get();
          return Response::json(['status'=>true,'order'=>$orders]);
        } catch (\Throwable $th) {
            //throw $th;
            return Response::json(['status'=>false, $th->getMessage()]);

        }
     }

     public function order_detail($id){
        try {
            // Retrieve the order with its associated order items and products
            $order = Order::with(['orderItems.product'])->findOrFail($id);

            // Extract the product details from the order items
            $products = $order->orderItems->map(function ($orderItem) {
                return [
                    'product_id' => $orderItem->product->id,
                    'product_name' => $orderItem->product->name,
                    'price' => $orderItem->price,
                    'quantity' => $orderItem->quantity,
                ];
            });

            return response()->json(['status' => true, 'order' => $order, 'products' => $products]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()]);
        }
     }



     public function check_livraison_take($id){
        $orders=Order::findOrfail($id);

          if($orders->take==false){

              $order =Order::where('user_id',Auth::user()->id)
                     ->where('status_order',true)
                      ->whereDate('created_at',Carbon::today())
                      ->get();

                  if($order->count()==0){
                      $orders->user_id=Auth::user()->id;
                      $orders->status_order=true;
                      $orders->take=true;
                      $orders->save();
                      return Response::json(['status'=>true ,'messages'=>"Commande ajouter"]);
                  }else{
                      return  Response::json(['status'=>false,'messages'=>"Vous avez des commandés non livrées"]);
                  }
          }else{
            return  Response::json(['status'=>400,'messages'=>"Commande déjà prise"]);

          }

      }

      public function auth_user_livrable(){
          try {
            //code...
            $orders =Order::where('user_id',Auth::user()->id)
            ->where('status_order',true)
            ->whereDate('created_at',Carbon::today())
            ->with(['costumer','user' ,'orderItems.product'])
            ->get();
             return Response::json(['status'=>true,'orders'=>$orders]);
          } catch (\Throwable $th) {
            //throw $th;
          }
      }


      public function auth_user_livrable_list(){
        try {
          //code...
          $orders =Order::where('user_id',Auth::user()->id)
          ->where('status','delivered')
          ->where('type','PU')
          ->where('brouillon',1)
          ->whereDate('created_at',Carbon::today())
          ->with(['costumer','user' ,'orderItems.product'])
          ->get();
           return Response::json(['status'=>true,'orders'=>$orders]);
        } catch (\Throwable $th) {
          //throw $th;
        }
    }









      public function change_order_status_user(Request $request,$id){
          try {
            //code...
            $orders=Order::findOrfail($id);

             if($orders){
                 if($request->status=="canceled"){
                    $validator=Validator::make($request->all(),['motif'=>'required'],
                    ['motif.required' => 'Entrer le motif d\'annulation.']);



                   $orders->status=$request->status;
                   $orders->motif=$request->motif;
                   $orders->user_id=Auth::user()->id;
                   $orders->status_order=false;
                   $orders->take=false;
                   $orders->save();
                  return Response::json(['status'=>true,'messages'=>'Commande annuler']);

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

                   $orders->save();
                  return Response::json(['status'=>true,'messages'=>'Commande valider']);

                 }

             }else{
                return Response::json(['status'=>false,'messages'=>'error']);


             }
          } catch (\Throwable $e) {
            //throw $th;
            return response()->json(['status' => false, 'error' => $e->getMessage()]);

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
    //end order
}
