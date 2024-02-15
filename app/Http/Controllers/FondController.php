<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Fond;
use App\Models\Retrait;
use App\Models\TotalFond;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FondController extends Controller
{

    public function index(){
      $fonds=Fond::latest()->get();
       $totalfons= TotalFond::sum('totals');
       $startOfWeek = Carbon::now()->startOfWeek();

       $totalretrait= Retrait::where('created_at', '>=', $startOfWeek)
       ->sum('amount');
       return view('pourcentagelist.roulement',compact('fonds','totalfons' ,'totalretrait'));
   }

   public function retrait(Request $request){
      try {
        //code...
        $soldefonds=TotalFond::where("etat",0)->first();
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
             Retrait::create(['amount'=>$request->amount,'raison'=>$request->raison]);
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
