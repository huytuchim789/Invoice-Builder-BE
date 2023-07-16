<?php

use App\Http\Controllers\InvoiceController;
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
Route::get('/invoices/{invoiceId}/list-pins', [InvoiceController::class, 'listPins']);
Route::resource('/invoices', InvoiceController::class);
Route::get('/invoices/{id}/download', [InvoiceController::class, 'downloadFile'])->name('invoices.downloadFile');
Route::post('/invoices/pay-invoice', [InvoiceController::class, 'payInvoice'])->name('invoices.payInvoice');
Route::post('/invoices/get-total-sum', [InvoiceController::class, 'getTotalSum'])->name('invoices.getTotalSum');
