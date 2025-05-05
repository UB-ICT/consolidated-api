<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCampusResource;
use App\Http\Resources\UserCampusCollection;
use App\Http\Requests\StoreUserCampusRequest;
use App\Http\Requests\UpdateUserCampusRequest;
use App\Models\UserCampus;

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
