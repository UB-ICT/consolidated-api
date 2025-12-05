<?php

use Illuminate\Support\Facades\Route;
use Modules\PublicSafety\Http\Controllers\RoleController;
use Modules\PublicSafety\Http\Controllers\PermissionController;
use Modules\PublicSafety\Http\Controllers\UserController;
use Modules\PublicSafety\Http\Controllers\UserCampusController;
use Modules\PublicSafety\Http\Controllers\CampusController;
use Modules\PublicSafety\Http\Controllers\MessageCategoryController;
use Modules\PublicSafety\Http\Controllers\BuildingController;
use Modules\PublicSafety\Http\Controllers\IncidentReportController;
use Modules\PublicSafety\Http\Controllers\IncidentStatusController;
use Modules\PublicSafety\Http\Controllers\MenuController;
use Modules\PublicSafety\Http\Controllers\FileUploadController;
use Modules\PublicSafety\Http\Controllers\MessageController;


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


// This will be the only unprotected route because this is used for authentication

Route::group([
    'prefix' => 'v1/publicSafety',
    'namespace' => 'Modules\PublicSafety\Http\Controllers',
    'middleware' => 'auth:sanctum',
], function () {


    Route::apiResource('users', UserController::class);
    Route::get('usersTotal', [UserController::class, 'getTotalUsers']);


    // New routes for CampusController
    Route::get('campuses', [CampusController::class, 'index']);
    Route::post('campuses', [CampusController::class, 'store']);
    Route::get('campuses/{campusID}', [CampusController::class, 'show']);
    Route::put('campuses/{campusID}', [CampusController::class, 'update']);
    Route::delete('campuses/{campusID}', [CampusController::class, 'destroy']);

    Route::get('buildings', [BuildingController::class, 'index']);
    Route::post('buildings', [BuildingController::class, 'store']);
    Route::get('buildings/{buildingID}', [BuildingController::class, 'show']);
    Route::put('buildings/{buildingID}', [BuildingController::class, 'update']);
    Route::delete('buildings/{buildingID}', [BuildingController::class, 'destroy']);

    // Route::apiResource('messageCategories', MessageCategoryController::class);
    // Route::apiResource('messages', MessageController::class);
    // Route::apiResource('userCampuses', UserCampusController::class);
    // Route::apiResource('incidentStatuses', IncidentStatusController::class);

    Route::get('/incidentReports', [IncidentReportController::class, 'index']);
    Route::post('/incidentReports', [IncidentReportController::class, 'store']);
    Route::get('incidentReports/{incidentReportID}', [IncidentReportController::class, 'show']);
    Route::put('incidentReports/{incidentReportID}', [IncidentReportController::class, 'update']);
    Route::delete('incidentReports/{incidentReportID}', [IncidentReportController::class, 'destroy']);
    Route::get('incidentReportTotal', [IncidentReportController::class, 'getTotalIncidentReport']);

    Route::post('/uploadIncidentReportPhoto/{IncidentReportID}', [FileUploadController::class, 'uploadIncidentReportPhoto']);

    //menus
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/menu', [MenuController::class, 'store']);
    Route::put('/menu/{id}', [MenuController::class, 'update']);
    Route::delete('/menu/{id}', [MenuController::class, 'destroy']);
});
