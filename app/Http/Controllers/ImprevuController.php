<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Imprevu;
use App\Models\RetraitImprevu;
use App\Models\TolalFondImprevu;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ImprevuController extends Controller
{
    //
    public function index(){
        $imprevus=Imprevu::latest()->get();
        $totalfons= TolalFondImprevu::sum('totals');
        $startOfWeek = Carbon::now()->startOfWeek();

        $totalretrait= RetraitImprevu::where('created_at', '>=', $startOfWeek)
        ->sum('amount');
       return view('pourcentagelist.imprevu',compact('imprevus','totalfons' ,'totalretrait'));
   }

   public function retraitimprevu(Request $request){
    try {
      //code...
      $soldefonds=TolalFondImprevu::where("etat",0)->first();
      if($soldefonds==null){
          session()->flash("messages","Vous n'avez pas encore de fonds en attente");
          return back();
      }else{
           if(intval($soldefonds->totals)  < intval ($request->amount)){
            session()->flash("messages","Le montant saisi est supérieur au solde disponible");
            return back();
        }else{
          $new_montant=intval($soldefonds->totals) - intval($request->amount);
          $soldefonds->totals=$new_montant;
           RetraitImprevu::create(['amount'=>$request->amount,'raison'=>$request->raison]);
           $soldefonds->save();
          session()->flash("messages","Retrait effectuer");

           return back();
           }

      }
       } catch (\Throwable $th) {
      //throw $th;
    }
 }

}
