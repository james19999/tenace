<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Products\ProductCart;
use App\Http\Livewire\Products\ProductForm;
use App\Http\Livewire\Products\ProductList;
use App\Http\Controllers\Admins\CheckController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Admins\DashboarController;
use App\Http\Controllers\Livreurs\LivreurController;
use App\Http\Controllers\Costumers\CostumerController;
use App\Http\Controllers\Brouillons\BrouillonController;
use App\Http\Controllers\Stock\StockController;
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
Route::post('/store/pathner',[DashboarController::class,'store_pathner'])->name('storepathner');


Route::middleware(['auth'])->group(function () {
Route::get('type/expensives',[TypeExpensiveController::class,'index'])->name('type-expensives');
Route::delete('destroy/expensives/{id}',[TypeExpensiveController::class,'destroy'])->name('destroy-expensives');
Route::post('type/expensives/create',[TypeExpensiveController::class,'create'])->name('type-expensives-create');

Route::get('show/{id}/product',[StockController::class,'show_product'])->name('show-product');
Route::post('entrer/stock/{id}',[StockController::class,'enter_stocks'])->name('enter_stock');
Route::post('out/stock/{id}',[StockController::class,'out_stock'])->name('out_stock');

Route::get('/Admin',[DashboarController::class,'dashboard'])->name('Admin');

Route::get('/brouillon/brouillons',[BrouillonController::class,'index'])->name('brouillons');
Route::get('/brouillon/show/{id}',[BrouillonController::class,'show'])->name('brouillonshow');
Route::get('/consultation',[DashboarController::class,'consultation'])->name('consultation');

Route::get('/parthners/order',[BrouillonController::class,'parthners_order'])->name('parthnersorder');

Route::get('/parthners',[BrouillonController::class,'parthners'])->name('parthners');

Route::get('unlock/brouillon/{id}',[BrouillonController::class,'unlock_brouillon'])->name('unlockbrouillon');

Route::get('/history',[DashboarController::class,'history'])->name('history');
Route::get('/getProductOrders',[DashboarController::class,'getProductOrders'])->name('getProductOrders');



Route::get('product/list',ProductList::class)->name('product');
Route::get('product/form',ProductForm::class)->name('productform');

Route::get('product/cart',ProductCart::class)->name('productcart');
Route::get('costumer/top',[CostumerController::class,'topcostumer'])->name('top-costumers');
Route::get('view/costumer/{id}',[CostumerController::class,'viewcostumer'])->name('view-costumers');

Route::resource('costumer',CostumerController::class);

Route::post('palce/order',[CheckController::class,'palce_order'])->name('palceorder');

Route::get('order/list',[OrderController::class,'index'])->name('order');

Route::get('order/check/type/{id}',[OrderController::class,'check_type'])->name('check-type');

Route::get('order/show/{id}',[OrderController::class,'show'])->name('ordershow');

Route::put('change/order/status/{id}',[OrderController::class,'change_order_status'])->name('changestatus');

Route::put('refresh/order/{id}',[OrderController::class,'refresh_order'])->name('refreshorder');

Route::put('edit/hours/{id}',[OrderController::class,'edit_hours_order'])->name('edit-hours');

Route::delete('delete/{id}',[OrderController::class,'delete'])->name('deleteorder');

Route::resource('livreurs',LivreurController::class);

Route::get('user/admin/list',[LivreurController::class,'user_admin_list'])->name('useradminlist');

Route::get('livrable/list',[LivreurController::class,'livrable'])->name('livrable');

Route::get('livrable/show/{id}',[LivreurController::class,'livrable_show'])->name('livrableshow');

Route::get('auth/livrable/{id}',[LivreurController::class,'auth_user_livrable'])->name('authlivrable');

Route::get('unlock/{id}',[LivreurController::class,'unlock'])->name('unlock');

Route::get('archive/list',[LivreurController::class,'archive_list'])->name('archivelist');

Route::post('check/livraison/{id}',[LivreurController::class,'check_livraison'])->name('checklivraison');


Route::put('change/order/status/user/{id}',[LivreurController::class,'change_order_status_user'])->name('changestatususer');

Route::get('edit/product/{id}',[DashboarController::class,'edit'])->name('editproduct');

Route::put('update/product/{id}',[DashboarController::class,'updates'])->name('updateproduct');

Route::delete('edit/product/{id}',[DashboarController::class,'delete'])->name('delteproduct');

});
