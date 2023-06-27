<?php

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

Route::group(['prefix' => 'billing'], function () {
    Route::get('/check-subscription', [\App\Http\Controllers\SubscriptionController::class, 'checkSubcription']);
    Route::get('/check-card', [\App\Http\Controllers\SubscriptionController::class, 'checkCard']);
    Route::post('/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe']);
    Route::post('/cancel-subscription', [\App\Http\Controllers\SubscriptionController::class, 'cancelSubscription']);
    Route::post('/create-payment-method', [\App\Http\Controllers\SubscriptionController::class, 'createPaymentMethod']);
    Route::post('/detach-payment', [\App\Http\Controllers\SubscriptionController::class, 'detachPaymentMethod']);
    Route::post('/trial-plan', [\App\Http\Controllers\SubscriptionController::class, 'trialSubscription']);
});
