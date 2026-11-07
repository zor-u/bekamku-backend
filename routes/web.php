<?php

use App\Http\Controllers\Auth\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login'])
        ->middleware('guest:admin')
        ->name('admin.authenticate');

    Route::get('/login', fn () => response()->json([
        'message' => 'Admin login required.',
    ]))->name('admin.login');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/', [AdminController::class, 'me'])->name('admin.dashboard');
        Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
    });
});
