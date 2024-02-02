<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PourcentageCommission;
use Illuminate\Http\Request;

class PercentController extends Controller
{
      public function index()
     {
         $percents=PourcentageCommission::all();
         return view('percent.index',compact('percents'));
     # code...
     }

     public function create(Request $request){
        $request->validate([
            'amount' => 'required',
            'percent' => 'required',
          ]);

           if (PourcentageCommission::count()<6) {
            # code...
            PourcentageCommission::create($request->all());
            session()->flash('messages','Le pourcentage a été ajoutée avec succès');
            return back();

           } else {
            # code...
            session()->flash('messages','le pourcentage existe déjà');

            return back();
           }

     }

     public function edit(Request $request,$id){
       $percent=PourcentageCommission::find($id);
       $percent->update($request->all());
       session()->flash('messages','Le pourcentage a été modifiée avec succès');
       return back();


     }
}
