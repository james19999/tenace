<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pub;
use Illuminate\Http\Request;

class PubController extends Controller
{
    public function index(){
        $pubs=Pub::latest()->get();
       return view('pourcentagelist.pub',compact('pubs'));
   }
}
