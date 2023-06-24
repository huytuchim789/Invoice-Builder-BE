<?php

use App\Http\Controllers\EmailTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix'=>'billing'], function(){
    Route::post('/subscribe', [\App\Http\Controllers\SubscriptionController::class,'subscribe']);
    Route::post('/cancel-subscription', [\App\Http\Controllers\SubscriptionController::class,'cancelSubscription']);
    Route::post('/create-payment-method', [\App\Http\Controllers\SubscriptionController::class,'createPaymentMethod']);
Route::get('/check-subscription', [\App\Http\Controllers\SubscriptionController::class,'checkSubscription']);
});
