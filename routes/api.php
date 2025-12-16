<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;

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
Route::post('admin/login',[AdminController::class,'login']);


Route::post('client/register',[ClientController::class,'register']);
Route::post('client/login',[ClientController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::put('admin/editapprove/{id}',[AdminController::class,'edit_is_approved']);

});