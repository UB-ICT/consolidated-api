<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Http\Requests\StageStoreRequest;

class StageController extends Controller
{
    /**
     * List all stages with their associated pipelines
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            // Changed from 'pipeline' to 'pipelines' to match model pluralization
            'data' => Stage::with('pipelines')->get(),
        ]);
    }

    /**
     * Create stage and attach to pipelines with sequences
     */
    public function store(StageStoreRequest $request)
    {
        // 1. Create the base stage record
        $stage = Stage::create($request->validated());

        // 2. Attach to pipelines if provided
        // Expecting structure: 'pipelines' => [ ['id' => 1, 'sequence' => 1], ['id' => 2, 'sequence' => 3] ]
        if ($request->has('pipelines')) {
            foreach ($request->input('pipelines') as $pipelineData) {
                $stage->pipelines()->attach($pipelineData['id'], [
                    'sequence' => $pipelineData['sequence'] ?? 1
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Stage created successfully.',
            'data' => $stage->load('pipelines'),
        ], 201);
    }

    /**
     * Show stage
     */
    public function show(Stage $stage)
    {
        return response()->json([
            'success' => true,
            'data' => $stage->load('pipelines'), // Changed to plural
        ]);
    }

    /**
     * Update stage and sync pipeline sequences
     */
    public function update(StageStoreRequest $request, Stage $stage)
    {
        // 1. Update basic stage details
        $stage->update($request->validated());

        // 2. Sync relationships and pivot data
        if ($request->has('pipelines')) {
            $syncData = [];
            foreach ($request->input('pipelines') as $pipelineData) {
                $syncData[$pipelineData['id']] = [
                    'sequence' => $pipelineData['sequence'] ?? 1
                ];
            }
            // sync() deletes omitted connections and updates existing sequences
            $stage->pipelines()->sync($syncData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stage updated successfully.',
            'data' => $stage->load('pipelines'),
        ]);
    }

    /**
     * Delete stage
     */
    public function destroy(Stage $stage)
    {
        // Because of ->onDelete('cascade') in your migration, 
        // the pivot rows in pipeline_stages will delete automatically!
        $stage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stage deleted successfully.',
        ]);
    }
}