<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EcomController extends Controller
{
    //

    public function AddToCart(Request $request) {
      return  Cart::instance('cart')->add($request->id, $request->name,1,$request->price)->associate(Product::class);

     }

     public function CartList () {
       return Cart::instance('cart')->content();
     }
}
