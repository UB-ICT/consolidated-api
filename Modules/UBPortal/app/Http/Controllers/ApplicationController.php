<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\Application;

/**
 * Handles CRUD operations for portal applications.
 *
 * Applications are logical systems/modules that can be
 * associated with roles and access control policies.
 */
class ApplicationController extends Controller
{
    /**
     * Display all applications.
     *
     * Includes role counts so clients can show usage summaries
     * without additional API calls.
     */
    public function index(): JsonResponse
    {
        // Include role totals for each application in list responses.
        $applications = Application::withCount('roles')
            ->orderBy('app_name')
            ->get();

        return response()->json($applications);
    }

    /**
     * Store a newly created application.
     *
     * Validates required fields and enforces unique application names.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_name'    => 'required|string|max:255|unique:applications,app_name',
            'description' => 'nullable|string',
        ]);

        // Persist the application record.
        $application = Application::create($data);

        return response()->json($application, 201);
    }

    /**
     * Display a specific application.
     *
     * Route model binding resolves the Application instance
     * from the route parameter automatically.
     */
    public function show(Application $application): JsonResponse
    {
        // Include related roles for detail and edit screens.
        return response()->json($application->load('roles'));
    }

    /**
     * Update an existing application.
     *
     * Uses partial validation so only provided fields are updated.
     */
    public function update(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate([
            // Keep app_name unique while ignoring the current application.
            'app_name'    => 'sometimes|required|string|max:255|unique:applications,app_name,' . $application->id,
            'description' => 'nullable|string',
        ]);

        // Apply validated updates.
        $application->update($data);

        return response()->json($application);
    }

    /**
     * Delete an application.
     */
    public function destroy(Application $application): JsonResponse
    {
        // Remove the application record.
        $application->delete();

        return response()->json(['message' => 'Application removed from portal successfully']);
    }
}