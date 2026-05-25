<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\CostCenter;

class CostCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return view('requisitionsystem::index');
        $costCenters = CostCenter::all();
        return response()->json($costCenters);
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
        $data = $request->validate([
            '*.name' => 'required|string', // The * means "check every item in the array"
            '*.type' => 'required|string',
        ]);

        foreach ($request->all() as $item) {
            CostCenter::create([
                'name' => $item['name'],
                'type' => $item['type'],
            ]);
        }

        return response()->json(['message' => 'All items created successfully!'], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return CostCenter::findOrFail($id); // Returns 1 item or 404 if missing
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
        $costCenter = CostCenter::findOrFail($id);
        $costCenter->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $costCenter]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $costCenter = CostCenter::findOrFail($id);
        $costCenter->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
