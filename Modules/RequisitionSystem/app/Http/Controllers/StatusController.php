<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Http\Requests\StatusStoreRequest;

class StatusController extends Controller
{
    /**
     * List all statuses
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Status::all(),
        ]);
    }

    /**
     * Create status
     */
    public function store(StatusStoreRequest $request)
    {
        $status = Status::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Status created successfully.',
            'data' => $status,
        ], 201);
    }

    /**
     * Show status
     */
    public function show(Status $status)
    {
        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Update status
     */
    public function update(StatusStoreRequest $request, Status $status)
    {
        $status->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => $status,
        ]);
    }

    /**
     * Delete status
     */
    public function destroy(Status $status)
    {
        $status->delete();

        return response()->json([
            'success' => true,
            'message' => 'Status deleted successfully.',
        ]);
    }
}
