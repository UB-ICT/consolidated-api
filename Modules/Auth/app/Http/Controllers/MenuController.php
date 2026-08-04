<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\Menu;

/**
 * Handles CRUD operations for portal menu items.
 *
 * Menu items are nested through parent/child relationships
 * where root items act as Application modules. Visibility is
 * controlled by the role_menu pivot (empty = public).
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

        $menuItems = Menu::query()
            ->whereIn('type', [Menu::TYPE_USER_MENU, Menu::TYPE_EXTERNAL_LINK])
            ->visibleToRoles($roleIds)
            ->with('roles:id,role_name')
            ->orderBy('sort_order')
            ->get();

        $links = $menuItems->where('type', Menu::TYPE_USER_MENU)->values();
        $externals = $menuItems->where('type', Menu::TYPE_EXTERNAL_LINK)->values();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'initials' => collect(explode(' ', $user->name))->map(fn ($n) => mb_substr($n, 0, 1))->join(''),
            ],
            'navigation' => $links,
            'external_links' => $externals,
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

        if ($roles->contains('role_name', 'super-admin')) {
            $allApps = Menu::whereNull('parent_id')
                ->where('type', Menu::TYPE_APPLICATION)
                ->where('status', Menu::STATUS_ACTIVE)
                ->select('id', 'label', 'path', 'icon', 'sort_order')
                ->orderBy('sort_order')
                ->get();

            return response()->json($allApps);
        }

        $myApps = Menu::query()
            ->whereNull('parent_id')
            ->where('type', Menu::TYPE_APPLICATION)
            ->where('status', Menu::STATUS_ACTIVE)
            ->where(function ($query) use ($roleIds) {
                // Public submenus (no roles) or role-restricted submenus matching the user.
                $query->whereHas('children', function ($children) use ($roleIds) {
                    $children->visibleToRoles($roleIds);
                });
            })
            ->select('id', 'label', 'path', 'icon', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        return response()->json($myApps);
    }

    /**
     * Display all menu items for admin management (flat + nested).
     */
    public function index(Request $request): JsonResponse
    {
        $parentId = $request->query('parent_id');

        $query = Menu::query()
            ->with(['roles:id,role_name', 'parent:id,label,path,type'])
            ->withCount(['children', 'roles'])
            ->orderBy('sort_order')
            ->orderBy('label');

        if ($request->boolean('roots_only')) {
            $query->whereNull('parent_id');
        } elseif ($parentId === 'null' || $request->query('parent_id') === '') {
            $query->whereNull('parent_id');
        } elseif ($parentId) {
            $query->where('parent_id', $parentId);
        }

        return response()->json($query->get());
    }

    /**
     * GET /api/menus/catalog
     * Admin view of the full applications catalog (every status) for the Applications management page.
     */
    public function catalog(): JsonResponse
    {
        $applications = Menu::whereNull('parent_id')
            ->where('type', Menu::TYPE_APPLICATION)
            ->select('id', 'label', 'path', 'icon', 'status', 'description', 'category', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'total' => $applications->count(),
            'applications' => $applications,
        ]);
    }

    /**
     * POST /api/menu/icon
     */
    public function uploadIcon(Request $request): JsonResponse
    {
        $request->validate([
            'icon' => 'required|file|mimes:jpg,jpeg,png,svg,webp|max:2048',
        ]);

        $path = $request->file('icon')->store('menu-icons', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', Rule::in([
                Menu::TYPE_APPLICATION,
                Menu::TYPE_SUBMENU,
                Menu::TYPE_USER_MENU,
                Menu::TYPE_EXTERNAL_LINK,
            ])],
            'status' => ['nullable', 'string', Rule::in([
                Menu::STATUS_ACTIVE,
                Menu::STATUS_MAINTENANCE,
                Menu::STATUS_DISABLED,
            ])],
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'parent_id' => 'nullable|uuid|exists:pgsql.menus,id',
            'sort_order' => 'nullable|integer|min:0',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'uuid|exists:pgsql.roles,id',
        ]);

        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        if (!isset($data['type'])) {
            $data['type'] = $data['parent_id']
                ? Menu::TYPE_SUBMENU
                : Menu::TYPE_APPLICATION;
        }

        $menu = Menu::create($data);

        if (is_array($roleIds)) {
            $menu->roles()->sync($roleIds);
        }

        return response()->json($this->formatMenu($menu->fresh()), 201);
    }

    /**
     * Display a specific menu item with related data.
     */
    public function show(Menu $menu): JsonResponse
    {
        return response()->json($this->formatMenu($menu));
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
            'label' => 'sometimes|required|string|max:255',
            'path' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', Rule::in([
                Menu::TYPE_APPLICATION,
                Menu::TYPE_SUBMENU,
                Menu::TYPE_USER_MENU,
                Menu::TYPE_EXTERNAL_LINK,
            ])],
            'status' => ['nullable', 'string', Rule::in([
                Menu::STATUS_ACTIVE,
                Menu::STATUS_MAINTENANCE,
                Menu::STATUS_DISABLED,
            ])],
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'parent_id' => 'nullable|uuid|exists:pgsql.menus,id',
            'sort_order' => 'nullable|integer|min:0',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'uuid|exists:pgsql.roles,id',
        ])->validate();

        $roleIds = array_key_exists('role_ids', $data) ? $data['role_ids'] : null;
        unset($data['role_ids']);

        if ($data !== []) {
            $menu->update($data);
        }

        if (is_array($roleIds)) {
            $menu->roles()->sync($roleIds);
        }

        return response()->json($this->formatMenu($menu->fresh()));
    }

    /**
     * Sync roles for a menu item.
     */
    public function syncRoles(Request $request, Menu $menu): JsonResponse
    {
        $data = $request->validate([
            'role_ids' => 'present|array',
            'role_ids.*' => 'uuid|exists:pgsql.roles,id',
        ]);

        $menu->roles()->sync($data['role_ids']);

        return response()->json([
            'message' => 'Menu roles updated successfully.',
            'menu' => $this->formatMenu($menu->fresh()),
        ]);
    }

    /**
     * Delete a menu item.
     */
    public function destroy(Menu $menu): JsonResponse
    {
        if ($menu->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a menu that has child items. Remove or reassign children first.',
            ], 422);
        }

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

        $applicationMenu = Menu::on('pgsql')
            ->where('id', $applicationId)
            ->with(['children' => function ($query) use ($roleIds, $isSuperAdmin) {
                $query->where('type', Menu::TYPE_SUBMENU)->orderBy('sort_order');

                if (!$isSuperAdmin) {
                    $query->visibleToRoles($roleIds);
                }
            }])
            ->first();

        if (!$applicationMenu) {
            return response()->json(['message' => 'Application layout could not be found.'], 404);
        }

        $uniqueChildren = $applicationMenu->children
            ->unique('path')
            ->values();

        return response()->json([
            'id' => $applicationMenu->id,
            'label' => $applicationMenu->label,
            'path' => $applicationMenu->path,
            'icon' => $applicationMenu->icon,
            'sort_order' => $applicationMenu->sort_order,
            'children' => $uniqueChildren,
        ]);
    }

    private function formatMenu(Menu $menu): Menu
    {
        return $menu->load(['roles:id,role_name', 'parent:id,label,path,type'])
            ->loadCount(['children', 'roles']);
    }
}
