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

Route::middleware(['auth'])->group(function () {


Route::get('/Admin',[DashboarController::class,'dashboard'])->name('Admin');

Route::get('/brouillon/brouillons',[BrouillonController::class,'index'])->name('brouillons');
Route::get('/brouillon/show/{id}',[BrouillonController::class,'show'])->name('brouillonshow');

Route::get('/parthners',[BrouillonController::class,'parthners'])->name('parthners');

Route::get('unlock/brouillon/{id}',[BrouillonController::class,'unlock_brouillon'])->name('unlockbrouillon');

Route::get('/history',[DashboarController::class,'history'])->name('history');

Route::get('product/list',ProductList::class)->name('product');
Route::get('product/form',ProductForm::class)->name('productform');

Route::get('product/cart',ProductCart::class)->name('productcart');

Route::resource('costumer',CostumerController::class);

Route::post('palce/order',[CheckController::class,'palce_order'])->name('palceorder');

Route::get('order/list',[OrderController::class,'index'])->name('order');

Route::get('order/show/{id}',[OrderController::class,'show'])->name('ordershow');

Route::put('change/order/status/{id}',[OrderController::class,'change_order_status'])->name('changestatus');

Route::put('refresh/order/{id}',[OrderController::class,'refresh_order'])->name('refreshorder');

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
