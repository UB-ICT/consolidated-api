<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\PublicSafety\Http\Controllers\PublicSafetyController;
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
use Modules\PublicSafety\Http\Controllers\AccessRightController;
use Modules\PublicSafety\Http\Controllers\IncidentTypeController;
use Modules\PublicSafety\Http\Controllers\DepartmentController;
use Modules\PublicSafety\Http\Controllers\DepartmentMemberController;
use Modules\PublicSafety\Http\Controllers\MenuController;
use Modules\PublicSafety\Http\Controllers\SubMenuController;
use Modules\PublicSafety\Http\Controllers\MenuRoleController;
use Modules\PublicSafety\Http\Controllers\FileUploadController;
use Modules\PublicSafety\Http\Controllers\PDFController;
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

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('publicsafety', PublicSafetyController::class)->names('publicsafety');
});

Route::post('auth/login', [AuthController::class, 'login']);


Route::group([
    'prefix' => 'v1/publicSafety',
    'namespace' => 'App\Http\Controllers',
    'middleware' => 'auth:sanctum'
], function () {
    // Existing routes
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('campuses', CampusController::class);
    Route::apiResource('messageCategories', MessageCategoryController::class);
    Route::apiResource('userCampuses', UserCampusController::class);
    Route::apiResource('buildings', BuildingController::class);
    Route::apiResource('incidentStatuses', IncidentStatusController::class);
    Route::apiResource('incidentReports', IncidentReportController::class);
    Route::post('/uploadIncidentFile', [IncidentReportController::class, 'uploadIncidentFile']);
    Route::apiResource('userStatuses', UserStatusController::class);
    Route::apiResource('accessRights', AccessRightController::class);
    Route::apiResource('incidentTypes', IncidentTypeController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('departmentMembers', DepartmentMemberController::class);
    Route::get('menus', [MenuController::class, 'getMenus']);
    Route::apiResource('menuRoles', MenuRoleController::class);
    Route::apiResource('subMenus', SubMenuController::class);
    Route::get('getUsers', [UserController::class, 'getUsers']);
    Route::get('usersTotal', [UserController::class, 'getTotalUsers']);
    Route::get('incidentReportTotal', [IncidentReportController::class, 'getTotalIncidentReport']);
    Route::post('/assignRoles', [RoleController::class, 'assignRoleToUser']);
    Route::post('/upload', [FileUploadController::class, 'upload']);
    Route::get('/download-pdf/{id}', [PDFController::class, 'downloadIncidentReport']);
});




// Route::post('/v1/publicSafety/send-notification', function (Request $request, FCMService $fcmService) {
//     $request->validate([
//         // 'deviceToken' => 'required|string',
//         'title' => 'required|string',
//         'body' => 'required|string',
//     ]);

//     return $fcmService->sendNotification($request->deviceToken, $request->title, $request->body);
// })->middleware('auth:sanctum');
