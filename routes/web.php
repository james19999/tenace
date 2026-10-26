<?php

use App\Http\Livewire\OrderList;
use App\Http\Livewire\CostumerFollowUp;
use App\Http\Livewire\CustomerServiceCases;
use App\Http\Livewire\CustomerLoyalty;
use App\Http\Livewire\OrderFilter;
use App\Http\Livewire\RepportOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PubController;
use App\Http\Livewire\Orders\EditOrder;
use App\Http\Controllers\FondController;
use App\Http\Livewire\ProductAuditReport;
use App\Http\Controllers\EpargneController;
use App\Http\Controllers\ImprevuController;
use App\Http\Controllers\PercentController;
use App\Http\Controllers\RetraitController;
use App\Http\Livewire\Products\ProductCart;
use App\Http\Livewire\Products\ProductForm;
use App\Http\Livewire\Products\ProductList;
use App\Http\Controllers\Api\EcomController;
use App\Http\Controllers\VirementController;
use App\Http\Livewire\WithdrawalRequestForm;
use App\Http\Controllers\Stock\StockController;
use App\Http\Controllers\Stock\StockControlController;
use App\Http\Controllers\Stock\StockReceiptController;
use App\Http\Controllers\Stock\StockTransferController;
use App\Http\Controllers\Stock\StockCountController;
use App\Http\Controllers\Stock\StockAdjustmentController;
use App\Http\Controllers\Stock\StockMovementController;
use App\Http\Controllers\Stock\StockAlertController;
use App\Http\Controllers\Stock\StockAuditController;
use App\Http\Livewire\Orders\OrderAuthUserList;
use App\Http\Controllers\Admins\CheckController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Admins\DashboarController;
use App\Http\Controllers\Livreurs\LivreurController;
use App\Http\Controllers\Costumers\CostumerController;
use App\Http\Controllers\CustomerServiceCaseAttachmentController;
use App\Http\Controllers\PublicReviewController;
use App\Http\Controllers\PartnerOrderingSetupController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\PartnerOrderController;
use App\Http\Controllers\PartnerMonitoringController;
use App\Http\Controllers\CourierPublicOrderController;
use App\Http\Controllers\DgAppointmentController;
use App\Http\Controllers\FedaPayWebhookController;
use App\Http\Controllers\ProductQuantityPriceController;
use App\Http\Controllers\QuantityPromotionController;
use App\Http\Controllers\WholesaleCustomerMonitoringController;
use App\Http\Controllers\Expensive\ExpensiveController;
use App\Http\Controllers\Brouillons\BrouillonController;
use App\Http\Controllers\TypeExpensive\TypeExpensiveController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return redirect($user instanceof \App\Models\User ? $user->homeUrl() : \App\Providers\RouteServiceProvider::HOME);
    }
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/avis-clients', [PublicReviewController::class, 'index'])->name('public-reviews.index');
Route::get('/espace-client', [CustomerPortalController::class, 'index'])->name('customer-portal.index');
Route::post('/espace-client/profils', [CustomerPortalController::class, 'storeProfile'])->middleware('throttle:10,1')->name('customer-portal.profiles.store');
Route::post('/espace-client/connexion', [CustomerPortalController::class, 'login'])->middleware('throttle:12,1')->name('customer-portal.login');
Route::post('/espace-client/deconnexion', [CustomerPortalController::class, 'logout'])->name('customer-portal.logout');
Route::get('/commande/suivi/{trackingToken}/espace-client', [CustomerPortalController::class, 'activate'])->name('customer-portal.activate');
Route::post('/commande/suivi/{trackingToken}/espace-client', [CustomerPortalController::class, 'storeAccount'])->middleware('throttle:8,1')->name('customer-portal.store');
Route::get('/commander/manifest.json', [PublicOrderController::class, 'manifest'])->name('public-order.app.manifest');
Route::get('/commander/catalog', [PublicOrderController::class, 'catalog'])->middleware('throttle:60,1')->name('public-order.app.catalog');
Route::post('/commander', [PublicOrderController::class, 'placeOrder'])->middleware('throttle:10,1')->name('public-order.app.store');
Route::get('/commander', [PublicOrderController::class, 'app'])->name('public-order.app');
Route::get('/commander/{token}/manifest.json', [PublicOrderController::class, 'manifest'])->name('public-order.manifest');
Route::get('/commander/{token}/catalog', [PublicOrderController::class, 'catalog'])->middleware('throttle:60,1')->name('public-order.catalog');
Route::post('/commander/{token}', [PublicOrderController::class, 'placeOrder'])->middleware('throttle:10,1')->name('public-order.store');
Route::get('/commander/{token}', [PublicOrderController::class, 'show'])->name('public-order.show');
Route::get('/commande/suivi/{trackingToken}', [PublicOrderController::class, 'track'])->name('public-order.track');
Route::post('/commande/suivi/{trackingToken}/confirmer-devis', [PublicOrderController::class, 'confirmQuote'])->middleware('throttle:10,1')->name('public-order.quote.confirm');
Route::post('/commande/suivi/{trackingToken}/paiement', [PublicOrderController::class, 'submitPrepayment'])->middleware('throttle:5,1')->name('public-order.payment');
Route::post('/fedapay/webhook', FedaPayWebhookController::class)->middleware('throttle:120,1')->name('fedapay.webhook');

Route::get('/tenacos/parthner',[DashboarController::class,'register']);
Route::get('/space',[EcomController::class,'space']);
Route::post('/store/pathner',[DashboarController::class,'store_pathner'])->name('storepathner');


Route::middleware(['auth'])->group(function () {

    // -------------------------------------------------------------------------
    // 1. ROUTES STRICTEMENT ADMINISTRATEUR (ADMINUSER)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER'])->group(function () {
        Route::get('/partner-monitoring', [PartnerMonitoringController::class, 'index'])->name('partner-monitoring.index');
        Route::get('/partner-monitoring/snapshot', [PartnerMonitoringController::class, 'snapshot'])->name('partner-monitoring.snapshot');
        Route::get('/promotions', [QuantityPromotionController::class, 'index'])->name('quantity-promotions.index');
        Route::post('/promotions', [QuantityPromotionController::class, 'save'])->name('quantity-promotions.save');
        Route::post('/promotions/products/{product}', [QuantityPromotionController::class, 'saveProduct'])->name('quantity-promotions.products.save');
        Route::post('/promotions/periods/delete', [QuantityPromotionController::class, 'deletePeriod'])->name('quantity-promotions.periods.delete');
        Route::post('/avis-clients/phone-display', [PublicReviewController::class, 'updatePhoneDisplay'])->name('public-reviews.phone-display');
        // Paramètres & Rôles & Sécurité
        Route::get('settings/config', [DashboarController::class, 'settings'])->name('setting');
        Route::post('settings/dg-appointments', [DgAppointmentController::class, 'updateSettings'])->name('dg-appointments.settings.update');
        Route::get('settings/dg-appointments/count', [DgAppointmentController::class, 'upcomingCount'])->name('dg-appointments.settings.count');
        Route::put('settings/theme', [DashboarController::class, 'updateTheme'])->name('setting-theme');
        Route::post('settinginfo/config', [DashboarController::class, 'settinginfo'])->name('setting-info');
        Route::put('settinginfo/update/config/{id}', [DashboarController::class, 'settinginfoupdate'])->name('setting-update');
        Route::put('/user/role/{id}', [DashboarController::class, 'updateUserRole'])->name('user.update-role');
        Route::put('/set/password/{id}', [DashboarController::class, 'setpassword'])->name('set-password');
        Route::get('active/user/{id}', [DashboarController::class, 'active'])->name('active-user');

        // Gestion Membres & Livreurs
        Route::resource('livreurs', LivreurController::class)->except(['index', 'show']);
        Route::get('livreurs/{user}/documents/{document}', [LivreurController::class, 'showCourierDocument'])->name('livreurs.documents.show');
        Route::get('user/admin/list', [LivreurController::class, 'user_admin_list'])->name('useradminlist');
        Route::get('user/show/{id}', [LivreurController::class, 'user_show'])->name('user-show');

        // Produits - Création, Modification, Suppression
        Route::get('product/form', ProductForm::class)->name('productform');
        Route::get('edit/product/{id}', [DashboarController::class, 'edit'])->name('editproduct');
        Route::put('update/product/{id}', [DashboarController::class, 'updates'])->name('updateproduct');
        Route::delete('edit/product/{id}', [DashboarController::class, 'delete'])->name('delteproduct');

        // Brouillons, Consultations, Archives
        Route::get('/brouillon/brouillons', [BrouillonController::class, 'index'])->name('brouillons');
        Route::get('/brouillon/show/{id}', [BrouillonController::class, 'show'])->name('brouillonshow');
        Route::get('unlock/brouillon/{id}', [BrouillonController::class, 'unlock_brouillon'])->name('unlockbrouillon');
        Route::get('/consultation', [DashboarController::class, 'consultation'])->name('consultation');
        Route::get('archive/list', [LivreurController::class, 'archive_list'])->name('archivelist');
        Route::post('archive/case-access/{accessRequest}/approve', [LivreurController::class, 'approveCaseArchiveAccess'])->name('archive.case-access.approve');
        Route::post('archive/case-access/{accessRequest}/reject', [LivreurController::class, 'rejectCaseArchiveAccess'])->name('archive.case-access.reject');

        // Trésorerie, Fonds & Retraits
        Route::get('pub/list', [PubController::class, 'index'])->name('pub-list');
        Route::post('add/retrait/pub', [PubController::class, 'retraitpub'])->name('retrait-pub');
        Route::get('imprevue/list', [ImprevuController::class, 'index'])->name('imprevu-list');
        Route::post('add/retrait/imprevu', [ImprevuController::class, 'retraitimprevu'])->name('retrait-imprevu');
        Route::get('fond/list', [FondController::class, 'index'])->name('fond-list');
        Route::post('fond/list', [FondController::class, 'retrait'])->name('fond-retrait');
        Route::get('epargne/list', [EpargneController::class, 'index'])->name('epargne-list');
        Route::get('retraits/list', [RetraitController::class, 'index'])->name('retrait-list');
        Route::get('retraits/list/imprevue', [RetraitController::class, 'indeximpre'])->name('index-impre');
        Route::get('retraits/list/pub', [RetraitController::class, 'indexpub'])->name('index-pub');

        // Pourcentages
        Route::get('list/percent', [PercentController::class, 'index'])->name('list-percent');
        Route::post('post/percent', [PercentController::class, 'create'])->name('create-post');
        Route::put('edit/percent/{id}', [PercentController::class, 'edit'])->name('percen-edit');
    });

    Route::middleware(['role:ADMINUSER,RCM'])->group(function () {
        Route::get('/suivi-grossistes', [WholesaleCustomerMonitoringController::class, 'index'])->name('wholesale-monitoring.index');
        Route::get('/suivi-grossistes/{customer}', [WholesaleCustomerMonitoringController::class, 'show'])->name('wholesale-monitoring.show');
    });

    Route::middleware(['role:ADMINUSER,MNG,SCR'])->group(function () {
        Route::get('/prix-degressifs', [ProductQuantityPriceController::class, 'index'])->name('product-tier-prices.index');
        Route::post('/prix-degressifs/{product}', [ProductQuantityPriceController::class, 'save'])->name('product-tier-prices.save');
    });

    Route::middleware(['role:ADMINUSER,MNG'])->group(function () {
        Route::get('livreurs', [LivreurController::class, 'index'])->name('livreurs.index');
        Route::get('livreurs/{livreur}', [LivreurController::class, 'show'])->name('livreurs.show');
    });

    Route::middleware(['role:PT,ADMINUSER,MNG'])->prefix('partner-ordering')->name('partner-ordering.')->group(function () {
        Route::get('/prices/{country}', [PartnerOrderingSetupController::class, 'prices'])->where('country', '[A-Za-z]{2}')->name('prices');
        Route::put('/prices/{country}', [PartnerOrderingSetupController::class, 'savePrices'])->where('country', '[A-Za-z]{2}')->name('prices.save');
        Route::post('/prices/{country}/replenish', [PartnerOrderingSetupController::class, 'replenishStock'])->where('country', '[A-Za-z]{2}')->name('prices.replenish');
    });

    Route::middleware(['role:ADMINUSER,MNG'])->prefix('partner-ordering')->name('partner-ordering.')->group(function () {
        Route::get('/links', [PartnerOrderingSetupController::class, 'links'])->name('links');
        Route::post('/links', [PartnerOrderingSetupController::class, 'createLink'])->name('links.create');
        Route::put('/links/{link}/toggle', [PartnerOrderingSetupController::class, 'toggleLink'])->name('links.toggle');
        Route::delete('/links/{link}', [PartnerOrderingSetupController::class, 'deleteLink'])->name('links.destroy');
    });

    Route::middleware(['role:ADMINUSER,MNG'])->prefix('stock-control')->name('stock-control.')->group(function () {
        Route::get('/audit/export', [StockAuditController::class, 'export'])->middleware('role:ADMINUSER')->name('audit.export');
        Route::get('/audit', [StockAuditController::class, 'index'])->middleware('role:ADMINUSER')->name('audit');
        Route::post('/locations', [StockControlController::class, 'storeLocation'])->name('locations.store');
        Route::put('/locations/{location}', [StockControlController::class, 'updateLocation'])->name('locations.update');
        Route::delete('/locations/{location}', [StockControlController::class, 'destroyLocation'])->name('locations.destroy');
        Route::put('/rules', [StockControlController::class, 'updateRules'])->name('rules.update');
        Route::get('/receipts', [StockReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/create', [StockReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [StockReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{receipt}', [StockReceiptController::class, 'show'])->name('receipts.show');
        Route::put('/receipts/{receipt}/schedule', [StockReceiptController::class, 'reschedule'])->name('receipts.schedule');
        Route::get('/receipts/{receipt}/deliveries/{event}/print', [StockReceiptController::class, 'deliveryReceipt'])->name('receipts.delivery-receipt');
        Route::post('/receipts/{receipt}/deliveries', [StockReceiptController::class, 'recordDelivery'])->name('receipts.deliveries.store');
        Route::post('/receipts/{receipt}/deliveries/{event}/approve', [StockReceiptController::class, 'approveDelivery'])->name('receipts.deliveries.approve');
        Route::get('/attachments/{attachment}', [StockReceiptController::class, 'downloadAttachment'])->name('attachments.download');
        Route::get('/attachments/{attachment}/preview', [StockReceiptController::class, 'previewImage'])->name('attachments.preview');
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store');
        Route::post('/transfers/{transfer}/authorize', [StockTransferController::class, 'authorizeTransfer'])->name('transfers.authorize');
        Route::post('/transfers/{transfer}/ship', [StockTransferController::class, 'ship'])->name('transfers.ship');
        Route::get('/counts', [StockCountController::class, 'index'])->name('counts.index');
        Route::get('/counts/create', [StockCountController::class, 'create'])->name('counts.create');
        Route::post('/counts', [StockCountController::class, 'store'])->name('counts.store');
        Route::get('/counts/{count}/products/search', [StockCountController::class, 'searchProducts'])->name('counts.products.search');
        Route::get('/counts/{count}', [StockCountController::class, 'show'])->name('counts.show');
        Route::get('/counts/{count}/print', [StockCountController::class, 'printReceipt'])->name('counts.print');
        Route::post('/counts/{count}/submit', [StockCountController::class, 'submit'])->name('counts.submit');
        Route::post('/counts/{count}/approve', [StockCountController::class, 'approve'])->name('counts.approve');
        Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::get('/adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');
        Route::get('/adjustments/{adjustment}/print', [StockAdjustmentController::class, 'printReceipt'])->name('adjustments.print');
        Route::post('/adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('/adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');
        Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::get('/movements/{movement}', [StockMovementController::class, 'show'])->name('movements.show');
        Route::get('/alerts', [StockAlertController::class, 'index'])->name('alerts.index');
        Route::post('/alerts/refresh', [StockAlertController::class, 'refresh'])->name('alerts.refresh');
        Route::post('/alerts/{alert}/resolve', [StockAlertController::class, 'resolve'])->name('alerts.resolve');
    });

    Route::middleware(['role:ADMINUSER,MNG'])->prefix('stock-control')->name('stock-control.')->group(function () {
        Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/{transfer}', [StockTransferController::class, 'show'])->name('transfers.show');
        Route::get('/transfers/{transfer}/shipment-receipt', [StockTransferController::class, 'shipmentReceipt'])->name('transfers.shipment-receipt');
        Route::get('/transfers/{transfer}/events/{event}/print', [StockTransferController::class, 'eventReceipt'])->name('transfers.event-receipt');
        Route::post('/transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
        Route::get('/transfer-attachments/{attachment}', [StockTransferController::class, 'downloadAttachment'])->name('transfers.attachments.download');
    });

    Route::get('stock-control/', [StockControlController::class, 'index'])
        ->middleware('role:ADMINUSER')
        ->name('stock-control.index');

    Route::get('stock-control/balances', [StockControlController::class, 'balances'])
        ->middleware('role:ADMINUSER,COMPTABLE')
        ->name('stock-control.balances');

    // -------------------------------------------------------------------------
    // 2. SAV / CALL-CENTER STRICTEMENT (ADMINUSER, CALLCENTER)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER,CALLCENTER'])->group(function () {
        Route::get('costumer/follow-up', CostumerFollowUp::class)->name('costumer.follow-up');
    });

    Route::middleware(['role:ADMINUSER'])->group(function () {
        Route::get('service-client/loyalty', CustomerLoyalty::class)->name('customer-loyalty.index');
        Route::get('service-client/loyalty/rules', CustomerLoyalty::class)->name('customer-loyalty.rules');
    });

    // Dossiers du service client : agents SAV et encadrement autorisés.
    Route::middleware(['role:ADMINUSER,MNG,SCR,CALLCENTER'])->group(function () {
        Route::get('dg-appointments', [DgAppointmentController::class, 'index'])->name('dg-appointments.index');
        Route::get('dg-appointments/available-slots', [DgAppointmentController::class, 'availableSlots'])->name('dg-appointments.slots');
        Route::post('dg-appointments', [DgAppointmentController::class, 'store'])->name('dg-appointments.store');
        Route::get('service-client/cases', CustomerServiceCases::class)->name('service-cases.index');
        Route::get('service-client/cases/{caseId}', CustomerServiceCases::class)->whereNumber('caseId')->name('service-cases.show');
        Route::get('service-client/attachments/{attachment}', [CustomerServiceCaseAttachmentController::class, 'download'])->name('service-cases.attachments.download');
    });

    // -------------------------------------------------------------------------
    // 3. PARTENAIRES (ADMINUSER, PT)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER,PT'])->group(function () {
        Route::get('/parthners/order', [BrouillonController::class, 'parthners_order'])->name('parthnersorder');
        Route::get('/parthners', [BrouillonController::class, 'parthners'])->name('parthners');
        Route::get('trie/order/parther', [OrderController::class, 'trie_order_parther'])->name('trie-order-parther');
        Route::get('with/auth/user/list', [VirementController::class, 'createform'])->name('with-auth-user-list');
        Route::get('virement/auth/user/list', [VirementController::class, 'virementverify'])->name('virementverify');
    });

    Route::post('/parthners', [BrouillonController::class, 'storePartner'])
        ->middleware('role:ADMINUSER')
        ->name('parthners.store');

    Route::middleware(['role:PT'])->prefix('partner')->name('partner.')->group(function () {
        Route::get('/dashboard', [PartnerOrderController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [PartnerOrderController::class, 'index'])->name('orders.index');
        Route::get('/couriers', [PartnerOrderController::class, 'couriers'])->name('couriers.index');
        Route::get('/couriers/create', [PartnerOrderController::class, 'createCourier'])->name('couriers.create');
        Route::post('/couriers', [PartnerOrderController::class, 'storeCourier'])->name('couriers.store');
        Route::post('/couriers/{courier}/stock', [PartnerOrderController::class, 'issueCourierStock'])->whereNumber('courier')->name('couriers.stock.issue');
        Route::get('/couriers/{courier}/edit', [PartnerOrderController::class, 'editCourier'])->whereNumber('courier')->name('couriers.edit');
        Route::put('/couriers/{courier}', [PartnerOrderController::class, 'updateCourier'])->whereNumber('courier')->name('couriers.update');
        Route::delete('/couriers/{courier}', [PartnerOrderController::class, 'deactivateCourier'])->whereNumber('courier')->name('couriers.destroy');
        Route::patch('/couriers/{courier}/reactivate', [PartnerOrderController::class, 'reactivateCourier'])->whereNumber('courier')->name('couriers.restore');
        Route::get('/couriers/{courier}/documents/{document}', [PartnerOrderController::class, 'showCourierDocument'])->name('couriers.documents.show');
        Route::get('/orders/{order}', [PartnerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/delivery-fee', [PartnerOrderController::class, 'setDeliveryFee'])->name('orders.delivery-fee');
        Route::post('/orders/{order}/reschedule', [PartnerOrderController::class, 'rescheduleOrder'])->name('orders.reschedule');
        Route::post('/orders/{order}/cancel', [PartnerOrderController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/from-cart', [PartnerOrderController::class, 'storeFromCart'])->name('orders.from-cart');
        Route::post('/orders/{order}/payments/{payment}/confirm', [PartnerOrderController::class, 'confirmPayment'])->name('orders.payments.confirm');
        Route::post('/orders/{order}/ready', [PartnerOrderController::class, 'markReady'])->name('orders.ready');
        Route::post('/orders/{order}/assign', [PartnerOrderController::class, 'assignCourier'])->name('orders.assign');
        Route::post('/orders/{order}/handover', [PartnerOrderController::class, 'handover'])->name('orders.handover');
        Route::post('/orders/{order}/remittances/{payment}/confirm', [PartnerOrderController::class, 'confirmCashRemittance'])->name('orders.remittance.confirm');
    });

    Route::middleware(['role:LVS'])->prefix('courier/public-orders')->name('courier.public-orders.')->group(function () {
        Route::get('/', [CourierPublicOrderController::class, 'index'])->name('index');
        Route::post('/{order}/claim', [CourierPublicOrderController::class, 'claim'])->name('claim');
        Route::post('/{order}/receive', [CourierPublicOrderController::class, 'receive'])->name('receive');
        Route::post('/{order}/deliver', [CourierPublicOrderController::class, 'deliver'])->name('deliver');
        Route::post('/{order}/mobile-payment', [CourierPublicOrderController::class, 'startMobilePayment'])->middleware('throttle:8,1')->name('mobile-payment.start');
        Route::get('/{order}/mobile-payment/{payment}/status', [CourierPublicOrderController::class, 'mobilePaymentStatus'])->middleware('throttle:30,1')->name('mobile-payment.status');
    });

    // -------------------------------------------------------------------------
    // 4. DASHBOARD (ADMINUSER, MNG, SCR, LVS)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER,MNG,SCR,LVS'])->group(function () {
        Route::get('/Admin', [DashboarController::class, 'dashboard'])->name('Admin');
        Route::get('/rupture/products/stock', [DashboarController::class, 'rupture'])->name('rupture');
    });

    // -------------------------------------------------------------------------
    // 5. PRODUITS (LECTURE), COMMANDES, HISTORIQUES, CLASSEMENT
    //    (Accessible à ADMINUSER, MNG, SCR, VDS, CSA, PT)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER,MNG,SCR,VDS,PT,CSA,RCM'])->group(function () {
        Route::get('shipping/countries/{country}/cities', [CheckController::class, 'shippingCities'])->where('country', '[A-Za-z]{2}')->name('shipping.cities');
        Route::get('product/list', ProductList::class)->name('product');
        Route::get('product/cart', ProductCart::class)->name('productcart');
        Route::post('costumer/anonymize-all', [CostumerController::class, 'anonymizeAllFromCart'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('costumer.anonymize-all');
        Route::post('palce/order', [CheckController::class, 'palce_order'])->name('palceorder');
        Route::get('order/liste', OrderList::class)->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('order-liste-order');
        Route::get('edit/order/{order}', EditOrder::class)->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('edit-order');
        Route::get('order/list', [OrderController::class, 'index'])->name('order');
        Route::get('order/show/{id}', [OrderController::class, 'show'])->name('ordershow');
        Route::get('order/show/{order}/package-photos/{photo}/download', [LivreurController::class, 'downloadPackagePhoto'])->middleware('role:ADMINUSER,MNG')->name('ordershow.package-photo.download');
        Route::post('order/show/{id}/payment', [OrderController::class, 'updatePayment'])->middleware('role:ADMINUSER,MNG,RCM')->name('ordershow.payment');
        Route::get('order/cancel/list', [OrderController::class, 'order_cancel_list'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('order-cancel-list');
        Route::get('order/report/list', [OrderController::class, 'order_report_list'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('order-report-list');
        Route::get('ordered/list/ordered', [OrderController::class, 'ordered_list'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('ordered-list-orderer');
        Route::put('reporte/order/{id}', [OrderController::class, 'reporte_order'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('reporte-order');
        Route::put('refresh/specific/order/{id}', [OrderController::class, 'refresh_specific_order'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('refresh-specific-order');
        Route::get('order/check/type/{id}', [OrderController::class, 'check_type'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('check-type');
        Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA,RCM')->name('orders.invoice');
        Route::put('change/order/status/{id}', [OrderController::class, 'change_order_status'])->name('changestatus');
        Route::put('refresh/order/{id}', [OrderController::class, 'refresh_order'])->name('refreshorder');
        Route::put('edit/hours/{id}', [OrderController::class, 'edit_hours_order'])->name('edit-hours');
        Route::delete('delete/{id}', [OrderController::class, 'delete'])->name('deleteorder');

        Route::get('/history', [DashboarController::class, 'history'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('history');
        Route::get('/getProductOrders', [DashboarController::class, 'getProductOrders'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('getProductOrders');
        Route::get('repport/order', [OrderController::class, 'order_repport'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('repport-order');
        Route::get('costumer/achat/order', [OrderController::class, 'OrderFilter'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('achat');

        Route::get('ranking/ranking', [OrderController::class, 'ranking'])->middleware('role:ADMINUSER,MNG,SCR,VDS,PT,CSA')->name('ranking');
    });

    // Weekly delivery overview is also the delivery driver's ranking page.
    Route::middleware(['role:ADMINUSER,MNG,SCR,VDS,PT,CSA,LVS'])->group(function () {
        Route::get('show/deliveries', [OrderController::class, 'showDeliveries'])->name('show-deliveries');
    });

    // -------------------------------------------------------------------------
    // 6. LIVRAISONS (ADMINUSER, MNG, LVS)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER,MNG,LVS'])->group(function () {
        Route::get('livrable/list', [LivreurController::class, 'livrable'])->name('livrable');
        Route::get('livrable/show/{id}', [LivreurController::class, 'livrable_show'])->name('livrableshow');
        Route::post('livrable/show/{id}/payment', [LivreurController::class, 'updateOrderPayment'])->middleware('role:ADMINUSER,MNG')->name('livrableshow.payment');
        Route::post('livrable/show/{id}/mobile-payment', [LivreurController::class, 'startDeliveryMobilePayment'])->middleware(['role:LVS', 'throttle:8,1'])->name('livrableshow.mobile-payment.start');
        Route::get('livrable/show/{id}/mobile-payment/{payment}/status', [LivreurController::class, 'deliveryMobilePaymentStatus'])->middleware(['role:LVS', 'throttle:30,1'])->name('livrableshow.mobile-payment.status');
        Route::post('livrable/show/{id}/package-photos', [LivreurController::class, 'uploadPackagePhotos'])->name('livrableshow.package-photos');
        Route::get('livrable/show/{id}/package-photos/{photo}', [LivreurController::class, 'showPackagePhoto'])->name('livrableshow.package-photo');
        Route::get('auth/livrable/{id}', [LivreurController::class, 'auth_user_livrable'])->name('authlivrable');
        Route::get('unlock/{id}', [LivreurController::class, 'unlock'])->name('unlock');
        Route::post('check/livraison/{id}', [LivreurController::class, 'check_livraison'])->name('checklivraison');
        Route::put('change/order/status/user/{id}', [LivreurController::class, 'change_order_status_user'])->name('changestatususer');
    });

    // -------------------------------------------------------------------------
    // 7. CLIENTS, COMPTABILITÉ & STOCKS (ADMINUSER, MNG, CALLCENTER/VDS pour fiche client)
    // -------------------------------------------------------------------------
    Route::middleware(['role:ADMINUSER,MNG,SCR,VDS,PT,CSA,CALLCENTER,RCM'])->group(function () {
        Route::get('view/costumer/{id}', [CostumerController::class, 'viewcostumer'])->name('view-costumers');
        Route::get('costumer/search', [CostumerController::class, 'search'])->name('costumer.search');
    });

    Route::middleware(['role:ADMINUSER,MNG,VDS,PT'])->group(function () {
        Route::get('costumer/top', [CostumerController::class, 'topcostumer'])->name('top-costumers');
        Route::get('costumer/data', [CostumerController::class, 'datatable'])->name('costumer.data');
        Route::resource('costumer', CostumerController::class);
    });

    Route::middleware(['role:ADMINUSER,MNG,COMPTABLE'])->group(function () {
        Route::get('type/expensives', [TypeExpensiveController::class, 'index'])->name('type-expensives');
        Route::get('/expensives', [ExpensiveController::class, 'index'])->name('expensives');
        Route::get('erport/expensive/repport/expensives', [ExpensiveController::class, 'repport'])->name('repport-expensives');
    });

    Route::middleware(['role:ADMINUSER,MNG'])->group(function () {
        Route::delete('destroy/expensives/{id}', [TypeExpensiveController::class, 'destroy'])->name('destroy-expensives');
        Route::post('type/expensives/create', [TypeExpensiveController::class, 'create'])->name('type-expensives-create');
        Route::put('type/expensives/expensives/{id}', [TypeExpensiveController::class, 'update'])->name('typeexpensives');
        Route::post('/expensives/create', [ExpensiveController::class, 'create'])->name('expensives-create');
        Route::put('/expensives/{id}', [ExpensiveController::class, 'update'])->name('expensives-update');
    });

    Route::middleware(['role:ADMINUSER,MNG'])->group(function () {
        Route::get('show/{id}/product', [StockController::class, 'show_product'])->name('show-product');
        Route::post('entrer/stock/{id}', [StockController::class, 'enter_stocks'])->name('enter_stock');
        Route::post('out/stock/{id}', [StockController::class, 'out_stock'])->name('out_stock');
    });

    Route::middleware(['role:ADMINUSER,MNG,COMPTABLE'])->group(function () {
        Route::get('/audit-produits', ProductAuditReport::class)->name('audit.products');
    });

});
