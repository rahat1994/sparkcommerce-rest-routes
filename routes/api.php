<?php

use Illuminate\Support\Facades\Route;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\AuthController;

Route::group(['prefix' => 'sc/v1'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/confirm-password', [AuthController::class, 'confirmPassword']);
        Route::post('/update-password', [AuthController::class, 'updatePassword']);
        Route::post('/update-profile ', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);
