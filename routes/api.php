<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('orders', OrderController::class);

    Route::get('orders/{order}/payments', [PaymentController::class, 'forOrder']);
    Route::post('orders/{order}/payments', [PaymentController::class, 'store']);

    Route::get('payment-methods', [PaymentController::class, 'methods']);
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});
