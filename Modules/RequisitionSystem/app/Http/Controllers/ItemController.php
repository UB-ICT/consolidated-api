<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\Item;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = Item::all();
        return response()->json($items);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('requisitionsystem::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payload = $request->all();

        if (isset($payload[0]) && is_array($payload[0])) {
            foreach ($payload as $item) {
                Item::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $item = Item::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $item], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return Item::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('requisitionsystem::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $item->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $item]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
