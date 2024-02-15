<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pub;
use App\Models\RetraitPub;
use App\Models\TolalFondPub;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PubController extends Controller
{
    public function index(){
        $pubs=Pub::latest()->get();
        $totalfons= TolalFondPub::sum('totals');
        $startOfWeek = Carbon::now()->startOfWeek();

        $totalretrait= RetraitPub::where('created_at', '>=', $startOfWeek)
        ->sum('amount');
       return view('pourcentagelist.pub',compact('pubs','totalfons' ,'totalretrait'));
   }


   public function retraitpub(Request $request){
    try {
      //code...
      $soldefonds=TolalFondPub::where("etat",0)->first();
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
           RetraitPub::create(['amount'=>$request->amount,'raison'=>$request->raison]);
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
