<?php

use Illuminate\Support\Facades\Route;
use Modules\UBPortal\Http\Controllers\AccessRequestController;
use Modules\UBPortal\Http\Controllers\ApplicationController;
use Modules\UBPortal\Http\Controllers\AuditLogController;
use Modules\UBPortal\Http\Controllers\GroupController;
use Modules\UBPortal\Http\Controllers\MenuItemController;
use Modules\UBPortal\Http\Controllers\PermissionController;
use Modules\UBPortal\Http\Controllers\RoleController;
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

Route::middleware(['auth:sanctum'])->prefix('v1/UBPortal')->group(function () {
    Route::apiResource('applications', ApplicationController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/permissions', [RoleController::class, 'attachPermissions']);       // Attach one or more permissions to a role (additive, keeps existing)
    Route::put('roles/{role}/syncPermissions', [RoleController::class, 'syncPermissions']);         // Replace all permissions on a role with the provided set
    Route::delete('roles/{role}/permissions/{permission}', [RoleController::class, 'detachPermission']); // Remove a single permission from a role
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('menu-items', MenuItemController::class);
    Route::apiResource('access-requests', AccessRequestController::class);
    Route::apiResource('audit-logs', AuditLogController::class);
});
