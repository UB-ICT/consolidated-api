<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Country;
use Modules\RequisitionSystem\Http\Requests\CountryStoreRequest;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Country::all(),
        ]);
    }

    /**
     * Store a newly created country.
     */
    public function store(CountryStoreRequest $request)
    {
        $country = Country::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Country created successfully.',
            'data' => $country,
        ], 201);
    }

    /**
     * Display the specified country.
     */
    public function show(Country $country)
    {
        return response()->json([
            'success' => true,
            'data' => $country,
        ]);
    }

    /**
     * Update the specified country.
     */
    public function update(CountryStoreRequest $request, Country $country)
    {
        $country->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Country updated successfully.',
            'data' => $country,
        ]);
    }

    /**
     * Remove the specified country.
     */
    public function destroy(Country $country)
    {
        $country->delete();

        return response()->json([
            'success' => true,
            'message' => 'Country deleted successfully.',
        ]);
    }
}
