<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Http\Requests\UserStageStoreRequest;

class UserStageController extends Controller
{
    /**
     * List all user-stage assignments
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => UserStage::with(['user', 'stage'])->get(),
        ]);
    }

    /**
     * Assign user to stage
     */
    public function store(UserStageStoreRequest $request)
    {
        $userStage = UserStage::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User assigned to stage successfully.',
            'data' => $userStage,
        ], 201);
    }

    /**
     * Show assignment
     */
    public function show(UserStage $userStage)
    {
        return response()->json([
            'success' => true,
            'data' => $userStage->load(['user', 'stage']),
        ]);
    }

    /**
     * Update assignment
     */
    public function update(UserStageStoreRequest $request, UserStage $userStage)
    {
        $userStage->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User-stage updated successfully.',
            'data' => $userStage,
        ]);
    }

    /**
     * Delete assignment
     */
    public function destroy(UserStage $userStage)
    {
        $userStage->delete();

        return response()->json([
            'success' => true,
            'message' => 'User removed from stage successfully.',
        ]);
    }
}
