<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Retrait;
use Illuminate\Http\Request;

class RetraitController extends Controller
{
    //

     public function index(){
        $retraits =Retrait::latest()->get();
        return view('retraits.index',compact('retraits'));
     }
}
