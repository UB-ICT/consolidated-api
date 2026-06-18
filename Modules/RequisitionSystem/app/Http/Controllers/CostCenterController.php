<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\UserCostCenter;
use Modules\RequisitionSystem\Http\Requests\CostCenterStoreRequest;

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

    public function assignedToMe(Request $request): JsonResponse
    {
        $assignment = UserCostCenter::with('costCenter')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $assignment?->costCenter,
        ]);
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
}
