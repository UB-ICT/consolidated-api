<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\IncidentTypeResource;
use Modules\PublicSafety\Transformers\IncidentTypeCollection;
use Modules\PublicSafety\Http\Requests\StoreIncidentTypeRequest;
use Modules\PublicSafety\Http\Requests\UpdateIncidentTypeRequest;
use Modules\PublicSafety\Models\IncidentType;

class IncidentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new IncidentTypeCollection(IncidentType::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncidentTypeRequest $request)
    {
        return new IncidentTypeResource(IncidentType::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(IncidentType $incidentType)
    {
        return new IncidentTypeResource($incidentType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncidentTypeRequest $request, IncidentType $incidentType)
    {
        $incidentType->update($request->all());
        return response()->json(['message' => 'updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncidentType $incidentType)
    {
        $incidentType->delete();
        return response()->json(['message' => 'deleted successfully'], 200);
    }
}
