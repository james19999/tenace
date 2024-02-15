<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Retrait;
use App\Models\RetraitImprevu;
use App\Models\RetraitPub;
use Illuminate\Http\Request;

class RetraitController extends Controller
{
    //

     public function index(){
        $retraits =Retrait::latest()->get();
        return view('retraits.index',compact('retraits'));
     }
     public function indexpub(){
        $retraits =RetraitPub::latest()->get();
        return view('retraits.retrait-pub',compact('retraits'));
     }
     public function indeximpre(){
        $retraits =RetraitImprevu::latest()->get();
        return view('retraits.retrait-imprevu',compact('retraits'));
     }
}
