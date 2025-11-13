<?php

use Illuminate\Support\Facades\Route;

use App\Models\Product;
use App\Models\SubProduct;

Route::get('/', function () {
    return view('welcome');
});


use App\Http\Controllers\PipedriveController;

Route::get('/oauth/callback', [PipedriveController::class, 'oauthCallback'])->name('oauth.callback');
Route::get('/panel', [PipedriveController::class, 'panel'])->name('panel');

Route::get('/api/transactions', [PipedriveController::class, 'transactions'])->name('transactions');

Route::get('/getproducts',function(){
    return Product::with('subproducts')->get();
});
Route::get('/getsubproducts',function(){
    return SubProduct::with('product')->get();
});

