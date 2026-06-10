<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;

class RequisitionController extends Controller
{

   

    /**
     * List all requisitions
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Requisition::all(),
        ]);
    }

    /**
     * Create requisition
     */
    public function store(RequisitionStoreRequest $request)
    {
        $requisition = Requisition::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Requisition created successfully.',
            'data' => $requisition,
        ], 201);
    }

    /**
     * Show requisition
     */
    public function show(Requisition $requisition)
    {
        return response()->json([
            'success' => true,
            'data' => $requisition,
        ]);
    }

    /**
     * Update requisition
     */
    public function update(RequisitionStoreRequest $request, Requisition $requisition)
    {
        $requisition->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Requisition updated successfully.',
            'data' => $requisition,
        ]);
    }

    /**
     * Delete requisition
     */
    public function destroy(Requisition $requisition)
    {
        $requisition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Requisition deleted successfully.',
        ]);
    }
}
