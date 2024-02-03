<?php

namespace App\Http\Controllers\Costumers;

use Carbon\Carbon;
use App\Models\Costumer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CostomerNew;
use Illuminate\Support\Facades\Auth;


class CostumerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        $costumers=CostomerNew::all()->sortBy('name');


        return view('costumer.index',compact('costumers'));
    }

    public function topcostumer(Request $request)
    {

        $montharray = [
            1 => "Janvier",
            2 => "Février",
            3 => "Mars",
            4 => "Avril",
            5 => "Mai",
            6 => "Juin",
            7 => "Juillet",
            8 => "Août",
            9 => "Septembre",
            10 => "Octobre",
            11 => "Novembre",
            12 => "Décembre"
         ];



                # code...
                $costumers= Costumer::withCount('orders')
                ->withSum('orders','total')
                ->orderByDesc('orders_sum_total')->
                // whereMonth('created_at', Carbon::now()->month)
                // ->
                whereMonth('created_at', $request->month)
                ->limit($request->limit)
                ->get();
                return view('costumer.top_costumer',compact('costumers','montharray'));

    }
    public function viewcostumer($id)
    {
        //
                //
                $costumers= Costumer::where('id',$id)->withCount('orders')
                ->withSum('orders','total')
                ->first();

        return view('costumer.view_costomer',compact('costumers'));
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
                 'phone'=>str_replace(' ', '',$request->phone)  ,
                 'email'=>$request->email,
                 'adresse'=>$request->adresse,
                 'user_id'=>Auth::user()->id,
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
            'phone'=>str_replace(' ', '',$request->phone),
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
