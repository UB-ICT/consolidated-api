<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Http\Requests\StageStoreRequest;

class StageController extends Controller
{
    /**
     * List all stages
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Stage::with('pipeline')->get(),
        ]);
    }

    /**
     * Create stage
     */
    public function store(StageStoreRequest $request)
    {
        $stage = Stage::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stage created successfully.',
            'data' => $stage,
        ], 201);
    }

    /**
     * Show stage
     */
    public function show(Stage $stage)
    {
        return response()->json([
            'success' => true,
            'data' => $stage->load('pipeline'),
        ]);
    }

    /**
     * Update stage
     */
    public function update(StageStoreRequest $request, Stage $stage)
    {
        $stage->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stage updated successfully.',
            'data' => $stage,
        ]);
    }

    /**
     * Delete stage
     */
    public function destroy(Stage $stage)
    {
        $stage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stage deleted successfully.',
        ]);
    }
}
