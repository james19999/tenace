<?php

namespace App\Http\Controllers\Stock;

use App\Models\Product;
use App\Models\EnterStock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OutStock;

class StockController extends Controller
{
   public function show_product($id){

    $product= Product::find($id);
    $totalEnterStock=$product->enterstocks()->sum('qt_stock');
    $totalOuttock=$product->outstocks()->sum('qt_stock');
    return view ('stocks.show_product',compact('product','totalEnterStock','totalOuttock'));
   }


public  function enter_stocks(Request $request ,$id){
    $request->validate([
        'qt_stock'=>'required|min:0',
        'amount'=>'required|min:0',
    ]);
    $Products=Product::findOrfail($id);
    $Products->qt_initial+=$request->qt_stock;
    EnterStock::create([
            'qt_stock'=>$request->qt_stock,
            'amount'=>$request->amount,
            'product_id'=>$request->product_id,
    ]);
    $Products->price_by+=$request->amount;
   $Products->save();
   session()->flash('message', "vous avez fait une entrée de stock de $request->qt_stock quantité Actuel  $Products->qt_initial");
   return  back();
}


public  function out_stock(Request $request,$id){

    $request->validate([
        'qt_stock' =>'required|integer|min:0' ,
        'raison'=>'required' ,
      ]);

    $Products=Product::findOrfail($id);
    $output=OutStock::create([
          'qt_stock'=>$request->qt_stock,
          'raison'=>$request->raison,
          'product_id'=>$Products->id,
      ]);
      if($output->qt_stock<=$Products->qt_initial){

          $Products->qt_initial-=$output->qt_stock;
          $Products->save();
      }
     session()->flash('message', "vous avez fait une  sortie de stock de $output->qt_stock quantité Actuel  $Products->qt_initial");

     return  back();


  }
}
