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
Route::get('/items/all', [\App\Http\Controllers\ItemController::class, 'itemlist']);
Route::get('/items/export', [\App\Http\Controllers\ItemController::class, 'exportCsv']);
Route::resource('/items', \App\Http\Controllers\ItemController::class);
Route::post('/items/validate-csv', [\App\Http\Controllers\ItemController::class, 'validateCSV']);
Route::post('/items/import', [\App\Http\Controllers\ItemController::class, 'saveCSV']);
