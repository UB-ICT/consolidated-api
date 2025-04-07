<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PushNotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCampusController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\MessageCategoryController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\IncidentStatusController;
use App\Http\Controllers\IncidentFileController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\AccessRightController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\IncidentTypeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentMemberController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SubMenuController;
use App\Http\Controllers\MenuRoleController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\PDFController;

use App\Services\FCMService;



// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);

Route::group(['prefix' => 'v1/publicSafety', 'namespace' => 'App\Http\Controllers', 'middleware' => 'auth:sanctum'], function () {
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
    Route::apiResource('recipients', RecipientController::class);
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
    Route::post('uploadPicture', [UserController::class, 'uploadPicture']);
    Route::post('/upload', [FileUploadController::class, 'upload']);
    Route::get('/download-pdf/{id}', [PDFController::class, 'downloadIncidentReport']);
});




Route::post('/v1/publicSafety/send-notification', function (Request $request, FCMService $fcmService) {
    $request->validate([
        // 'deviceToken' => 'required|string',
        'title' => 'required|string',
        'body' => 'required|string',
    ]);

    return $fcmService->sendNotification($request->deviceToken, $request->title, $request->body);
})->middleware('auth:sanctum');


