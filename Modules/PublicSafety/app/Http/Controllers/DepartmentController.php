<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\DepartmentResource;
use Modules\PublicSafety\Transformers\DepartmentCollection;
use Modules\PublicSafety\Http\Requests\StoreDepartmentRequest;
use Modules\PublicSafety\Http\Requests\UpdateDepartmentRequest;
use Modules\PublicSafety\Models\Department;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new DepartmentCollection(Department::paginate());

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepartmentRequest $request)
    {
        return new DepartmentResource(Department::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return new DepartmentResource($department);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->all());
        return response()->json(['message' => 'updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'deleted successfully'], 200);
    }
}
