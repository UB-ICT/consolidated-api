<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\Approval;

class ApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $approvals = Approval::all();
        return response()->json($approvals);
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
                Approval::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $approval = Approval::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $approval], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return Approval::findOrFail($id);
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
        $approval = Approval::findOrFail($id);
        $approval->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $approval]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $approval = Approval::findOrFail($id);
        $approval->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
