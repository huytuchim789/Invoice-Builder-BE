<?php

use App\Http\Controllers\CustomerController;
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

// Route::group([
//     'prefix' => '/'
// ], function ($router) {
// });
Route::get('/customers/export', [CustomerController::class, 'exportCsv']);
Route::resource('/customers', CustomerController::class);
Route::post('/customers/validate-csv', [CustomerController::class, 'validateCSV']);
Route::post('/customers/import', [CustomerController::class, 'saveCSV']);
