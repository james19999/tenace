<?php

namespace App\Http\Controllers\Api;

use App\Traits\MyTrait;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Orders\Order;
use App\Models\CourierOrderCancellationRequest;
use App\Notifications\CourierCancellationRequestNotification;
use App\Services\StockDeliveryService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    use MyTrait;
    //login système

    // public function login (Request $request) {

    //    try {
    //      $validator=Validator::make($request->all(),[
    //           'email'=>'required|email',
    //           'password'=>'required',
    //      ]);

    //       if ($validator->fails()) {
    //         # code...
    //         return Response::json([
    //              'status'=>false,
    //              'message'=>$validator->getMessageBag()
    //         ]);
    //       } else {
    //         # code...

    //     $user = User::where('email', $request->email)
    //                       ->
    //                       where('active',true)
    //                        ->first();

    //     if (! $user || ! Hash::check($request->password, $user->password)) {
    //         throw ValidationException::withMessages([
    //             'email' => ['The provided credentials are incorrect.'],
    //         ]);
    //     }

    //      $token= $user->createToken($request->password)->plainTextToken;
    //      return Response::json([
    //         'token'=>$token,
    //         'user'=>$user,
    //         'status'=>true,
    //      ]);
    //       }

    //    } catch (\Throwable $th) {
    //     //throw $th;
    //     return Response::json(['message'=>$th->getMessage()]);
    //    }
    // }

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
        abort_unless(Auth::user()?->user_type === User::ROLE_LIVREUR, 403);
        $result = DB::transaction(function () use ($id): string {
            User::query()->whereKey(Auth::id())->lockForUpdate()->firstOrFail();
            $order = Order::query()->lockForUpdate()->findOrFail($id);
            if ($order->take || $order->status_order || filled($order->user_id) || $order->status !== 'ordered') {
                return 'taken';
            }

            $hasDeliveryInProgress = Order::query()->where('user_id', (string) Auth::id())
                ->where('status', 'ordered')->exists();
            if ($hasDeliveryInProgress) {
                return 'busy';
            }

            $order->user_id = (string) Auth::id();
            $order->assigned_at = now();
            $order->status_order = true;
            $order->take = true;
            $order->save();

            return 'assigned';
        });

        if ($result === 'busy') {
            return Response::json(['status' => false, 'messages' => 'Tu as déjà une commande en cours ou en attente. Termine-la ou attends la décision du responsable avant d’en prendre une autre.'], 409);
        }
        if ($result === 'taken') {
            return Response::json(['status' => false, 'messages' => 'Cette commande a déjà été prise.'], 409);
        }

        return Response::json(['status' => true, 'messages' => 'Commande ajoutée.']);

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









      public function change_order_status_user(Request $request,$id, StockDeliveryService $stockDelivery){
          try {
            //code...
            $orders=Order::findOrfail($id);

             if($orders){
                 if ($request->status === 'waiting' && $request->user()?->user_type === User::ROLE_LIVREUR) {
                    $validator = Validator::make($request->all(), ['motif' => ['required', 'string', 'min:3', 'max:500']], [
                        'motif.required' => 'Indique pourquoi tu demandes la mise en attente de cette commande.',
                        'motif.min' => 'Le motif doit contenir au moins 3 caractères.',
                    ]);
                    if ($validator->fails()) {
                        return Response::json(['status' => false, 'messages' => $validator->errors()->first()], 422);
                    }
                    if ((string) $orders->user_id !== (string) $request->user()->id || $orders->status !== 'ordered') {
                        return Response::json(['status' => false, 'messages' => 'Cette commande n’est plus sous ta responsabilité.'], 409);
                    }
                    $waitingRequest = DB::transaction(function () use ($orders, $request): CourierOrderCancellationRequest {
                        $lockedOrder = Order::query()->whereKey($orders->id)->lockForUpdate()->firstOrFail();
                        if ((string) $lockedOrder->user_id !== (string) $request->user()->id || $lockedOrder->status !== 'ordered') {
                            abort(409, 'Cette commande n’est plus sous ta responsabilité.');
                        }
                        if (CourierOrderCancellationRequest::query()->where('order_id', $lockedOrder->id)->where('status', 'pending')->exists()) {
                            throw ValidationException::withMessages(['motif' => 'Une demande est déjà en attente de la décision du responsable.']);
                        }
                        return CourierOrderCancellationRequest::query()->create([
                            'order_id' => $lockedOrder->id,
                            'courier_id' => $request->user()->id,
                            'request_type' => 'waiting',
                            'reason' => trim((string) $request->input('motif')),
                            'was_delivery_waiting' => (bool) $lockedOrder->delivery_waiting,
                            'status' => 'pending',
                        ]);
                    });
                    Notification::send(
                        User::query()->where('active', true)->whereIn('user_type', User::ORDER_CANCELLATION_REVIEW_ROLES)->get(),
                        new CourierCancellationRequestNotification($waitingRequest->load(['order', 'courier']))
                    );
                    return Response::json(['status' => true, 'messages' => 'Ta demande de mise en attente a été envoyée au responsable. La commande reste bloquée jusqu’à sa décision.']);
                 }
                 if($request->status=="canceled"){
                    $validator=Validator::make($request->all(),['motif'=>'required'],
                    ['motif.required' => 'Entrer le motif d\'annulation.']);
                    if ($validator->fails()) {
                        return Response::json(['status' => false, 'messages' => $validator->errors()->first()], 422);
                    }

                    if ($request->user()?->user_type === User::ROLE_LIVREUR) {
                        if ((string) $orders->user_id !== (string) $request->user()->id || $orders->status !== 'ordered') {
                            return Response::json(['status' => false, 'messages' => 'Cette commande n’est plus sous ta responsabilité.'], 409);
                        }
                        $cancellationRequest = DB::transaction(function () use ($orders, $request): CourierOrderCancellationRequest {
                            $lockedOrder = Order::query()->whereKey($orders->id)->lockForUpdate()->firstOrFail();
                            if ((string) $lockedOrder->user_id !== (string) $request->user()->id || $lockedOrder->status !== 'ordered') {
                                abort(409, 'Cette commande n’est plus sous ta responsabilité.');
                            }
                            if (CourierOrderCancellationRequest::query()->where('order_id', $lockedOrder->id)->where('status', 'pending')->exists()) {
                                abort(409, 'Une demande d’annulation attend déjà la décision du responsable.');
                            }
                            return CourierOrderCancellationRequest::query()->create([
                                'order_id' => $lockedOrder->id,
                                'courier_id' => $request->user()->id,
                                'reason' => trim((string) $request->input('motif')),
                                'was_delivery_waiting' => (bool) $lockedOrder->delivery_waiting,
                                'status' => 'pending',
                            ]);
                        });
                        Notification::send(
                            User::query()->where('active', true)->whereIn('user_type', User::ORDER_CANCELLATION_REVIEW_ROLES)->get(),
                            new CourierCancellationRequestNotification($cancellationRequest->load(['order', 'courier']))
                        );
                        return Response::json(['status' => true, 'messages' => 'Ta demande attend la validation du responsable. La commande reste sous ta responsabilité.']);
                    }

                    app(\App\Services\FinanceOrderCashIntegrationService::class)
                        ->refundCashForCanceledOrder($orders, $request->user());

                   $orders->status=$request->status;
                   $orders->motif=$request->motif;
                   $orders->user_id=Auth::user()->id;
                   $orders->status_order=false;
                   $orders->take=false;
                   $orders->save();
                  return Response::json(['status'=>true,'messages'=>'Commande annuler']);

                 }else if($request->status=="delivered"){
                    $order=Order::with('orderItems')->findOrFail($orders->id);
                    if (! $stockDelivery->deliver($order, (int) Auth::id())) {
                        return Response::json(['status'=>true,'messages'=>'Cette commande a déjà été validée comme livrée']);
                    }
                   $orders->status=$request->status;
                   $orders->user_id=Auth::user()->id;
                   $orders->status_order=false;
                   $orders->take=false;

                   $this->commission($order);

                   $this->CalculCommissions($order);
                   $this->CalculTauxPub($order);
                   $this->CalculImprevu($order);
                   $this->CalculTauxEpargne($order);
                   $this->CalculTauxFond($order);

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
