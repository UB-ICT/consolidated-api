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
use Modules\PublicSafety\Http\Controllers\IncidentTypeController;
use Modules\PublicSafety\Models\IncidentReport;
use Modules\PublicSafety\Http\Controllers\EndOfShiftReportPatrolController;

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


    // Route::apiResource('users', UserController::class);

    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{userID}', [UserController::class, 'show']);
    Route::put('users/{userID}', [UserController::class, 'update']);
    Route::delete('users/{userID}', [UserController::class, 'destroy']);
    Route::get('usersTotal', [UserController::class, 'getTotalUsers']);
    Route::get('users/email/{email}', [UserController::class, 'getUserByEmail']);
    Route::post('users/device-token', [UserController::class, 'updateDeviceToken'])->middleware('auth:sanctum');
    Route::get('users/profile', [UserController::class, 'getProfile'])->middleware('auth:sanctum');


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

    Route::get('incidentTypes', [IncidentTypeController::class, 'index']);
    Route::post('incidentTypes', [IncidentTypeController::class, 'store']);
    Route::put('incidentTypes/{id}', [IncidentTypeController::class, 'update']);
    Route::delete('incidentTypes/{id}', [IncidentTypeController::class, 'destroy']);

    // Route::apiResource('messageCategories', MessageCategoryController::class);
    // Route::apiResource('messages', MessageController::class);
    // Route::apiResource('userCampuses', UserCampusController::class);

    Route::post('/initialize/incidentReports', [IncidentReportController::class, 'initialize']);
    Route::get('/incidentReports', [IncidentReportController::class, 'index']);
    Route::post('/incidentReports', [IncidentReportController::class, 'store']);
    Route::get('incidentReports/{incidentReportID}', [IncidentReportController::class, 'show']);
    Route::put('incidentReports/{incidentReportID}', [IncidentReportController::class, 'update']);
    Route::delete('incidentReports/{incidentReportID}', [IncidentReportController::class, 'destroy']);
    Route::get('incidentReportTotal', [IncidentReportController::class, 'getTotalIncidentReport']);
    Route::get('/generateIncidentReportPdf/{reportID}', [IncidentReportController::class, 'generateIncidentReportPdf']);
    Route::get('/unsubmittedIncidentReports', [IncidentReportController::class, 'getUnsubmittedIncidentReports']);


    //end of shift report patrol
    Route::post('/initialize/endOfShiftReportPatrol', [EndOfShiftReportPatrolController::class, 'initialize']);
    Route::get('/endOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'index']);
    Route::post('/endOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'store']);
    Route::get('/endOfShiftReportPatrols/{endOfShiftReportPatrolID}', [EndOfShiftReportPatrolController::class, 'show']);
    Route::put('/endOfShiftReportPatrols/{endOfShiftReportPatrolID}', [EndOfShiftReportPatrolController::class, 'update']);
    Route::delete('/endOfShiftReportPatrols/{endOfShiftReportPatrolID}', [EndOfShiftReportPatrolController::class, 'destroy']);
    Route::get('/endOfShiftReportPatrolsTotal', [EndOfShiftReportPatrolController::class, 'getTotalEndOfShiftReportPatrol']);
    Route::get('/generateEndOfShiftReportPatrolPdf/{reportID}', [EndOfShiftReportPatrolController::class, 'generateEndOfShiftReportPatrolPdf']);
    Route::get('/unsubmittedEndOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'getUnsubmittedEndOfShiftReportPatrols']);

    Route::get('incidentStatus', [IncidentStatusController::class, 'index']);
    Route::post('incidentStatus', [IncidentStatusController::class, 'store']);
    Route::put('incidentStatus/{id}', [IncidentStatusController::class, 'update']);
    Route::delete('incidentStatus/{id}', [IncidentStatusController::class, 'destroy']);


    Route::get('incidentTypes', [IncidentTypeController::class, 'index']);
    Route::post('incidentTypes', [IncidentTypeController::class, 'store']);
    Route::put('incidentTypes/{id}', [IncidentTypeController::class, 'update']);
    Route::delete('incidentTypes/{id}', [IncidentTypeController::class, 'destroy']);


    Route::post('/uploadPhoto/{Id}', [FileUploadController::class, 'uploadIncidentReportPhoto']);



    //menus
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/menu', [MenuController::class, 'store']);
    Route::put('/menu/{id}', [MenuController::class, 'update']);
    Route::delete('/menu/{id}', [MenuController::class, 'destroy']);
});
