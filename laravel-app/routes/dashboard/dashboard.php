<?php

use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(function () {
    Route::get('get-total-invoices-sum-in-month', [\App\Http\Controllers\DashboardController::class, 'getTotalInvoiceSumInMonth']);
    Route::get('get-analytics', [\App\Http\Controllers\DashboardController::class, 'getAnalytics']);
    Route::get('payment-history', [\App\Http\Controllers\DashboardController::class, 'getPaymentsHistory']);
    Route::get('recently-paid-invoices', [\App\Http\Controllers\DashboardController::class, 'getRecentlyPaidInvoices']);
});
