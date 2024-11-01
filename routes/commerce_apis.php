<?php

use Illuminate\Support\Facades\Route;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\CartController;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\OrderController;


Route::group(['prefix' => 'sc/v1'], function () {

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/associate_anonymous_cart', [CartController::class, 'associateAnonymousCart']);
        Route::post('/checkout', [CartController::class, 'checkout']);
        Route::post('/validate-coupon', [CartController::class, 'validateCoupon']);
    });

    Route::get('/cart/{reference?}', [CartController::class, 'getCart']);
    Route::post('/cart/{reference?}', [CartController::class, 'addToCart']);

    Route::delete('/cart/clear_all', [CartController::class, 'clearUserCart']);
    Route::delete('/cart/{slug}/{reference?}', [CartController::class, 'removeFromCart']);

    Route::group(['prefix' => 'orders', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{trackingNumber}', [OrderController::class, 'show']);
    });
});
