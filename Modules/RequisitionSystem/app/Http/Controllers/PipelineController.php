<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Http\Requests\PipelineStoreRequest;
use Illuminate\Http\JsonResponse;

class PipelineController extends Controller
{
    /**
     * Display all pipelines with their sequenced stages.
     */
    public function index(): JsonResponse
    {
        // 🔥 Eager loading 'stages' prevents N+1 database querying issues
        $pipelines = Pipeline::with('stages')->get();

        return response()->json([
            'success' => true,
            'data' => $pipelines,
        ]);
    }

    /**
     * Store a new pipeline
     */
    public function store(PipelineStoreRequest $request): JsonResponse
    {
        $pipeline = Pipeline::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pipeline created successfully.',
            'data' => $pipeline->load('stages'), // Load relationship for consistency
        ], 201);
    }

    /**
     * Show single pipeline along with its sorted stages.
     */
    public function show(Pipeline $pipeline): JsonResponse
    {
        // 🔥 Explicitly load the relationship on the route-bound instance
        return response()->json([
            'success' => true,
            'data' => $pipeline->load('stages'),
        ]);
    }

    /**
     * Update pipeline
     */
    public function update(PipelineStoreRequest $request, Pipeline $pipeline): JsonResponse
    {
        $pipeline->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pipeline updated successfully.',
            'data' => $pipeline->load('stages'),
        ]);
    }

    /**
     * Delete pipeline
     */
    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $pipeline->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pipeline deleted successfully.',
        ]);
    }
}
