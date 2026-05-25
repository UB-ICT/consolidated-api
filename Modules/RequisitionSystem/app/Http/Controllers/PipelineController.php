<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\Pipeline;

class PipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pipelines = Pipeline::all();
        return response()->json($pipelines);
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
                Pipeline::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $pipeline = Pipeline::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $pipeline], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return Pipeline::findOrFail($id);
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
        $pipeline = Pipeline::findOrFail($id);
        $pipeline->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $pipeline]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pipeline = Pipeline::findOrFail($id);
        $pipeline->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
