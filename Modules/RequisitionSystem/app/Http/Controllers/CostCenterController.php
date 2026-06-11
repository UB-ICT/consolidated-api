<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Http\Requests\CostCenterStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Auth\Models\User;

class CostCenterController extends Controller
{
    /**
     * Display a listing of cost centers.
     */
    public function index(): JsonResponse
    {
        $costCenters = CostCenter::all();

        return response()->json([
            'success' => true,
            'data'    => $costCenters
        ], 200);
    }

    /**
     * Store a newly created cost center in storage.
     */
    public function store(CostCenterStoreRequest $request): JsonResponse
    {
        $costCenter = CostCenter::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cost center created successfully.',
            'data'    => $costCenter
        ], 201);
    }

    /**
     * Display the specified cost center.
     */
    public function show(CostCenter $costCenter): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $costCenter
        ], 200);
    }

    /**
     * Update the specified cost center in storage.
     */
    public function update(CostCenterStoreRequest $request, CostCenter $costCenter): JsonResponse
    {
        $costCenter->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cost center updated successfully.',
            'data'    => $costCenter
        ], 200);
    }

    /**
     * Remove the specified cost center from storage.
     */
    public function destroy(CostCenter $costCenter): JsonResponse
    {
        $costCenter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cost center deleted successfully.'
        ], 200);
    }

    /**
     * Assign a user to a cost center (or vice versa).
     * * Expected JSON body: { "user_id": "UUID-HERE", "cost_center_id": 2 }
     */
    public function assignUserToCostCenter(Request $request): JsonResponse
    {
        // 1. Validate the incoming IDs
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|string|exists:pgsql.users,id', // 👈 explicit mapping to your core pgsql user table
            'cost_center_id' => 'required|integer|exists:cost_centers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Locate the models
        $user = User::findOrFail($request->input('user_id'));
        $costCenterId = $request->input('cost_center_id');

        // 3. Attach using syncWithoutDetaching to prevent duplicate entry crashes
        // This targets your 'user_cost_center' pivot table automatically
        $user->costCenters()->syncWithoutDetaching([$costCenterId]);

        return response()->json([
            'success' => true,
            'message' => "User '{$user->name}' successfully linked to Cost Center ID {$costCenterId}."
        ], 200);
    }
}
