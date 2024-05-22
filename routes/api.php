<?php

use Illuminate\Http\Request;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\AuthController;
 
Route::post('/login', [AuthController::class, 'login']);