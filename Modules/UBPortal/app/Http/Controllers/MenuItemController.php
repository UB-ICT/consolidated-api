<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\MenuItem;

class MenuItemController extends Controller
{
    public function index(): JsonResponse
    {
        // Only get top-level items so the recursive 'children' relationship 
        // builds the tree correctly without duplicates in the root list.
        $menuItems = MenuItem::whereNull('parent_id')
            ->with(['role', 'children']) // 'children' will recursively load sub-menus
            ->orderBy('sort_order')
            ->get();

        return response()->json($menuItems);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'      => 'required|string|max:255',
            'path'       => 'required|string|max:255',
            'icon'       => 'nullable|string|max:255',
            'role_id'    => 'nullable|uuid|exists:roles,id',
            'parent_id'  => 'nullable|uuid|exists:menu_items,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $menuItem = MenuItem::create($data);

        return response()->json($menuItem, 201);
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        return response()->json($menuItem->load(['role', 'children']));
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $data = $request->validate([
            'label'      => 'sometimes|required|string|max:255',
            'path'       => 'sometimes|required|string|max:255',
            'icon'       => 'nullable|string|max:255',
            'role_id'    => 'nullable|uuid|exists:roles,id',
            'parent_id'  => 'nullable|uuid|exists:menu_items,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $menuItem->update($data);

        return response()->json($menuItem);
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        // Note: Because of our migration's onDelete('cascade'), 
        // deleting a parent will automatically delete its children.
        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}
