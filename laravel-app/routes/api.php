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
    return auth()->user();
});

Route::get('/healthz', function () {
    return response()->json(["status" => "OK"], 200);
});
Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::name('customers')->group(base_path('routes/customer/customer.php'));
    Route::name('items')->group(base_path('routes/item/item.php'));
    Route::name('auth')->group(base_path('routes/auth/auth.php'));
    Route::name('google_auth')->group(base_path('routes/auth/googleAuth.php'));
    Route::name('invoices')->group(base_path('routes/invoice/invoice.php'));
    Route::name('transactions')->group(base_path('routes/transaction/transaction.php'));
    Route::name('notifications')->group(base_path('routes/notification/notification.php'));
    Route::name('pin')->group(base_path('routes/pin/pin.php'));
    Route::name('comments')->group(base_path('routes/comment/comment.php'));
    Route::name('organizations')->group(base_path('routes/organization/organization.php'));
    Route::name('billing')->group(base_path('routes/billing/billing.php'));
    Route::name('messengers')->group(base_path('routes/messenger/messenger.php'));
    Route::post('send-email', [InvoiceController::class, 'sendEmail']);
    Route::post('send-multiple-email', [InvoiceController::class, 'sendMultipleEmail']);
    Route::name('dashboard')->group(base_path('routes/dashboard/dashboard.php'));
});

