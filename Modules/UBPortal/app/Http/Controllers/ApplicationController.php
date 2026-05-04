<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\Application;

class ApplicationController extends Controller
{
    public function index(): JsonResponse
    {
        // Adding withCount('roles') lets you see how many security profiles 
        // are attached to each app (e.g., "Finance App" has 3 roles).
        $applications = Application::withCount('roles')
            ->orderBy('app_name')
            ->get();

        return response()->json($applications);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_name'    => 'required|string|max:255|unique:applications,app_name',
            'description' => 'nullable|string',
        ]);

        $application = Application::create($data);

        return response()->json($application, 201);
    }

    /**
     * Automatic UUID lookup using Application model type-hinting
     */
    public function show(Application $application): JsonResponse
    {
        return response()->json($application->load('roles'));
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate([
            'app_name'    => 'sometimes|required|string|max:255|unique:applications,app_name,' . $application->id,
            'description' => 'nullable|string',
        ]);

        $application->update($data);

        return response()->json($application);
    }

    public function destroy(Application $application): JsonResponse
    {
        $application->delete();

        return response()->json(['message' => 'Application removed from portal successfully']);
    }
}