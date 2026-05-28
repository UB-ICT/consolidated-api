<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Models\MenuItem;

/**
 * Handles CRUD operations for portal menu items.
 *
 * Menu items can be nested through parent/child relationships
 * to build hierarchical navigation structures.
 */
class MenuItemController extends Controller
{
    /**
     * Display top-level menu items with nested children.
     *
     * Root items are loaded first, and each item includes
     * its role and child menu items.
     */
    public function index(): JsonResponse
    {
        // Load only root items to avoid duplicating child nodes in the top-level list.
        $menuItems = MenuItem::whereNull('parent_id')
            // Include assigned role and recursively loaded descendants.
            ->with(['role', 'children'])
            ->orderBy('sort_order')
            ->get();

        return response()->json($menuItems);
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
            'parent_id'  => 'nullable|uuid|exists:pgsql.menu_items,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Persist the menu item record.
        $menuItem = MenuItem::create($data);

        return response()->json($menuItem, 201);
    }

    /**
     * Display a specific menu item with related data.
     */
    public function show(MenuItem $menuItem): JsonResponse
    {
        // Include role and nested children for detail views/edit screens.
        return response()->json($menuItem->load(['role', 'children']));
    }

    /**
     * Update an existing menu item.
     *
     * Uses partial validation so clients can send only changed fields.
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
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
            'parent_id'  => 'nullable|uuid|exists:pgsql.menu_items,id',
            'sort_order' => 'nullable|integer|min:0',
        ])->validate();

        // Apply validated updates.
        $menuItem->update($data);

        return response()->json($menuItem->fresh());
    }

    /**
     * Delete a menu item.
     *
     * Child items are removed automatically when cascade delete
     * is configured on the parent relation.
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        // Delete the selected menu item.
        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}