<?php

use App\Http\Controllers\InvoiceController;
use App\Jobs\SendMailJob;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/healthz', function () {
    return response()->json(["a" => "a"], 200);
});
Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::name('customers')->group(base_path('routes/customer/customer.php'));
    Route::name('auth')->group(base_path('routes/auth/auth.php'));
    Route::name('google_auth')->group(base_path('routes/auth/googleAuth.php'));
    Route::name('invoices')->group(base_path('routes/invoice/invoice.php'));
});

Route::post('send-email', [InvoiceController::class, 'sendEmail']);
