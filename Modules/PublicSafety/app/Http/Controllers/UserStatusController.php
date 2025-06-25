<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\UserStatusResource;
use Modules\PublicSafety\Transformers\UserStatusCollection;
use Modules\PublicSafety\Http\Requests\StoreUserStatusRequest;
use Modules\PublicSafety\Http\Requests\UpdateUserStatusRequest;
use Modules\PublicSafety\Models\UserStatus;

class UserStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new UserStatusCollection(UserStatus::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserStatusRequest $request)
    {
        return new UserStatusResource(UserStatus::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(UserStatus $userStatus)
    {
        return new UserStatusResource($userStatus);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserStatusRequest $request, UserStatus $userStatus)
    {
        $userStatus->update($request->all());
        return response()->json(['message' => 'updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserStatus $userStatus)
    {
        $userStatus->delete();
        return response()->json(['message' => 'deleted successfully'], 200);
    }
}
