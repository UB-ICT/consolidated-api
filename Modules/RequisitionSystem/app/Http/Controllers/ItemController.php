<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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
     * Store new item and refresh header total
     */
    public function store(ItemStoreRequest $request)
    {
        $item = DB::transaction(function () use ($request) {
            $newItem = Item::create($request->validated());

            // Recalculate parent requisition header total
            $newItem->requisition->update([
                'total' => $newItem->requisition->items()->sum('total')
            ]);

            return $newItem;
        });

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully and requisition updated.',
            'data' => $item->load('requisition'),
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
     * Update item and recalculate header total
     */
    public function update(ItemStoreRequest $request, Item $item)
    {
        DB::transaction(function () use ($request, $item) {
            $item->update($request->validated());

            // Recalculate parent requisition total header
            $item->requisition->update([
                'total' => $item->requisition->items()->sum('total')
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully and requisition updated.',
            'data' => $item,
        ]);
    }

    /**
     * Delete item and adjust header total downwards
     */
    public function destroy(Item $item)
    {
        DB::transaction(function () use ($item) {
            $requisition = $item->requisition;
            $item->delete();

            // Recalculate total header since this item no longer exists
            $requisition->update([
                'total' => $requisition->items()->sum('total')
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully and requisition updated.',
        ]);
    }
}
