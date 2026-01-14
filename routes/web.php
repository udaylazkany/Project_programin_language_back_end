<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;

// صفحة تسجيل الدخول
Route::get('/login', [AdminController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminController::class, 'login']);

// صفحة العملاء
Route::get('/clients', [ClientController::class, 'index'])->middleware('auth');

// تغيير الحالة
Route::post('/clients/toggle-status/{id}', [ClientController::class, 'toggleStatus'])->middleware('auth');

// تغيير الدور
Route::post('/clients/toggle-role/{id}', [ClientController::class, 'toggleRole'])->middleware('auth');

// تسجيل الخروج
Route::post('/logout', [AdminController::class, 'logout'])->name('logout')->middleware('auth');
