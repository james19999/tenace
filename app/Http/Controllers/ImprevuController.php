<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Imprevu;
use Illuminate\Http\Request;

class ImprevuController extends Controller
{
    //
    public function index(){
        $imprevus=Imprevu::latest()->get();
       return view('pourcentagelist.imprevu',compact('imprevus'));
   }
}
