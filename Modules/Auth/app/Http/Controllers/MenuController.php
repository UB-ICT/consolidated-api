<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Models\Menu;

/**
 * Handles CRUD operations for portal menu items.
 *
 * Menu items can be nested through parent/child relationships
 * to build hierarchical navigation structures.
 */
class MenuController extends Controller
{

    /**
     * Display menu items available to the authenticated user.
     *
     * Includes public menu entries (no role_id) and items
     * assigned to any role linked to the current user.
     */
    public function userMenus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Gather role IDs assigned to the current user.
        $roleIds = $user->roles()->pluck('roles.id');

        // Reusable filter for role-scoped and public menus.
        $applyRoleFilter = function ($query) use ($roleIds): void {
            $query->where(function ($menuQuery) use ($roleIds): void {
                $menuQuery->whereNull('role_id');

                if ($roleIds->isNotEmpty()) {
                    $menuQuery->orWhereIn('role_id', $roleIds);
                }
            });
        };

        $menus = Menu::query()
            ->whereNull('parent_id')
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
     * Display menu items available to a specific user.
     *
     * Useful for admin screens that need to inspect menu visibility
     * for users other than the currently authenticated user.
     */
    public function userMenusByUser(Request $request): JsonResponse
    {
        $targetUser = $request->user();

        if (!$targetUser) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Gather role IDs assigned to the target user.
        $roleIds = $targetUser->roles()->pluck('roles.id');

        // Reusable filter for role-scoped and public menus.
        $applyRoleFilter = function ($query) use ($roleIds): void {
            $query->where(function ($menuQuery) use ($roleIds): void {
                $menuQuery->whereNull('role_id');

                if ($roleIds->isNotEmpty()) {
                    $menuQuery->orWhereIn('role_id', $roleIds);
                }
            });
        };

        $menus = Menu::query()
            ->whereNull('parent_id')
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
     * Display top-level menu items with nested children.
     *
     * Root items are loaded first, and each item includes
     * its role and child menu items.
     */
    public function index(): JsonResponse
    {
        // Load only root items to avoid duplicating child nodes in the top-level list.
        $menus = Menu::whereNull('parent_id')
            // Include assigned role and recursively loaded descendants.
            ->with(['role', 'children'])
            ->orderBy('sort_order')
            ->get();

        return response()->json($menus);
    }

    /**
     * Store a newly created menu item.
     *
     * Supports optional nesting, role assignment,
     * and custom sort ordering.
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

        // Persist the menu item record.
        $menu = Menu::create($data);

        return response()->json($menu, 201);
    }

    /**
     * Display a specific menu item with related data.
     */
    public function show(Menu $menu): JsonResponse
    {
        // Include role and nested children for detail views/edit screens.
        return response()->json($menu->load(['role', 'children']));
    }

    /**
     * Update an existing menu item.
     *
     * Uses partial validation so clients can send only changed fields.
     */
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $payload = $request->all();

        // Fallback for clients that send raw JSON with incorrect headers.
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

        // Apply validated updates.
        $menu->update($data);

        return response()->json($menu->fresh());
    }

    /**
     * Delete a menu item.
     *
     * Child items are removed automatically when cascade delete
     * is configured on the parent relation.
     */
    public function destroy(Menu $menu): JsonResponse
    {
        // Delete the selected menu item.
        $menu->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}
