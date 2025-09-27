<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


use App\Http\Controllers\PipedriveController;

Route::get('/oauth/callback', [PipedriveController::class, 'oauthCallback'])->name('oauth.callback');
// Route::get('/panel', [PipedriveController::class, 'panel'])->name('panel');
Route::match(['get', 'post'], '/panel', [PipedriveController::class, 'show'])->name('show');

Route::get('/api/transactions', [PipedriveController::class, 'transactions'])->name('transactions');

