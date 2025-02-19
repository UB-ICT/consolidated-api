<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Http\Resources\RoleCollection;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;


class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new RoleCollection(Role::paginate());

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
    public function store(StoreRoleRequest $request)
    {
        return new RoleResource(Role::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return new RoleResource($role);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->all());
        return response()->json(['message' => 'role updated successfully'], 200);   
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'role deleted successfully'], 200);

    }


    // The assignRoleToUser function in the RoleController is responsible for assigning a role to a specific user in your Laravel application
    public function assignRoleToUser(Request $request)
    {

        //user_id and role_id are required fields and exist in the users and roles table respectively
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        //Find the user by the user_id and assign the role_id to the user
        $user = User::find($validatedData['user_id']);
        $user->role_id = $validatedData['role_id'];
        $user->save();

        return response()->json(['message' => 'Role assigned to user successfully', 'user' => $user]);
    }
}