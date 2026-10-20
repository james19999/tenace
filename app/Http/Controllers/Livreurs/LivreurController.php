<?php

namespace App\Http\Controllers\Livreurs;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Product;
use App\Traits\MyTrait;
use App\Traits\NewTrait;
use App\Models\Orders\Order;
use App\Models\Orders\OrderDeliveryPhoto;
use App\Models\OrderCourierHandoff;
use App\Models\CourierDocument;
use App\Models\OrderPayment;
use App\Models\CustomerServiceCase;
use App\Models\CustomerServiceCaseArchiveAccessRequest;
use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\PourcentageCommission;
use App\Services\StockDeliveryService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Support\WestAfricaShippingCatalog;
use App\Services\Payments\FedaPayPaymentProcessor;
use App\Services\Payments\FedaPayService;
use Illuminate\Support\Facades\Log;

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
        return view('livreurs.create', ['countries' => WestAfricaShippingCatalog::countries()]);
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
                 'country_code' => ['nullable', Rule::in(array_keys(WestAfricaShippingCatalog::countries()))],
                 'emergency_contact_name' => ['nullable', 'string', 'max:160'],
                 'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
                 'identity_card_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
               ]);



               $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'adresse' => $data['adresse'],
                'phone' => $data['phone'],
                'user_type' => $data['user_type'],
                'country_code' => in_array($data['user_type'], ['LVS', 'PT'], true) ? ($data['country_code'] ?? null) : null,
                'emergency_contact_name' => $data['user_type'] === 'LVS' ? ($data['emergency_contact_name'] ?? null) : null,
                'emergency_contact_phone' => $data['user_type'] === 'LVS' ? ($data['emergency_contact_phone'] ?? null) : null,
               ]);

               if (! empty($data['identity_card_photo'])) {
                   $user->courierDocuments()->create([
                       'document_type' => 'identity_card',
                       'path' => $data['identity_card_photo']->store('courier-documents', 'local'),
                       'original_name' => $data['identity_card_photo']->getClientOriginalName(),
                       'uploaded_by' => $request->user()->id,
                   ]);
               }

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

        return view('livreurs.edit', [
            'users' => $users->load('courierDocuments'),
            'countries' => WestAfricaShippingCatalog::countries(),
        ]);
    }

    public function showCourierDocument(string $userId, string $documentId)
    {
        $document = CourierDocument::query()->where('user_id', $userId)->findOrFail($documentId);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Disposition' => 'inline; filename="' . addslashes($document->original_name ?: basename($document->path)) . '"',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {



        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'adresse' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($id)],
            'user_type' => ['required', 'in:LVS,PT,VDS,CSA,MNG,SCR,CALLCENTER,ADMINUSER,COMPTABLE'],
            'country_code' => ['nullable', Rule::in(array_keys(WestAfricaShippingCatalog::countries()))],
            'emergency_contact_name' => ['nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            'identity_card_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
          ]);
        $users=User::findOrfail($id);



        $users->update([
           'name' => $data['name'],
           'email' => $data['email'],
           'adresse' => $data['adresse'],
           'phone' => $data['phone'],
           'user_type' => $data['user_type'],
           'country_code' => in_array($data['user_type'], ['LVS', 'PT'], true) ? ($data['country_code'] ?? null) : null,
           'emergency_contact_name' => $data['user_type'] === 'LVS' ? ($data['emergency_contact_name'] ?? null) : null,
           'emergency_contact_phone' => $data['user_type'] === 'LVS' ? ($data['emergency_contact_phone'] ?? null) : null,
          ]);

        if (! empty($data['identity_card_photo'])) {
            $users->courierDocuments()->create([
                'document_type' => 'identity_card',
                'path' => $data['identity_card_photo']->store('courier-documents', 'local'),
                'original_name' => $data['identity_card_photo']->getClientOriginalName(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

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

        $query = Order::with('costumer')
            ->where('status', 'ordered')
            ->where('brouillon', 1)
            ->where('type', 'PU')
            ->where('status_order', false)
            ->whereDate('created_at', Carbon::today());

        if (Auth::user()?->user_type === User::ROLE_LIVREUR) {
            $courierCountry = $this->courierCountryCode(Auth::user());
            $defaultCountry = strtoupper((string) config('services.fedapay.country_code', 'TG'));
            $query->whereRaw(
                "UPPER(COALESCE(NULLIF(country_code, ''), NULLIF(shipping_country, ''), ?)) = ?",
                [$defaultCountry, $courierCountry]
            );
        }

        $orders = $query->latest()->get();

        return view('livreurs.livrable',compact('orders'));
    }


    public function livrable_show($id){

        $Orders=Order::with(['costumer', 'user', 'createduser', 'transportCompany', 'deliveryPhotos.user', 'orderItems.product'])->findOrfail($id);
        $isCourier = auth()->user()?->user_type === 'LVS';
        if ($isCourier) {
            $courierCountry = $this->courierCountryCode(auth()->user());
            $isAssignedToCourier = (string) $Orders->user_id === (string) auth()->id();
            $isAvailableInCourierCountry = ! $Orders->user_id
                && ! $Orders->take
                && ! $Orders->status_order
                && $Orders->status === 'ordered'
                && $this->orderCountryCode($Orders) === $courierCountry;
            abort_unless($this->orderCountryCode($Orders) === $courierCountry
                && ($isAssignedToCourier || $isAvailableInCourierCountry), 403);
        }
        $isPartnerPublicOrder = $Orders->partner_id && $Orders->public_tracking_token;
        $handoffConfirmed = ! ($isCourier && $isPartnerPublicOrder)
            || OrderCourierHandoff::query()->where('order_id', $Orders->id)
                ->where('courier_id', auth()->id())
                ->where('received_by_courier', auth()->id())
                ->where('status', 'received')->exists();
        $deliveryPayment = $Orders->payments()->where('stage', 'on_delivery')->latest('id')->first();

        return view('livreurs.livrablde_show', compact('Orders', 'handoffConfirmed', 'isPartnerPublicOrder', 'deliveryPayment'));
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

    public function startDeliveryMobilePayment(Request $request, string $id, FedaPayService $fedapay)
    {
        $courier = $request->user();
        abort_unless($courier?->user_type === User::ROLE_LIVREUR, 403);
        abort_unless($fedapay->isDeliverySandboxEnabled(), 404);
        abort_unless($fedapay->isConfigured(), 503, 'Le paiement Sandbox FedaPay n’est pas encore configuré.');

        $validated = $request->validate([
            'provider_method' => ['required', 'in:mixx,moov'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[+0-9 ()-]+$/'],
            'email' => ['required', 'email', 'max:160'],
        ]);
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '228') && strlen($phone) === 11) {
            $phone = substr($phone, 3);
        }
        abort_unless((bool) preg_match('/^[0-9]{8}$/', $phone), 422, 'Saisis un numéro togolais valide à 8 chiffres.');
        $gatewayMethod = $validated['provider_method'] === 'mixx' ? 'togocel' : 'moov_tg';

        [$payment, $shouldStart] = DB::transaction(function () use ($id, $courier, $gatewayMethod): array {
            $order = Order::query()->lockForUpdate()->findOrFail($id);
            abort_unless((string) $order->user_id === (string) $courier->id, 403);
            $deliveryCountry = strtoupper(trim((string) ($order->country_code ?: ($order->shipping_country ?: ($courier->country_code ?: config('services.fedapay.country_code', 'TG'))))));
            abort_unless($deliveryCountry === 'TG' && $order->status === 'ordered', 409,
                'Le paiement mobile est disponible pour les commandes togolaises encore à livrer.');
            $amountDue = (int) ($order->montant ?? ((int) $order->total + (int) $order->tax));
            $balance = max(0, $amountDue - (int) $order->paid_amount);
            abort_if($balance < 1, 409, 'Cette commande est déjà réglée.');

            $existing = OrderPayment::query()->where('order_id', $order->id)
                ->where('stage', 'on_delivery')->where('gateway', 'fedapay')
                ->whereIn('status', ['initiating', 'pending'])->lockForUpdate()->first();
            if ($existing) {
                return [$existing, false];
            }

            return [OrderPayment::query()->create([
                'order_id' => $order->id,
                'stage' => 'on_delivery',
                'method' => 'mobile_money',
                'amount' => $balance,
                'status' => 'initiating',
                'gateway' => 'fedapay',
                'gateway_method' => $gatewayMethod,
                'gateway_status' => 'creating',
                'collected_by' => $courier->id,
                'collected_at' => now(),
            ]), true];
        });

        if (! $shouldStart) {
            return response()->json([
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'message' => 'Une transaction existe déjà. Vérification de son état Sandbox en cours; aucune nouvelle simulation n’a été lancée.',
                'status_url' => route('livrableshow.mobile-payment.status', [$id, $payment]),
            ], 202);
        }

        try {
            $order = Order::with('costumer')->findOrFail($id);
            $transaction = $fedapay->createTransaction($order, $phone, $validated['email']);
            $payment->gateway_transaction_id = (string) $transaction['id'];
            $payment->gateway_reference = isset($transaction['reference']) ? (string) $transaction['reference'] : null;
            $payment->transaction_reference = $payment->gateway_reference;
            $payment->gateway_status = (string) ($transaction['status'] ?? 'pending');
            $payment->status = 'pending';
            $payment->save();
            $fedapay->requestMobilePayment((string) $transaction['id'], $gatewayMethod);
        } catch (\Throwable $exception) {
            if (! $payment->gateway_transaction_id) {
                $payment->status = 'failed';
                $payment->gateway_status = 'creation_failed';
                $payment->save();
            }
            Log::warning('FedaPay delivery payment could not be started.', [
                'order_id' => $id, 'payment_id' => $payment->id,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'exception' => get_class($exception),
            ]);

            return response()->json(['message' => $payment->gateway_transaction_id
                ? 'La transaction Sandbox existe, mais la simulation n’a pas abouti. Consulte son état dans le tableau de bord FedaPay avant une nouvelle tentative.'
                : 'FedaPay Sandbox n’a pas pu créer la demande. Tu peux réessayer.'], 502);
        }

        return response()->json([
            'payment_id' => $payment->id,
            'status' => 'pending',
            'message' => 'Simulation Mobile Money lancée dans FedaPay Sandbox. Aucun SMS ne sera envoyé.',
            'status_url' => route('livrableshow.mobile-payment.status', [$id, $payment]),
        ], 202);
    }

    public function deliveryMobilePaymentStatus(
        Request $request,
        string $id,
        string $paymentId,
        FedaPayService $fedapay,
        FedaPayPaymentProcessor $processor
    ) {
        $courier = $request->user();
        abort_unless($courier?->user_type === User::ROLE_LIVREUR, 403);
        $order = Order::query()->findOrFail($id);
        abort_unless((string) $order->user_id === (string) $courier->id, 404);
        $payment = OrderPayment::query()->where('order_id', $order->id)
            ->where('gateway', 'fedapay')->findOrFail($paymentId);

        if ($payment->gateway_transaction_id && $payment->status !== 'confirmed' && $fedapay->isConfigured()) {
            try {
                $payment = $processor->refresh($payment,
                    $fedapay->retrieveTransaction((string) $payment->gateway_transaction_id));
            } catch (\Throwable $exception) {
                Log::warning('FedaPay delivery payment status refresh failed.', [
                    'order_id' => $order->id, 'payment_id' => $payment->id,
                    'exception' => get_class($exception),
                ]);
            }
        }

        return response()->json([
            'status' => $payment->fresh()->status,
            'gateway_status' => $payment->fresh()->gateway_status,
            'paid_amount' => (int) $order->fresh()->paid_amount,
            'message' => match ($payment->fresh()->status) {
                'confirmed' => 'Paiement confirmé par FedaPay. Tu peux terminer la livraison.',
                'failed' => 'Le paiement a échoué ou a été annulé. Vérifie avec le client avant de réessayer.',
                default => 'Simulation FedaPay Sandbox en attente. Aucun SMS opérateur ne sera envoyé.',
            },
        ]);
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


    public function change_order_status_user(
        Request $request,
        $id,
        StockDeliveryService $stockDelivery,
        FedaPayService $fedapay,
        FedaPayPaymentProcessor $fedapayProcessor
    ){

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
               if ($orders->partner_id && $orders->public_tracking_token) {
                   $orders->partner_order_status = 'canceled';
               }
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
                $cashCollection = 0;
                if ($request->user()?->user_type === User::ROLE_LIVREUR) {
                    // The dashboard may already show a declined Sandbox payment while
                    // this app still has its last local state as pending. Refresh it
                    // before deciding whether cash collection can safely proceed.
                    if ($fedapay->isConfigured()) {
                        $pendingGatewayPayments = OrderPayment::query()
                            ->where('order_id', $orders->id)
                            ->where('stage', 'on_delivery')
                            ->where('gateway', 'fedapay')
                            ->whereIn('status', ['initiating', 'pending'])
                            ->whereNotNull('gateway_transaction_id')
                            ->get();

                        foreach ($pendingGatewayPayments as $gatewayPayment) {
                            try {
                                $freshTransaction = $fedapay->retrieveTransaction((string) $gatewayPayment->gateway_transaction_id);
                                $fedapayProcessor->refresh($gatewayPayment, $freshTransaction);
                            } catch (\Throwable $exception) {
                                Log::warning('Could not refresh FedaPay status before cash delivery validation.', [
                                    'order_id' => $orders->id,
                                    'payment_id' => $gatewayPayment->id,
                                    'gateway_transaction_id' => $gatewayPayment->gateway_transaction_id,
                                    'exception' => get_class($exception),
                                ]);
                            }
                        }
                        $orders->refresh();
                    }

                    $amountDue = (int) ($orders->montant ?? ((int) $orders->total + (int) $orders->tax));
                    $balance = max(0, $amountDue - (int) $orders->paid_amount);
                    if ($balance > 0) {
                        $paymentMethod = $request->validate(['payment_method' => ['required', 'in:cash,mobile_money']])['payment_method'];
                        if ($paymentMethod === 'mobile_money' && ! $fedapay->isDeliverySandboxEnabled()) {
                            return redirect()->back()->withErrors([
                                'delivery' => 'Le paiement Mobile Money Sandbox est disponible uniquement en local ou en test.',
                            ]);
                        }
                        $fedapayPayment = OrderPayment::query()->where('order_id', $orders->id)
                            ->where('stage', 'on_delivery')->where('gateway', 'fedapay')
                            ->whereIn('status', ['initiating', 'pending'])->exists();
                        if ($paymentMethod === 'mobile_money' && $balance > 0) {
                            return redirect()->back()->withErrors([
                                'delivery' => $fedapayPayment
                                    ? 'Attends la confirmation du paiement Mobile Money avant de valider la livraison.'
                                    : 'Lance le paiement Mobile Money et attends sa confirmation avant de valider la livraison.',
                            ]);
                        }
                        if ($paymentMethod === 'cash') {
                            if ($fedapayPayment) {
                                return redirect()->back()->withErrors(['delivery' => 'Une demande Mobile Money est en cours. Attends son résultat avant de choisir les espèces.']);
                            }
                            $cashCollection = $balance;
                        }
                    }
                }
                if ($request->user()?->user_type === 'LVS'
                    && $orders->partner_id
                    && $orders->public_tracking_token) {
                    $handoffReceived = $orders->partner_order_status === 'out_for_delivery'
                        && OrderCourierHandoff::query()->where('order_id', $orders->id)
                            ->where('courier_id', $request->user()->id)
                            ->where('received_by_courier', $request->user()->id)
                            ->where('status', 'received')->exists();

                    if (! $handoffReceived) {
                        return redirect()->back()->withErrors([
                            'delivery' => 'Tu dois d’abord confirmer la réception du colis avant de valider sa livraison.',
                        ]);
                    }
                }
                if ($orders->delivery_mode === 'expedition' && ! $orders->deliveryPhotos()->exists()) {
                    return redirect()->back()->withErrors([
                        'photos' => 'Ajoute au moins une photo des colis avant de valider cette expédition.',
                    ]);
                }
                $order=Order::with('orderItems')->findOrFail($orders->id);
               if (! $stockDelivery->deliver($order, (int) Auth::id())) {
                    $orders->refresh();
                    if ($orders->status === 'delivered'
                        && $orders->partner_id
                        && ! $orders->created_user
                        && in_array($request->user()?->user_type, ['ADMINUSER', 'MNG'], true)) {
                        $this->processSellerCommissionOnce($orders, true);

                        return redirect()->back()->with('success', 'La livraison était déjà validée. La commission du partenaire a été synchronisée.');
                    }
                    return redirect()->back()->with('success','Cette commande a déjà été validée comme livrée.');
                }
               if ($cashCollection > 0) {
                   OrderPayment::query()->create([
                       'order_id' => $orders->id,
                       'stage' => 'on_delivery',
                       'method' => 'cash',
                       'amount' => $cashCollection,
                       'status' => 'collected',
                       'collected_by' => Auth::id(),
                       'collected_at' => now(),
                   ]);
                   $orders->paid_amount = (int) $orders->paid_amount + $cashCollection;
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
               $this->processSellerCommissionOnce($order);


               $orders->save();
              return redirect()->back()->with('success','commande valider');
             }

         }else{
          return redirect()->back()->with('success','error');

         }
  }

    private function processSellerCommissionOnce(Order $order, bool $recoverOnly = false): void
    {
        DB::transaction(function () use ($order, $recoverOnly): void {
            $lockedOrder = Order::query()->with('orderItems.product')->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->partner_id && ! $order->created_user && $lockedOrder->created_user) {
                return;
            }
            if ($recoverOnly && $lockedOrder->created_user) {
                return;
            }
            $this->processCommissions($lockedOrder);

            // For public partner orders this field is empty until delivery.
            // Recording the partner after the wallet update makes a failed
            // commission recoverable on retry, while preventing a second credit.
            if ($lockedOrder->partner_id && ! $lockedOrder->created_user) {
                $lockedOrder->created_user = $lockedOrder->partner_id;
                $lockedOrder->save();
            }
        });
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
    $user = Auth::user();

      if ($user?->user_type === User::ROLE_LIVREUR) {
          $courierCountry = $this->courierCountryCode($user);
          $orderCountry = $this->orderCountryCode($orders);
          abort_unless($orderCountry === $courierCountry, 403,
              'Cette commande ne correspond pas au pays de ton compte livreur.');
      }

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

  private function orderCountryCode(Order $order): string
  {
      return strtoupper(trim((string) ($order->country_code ?: ($order->shipping_country ?: config('services.fedapay.country_code', 'TG')))));
  }

  private function courierCountryCode(User $courier): string
  {
      return strtoupper(trim((string) ($courier->country_code ?: config('services.fedapay.country_code', 'TG'))));
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
