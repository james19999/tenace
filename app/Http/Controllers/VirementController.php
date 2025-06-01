<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VirementController extends Controller
{
    public function createform(){
        return view('virement.virement-form');
    }
    public function virementverify(){
        return view('virement.virement-verify');
    }
}
