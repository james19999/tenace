<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\EcomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('gest-plus')->group(function () {

 Route::post('login/user',[ApiController::class,'login']);
 Route::post('AddTo/Cart',[EcomController::class,'AddToCart']);
 Route::get('Cart/List',[EcomController::class,'CartList']);

 Route::middleware('auth:sanctum')->group(function () {
   Route::post('logout/user',[ApiController::class,'logout_user']);
   Route::post('change/order:status/user/{id}',[ApiController::class,'change_order_status_user']);
   Route::get('get/order/livrable',[ApiController::class,'get_order_livrable']);
   Route::get('order/detail/{id}',[ApiController::class,'order_detail']);
   Route::get('check/livraison/take/{id}',[ApiController::class,'check_livraison_take']);
   Route::get('auth/user/livrable',[ApiController::class,'auth_user_livrable']);
   Route::get('auth/user/livrable/list',[ApiController::class,'auth_user_livrable_list']);
 });

});
