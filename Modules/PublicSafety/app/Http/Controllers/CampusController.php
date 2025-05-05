<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Http\Resources\CampusResource;
use Modules\PublicSafety\Http\Resources\CampusCollection;
use Modules\PublicSafety\Http\Requests\StoreCampusRequest;
use Modules\PublicSafety\Http\Requests\UpdateCampusRequest;
use Modules\PublicSafety\Models\Campus;

class CampusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new CampusCollection(Campus::paginate());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampusRequest $request)
    {
        return new CampusResource(Campus::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Campus $campus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campus $campus)
    {
        return new CampusResource($campus);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampusRequest $request, Campus $campus)
    {
        $campus->update($request->all());
        return response()->json(['message' => 'campus updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campus $campus)
    {
        $campus->delete();
        return response()->json(['message' => 'campus deleted successfully'], 200);
    }
}
