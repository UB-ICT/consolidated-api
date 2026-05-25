<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\AccessRequest;

/**
 * Handles all access request operations.
 *
 * This controller allows users to:
 * - View access requests
 * - Submit new access requests
 * - View a single request
 * - Approve or deny requests
 * - Delete requests
 */
class AccessRequestController extends Controller
{
    /**
     * Display a paginated list of access requests.
     *
     * Relationships are eager loaded to reduce query count:
     * - requester      => user making the request
     * - application    => application being requested
     * - requestedRole  => role being requested
     */
    public function index(): JsonResponse
    {
        // Use pagination because access requests can grow significantly over time
        $accessRequests = AccessRequest::with([
            'requester',
            'application',
            'requestedRole'
        ])
            ->latest() // Order by newest first
            ->paginate(20); // Return 20 results per page

        return response()->json($accessRequests);
    }

    /**
     * Store a newly created access request.
     *
     * Validates incoming data before creating the request.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request payload
        $data = $request->validate([
            // User requesting access
            'requester_id' => 'required|uuid|exists:users,id',

            // Application the user wants access to
            'app_id' => 'required|uuid|exists:applications,id',

            // Role being requested
            'requested_role_id' => 'required|uuid|exists:roles,id',

            // Optional explanation for why access is needed
            'reason' => 'nullable|string',
        ]);

        // New requests should always start as pending
        $data['status'] = 'pending';

        // Create access request record
        $accessRequest = AccessRequest::create($data);

        // Return created resource with HTTP 201 status
        return response()->json($accessRequest, 201);
    }

    /**
     * Display a specific access request.
     *
     * Route model binding automatically resolves the AccessRequest model.
     */
    public function show(AccessRequest $accessRequest): JsonResponse
    {
        // Load related models before returning response
        return response()->json(
            $accessRequest->load([
                'requester',
                'application',
                'requestedRole'
            ])
        );
    }

    /**
     * Update an existing access request.
     *
     * Typically used by administrators to:
     * - approve requests
     * - deny requests
     * - add administrative notes
     */
    public function update(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        $payload = $request->all();

        // Fallback for clients that send raw JSON with incorrect headers.
        if (empty($payload)) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // Validate update payload
        $data = validator($payload, [
            // Request status must be one of these values
            'status' => 'required|string|in:pending,approved,denied',

            // Optional notes from administrator/reviewer
            'admin_notes' => 'nullable|string',
        ])->validate();

        // Update request record
        $accessRequest->update($data);

        /**
         * If the request is approved:
         * Automatically assign the requested role to the user.
         *
         * syncWithoutDetaching() ensures:
         * - existing roles are preserved
         * - duplicate role assignments are avoided
         */
        if ($accessRequest->status === 'approved') {
            $accessRequest->requester
                ->roles()
                ->syncWithoutDetaching([
                    $accessRequest->requested_role_id
                ]);
        }

        return response()->json($accessRequest->fresh());
    }

    /**
     * Remove an access request from the system.
     */
    public function destroy(AccessRequest $accessRequest): JsonResponse
    {
        // Delete request record
        $accessRequest->delete();

        return response()->json([
            'message' => 'Access request removed.'
        ]);
    }
}
