<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Fond;
use Illuminate\Http\Request;

class FondController extends Controller
{

    public function index(){
        $fonds=Fond::latest()->get();
       return view('pourcentagelist.roulement',compact('fonds'));
   }
}
