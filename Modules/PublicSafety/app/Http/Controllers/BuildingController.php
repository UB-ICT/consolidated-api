<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\BuildingResource;
use Modules\PublicSafety\Transformers\BuildingCollection;
use Modules\PublicSafety\Http\Requests\StoreBuildingRequest;
use Modules\PublicSafety\Http\Requests\UpdateBuildingRequest;
use Modules\PublicSafety\Models\Building;

class BuildingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new BuildingCollection(Building::paginate());

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBuildingRequest $request)
    {
        return new BuildingResource(Building::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Building $building)
    {
        return new BuildingResource($building);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $building->update($request->all());
        return response()->json(['message' => 'building updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Building $building)
    {
        $building->delete();
        return response()->json(['message' => 'building deleted successfully'], 200);
    }
}
