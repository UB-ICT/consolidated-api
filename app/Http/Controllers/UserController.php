<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
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
            return response()->json(['message' => 'not authorize to delete'], 401);
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

    public function uploadPicture(Request $request)
    {
        $validated = $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'userId' => 'required|integer|exists:users,id'
        ]);

        $user = User::findOrFail($validated['userId']);

        // Handle file upload
        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('profile_pictures', 'public');

            // Delete old picture if exists
            if ($user->picture) {
                Storage::delete('public/profile_pictures/' . $user->picture);
            }

            $user->picture = basename($path);
            $user->save();

            return response()->json([
                'data' => [
                    'picture' => asset('storage/profile_pictures/' . $user->picture)
                ]
            ]);
        }

        return response()->json(['error' => 'File upload failed'], 400);
    }
}