<?php

namespace App\Http\Controllers\Brouillons;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;


class BrouillonController extends Controller
{

    public function index(){

        $orders=Order::latest()
        ->orderby('created_at','DESC')
         ->where('brouillon',0)
        ->get();

        return view('brouillons.index',compact('orders'));
    }


    public function show($id){

        $Orders=Order::findOrfail($id);
       return  view('brouillons.show',compact('Orders'));
    }


    public function unlock_brouillon($id){

        $order=Order::findOrfail($id);
        if ($order) {

            if($order->brouillon==0){
             $order->brouillon=1;
             $order->save();
             return back();

            }
        }

      }

      public function parthners()
      {
          $users=User::where('user_type','PT')->latest()->get();

          $countries = WestAfricaShippingCatalog::countries();

          return view('parthners.index',compact('users', 'countries'));
      }

      public function storePartner(Request $request)
      {
          $data = $request->validate([
              'name' => ['required', 'string', 'max:255'],
              'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
              'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
              'adresse' => ['required', 'string', 'max:255'],
              'country_code' => ['required', Rule::in(array_keys(WestAfricaShippingCatalog::countries()))],
              'password' => ['required', 'string', 'min:8', 'confirmed'],
          ]);

          User::create([
              'name' => $data['name'],
              'email' => $data['email'],
              'phone' => $data['phone'],
              'adresse' => $data['adresse'],
              'country_code' => strtoupper($data['country_code']),
              'password' => Hash::make($data['password']),
              'user_type' => User::ROLE_PARTNER,
          ]);

          return redirect()->to(route('parthners', [], false))
              ->with('messages', 'Le compte partenaire a été créé.');
      }


      public function parthners_order () {
        $orders=Order::where('created_user',Auth::user()->id)
        // ->whereDate('created_at',Carbon::today())
        ->where('created_at','>', Carbon::now()->subDays(2))
        ->get();
         $deliveredOrdersCount =$orders->where('status','delivered')->count();
         $totalCommission=Wallet::where('user_id',Auth::user()->id)->sum('balance');
        return view('parthners.parthners_order',compact('orders','deliveredOrdersCount','totalCommission'));
      }
}
