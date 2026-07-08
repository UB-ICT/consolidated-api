<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
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
            ->where('status', Menu::STATUS_ACTIVE)
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
                ->where('status', Menu::STATUS_ACTIVE)
                ->select('id', 'label', 'path', 'icon', 'sort_order')
                ->orderBy('sort_order')
                ->get();

            return response()->json($allApps);
        }

        // Standard user role filtering logic
        $myApps = Menu::query()
            ->whereNull('parent_id')
            ->where('type', 'application') // 👈 CRITICAL: Excludes 'user-menu' items with null parents!
            ->where('status', Menu::STATUS_ACTIVE)
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
     * GET /api/menus/catalog
     * Admin view of the full applications catalog (every status) for the Applications management page.
     */
    public function catalog(): JsonResponse
    {
        $applications = Menu::whereNull('parent_id')
            ->where('type', 'application')
            ->select('id', 'label', 'path', 'icon', 'status', 'description', 'category', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'total'        => $applications->count(),
            'applications' => $applications,
        ]);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'       => 'required|string|max:255',
            'path'        => 'required|string|max:255',
            'icon'        => 'nullable|string|max:255',
            'status'      => ['nullable', 'string', Rule::in([Menu::STATUS_ACTIVE, Menu::STATUS_MAINTENANCE, Menu::STATUS_DISABLED])],
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255',
            'role_id'     => 'nullable|uuid|exists:pgsql.roles,id',
            'parent_id'   => 'nullable|uuid|exists:pgsql.menus,id',
            'sort_order'  => 'nullable|integer|min:0',
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
            'label'       => 'sometimes|required|string|max:255',
            'path'        => 'sometimes|required|string|max:255',
            'icon'        => 'nullable|string|max:255',
            'status'      => ['nullable', 'string', Rule::in([Menu::STATUS_ACTIVE, Menu::STATUS_MAINTENANCE, Menu::STATUS_DISABLED])],
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255',
            'role_id'     => 'nullable|uuid|exists:pgsql.roles,id',
            'parent_id'   => 'nullable|uuid|exists:pgsql.menus,id',
            'sort_order'  => 'nullable|integer|min:0',
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

    public function getActiveApplicationMenu(Request $request): JsonResponse
    {
        $applicationId = $request->query('application_id');

        if (!$applicationId) {
            return response()->json(['message' => 'The application_id parameter is required.'], 400);
        }

        $user = $request->user();
        $roles = $user->roles;
        $roleIds = $roles->pluck('id')->toArray();
        $isSuperAdmin = $roles->contains('role_name', 'super-admin');

        // Force explicit postgres model instantiation to honor model database connections
        $applicationMenu = Menu::on('pgsql')
            ->where('id', $applicationId)
            ->with(['children' => function ($query) use ($roleIds, $isSuperAdmin) {
                $query->where('type', 'submenu')->orderBy('sort_order');

                if (!$isSuperAdmin) {
                    $query->where(function ($subQuery) use ($roleIds) {
                        $subQuery->whereNull('role_id');
                        if (!empty($roleIds)) {
                            $subQuery->orWhereIn('role_id', $roleIds);
                        }
                    });
                }
            }])
            ->first();

        if (!$applicationMenu) {
            return response()->json(['message' => 'Application layout could not be found.'], 404);
        }

        // --- FIX DETECTED HERE ---
        // Filter the children array to keep only unique paths, resetting array indices cleanly
        $uniqueChildren = $applicationMenu->children
            ->unique('path')
            ->values();

        return response()->json([
            'id'         => $applicationMenu->id,
            'label'      => $applicationMenu->label,
            'path'       => $applicationMenu->path,
            'icon'       => $applicationMenu->icon,
            'sort_order' => $applicationMenu->sort_order,
            'children'   => $uniqueChildren
        ]);
    }
}
