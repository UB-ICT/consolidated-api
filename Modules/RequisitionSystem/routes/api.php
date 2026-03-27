<?php

use Illuminate\Support\Facades\Route;
use Modules\RequisitionSystem\Http\Controllers\RequisitionSystemController;
use Modules\RequisitionSystem\Http\Controllers\CostCenterController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('requisitionsystem', RequisitionSystemController::class)->names('requisitionsystem');
});

// The "Read" route (GET)
Route::get('/cost-centers', [CostCenterController::class, 'index']);

// The "Create" route (POST)
Route::post('/cost-centers', [CostCenterController::class, 'store']);