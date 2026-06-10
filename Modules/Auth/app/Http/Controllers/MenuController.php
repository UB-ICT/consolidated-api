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
     * GET /api/menus/profile
     * Build the user profile dropdown layout dynamically based on roles.
     */
    public function profileMenu(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleIds = $user->roles()->pluck('roles.id')->toArray();

        // Query links marked for user-menu or external-link types
        $menuItems = Menu::query()
            ->whereIn('type', ['user-menu', 'external-link'])
            ->where(function ($query) use ($roleIds) {
                // If role_id is null, it's public to all logged-in users
                $query->whereNull('role_id');

                // If it has a role restriction, the user must possess that role ID
                if (!empty($roleIds)) {
                    $query->orWhereIn('role_id', $roleIds);
                }
            })
            ->orderBy('sort_order')
            ->get();

        // Split items by type for clean frontend consumption
        $links = $menuItems->where('type', 'user-menu')->values();
        $externals = $menuItems->where('type', 'external-link')->values();

        return response()->json([
            'user' => [
                'name'     => $user->name,
                'email'    => $user->email,
                'initials' => collect(explode(' ', $user->name))->map(fn($n) => mb_substr($n, 0, 1))->join(''),
            ],
            'navigation'     => $links,
            'external_links' => $externals
        ]);
    }

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
        $user = $request->user();
        $roles = $user->roles;
        $roleIds = $roles->pluck('id')->toArray();

        // 👑 SUPER ADMIN BYPASS: Give access to all applications immediately
        if ($roles->contains('role_name', 'super-admin')) {
            $allApps = Menu::whereNull('parent_id')
                ->where('type', 'application') // 👈 CRITICAL: Must be an application type!
                ->select('id', 'label', 'path', 'icon', 'sort_order')
                ->orderBy('sort_order')
                ->get();

            return response()->json($allApps);
        }

        // Standard user role filtering logic
        $myApps = Menu::query()
            ->whereNull('parent_id')
            ->where('type', 'application') // 👈 CRITICAL: Excludes 'user-menu' items with null parents!
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
        $roles = $user->roles;
        $roleIds = $roles->pluck('id');

        // 👑 SUPER ADMIN BYPASS: Pull all child menus under this app without filtering by role
        if ($roles->contains('role_name', 'super-admin')) {
            $menus = Menu::query()
                ->where('parent_id', $request->query('application_id'))
                ->with(['role', 'children.children']) // Deep structural load
                ->orderBy('sort_order')
                ->get();

            return response()->json($menus);
        }

        // Standard dynamic role filtering for regular employees
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
