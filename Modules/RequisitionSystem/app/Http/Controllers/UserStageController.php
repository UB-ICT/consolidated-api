<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\UserStage;

class UserStageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userStages = UserStage::all();
        return response()->json($userStages);
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
                UserStage::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $userStage = UserStage::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $userStage], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return UserStage::findOrFail($id);
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
        $userStage = UserStage::findOrFail($id);
        $userStage->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $userStage]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $userStage = UserStage::findOrFail($id);
        $userStage->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
