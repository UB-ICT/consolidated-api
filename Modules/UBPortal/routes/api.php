<?php

use Illuminate\Support\Facades\Route;
use Modules\UBPortal\Http\Controllers\AccessRequestController;
use Modules\UBPortal\Http\Controllers\ApplicationController;
use Modules\UBPortal\Http\Controllers\AuditLogController;
use Modules\UBPortal\Http\Controllers\EventController;
use Modules\UBPortal\Http\Controllers\GroupController;
use Modules\UBPortal\Http\Controllers\MenuItemController;
use Modules\UBPortal\Http\Controllers\PermissionController;
use Modules\UBPortal\Http\Controllers\PostController;
use Modules\UBPortal\Http\Controllers\PostViewController;
use Modules\UBPortal\Http\Controllers\RoleController;
use Modules\UBPortal\Http\Controllers\TagController;
use Modules\UBPortal\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| UBPortal API Routes
|--------------------------------------------------------------------------
*/

// Public endpoints
Route::get('v1/UBPortal/users/count', [UserController::class, 'UserCount']);
Route::get('v1/UBPortal/applications/count', [ApplicationController::class, 'applicationCount']);

Route::middleware(['auth:sanctum'])->prefix('v1/UBPortal')->group(function () {
    // Core resources
    Route::apiResource('users', UserController::class);
    Route::apiResource('applications', ApplicationController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('menu-items', MenuItemController::class);
    Route::apiResource('access-requests', AccessRequestController::class);
    Route::apiResource('audit-logs', AuditLogController::class);

    // User relationships
    Route::post('users/{user}/roles', [UserController::class, 'attachRoles']);
    Route::put('users/{user}/syncRoles', [UserController::class, 'syncRoles']);
    Route::delete('users/{user}/roles/{role}', [UserController::class, 'detachRole']);
    Route::post('users/{user}/groups', [UserController::class, 'attachGroups']);
    Route::put('users/{user}/syncGroups', [UserController::class, 'syncGroups']);
    Route::delete('users/{user}/groups/{group}', [UserController::class, 'detachGroup']);

    // Role relationships
    Route::post('roles/{role}/permissions', [RoleController::class, 'attachPermissions']);
    Route::put('roles/{role}/syncPermissions', [RoleController::class, 'syncPermissions']);
    Route::delete('roles/{role}/permissions/{permission}', [RoleController::class, 'detachPermission']);
    Route::post('roles/{role}/applications', [RoleController::class, 'attachApplications']);
    Route::put('roles/{role}/syncApplications', [RoleController::class, 'syncApplications']);
    Route::delete('roles/{role}/applications/{application}', [RoleController::class, 'detachApplication']);

    // Group interactions
    Route::post('groups/{group}/follow', [GroupController::class, 'toggleFollow']);

    // Content resources
    Route::apiResource('posts', PostController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('events', EventController::class);
    Route::apiResource('post-views', PostViewController::class);

    // Post interactions and workflow
    Route::post('posts/{post}/like', [PostController::class, 'toggleLike']);
    Route::post('posts/{post}/bookmark', [PostController::class, 'toggleBookmark']);
    Route::post('posts/{post}/read', [PostController::class, 'logViewAndStreak']);
    Route::get('posts/feed/{filter}', [PostController::class, 'getFeed'])->where('filter', 'popular|recent|best-discussed');
    Route::get('posts/tag/{slug}', [PostController::class, 'filterByTag']);
    Route::get('admin/posts/pending', [PostController::class, 'getPendingQueue']);
    Route::patch('admin/posts/{post}/review', [PostController::class, 'updateStatus']);
});
