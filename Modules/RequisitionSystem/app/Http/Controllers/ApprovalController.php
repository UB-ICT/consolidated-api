<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Http\Requests\ApprovalStoreRequest;

class ApprovalController extends Controller
{
    /**
     * Display a listing of approvals.
     */
    public function index(): JsonResponse
    {
        $approvals = Approval::all();

        return response()->json([
            'success' => true,
            'data'    => $approvals
        ], 200);
    }

    /**
     * Store a newly created approval in storage.
     */
    public function store(ApprovalStoreRequest $request): JsonResponse
    {
        // Automatically merges validated data; can include a timestamp for your casted 'signed_at'
        $data = $request->validated();
        $data['signed_at'] = now();

        $approval = Approval::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Approval recorded successfully.',
            'data'    => $approval
        ], 201);
    }

    /**
     * Display the specified approval.
     */
    public function show(Approval $approval): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $approval
        ], 200);
    }

    /**
     * Update the specified approval in storage.
     */
    public function update(ApprovalStoreRequest $request, Approval $approval): JsonResponse
    {
        $approval->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Approval updated successfully.',
            'data'    => $approval
        ], 200);
    }

    /**
     * Remove the specified approval from storage.
     */
    public function destroy(Approval $approval): JsonResponse
    {
        $approval->delete();

        return response()->json([
            'success' => true,
            'message' => 'Approval deleted successfully.'
        ], 200);
    }
}
