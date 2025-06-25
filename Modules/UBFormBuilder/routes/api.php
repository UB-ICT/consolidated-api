<?php

use Illuminate\Support\Facades\Route;
use Modules\UBFormBuilder\Http\Controllers\FormBuilderController;
use Modules\UBFormBuilder\Http\Controllers\UBFormBuilderController;

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

Route::middleware(['auth:sanctum'])->prefix('v1/UBFormBuilder')->group(function () {
    Route::apiResource('ubformbuilder', UBFormBuilderController::class)->names('ubformbuilder');

    Route::get('/', [FormBuilderController::class, 'index']);
    Route::post('/', [FormBuilderController::class, 'store']);
    Route::get('/{id}', [FormBuilderController::class, 'show']);
    Route::put('/{id}', [FormBuilderController::class, 'update']);
    Route::delete('/{id}', [FormBuilderController::class, 'destroy']);

    // Form builder specific routes
    Route::get('/{id}/builder', [FormBuilderController::class, 'getBuilderSchema']);
    Route::post('/{id}/builder', [FormBuilderController::class, 'saveBuilderSchema']);

    // Form submission routes
    Route::post('/{id}/submit', [FormBuilderController::class, 'submitForm']);
    Route::get('/{id}/submissions', [FormBuilderController::class, 'getSubmissions']);
});
