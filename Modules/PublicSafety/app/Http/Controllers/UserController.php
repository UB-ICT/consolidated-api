<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\UserResource;
use Modules\PublicSafety\Transformers\UserCollection;
use Modules\PublicSafety\Http\Requests\StoreUserRequest;
use Modules\PublicSafety\Http\Requests\UpdateUserRequest;
use Modules\PublicSafety\Models\User;
use Illuminate\Support\Facades\Auth;



class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new UserCollection(User::paginate());

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        return new UserResource(User::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->all());
        return response()->json(['message' => 'user updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if (!Auth::user()->can('Delete User')) {
            return response()->json(['message' => 'not Authorize to delete'], 401);
        }

        $user->delete();
        return response()->json(['message' => 'user deleted successfully'], 200);
    }

    public function getTotalUsers(User $user)
    {
        $user = User::count();
        return response()->json(['total' => $user]);
    }

    public function getUsers()
    {
        $users = User::select('id', 'name', )->get();

        return response()->json([
            'users' => $users
        ]);
    }
}