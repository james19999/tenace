<?php

namespace App\Http\Controllers\Costumers;

use App\Http\Controllers\Controller;
use App\Models\Costumer;
use Illuminate\Http\Request;

class CostumerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        $costumers=Costumer::latest()->get();

        return view('costumer.index',compact('costumers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('costumer.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
             'name'=>'required',
             'phone'=>'required',
             'adresse'=>'required',
        ]);


            Costumer::create([
                 'name'=>$request->name,
                 'phone'=>$request->phone,
                 'email'=>$request->email,
                 'adresse'=>$request->adresse,
            ]);
            
            return  redirect()->route('costumer.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $costumers=Costumer::findOrfail($id);

        return view('costumer.edit',compact('costumers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $costumers=Costumer::findOrfail($id);

        $costumers->update([
            'name'=>$request->name,
            'phone'=>$request->phone,
            'email'=>$request->email,
            'adresse'=>$request->adresse,
        ]);


        return redirect()->route('costumer.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        
        Costumer::destroy($id);

        return back();
    }
}
