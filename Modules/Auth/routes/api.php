<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\GroupController;
use Modules\Auth\Http\Controllers\GroupRoleController;
use Modules\Auth\Http\Controllers\GoogleAuthController;
use Modules\Auth\Http\Controllers\MenuItemController;
use Modules\Auth\Http\Controllers\PermissionController;
use Modules\Auth\Http\Controllers\RoleController;
use Modules\Auth\Http\Controllers\RolePermissionController;
use Modules\Auth\Http\Controllers\UserController;
use Modules\Auth\Http\Controllers\UserGroupController;
use Modules\Auth\Http\Controllers\UserRoleController;

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

Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/v1/auth/mockGoogleLogin', [GoogleAuthController::class, 'mockGoogleLogin']);
Route::get('/v1/user', [GoogleAuthController::class, 'getAnnualReportUserInfo'])->middleware('auth:sanctum');
Route::get('/v1/publicSafety/user', [GoogleAuthController::class, 'getPublicSafetyUserInfo'])->middleware('auth:sanctum');
Route::get('/user', [GoogleAuthController::class, 'user']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
	// User CRUD endpoints.
	Route::get('/users', [UserController::class, 'index']); // List all users with groups/roles.
	Route::post('/users', [UserController::class, 'store']); // Create a new user.
	Route::get('/users/{user}', [UserController::class, 'show']); // Get one user by route-model binding.
	Route::put('/users/{user}', [UserController::class, 'update']); // Replace/update user fields.
	Route::patch('/users/{user}', [UserController::class, 'update']); // Partially update user fields.
	Route::delete('/users/{user}', [UserController::class, 'destroy']); // Delete a user.

	// Group CRUD endpoints.
	Route::get('/groups', [GroupController::class, 'index']); // List all groups with counts.
	Route::post('/groups', [GroupController::class, 'store']); // Create a new group.
	Route::get('/groups/{group}', [GroupController::class, 'show']); // Get one group with users/roles.
	Route::put('/groups/{group}', [GroupController::class, 'update']); // Replace/update group fields.
	Route::patch('/groups/{group}', [GroupController::class, 'update']); // Partially update group fields.
	Route::delete('/groups/{group}', [GroupController::class, 'destroy']); // Delete a group.

	// Role CRUD endpoints.
	Route::get('/roles', [RoleController::class, 'index']); // List all roles and related data.
	Route::post('/roles', [RoleController::class, 'store']); // Create a new role.
	Route::get('/roles/{role}', [RoleController::class, 'show']); // Get one role with permissions/menu items.
	Route::put('/roles/{role}', [RoleController::class, 'update']); // Replace/update role fields.
	Route::patch('/roles/{role}', [RoleController::class, 'update']); // Partially update role fields.
	Route::delete('/roles/{role}', [RoleController::class, 'destroy']); // Delete a role.

	// Role-permission helpers on RoleController.
	Route::post('/roles/{role}/permissions/attach', [RoleController::class, 'attachPermissions']); // Add permission(s) without detaching existing ones.
	Route::put('/roles/{role}/permissions/sync', [RoleController::class, 'syncPermissions']); // Replace all role permissions with provided list.
	Route::delete('/roles/{role}/permissions/{permission}', [RoleController::class, 'detachPermission']); // Remove one permission from role.

	// Permission CRUD endpoints.
	Route::get('/permissions', [PermissionController::class, 'index']); // List all permissions.
	Route::post('/permissions', [PermissionController::class, 'store']); // Create a new permission.
	Route::get('/permissions/{permission}', [PermissionController::class, 'show']); // Get one permission with roles.
	Route::put('/permissions/{permission}', [PermissionController::class, 'update']); // Replace/update permission fields.
	Route::patch('/permissions/{permission}', [PermissionController::class, 'update']); // Partially update permission fields.
	Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']); // Delete a permission.

	// Menu item CRUD endpoints.
	Route::get('/menu-items', [MenuItemController::class, 'index']); // List top-level menu items with children.
	Route::post('/menu-items', [MenuItemController::class, 'store']); // Create a menu item.
	Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']); // Get one menu item with role/children.
	Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']); // Replace/update menu item.
	Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update']); // Partially update menu item.
	Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']); // Delete a menu item.

	// User-group pivot CRUD endpoints (composite key: userId + groupId).
	Route::get('/user-groups', [UserGroupController::class, 'index']); // List all user-group assignments.
	Route::post('/user-groups', [UserGroupController::class, 'store']); // Create one user-group assignment.
	Route::get('/user-groups/{userId}', [UserGroupController::class, 'show']); // Get user with all assigned groups.
	Route::get('/user-groups/{userId}/{groupId}', [UserGroupController::class, 'show']); // Get one specific user-group assignment.
	Route::put('/user-groups/{userId}/{groupId}', [UserGroupController::class, 'update']); // Replace one user-group assignment.
	Route::patch('/user-groups/{userId}/{groupId}', [UserGroupController::class, 'update']); // Partially replace one user-group assignment.
	Route::delete('/user-groups/{userId}/{groupId}', [UserGroupController::class, 'destroy']); // Delete one user-group assignment.

	// User-role pivot CRUD endpoints (composite key: userId + roleId).
	Route::get('/user-roles', [UserRoleController::class, 'index']); // List all user-role assignments.
	Route::post('/user-roles', [UserRoleController::class, 'store']); // Create one user-role assignment.
	Route::get('/user-roles/{userId}', [UserRoleController::class, 'show']); // Get user with all assigned roles.
	Route::get('/user-roles/{userId}/{roleId}', [UserRoleController::class, 'show']); // Get one specific user-role assignment.
	Route::put('/user-roles/{userId}/{roleId}', [UserRoleController::class, 'update']); // Replace one user-role assignment.
	Route::patch('/user-roles/{userId}/{roleId}', [UserRoleController::class, 'update']); // Partially replace one user-role assignment.
	Route::delete('/user-roles/{userId}/{roleId}', [UserRoleController::class, 'destroy']); // Delete one user-role assignment.

	// Group-role pivot CRUD endpoints (composite key: groupId + roleId).
	Route::get('/group-roles', [GroupRoleController::class, 'index']); // List all group-role assignments.
	Route::post('/group-roles', [GroupRoleController::class, 'store']); // Create one group-role assignment.
	Route::get('/group-roles/{groupId}', [GroupRoleController::class, 'show']); // Get group with all assigned roles.
	Route::get('/group-roles/{groupId}/{roleId}', [GroupRoleController::class, 'show']); // Get one specific group-role assignment.
	Route::put('/group-roles/{groupId}/{roleId}', [GroupRoleController::class, 'update']); // Replace one group-role assignment.
	Route::patch('/group-roles/{groupId}/{roleId}', [GroupRoleController::class, 'update']); // Partially replace one group-role assignment.
	Route::delete('/group-roles/{groupId}/{roleId}', [GroupRoleController::class, 'destroy']); // Delete one group-role assignment.

	// Role-permission pivot CRUD endpoints (composite key: roleId + permissionId).
	Route::get('/role-permissions', [RolePermissionController::class, 'index']); // List all role-permission assignments.
	Route::post('/role-permissions', [RolePermissionController::class, 'store']); // Create one role-permission assignment.
	Route::get('/role-permissions/{roleId}', [RolePermissionController::class, 'show']); // Get role with all assigned permissions.
	Route::get('/role-permissions/{roleId}/{permissionId}', [RolePermissionController::class, 'show']); // Get one specific role-permission assignment.
	Route::put('/role-permissions/{roleId}/{permissionId}', [RolePermissionController::class, 'update']); // Replace one role-permission assignment.
	Route::patch('/role-permissions/{roleId}/{permissionId}', [RolePermissionController::class, 'update']); // Partially replace one role-permission assignment.
	Route::delete('/role-permissions/{roleId}/{permissionId}', [RolePermissionController::class, 'destroy']); // Delete one role-permission assignment.
});
