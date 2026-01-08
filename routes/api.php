<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\CommentController;

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

Route::middleware(['auth:sanctum', 'throttle:60,1'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('admin/login',[AdminController::class,'login']);
Route::post('client/register',[ClientController::class,'register']);
Route::post('client/login',[ClientController::class,'login']);


Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::put('admin/editapprove/{id}',[AdminController::class,'edit_is_approved']);
    Route::put('admin/edit_Role/{id}',[AdminController::class,'edit_Role']);
    
});


Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('client/addApartment',[ApartmentController::class,'addApartment']);
    Route::get('client/showAll',[ApartmentController::class,'showAll']);
    Route::get('client/showmyAll',[ApartmentController::class,'showmyAll']);
    Route::get('client/showOne/{id}',[ApartmentController::class,'showOne']);
    Route::post('/apartment/status', [ApartmentController::class, 'getStatus']);
    Route::post('/apartment/canceled', [ApartmentController::class, 'cancelBooking']);
    Route::post('/apartments/contractsbook', [ApartmentController::class, 'bookApartment']);
    Route::get('/apartment/mycontracts', [ApartmentController::class, 'myContracts']);
    Route::post('/apartment/updateBooking', [ApartmentController::class, 'updateBooking']);
    Route::post('/apartments/filter', [ApartmentController::class, 'filterApartments']);
    Route::get('/apartments/can-comment/{apartmentId}/{tenantId}', [CommentController::class, 'canComment']);
    Route::post('/apartments/Addcomments', [CommentController::class, 'store']);
Route::get('/apartments/comments/{id}', [CommentController::class, 'getApartmentComments']);
    Route::post('client/logout', [ClientController::class, 'logout']);


});



