<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\Group;

/**
 * Handles CRUD operations for user groups.
 *
 * Groups can organize users and roles for easier
 * permission management across the portal.
 */
class GroupController extends Controller
{
    /**
     * Display all groups.
     *
     * Includes related roles and a users_count value
     * for quick frontend summaries.
     */
    public function index(): JsonResponse
    {
        // Include role assignments and member counts for list views.
        $groups = Group::with('roles')
            ->withCount('users')
            ->orderBy('group_name')
            ->get();

        return response()->json($groups);
    }

    /**
     * Store a newly created group.
     *
     * Validates required fields and enforces unique group names.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_name'  => 'required|string|max:255|unique:groups,group_name',
            'description' => 'nullable|string',
        ]);

        // Persist the group record.
        $group = Group::create($data);

        return response()->json($group, 201);
    }

    /**
     * Display a specific group.
     *
     * Route model binding resolves the Group instance
     * from the route parameter automatically.
     */
    public function show(Group $group): JsonResponse
    {
        // Include users and roles for detail/edit screens.
        return response()->json($group->load(['roles', 'users']));
    }

    /**
     * Update an existing group.
     *
     * Uses partial validation so clients can send only changed fields.
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        $data = $request->validate([
            // Keep group_name unique while ignoring the current group.
            'group_name'  => 'sometimes|required|string|max:255|unique:groups,group_name,' . $group->id,
            'description' => 'nullable|string',
        ]);

        // Apply validated updates.
        $group->update($data);

        return response()->json($group);
    }

    /**
     * Delete a group.
     *
     * Relationship cleanup depends on configured foreign key
     * constraints and pivot table behavior.
     */
    public function destroy(Group $group): JsonResponse
    {
        // Deleting a group will detach users/roles automatically 
        // due to our cascade migrations.
        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }
}
