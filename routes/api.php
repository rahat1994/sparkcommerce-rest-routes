<?php

use Illuminate\Http\Request;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\AuthController;

Route::group(['prefix' => 'sc/v1'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);