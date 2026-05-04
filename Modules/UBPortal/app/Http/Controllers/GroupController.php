<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\Group;

class GroupController extends Controller
{
    public function index(): JsonResponse
    {
        // Added withCount('users') so the UI can show group size efficiently
        $groups = Group::with('roles')
            ->withCount('users')
            ->orderBy('group_name')
            ->get();

        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_name'  => 'required|string|max:255|unique:groups,group_name',
            'description' => 'nullable|string',
        ]);

        $group = Group::create($data);

        return response()->json($group, 201);
    }

    /**
     * Using Group model type-hinting for automatic UUID lookup
     */
    public function show(Group $group): JsonResponse
    {
        return response()->json($group->load(['roles', 'users']));
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        $data = $request->validate([
            'group_name'  => 'sometimes|required|string|max:255|unique:groups,group_name,' . $group->id,
            'description' => 'nullable|string',
        ]);

        $group->update($data);

        return response()->json($group);
    }

    public function destroy(Group $group): JsonResponse
    {
        // Deleting a group will detach users/roles automatically 
        // due to our cascade migrations.
        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }
}
