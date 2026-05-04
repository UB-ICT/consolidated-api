<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\AccessRequest;

class AccessRequestController extends Controller
{
    public function index(): JsonResponse
    {
        // Use pagination for requests as they will grow over time
        $accessRequests = AccessRequest::with(['requester', 'application', 'requestedRole'])
            ->latest()
            ->paginate(20);

        return response()->json($accessRequests);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requester_id'      => 'required|uuid|exists:users,id',
            'app_id'            => 'required|uuid|exists:applications,id',
            'requested_role_id' => 'required|uuid|exists:roles,id',
            'reason'            => 'nullable|string', // Added a reason field for the student/staff
        ]);

        // Default status to pending on creation
        $data['status'] = 'pending';

        $accessRequest = AccessRequest::create($data);

        return response()->json($accessRequest, 201);
    }

    public function show(AccessRequest $accessRequest): JsonResponse
    {
        return response()->json($accessRequest->load(['requester', 'application', 'requestedRole']));
    }

    public function update(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,approved,denied',
            'admin_notes' => 'nullable|string', // Notes from the person approving/denying
        ]);

        $accessRequest->update($data);

        // Logic Suggestion: If approved, link the role to the user automatically
        if ($accessRequest->status === 'approved') {
            $accessRequest->requester->roles()->syncWithoutDetaching([$accessRequest->requested_role_id]);
        }

        return response()->json($accessRequest);
    }

    public function destroy(AccessRequest $accessRequest): JsonResponse
    {
        $accessRequest->delete();
        return response()->json(['message' => 'Access request removed.']);
    }
}