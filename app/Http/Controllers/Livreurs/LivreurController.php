<?php

namespace App\Http\Controllers\Livreurs;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Product;
use App\Traits\MyTrait;
use App\Traits\NewTrait;
use App\Models\Orders\Order;
use App\Models\Orders\OrderDeliveryPhoto;
use App\Models\CustomerServiceCase;
use App\Models\CustomerServiceCaseArchiveAccessRequest;
use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\PourcentageCommission;
use App\Services\StockDeliveryService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class LivreurController extends Controller
{
    use MyTrait;
    use NewTrait;
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
         $data = $request->validate([
                 'name' => ['required', 'string', 'max:255'],
                 'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                 'password' => ['required', 'string', 'min:8', 'confirmed'],
                 'adresse' => ['required', 'string'],
                 'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
                 'user_type' => ['required', 'in:LVS,PT,VDS,CSA,MNG,SCR,CALLCENTER,ADMINUSER,COMPTABLE'],
               ]);



               User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'adresse' => $data['adresse'],
                'phone' => $data['phone'],
                'user_type' => $data['user_type'],
               ]);

         return redirect()->route('useradminlist', ['user_type' => $data['user_type']])->with('messages','utilisateur créer');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
      $user=User::findOrfail($id);
      abort_unless($user->user_type === 'LVS', 404);

    //    $sum=Order::where('user_id',$id)->where('status','delivered')
    //     ->whereDate('created_at',Carbon::today())
    //     ->sum('total');

       $orde=Order::where('user_id',$id)->where('status','delivered')
        ->whereDate('updated_at',Carbon::today())
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


    public function user_admin_list(Request $request){
        $userType = $request->query('user_type', 'LVS');
        $allowedTypes = ['LVS', 'PT', 'VDS', 'CSA', 'MNG', 'SCR', 'CALLCENTER', 'ADMINUSER', 'COMPTABLE'];
        if (! in_array($userType, $allowedTypes, true)) {
            $userType = 'LVS';
        }
        $users = User::where('user_type', $userType)->latest()->get();
        return view('users.index', compact('users', 'userType'));
    }

    public function user_show($id){
         $user=User::findOrfail($id);
         $startOfWeek = Carbon::now()->startOfWeek();


         $commiss=  $user->commissions()->where('created_at', '>=', $startOfWeek)->get();
         return view('users.user_show',compact('user' ,'commiss'));
    }



    public function livrable() {

        $orders = Order::with('costumer')
            ->where('status', 'ordered')
            ->where('brouillon', 1)
            ->where('type', 'PU')
            ->where('status_order', false)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->get();

        return view('livreurs.livrable',compact('orders'));
    }


    public function livrable_show($id){

        $Orders=Order::with(['costumer', 'user', 'createduser', 'transportCompany', 'deliveryPhotos.user', 'orderItems.product'])->findOrfail($id);

        return view('livreurs.livrablde_show',compact('Orders'));
    }

    public function updateOrderPayment(Request $request, string $id)
    {
        abort_unless(in_array($request->user()?->user_type, ['ADMINUSER', 'MNG'], true), 403);

        $order = Order::findOrFail($id);
        $amountDue = (float) ($order->montant ?? ((float) $order->total + (float) $order->tax));
        $validated = $request->validate([
            'paid_amount' => ['required', 'integer', 'min:0', 'max:'.$amountDue],
        ], [
            'paid_amount.max' => 'Le montant encaissé ne peut pas dépasser le total de la commande.',
        ]);

        $order->paid_amount = (int) $validated['paid_amount'];
        $order->save();

        return redirect()->back()->with('success', 'Le règlement de la commande a été enregistré.');
    }

    public function uploadPackagePhotos(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        $this->ensureOrderPhotoAccess($order, $request);
        abort_unless($order->status === 'ordered', 409, 'Les photos ne peuvent être ajoutées que pendant la préparation de la livraison.');

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'photos.required' => 'Prends au moins une photo des colis.',
            'photos.*.image' => 'Chaque fichier doit être une photo valide.',
            'photos.*.max' => 'Chaque photo doit faire au maximum 10 Mo.',
        ]);

        $storedPhotos = [];
        foreach ($validated['photos'] as $photoFile) {
            $path = $photoFile->store("orders/{$order->id}/package-photos", 'local');
            $photo = $order->deliveryPhotos()->create([
                'user_id' => $request->user()->id,
                'path' => $path,
                'original_name' => $photoFile->getClientOriginalName(),
            ]);
            $storedPhotos[] = [
                'id' => $photo->id,
                'url' => route('livrableshow.package-photo', [$order->id, $photo->id]),
            ];
        }

        return response()->json([
            'message' => count($storedPhotos) . ' photo(s) enregistrée(s).',
            'photos' => $storedPhotos,
            'count' => $order->deliveryPhotos()->count(),
        ]);
    }

    public function showPackagePhoto(Request $request, string $orderId, string $photoId)
    {
        $order = Order::findOrFail($orderId);
        $this->ensureOrderPhotoAccess($order, $request);
        $photo = OrderDeliveryPhoto::where('order_id', $order->id)->findOrFail($photoId);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return response()->file(Storage::disk('local')->path($photo->path), [
            'Content-Disposition' => 'inline; filename="' . addslashes($photo->original_name ?: basename($photo->path)) . '"',
        ]);
    }

    public function downloadPackagePhoto(Request $request, string $orderId, string $photoId)
    {
        $order = Order::findOrFail($orderId);
        abort_unless(in_array($request->user()?->user_type, ['ADMINUSER', 'MNG'], true), 403);
        $photo = OrderDeliveryPhoto::where('order_id', $order->id)->findOrFail($photoId);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->download($photo->path, $photo->original_name ?: basename($photo->path));
    }

    private function ensureOrderPhotoAccess(Order $order, Request $request): void
    {
        $user = $request->user();
        $isManager = in_array($user?->user_type, ['ADMINUSER', 'MNG'], true);
        abort_unless($isManager || ($user?->user_type === 'LVS' && (int) $order->user_id === (int) $user->id), 403);
    }


    public function change_order_status_user(Request $request,$id, StockDeliveryService $stockDelivery){

        $orders=Order::findOrfail($id);

         abort_unless(
             in_array($request->user()?->user_type, ['ADMINUSER', 'MNG'], true)
                 || ($request->user()?->user_type === 'LVS' && (int) $orders->user_id === (int) $request->user()->id),
             403
         );

         if($orders){
             if($request->status=="canceled"){
               $request->validate(['motif'=>'required'],
               ['motif.required' => 'Entrer le motif d\'annulation.']
              );


               $orders->status=$request->status;
               $orders->motif=$request->motif;
               $orders->user_id=Auth::user()->id;
               $orders->status_order=false;
               $orders->take=false;
               $this->RemoveCommission($orders);
               $this->RemoveEpargne($orders);
               $this->RemoveFond($orders);
               $this->RemovePub($orders);
               $this->RemoveImprevue( $orders);


               $orders->save();
              return redirect()->back()->with('success','commande annuler');

             }else if($request->status=="delivered"){
                if ($orders->delivery_mode === 'expedition' && ! $orders->deliveryPhotos()->exists()) {
                    return redirect()->back()->withErrors([
                        'photos' => 'Ajoute au moins une photo des colis avant de valider cette expédition.',
                    ]);
                }
                $order=Order::with('orderItems')->findOrFail($orders->id);
                if (! $stockDelivery->deliver($order, (int) Auth::id())) {
                    return redirect()->back()->with('success','Cette commande a déjà été validée comme livrée.');
                }
               $orders->status=$request->status;
               $orders->user_id=Auth::user()->id;
               $orders->status_order=false;
               $orders->take=false;

               $this->commission($order);

               $this->CalculCommissions($order);

               $this->CalculImprevu($order);
               $this->CalculTauxEpargne($order);
               $this->CalculTauxFond($order);
               $this->CalculTauxPub($order);
               $this->processCommissions($order);


               $orders->save();
              return redirect()->back()->with('success','commande valider');
             }

         }else{
          return redirect()->back()->with('success','error');

         }
  }


  public function auth_user_livrable($id = null){

    // orders.user_id is stored as VARCHAR. Compare it to a string and include
    // assigned, still-ordered work even if an older assignment missed the flag.
    $orders = Order::where('user_id', (string) Auth::id())
        ->where(function ($q) {
            $q->where('status', 'ordered')
              ->orWhere(function ($q2) {
                  $q2->whereIn('status', ['delivered', 'canceled'])
                     ->whereDate('updated_at', Carbon::today());
              });
        })
        ->with('costumer')
        ->latest('updated_at')
        ->get();

     return view('livreurs.aut_livre_list', compact('orders'));
  }


  public function check_livraison($id){
    $orders=Order::findOrfail($id);

      if($orders->take==false){

          $order =Order::where('user_id',Auth::user()->id)
                 ->where('status_order',true)
                 ->whereDate('created_at',Carbon::today())
                  ->get();

              if($order->count()==0){
                  $orders->user_id=Auth::user()->id;
                  $orders->assigned_at=now();
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


  public function archive_list(Request $request){
    $archiveType = $request->query('type', 'orders');
    abort_unless(in_array($archiveType, ['orders', 'cases', 'requests'], true), 404);

    $orders = $archiveType === 'orders' ? Order::where('take', true)->get() : collect();
    $cases = $archiveType === 'cases'
        ? CustomerServiceCase::with(['customer', 'product', 'assignee'])
            ->whereNotNull('archived_at')
            ->latest('archived_at')
            ->paginate(15)
        : null;
    $accessRequests = $archiveType === 'requests'
        ? CustomerServiceCaseArchiveAccessRequest::with(['customerServiceCase.customer', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15)
        : null;

    return view('livreurs.archive', compact('orders', 'cases', 'accessRequests', 'archiveType'));
  }

  public function approveCaseArchiveAccess(CustomerServiceCaseArchiveAccessRequest $accessRequest){
    return $this->decideCaseArchiveAccess($accessRequest, 'approved');
  }

  public function rejectCaseArchiveAccess(CustomerServiceCaseArchiveAccessRequest $accessRequest){
    return $this->decideCaseArchiveAccess($accessRequest, 'rejected');
  }

  protected function decideCaseArchiveAccess(CustomerServiceCaseArchiveAccessRequest $accessRequest, string $status){
    abort_unless(Auth::user()->hasRole(['ADMINUSER']), 403);
    abort_unless($accessRequest->status === 'pending', 404);

    $accessRequest->load(['customerServiceCase.customer', 'requester']);
    $case = $accessRequest->customerServiceCase;
    $isCurrentArchive = $case->archived_at
        && $accessRequest->archive_snapshot_at
        && $accessRequest->archive_snapshot_at->equalTo($case->archived_at);

    if (! $isCurrentArchive) {
      $accessRequest->update([
        'status' => 'rejected',
        'decided_by' => Auth::id(),
        'decision_note' => 'La période d’archivage a changé avant le traitement de la demande.',
        'decided_at' => now(),
      ]);

      return redirect()->route('archivelist', ['type' => 'requests'])
        ->with('error', 'Cette demande concerne une ancienne période d’archivage. Elle a été refusée.');
    }

    $accessRequest->update([
      'status' => $status,
      'decided_by' => Auth::id(),
      'decided_at' => now(),
    ]);

    $approved = $status === 'approved';
    $case->activities()->create([
      'user_id' => Auth::id(),
      'activity_type' => 'archive_access',
      'body' => $approved
        ? 'Accès aux archives autorisé pour '.$accessRequest->requester->name.'.'
        : 'Demande d’accès aux archives refusée pour '.$accessRequest->requester->name.'.',
      'internal' => true,
      'occurred_at' => now(),
    ]);

    $accessRequest->requester->notify(new CustomerServiceCaseNotification(
      $case,
      $approved ? 'archive_access_approved' : 'archive_access_rejected',
      $approved
        ? 'Votre demande d’accès au dossier '.$case->case_number.' a été approuvée.'
        : 'Votre demande d’accès au dossier '.$case->case_number.' a été refusée.',
      url: $approved
        ? route('service-cases.show', ['caseId' => $case->id, 'archiveView' => 1])
        : route('service-cases.index', ['archiveView' => 1]),
    ));

    return redirect()->route('archivelist', ['type' => 'requests'])
      ->with('messages', $approved ? 'L’accès au dossier a été accordé.' : 'La demande d’accès a été refusée.');
  }

  public function unlock ($id){

    $order=Order::findOrfail($id);
    if ($order) {

        if($order->take==true){
         $order->take=false;
         $order->status_order=false;
         $order->user_id=null;
         $order->save();
         return back();

        }
    }

  }

  public function updateProdSale($prod_id,$newQty)
  {
      $prod = Product::where('id',$prod_id)->first();
      if ($prod) {
          $qty_init = $prod->qt_initial;
          $qts_sell = $prod->qts_sell;
          $qty_init -=$newQty;
          //dd($qty_init);
          $prod->qt_initial = $qty_init;
          $qts_sell +=intval($newQty);
          $prod->qts_sell = $qts_sell;
          $bnvendu=($prod->price - $prod->price_market) *$qts_sell;
          $prod->benefice =$bnvendu;
          //dd($prod->qts_sell);
          $prod->update();
          return true;//$prod->qt_initial;
      }else{
          return false;
      }
  }





}
