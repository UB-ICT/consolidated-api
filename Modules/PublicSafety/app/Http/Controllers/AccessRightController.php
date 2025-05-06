<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\AccessRightResource;
use Modules\PublicSafety\Transformers\AccessRightCollection;
use Modules\PublicSafety\Http\Requests\StoreAccessRightRequest;
use Modules\PublicSafety\Http\Requests\UpdateAccessRightRequest;
use Modules\PublicSafety\Models\AccessRight;

class AccessRightController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new AccessRightCollection(AccessRight::paginate());

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccessRightRequest $request)
    {
        return new AccessRightResource(AccessRight::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(AccessRight $accessRight)
    {
        return new AccessRightResource($accessRight);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccessRightRequest $request, AccessRight $accessRight)
    {
        $accessRight->update($request->all());
        return response()->json(['message' => 'accessRight updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccessRight $accessRight)
    {
        $accessRight->delete();
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
}
