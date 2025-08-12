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
use Modules\PublicSafety\Http\Controllers\UserStatusController;
use Modules\PublicSafety\Http\Controllers\IncidentTypeController;
use Modules\PublicSafety\Http\Controllers\MenuController;
use Modules\PublicSafety\Http\Controllers\SubMenuController;
use Modules\PublicSafety\Http\Controllers\MenuRoleController;
use Modules\PublicSafety\Http\Controllers\FileUploadController;
use Modules\PublicSafety\Http\Controllers\PDFController;
use Modules\PublicSafety\Http\Controllers\MessageController;
// use Modules\PublicSafety\Services\FCMService;


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
    // Existing routes
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('campuses', CampusController::class);
    Route::apiResource('messageCategories', MessageCategoryController::class);
    Route::apiResource('messages', MessageController::class);
    Route::apiResource('userCampuses', UserCampusController::class);
    Route::apiResource('buildings', BuildingController::class);
    Route::apiResource('incidentStatuses', IncidentStatusController::class);
    Route::apiResource('incidentReports', IncidentReportController::class);
    Route::post('/uploadIncidentReportPhoto/{IncidentReportID}', [FileUploadController::class, 'uploadIncidentReportPhoto']);
    Route::apiResource('userStatuses', UserStatusController::class);
    Route::apiResource('incidentTypes', IncidentTypeController::class);
    Route::apiResource('menus', MenuController::class);
    Route::get('menus', [MenuController::class, 'getMenus']);
    Route::apiResource('menuRoles', MenuRoleController::class);
    Route::apiResource('subMenus', SubMenuController::class);
    Route::get('usersTotal', [UserController::class, 'getTotalUsers']);
    Route::get('incidentReportTotal', [IncidentReportController::class, 'getTotalIncidentReport']);
    Route::post('assignRoles', [RoleController::class, 'assignRoleToUser']);
    // Route::post('upload', [FileUploadController::class, 'upload']);
    // Route::get('download-pdf/{id}', [PDFController::class, 'downloadIncidentReport']);
});
