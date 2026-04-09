<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\Status;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statuses = Status::all();
        return response()->json($statuses);
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
                Status::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $status = Status::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $status], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return Status::findOrFail($id);
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
        $status = Status::findOrFail($id);
        $status->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $status]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $status = Status::findOrFail($id);
        $status->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
