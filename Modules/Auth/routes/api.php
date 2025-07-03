<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\GoogleAuthController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::prefix('auth/google')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('callback', [GoogleAuthController::class, 'callback']);
    Route::post('logout', [GoogleAuthController::class, 'logout'])->middleware('auth:sanctum');
});
