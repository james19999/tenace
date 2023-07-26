<?php

namespace App\Http\Controllers\Livreurs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class LivreurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users=User::where('user_type','LVS')->latest()->get();

        return view('livreurs.index',compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('livreurs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $request->validate([
                 'name' => ['required', 'string', 'max:255'],
                 'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                 'password' => ['required', 'string', 'min:8', 'confirmed'],
                 'adresse' => ['required', 'string'],
                 'phone' => ['required', 'integer' ,'unique:users'],
               ]);



               User::create([
                'name' => $request->name,
                'email' =>$request->email ,
                'password' =>Hash::make($request->password),
                'adresse' => $request->adresse,
                'phone' => $request->phone,
                'user_type' => $request->user_type,
               ]);

         return redirect()->route('livreurs.index')->with('messages','utilisateur créer');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
      $user=User::findOrfail($id);

    //    $sum=Order::where('user_id',$id)->where('status','delivered')
    //     ->whereDate('created_at',Carbon::today())
    //     ->sum('total');

       $orde=Order::where('user_id',$id)->where('status','delivered')
        ->whereDate('created_at',Carbon::today())
        ->get();

      return view('livreurs.show',compact('user','orde'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $users=User::findOrfail($id);

        return view('livreurs.edit',compact('users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {



        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'adresse' => ['required', 'string'],
            'phone' => ['required', 'integer'],
          ]);
        $users=User::findOrfail($id);



        $users->update([
           'name' => $request->name,
           'email' =>$request->email ,
           'adresse' => $request->adresse,
           'phone' => $request->phone,
           'user_type' => $request->user_type,
          ]);

         return redirect()->route('livreurs.index')->with('messages','utilisateur modifier');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        User::destroy($id);


        return redirect()->back()->with('messages','utilisateur supprimer');

    }


    public function user_admin_list(){
        $users=User::where('user_type','ADMINUSER')->latest()->get();
        return view('users.index',compact('users'));
    }



    public function livrable() {

        $orders=Order::where('status_order',0)
              ->where('status','ordered')
              ->latest()
              ->whereDate('created_at',Carbon::today())
              ->get();
        return view('livreurs.livrable',compact('orders'));
    }


    public function livrable_show($id){

        $Orders=Order::findOrfail($id);

        return view('livreurs.livrablde_show',compact('Orders'));
    }


    public function change_order_status_user(Request $request,$id){

        $orders=Order::findOrfail($id);

         if($orders){
             if($request->status=="canceled"){
               $request->validate(['motif'=>'required'],
               ['motif.required' => 'Entrer le motif d\'annulation.']
              );


               $orders->status=$request->status;
               $orders->motif=$request->motif;
               $orders->user_id=Auth::user()->id;
               $orders->status_order=false;
               $orders->save();
              return redirect()->back()->with('success','commande annuler');

             }else if($request->status=="delivered"){
               $orders->status=$request->status;
               $orders->user_id=Auth::user()->id;
               $orders->status_order=false;
               $orders->save();
              return redirect()->back()->with('success','commande valider');
             }

         }else{
          return redirect()->back()->with('success','error');

         }
  }


  public function auth_user_livrable($id){

    $orders =Order::where('user_id',Auth::user()->id)
    ->where('status_order',true)
    ->whereDate('created_at',Carbon::today())
    ->get();
     return view('livreurs.aut_livre_list',compact('orders'));
  }


  public function check_livraison($id){
    $orders=Order::findOrfail($id);

      if($orders->take==false){

          $order =Order::where('user_id',Auth::user()->id)
                 ->where('status_order',true)
                  ->get();

              if($order->count()==0){
                  $orders->user_id=Auth::user()->id;
                  $orders->status_order=true;
                  $orders->take=true;
                  $orders->save();
                  return back();
              }else{
                  return redirect()->route('livrable')
                  ->with('error','Vous avez des commandes non livrées');
              }
      }else{
        return redirect()->route('livrable')
        ->with('waring','La commande déjà pris par un livreur');
      }

  }
}
