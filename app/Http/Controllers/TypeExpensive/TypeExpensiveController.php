<?php

namespace App\Http\Controllers\TypeExpensive;

use App\Http\Controllers\Controller;
use App\Models\TypeExpensive;
use Illuminate\Http\Request;

class TypeExpensiveController extends Controller
{
     public function index()
     {
     # code...
     $typeexpensives=TypeExpensive::latest()->get();
     return view('typeexpensive.index',compact('typeexpensives'));
     }

     public function create (Request $request){
        $request->validate([
            'name' => 'required',
          ]);
          TypeExpensive::create($request->all());

          return back()->with('messages','Type dépense créer');
     }

        public function destroy($id)
        {
            $type = TypeExpensive::find($id);
            if ($type) {
                $type->delete();
                return back()->with('messages','Type dépense supprimé');
            } else {
                return back()->with('messages','error');;
            }
      }



      public function update(Request $request, $id)
      {
          $item = TypeExpensive::find($id);
          $item->update($request->all());

          return back()->with('messages','Type dépense  modifier');

      }


}
