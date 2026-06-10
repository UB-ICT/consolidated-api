<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Http\Requests\ItemStoreRequest;

class ItemController extends Controller
{
    /**
     * List all items
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Item::all(),
        ]);
    }

    /**
     * Store new item
     */
    public function store(ItemStoreRequest $request)
    {
        $item = Item::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Show single item
     */
    public function show(Item $item)
    {
        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Update item
     */
    public function update(ItemStoreRequest $request, Item $item)
    {
        $item->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'data' => $item,
        ]);
    }

    /**
     * Delete item
     */
    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }
}
