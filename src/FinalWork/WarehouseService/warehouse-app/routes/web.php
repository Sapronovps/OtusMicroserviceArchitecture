<?php

use App\Http\Controllers\MetricsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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

Route::resource('users', UserController::class);

Route::get('products', [ProductsController::class, 'index'])->name('products.index');
Route::get('orders', [OrdersController::class, 'index'])->name('orders.index');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [StatusController::class, 'index']);
Route::get('/500', [StatusController::class, 'register500']);

Route::get('/metrics', [MetricsController::class, 'index']);
