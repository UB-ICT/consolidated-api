<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Models\Menu;

/**
 * Handles CRUD operations for portal menu items.
 *
 * Menu items are nested through parent/child relationships
 * where root items act as Application modules.
 */
class MenuController extends Controller
{
    /**
     * GET /api/menus/applications
     * Fetch ALL top-level application modules globally.
     */
    public function applications(): JsonResponse
    {
        $allApps = Menu::whereNull('parent_id')
            ->select('id', 'label', 'path', 'icon', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        return response()->json($allApps);
    }

    /**
     * GET /api/menus/my-applications
     * Fetch ONLY the top-level application modules accessible by the logged-in user's roles.
     */
    public function myApplications(Request $request): JsonResponse
    {
        // 1. Get an array of all role UUIDs assigned to the current authenticated user
        $roleIds = $request->user()->roles()->pluck('roles.id')->toArray();

        // 2. Query only root rows that have nested children matching those roles
        $myApps = Menu::whereNull('parent_id')
            ->whereHas('children', function ($query) use ($roleIds) {
                if (!empty($roleIds)) {
                    $query->whereIn('role_id', $roleIds);
                }
            })
            ->select('id', 'label', 'path', 'icon', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        return response()->json($myApps);
    }

    /**
     * Display menu items available to the authenticated user for a SPECIFIC application.
     * * Expects an application ID via query string: GET /api/user-menus?application_id=UUID
     */
    public function userMenus(Request $request): JsonResponse
    {
        $request->validate([
            'application_id' => 'required|uuid|exists:pgsql.menus,id'
        ]);

        $user = $request->user();
        $roleIds = $user->roles()->pluck('roles.id');

        // Reusable filter for role-scoped and public menus
        $applyRoleFilter = function ($query) use ($roleIds): void {
            $query->where(function ($menuQuery) use ($roleIds): void {
                $menuQuery->whereNull('role_id');

                if ($roleIds->isNotEmpty()) {
                    $menuQuery->orWhereIn('role_id', $roleIds);
                }
            });
        };

        // Fetch sub-menus whose parent is the selected Application ID
        $menus = Menu::query()
            ->where('parent_id', $request->query('application_id'))
            ->where($applyRoleFilter)
            ->with([
                'role',
                'children' => function ($query) use ($applyRoleFilter): void {
                    // This handles potential 3rd level sub-menus gracefully
                    $query->where($applyRoleFilter)->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json($menus);
    }

    /**
     * Display menu items available to a specific target user for a SPECIFIC application.
     * * Expects: GET /api/user-menus-by-user?user_id=UUID&application_id=UUID
     */
    public function userMenusByUser(Request $request): JsonResponse
    {
        // Adjust the target user discovery strategy to match your Admin Panel implementation
        $targetUser = $request->user();

        if (!$targetUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'application_id' => 'required|uuid|exists:pgsql.menus,id'
        ]);

        $roleIds = $targetUser->roles()->pluck('roles.id');

        $applyRoleFilter = function ($query) use ($roleIds): void {
            $query->where(function ($menuQuery) use ($roleIds): void {
                $menuQuery->whereNull('role_id');

                if ($roleIds->isNotEmpty()) {
                    $menuQuery->orWhereIn('role_id', $roleIds);
                }
            });
        };

        $menus = Menu::query()
            ->where('parent_id', $request->query('application_id'))
            ->where($applyRoleFilter)
            ->with([
                'role',
                'children' => function ($query) use ($applyRoleFilter): void {
                    $query->where($applyRoleFilter)->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json($menus);
    }

    /**
     * Display all top-level menu items (Applications) with recursively nested layouts.
     * Useful for global administrative schema management screens.
     */
    public function index(): JsonResponse
    {
        $menus = Menu::whereNull('parent_id')
            ->with(['role', 'children.children']) // Deep loading structural setups
            ->orderBy('sort_order')
            ->get();

        return response()->json($menus);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'      => 'required|string|max:255',
            'path'       => 'required|string|max:255',
            'icon'       => 'nullable|string|max:255',
            'role_id'    => 'nullable|uuid|exists:pgsql.roles,id',
            'parent_id'  => 'nullable|uuid|exists:pgsql.menus,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $menu = Menu::create($data);

        return response()->json($menu, 201);
    }

    /**
     * Display a specific menu item with related data.
     */
    public function show(Menu $menu): JsonResponse
    {
        return response()->json($menu->load(['role', 'children']));
    }

    /**
     * Update an existing menu item.
     */
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $data = validator($payload, [
            'label'      => 'sometimes|required|string|max:255',
            'path'       => 'sometimes|required|string|max:255',
            'icon'       => 'nullable|string|max:255',
            'role_id'    => 'nullable|uuid|exists:pgsql.roles,id',
            'parent_id'  => 'nullable|uuid|exists:pgsql.menus,id',
            'sort_order' => 'nullable|integer|min:0',
        ])->validate();

        $menu->update($data);

        return response()->json($menu->fresh());
    }

    /**
     * Delete a menu item.
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}
