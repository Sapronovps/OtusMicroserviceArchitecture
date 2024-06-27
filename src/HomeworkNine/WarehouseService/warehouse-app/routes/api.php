<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
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

Route::apiResource('users', UserController::class);

Route::controller(AuthController::class)->group(function () {
   Route::post('login', 'login');
   Route::post('register', 'register');
   Route::post('logout', 'logout');
   Route::post('refresh', 'refresh');
   Route::get('user/{id}', 'show');
   Route::put('user/{id}', 'update');
   Route::get('users', 'index');
});

Route::controller(ProductController::class)->group(function () {
    Route::get('products', 'index');
    Route::post('product', 'store');
    Route::get('product/{id}', 'show');
    Route::put('product/{id}', 'update');
    Route::delete('product/{id}', 'delete');
});


Route::controller(OrderController::class)->group(function () {
   Route::get('orders', 'index');
   Route::post('order', 'store');
   Route::post('order/shipment', 'shipment');
   Route::get('env', 'getEnv');
   Route::get('testKafka', 'testKafka');
});
