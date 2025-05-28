<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\IncidentStatusResource;
use Modules\PublicSafety\Transformers\IncidentStatusCollection;
use Modules\PublicSafety\Http\Requests\StoreIncidentStatusRequest;
use Modules\PublicSafety\Http\Requests\UpdateIncidentStatusRequest;
use Modules\PublicSafety\Models\IncidentStatus;

class IncidentStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new IncidentStatusCollection(IncidentStatus::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncidentStatusRequest $request)
    {
        return new IncidentStatusResource(IncidentStatus::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(IncidentStatus $incidentStatus)
    {
        return new IncidentStatusResource($incidentStatus);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncidentStatusRequest $request, IncidentStatus $incidentStatus)
    {
        $incidentStatus->update($request->all());
        return response()->json(['message' => 'updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncidentStatus $incidentStatus)
    {
        $incidentStatus->delete();
        return response()->json(['message' => 'deleted successfully'], 200);
    }
}
