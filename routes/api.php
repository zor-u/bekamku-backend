<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');

Route::prefix('user')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:30,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:30,1');

    Route::middleware(['auth:admin-api', 'staff.active'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        Route::middleware('staff.admin')->group(function () {
            Route::get('/staff', [StaffController::class, 'index']);
            Route::patch('/staff/{admin}', [StaffController::class, 'update']);
        });
    });
});
