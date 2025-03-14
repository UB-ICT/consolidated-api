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

    public function uploadProfilePicture(Request $request)
    {
        // Validate the request to ensure the file is an image
        $request->validate([
            'picture' => 'required|image|mimes:jpg,jpeg,png|max:12048', // 2MB max
        ]);

        // Check if file is uploaded
        if ($request->hasFile('picture')) { // Changed 'profile_picture' to 'picture'
            // Get the uploaded file
            $file = $request->file('picture');

            // Generate a unique name for the file and store it in the 'profile_pictures' directory
            $filename = 'picture' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('pictures', $filename, 'public');

            // Save the file path to the user's record
            $user = Auth::user();
            $user->picture = $filePath;
            $user->save();

            return response()->json([
                'message' => 'Profile picture uploaded successfully',
                'file_path' => Storage::url($filePath), // Returns the URL to the uploaded file
            ], 200);
        }

        return response()->json([
            'message' => 'No file uploaded',
        ], 400);
    }
}