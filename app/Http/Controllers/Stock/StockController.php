<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{

    public function index()
    {

         $Products=Product::orderBy('created_at','DESC')->get();

          $totale=$this->calcPrice();
          $totales=$this->calcPrices();
        return  view('stocks.stock_product_list',compact('Products','totale','totales'));
    }



    public function calcPrice(){
        $products = Product::all();
        $total = 0;
        foreach($products as $product){
            $total += $product->price_market * $product->qts_sell;
        }
        return $total;
    }
    public function calcPrices(){
        $products = Product::all();
        $total = 0;
        foreach($products as $product){
            $total += $product->price * $product->qts_sell;
        }
        return $total;
    }
}
