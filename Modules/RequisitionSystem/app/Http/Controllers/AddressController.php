<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\RequisitionSystem\Models\Address;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Display a listing of the addresses.
     */
    public function index(): JsonResponse
    {
        $addresses = Address::all();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ], 200);
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|integer', // Adjust based on your foreign key setup
            'street'      => 'required|string|max:255',
            'city'        => 'required|string|max:255',
            'district'    => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country_id'  => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $address = Address::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'data' => $address
        ], 201);
    }

    /**
     * Display the specified address.
     */
    public function show($id): JsonResponse
    {
        $address = Address::find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $address
        ], 200);
    }

    /**
     * Update the specified address in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $address = Address::find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|required|integer',
            'street'      => 'sometimes|required|string|max:255',
            'city'        => 'sometimes|required|string|max:255',
            'district'    => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country_id'  => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => $address
        ], 200);
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy($id): JsonResponse
    {
        $address = Address::find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.'
            ], 404);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.'
        ], 200);
    }
}
