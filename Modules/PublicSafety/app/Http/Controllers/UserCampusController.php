<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\UserCampusResource;
use Modules\PublicSafety\Transformers\UserCampusCollection;
use Modules\PublicSafety\Http\Requests\StoreUserCampusRequest;
use Modules\PublicSafety\Http\Requests\UpdateUserCampusRequest;
use Modules\PublicSafety\Models\UserCampus;

class UserCampusController extends Controller
{
    public function index()
    {
        return new UserCampusCollection(UserCampus::paginate());
    }
    public function store(StoreUserCampusRequest $request)
    {
        return new UserCampusResource(UserCampus::create($request->all()));

    }

    public function show(UserCampus $userCampus)
    {
        return new UserCampusResource($userCampus);
    }


    public function update(UpdateUserCampusRequest $request, UserCampus $userCampus)
    {
        $userCampus->update($request->all());
        return response()->json(['message' => 'userCampus updated successfully'], 200);
    }
    public function destroy(UserCampus $userCampus)
    {
        $userCampus->delete();
        return response()->json(['message' => 'userCampus deleted successfully'], 200);
    }
}
