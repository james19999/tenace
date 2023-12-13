<?php

namespace App\Http\Controllers\Expensive;

use App\Models\Expensive;
use Illuminate\Http\Request;
use App\Models\TypeExpensive;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ExpensiveController extends Controller
{
    public function index()
    {
    # code...
    $expensives=Expensive::latest()->get();
    $typeexpensives=TypeExpensive::latest()->get();

    return view('expensive.index',compact('expensives','typeexpensives'));
    }

    public function create (Request $request){
       $request->validate([
        'description'=>'required',
        'date_create'=>'required',
        'type_expensive_id'=>'required',
        'amount'=>'required',

         ]);
       $auth=  Auth::user()->id;
        Expensive::create([
            'user_id'=>$auth,
            'description'=>$request->description,
            'date_create'=>$request->date_create,
            'type_expensive_id'=>$request->type_expensive_id,
            'amount'=>$request->amount,
        ]);

         return back()->with('messages',' dépense créer');
    }

       public function destroy($id)
       {
           $expense = Expensive::find($id);
           if ($expense) {
               $expense->delete();
               return back()->with('messages',' dépense supprimé');
           } else {
               return back()->with('messages','error');;
           }
     }



     public function update(Request $request, $id)
     {
         $item = Expensive::find($id);
         $auth=  Auth::user()->id;
         $item->update([
             'user_id'=>$auth,
             'description'=>$request->description,
             'date_create'=>$request->date_create,
             'type_expensive_id'=>$request->type_expensive_id,
             'amount'=>$request->amount,
         ]);

         return back()->with('messages',' dépense  modifier');

     }
}
