<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\GoogleAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    Route::resource('auth', AuthController::class)->names('auth');
});

Route::get('/v1/login', function () {
    return view('welcome');
});

// Route to redirect to Google's OAuth page
Route::middleware(['web'])->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
    // Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

});

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/user', [GoogleAuthController::class, 'getAnnualReportUserInfo']);
    Route::get('/publicSafety/user', [GoogleAuthController::class, 'getPublicSafetyUserInfo']);
});
