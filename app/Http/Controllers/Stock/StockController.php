<?php

namespace App\Http\Controllers\Stock;

use App\Models\Product;
use App\Models\OutStock;
use App\Models\EnterStock;
use App\Models\StockAuditLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
            'user_id'=>Auth::user()->id,
    ]);
    $Products->price_by+=$request->amount;
   $Products->save();
   $this->audit($request, 'legacy.stock_received', $Products, null, [
       'product_name' => $Products->name,
       'quantity' => (float) $request->qt_stock,
       'amount' => (float) $request->amount,
       'warehouse_catalogue_quantity' => (float) $Products->qt_initial,
   ]);
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
          'user_id'=>Auth::user()->id,

      ]);
      if($output->qt_stock<=$Products->qt_initial){

          $Products->qt_initial-=$output->qt_stock;
          $Products->save();
          $this->audit($request, 'legacy.stock_issued', $Products, [
              'warehouse_catalogue_quantity' => (float) ($Products->qt_initial + $output->qt_stock),
          ], [
              'product_name' => $Products->name,
              'quantity' => (float) $output->qt_stock,
              'reason' => $output->raison,
              'warehouse_catalogue_quantity' => (float) $Products->qt_initial,
          ]);
      } else {
          $this->audit($request, 'legacy.stock_issue_rejected', $Products, [
              'warehouse_catalogue_quantity' => (float) $Products->qt_initial,
          ], [
              'product_name' => $Products->name,
              'requested_quantity' => (float) $output->qt_stock,
              'reason' => $output->raison,
              'message' => 'Quantité demandée supérieure au stock catalogue disponible.',
          ], 'failure');
      }
     session()->flash('message', "vous avez fait une  sortie de stock de $output->qt_stock quantité Actuel  $Products->qt_initial");

     return  back();


  }

  public function update_product_price(Request $request,  $id){

    $product= Product::find($id);
     $oldPrice = $product->high_price;
     $product->high_price= $request->high_price;
     $product->save();
     StockAuditLog::query()->create([
         'user_id' => Auth::id(), 'actor_role' => Auth::user()?->user_type,
         'action' => 'product.wholesale_price_updated', 'subject_type' => Product::class,
         'subject_id' => $product->id,
         'before_values' => ['high_price' => $oldPrice],
         'after_values' => ['product_name' => $product->name, 'high_price' => $product->high_price],
         'origin' => 'screen', 'result' => 'success',
     ]);
     session()->flash('message', "Succès");

    return  back();
   }

   private function audit(Request $request, string $action, Product $product, ?array $before, ?array $after, string $result = 'success'): void
   {
       StockAuditLog::query()->create([
           'user_id' => $request->user()->id, 'actor_role' => $request->user()->user_type,
           'action' => $action, 'subject_type' => Product::class, 'subject_id' => $product->id,
           'before_values' => $before, 'after_values' => $after,
           'origin' => 'legacy_product_detail', 'result' => $result,
           'request_id' => $request->headers->get('X-Request-Id'),
       ]);
   }
}
