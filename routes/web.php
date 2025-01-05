<?php

use App\Http\Livewire\RepportOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PubController;
use App\Http\Controllers\FondController;
use App\Http\Controllers\EpargneController;
use App\Http\Controllers\ImprevuController;
use App\Http\Controllers\PercentController;
use App\Http\Controllers\RetraitController;
use App\Http\Livewire\Products\ProductCart;
use App\Http\Livewire\Products\ProductForm;
use App\Http\Livewire\Products\ProductList;
use App\Http\Controllers\Api\EcomController;
use App\Http\Controllers\Stock\StockController;
use App\Http\Controllers\Admins\CheckController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Admins\DashboarController;
use App\Http\Controllers\Livreurs\LivreurController;
use App\Http\Controllers\Costumers\CostumerController;
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
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/tenacos/parthner',[DashboarController::class,'register']);
Route::get('/space',[EcomController::class,'space']);
Route::post('/store/pathner',[DashboarController::class,'store_pathner'])->name('storepathner');


Route::middleware(['auth'])->group(function () {
Route::get('type/expensives',[TypeExpensiveController::class,'index'])->name('type-expensives');
Route::delete('destroy/expensives/{id}',[TypeExpensiveController::class,'destroy'])->name('destroy-expensives');
Route::post('type/expensives/create',[TypeExpensiveController::class,'create'])->name('type-expensives-create');
Route::put('type/expensives/expensives/{id}', [TypeExpensiveController::class,'update'])->name('typeexpensives');


Route::get('erport/expensive/repport/expensives',[ExpensiveController::class,'repport'])->name('repport-expensives');

Route::get('/expensives',[ExpensiveController::class,'index'])->name('expensives');
Route::delete('destroy/expensives/{id}',[ExpensiveController::class,'destroy'])->name('destroy-expensives');
Route::post('/expensives/create',[ExpensiveController::class,'create'])->name('expensives-create');
Route::put('/expensives/{id}', [ExpensiveController::class,'update'])->name('expensives-update');


Route::get('show/{id}/product',[StockController::class,'show_product'])->name('show-product');
Route::post('entrer/stock/{id}',[StockController::class,'enter_stocks'])->name('enter_stock');
Route::post('out/stock/{id}',[StockController::class,'out_stock'])->name('out_stock');
Route::put('update/product/price/{id}',[StockController::class,'update_product_price'])->name('update-product-price');

Route::get('/Admin',[DashboarController::class,'dashboard'])->name('Admin');

Route::get('/brouillon/brouillons',[BrouillonController::class,'index'])->name('brouillons');
Route::get('/brouillon/show/{id}',[BrouillonController::class,'show'])->name('brouillonshow');

Route::get('/consultation',[DashboarController::class,'consultation'])->name('consultation');

Route::put('/set/password/{id}',[DashboarController::class,'setpassword'])->name('set-password');

Route::get('/rupture/products/stock',[DashboarController::class,'rupture'])->name('rupture');

Route::get('/parthners/order',[BrouillonController::class,'parthners_order'])->name('parthnersorder');

Route::get('/parthners',[BrouillonController::class,'parthners'])->name('parthners');

Route::get('unlock/brouillon/{id}',[BrouillonController::class,'unlock_brouillon'])->name('unlockbrouillon');

Route::get('/history',[DashboarController::class,'history'])->name('history');
Route::get('/getProductOrders',[DashboarController::class,'getProductOrders'])->name('getProductOrders');

Route::get('settings/config',[DashboarController::class,'settings'])->name('setting');
Route::post('settinginfo/config',[DashboarController::class,'settinginfo'])->name('setting-info');
Route::put('settinginfo/update/config/{id}',[DashboarController::class,'settinginfoupdate'])->name('setting-update');


Route::get('product/list',ProductList::class)->name('product');
Route::get('product/form',ProductForm::class)->name('productform');

Route::get('product/cart',ProductCart::class)->name('productcart');
Route::get('costumer/top',[CostumerController::class,'topcostumer'])->name('top-costumers');
Route::get('view/costumer/{id}',[CostumerController::class,'viewcostumer'])->name('view-costumers');

Route::resource('costumer',CostumerController::class);

Route::post('palce/order',[CheckController::class,'palce_order'])->name('palceorder');

Route::get('order/list',[OrderController::class,'index'])->name('order');
Route::get('order/cancel/list',[OrderController::class,'order_cancel_list'])->name('order-cancel-list');
Route::get('order/report/list',[OrderController::class,'order_report_list'])->name('order-report-list');
Route::get('ordered/list/ordered',[OrderController::class,'ordered_list'])->name('ordered-list-orderer');
Route::put('reporte/order/{id}',[OrderController::class,'reporte_order'])->name('reporte-order');

Route::put('refresh/specific/order/{id}',[OrderController::class,'refresh_specific_order'])->name('refresh-specific-order');

Route::get('order/check/type/{id}',[OrderController::class,'check_type'])->name('check-type');

Route::get('order/show/{id}',[OrderController::class,'show'])->name('ordershow');

Route::put('change/order/status/{id}',[OrderController::class,'change_order_status'])->name('changestatus');

Route::put('refresh/order/{id}',[OrderController::class,'refresh_order'])->name('refreshorder');

Route::get('trie/order/parther',[OrderController::class,'trie_order_parther'])->name('trie-order-parther');

Route::put('edit/hours/{id}',[OrderController::class,'edit_hours_order'])->name('edit-hours');

Route::delete('delete/{id}',[OrderController::class,'delete'])->name('deleteorder');

Route::resource('livreurs',LivreurController::class);

Route::get('user/admin/list',[LivreurController::class,'user_admin_list'])->name('useradminlist');
Route::get('user/show/{id}',[LivreurController::class,'user_show'])->name('user-show');

Route::get('livrable/list',[LivreurController::class,'livrable'])->name('livrable');

Route::get('livrable/show/{id}',[LivreurController::class,'livrable_show'])->name('livrableshow');

Route::get('auth/livrable/{id}',[LivreurController::class,'auth_user_livrable'])->name('authlivrable');

Route::get('unlock/{id}',[LivreurController::class,'unlock'])->name('unlock');

Route::get('archive/list',[LivreurController::class,'archive_list'])->name('archivelist');

Route::post('check/livraison/{id}',[LivreurController::class,'check_livraison'])->name('checklivraison');


Route::put('change/order/status/user/{id}',[LivreurController::class,'change_order_status_user'])->name('changestatususer');

Route::get('edit/product/{id}',[DashboarController::class,'edit'])->name('editproduct');
Route::get('active/user/{id}',[DashboarController::class,'active'])->name('active-user');

Route::put('update/product/{id}',[DashboarController::class,'updates'])->name('updateproduct');

Route::delete('edit/product/{id}',[DashboarController::class,'delete'])->name('delteproduct');

Route::get('list/percent',[PercentController::class,'index'])->name('list-percent');
Route::post('post/percent',[PercentController::class,'create'])->name('create-post');
Route::put('edit/percent/{id}',[PercentController::class,'edit'])->name('percen-edit');

Route::get("pub/list",[PubController::class,'index'])->name('pub-list');
Route::post("add/retrait/pub",[PubController::class,'retraitpub'])->name('retrait-pub');

Route::get("imprevue/list",[ImprevuController::class,'index'])->name('imprevu-list');

Route::post("add/retrait/imprevu",[ImprevuController::class,'retraitimprevu'])->name('retrait-imprevu');

Route::get("fond/list",[FondController::class,'index'])->name('fond-list');
Route::post("fond/list",[FondController::class,'retrait'])->name('fond-retrait');
Route::get("epargne/list",[EpargneController::class,'index'])->name('epargne-list');

Route::get('retraits/list',[RetraitController::class,'index'])->name('retrait-list');

Route::get('retraits/list/imprevue',[RetraitController::class,'indeximpre'])->name('index-impre');

Route::get('retraits/list/pub',[RetraitController::class,'indexpub'])->name('index-pub');
Route::get('show/deliveries',[OrderController::class,'showDeliveries'])->name('show-deliveries');

Route::get('repport/order',[OrderController::class,'order_repport'])->name('repport-order');
});
