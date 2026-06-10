<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Http\Requests\PipelineStoreRequest;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    /**
     * Display all pipelines
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Pipeline::all(),
        ]);
    }

    /**
     * Store a new pipeline
     */
    public function store(PipelineStoreRequest $request)
    {
        $pipeline = Pipeline::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pipeline created successfully.',
            'data' => $pipeline,
        ], 201);
    }

    /**
     * Show single pipeline
     */
    public function show(Pipeline $pipeline)
    {
        return response()->json([
            'success' => true,
            'data' => $pipeline,
        ]);
    }

    /**
     * Update pipeline
     */
    public function update(PipelineStoreRequest $request, Pipeline $pipeline)
    {
        $pipeline->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pipeline updated successfully.',
            'data' => $pipeline,
        ]);
    }

    /**
     * Delete pipeline
     */
    public function destroy(Pipeline $pipeline)
    {
        $pipeline->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pipeline deleted successfully.',
        ]);
    }
}
