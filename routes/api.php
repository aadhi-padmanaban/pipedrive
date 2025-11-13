<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SubProductController;
use App\Http\Controllers\QuestionsController;
use App\Http\Controllers\QuizzController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\YearsController;
use App\Http\Controllers\AnswersController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TerminalController;



Route::get('/test',function(){
    return 'Hi';
});

Route::middleware('web')->get('/connect/authorize', [StripeConnectController::class,'authorize']);
Route::middleware('web')->get('/connect/callback', [StripeConnectController::class,'callback']);

// Mobile session exchange (GET)
Route::get('/auth/mobile/session/{session_id}', [StripeConnectController::class,'mobileSession']);

// Example me endpoint (protect in production)
Route::get('/me', [StripeConnectController::class,'me']);

Route::get('/transaction/payments',[StripeController::class,'payments']);
Route::post('/terminal/connection_token', [TerminalController::class, 'connectionToken']);


// Route::post('/terminal/create_payment_intent', [TerminalController::class, 'createPaymentIntent']);
// Route::get('/terminal/retrieve_payment_intent/{id}', [TerminalController::class, 'retrievePaymentIntent']);

// Route::get('/terminal/connection_token', [TerminalController::class, 'xx']);