<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RequisitionSystem\Models\ConversionRate;
use Modules\RequisitionSystem\Http\Requests\ConversionRateStoreRequest;

class ConversionRateController extends Controller
{
    /**
     * Display a listing of conversion rates.
     */
    public function index(): JsonResponse
    {
        $rates = ConversionRate::all();

        return response()->json([
            'success' => true,
            'data'    => $rates
        ], 200);
    }

    /**
     * Store a newly created conversion rate in storage.
     */
    public function store(ConversionRateStoreRequest $request): JsonResponse
    {
        $rate = ConversionRate::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Conversion rate added successfully.',
            'data'    => $rate
        ], 201);
    }

    /**
     * Display the specified conversion rate.
     */
    public function show(ConversionRate $conversionRate): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $conversionRate
        ], 200);
    }

    /**
     * Update the specified conversion rate in storage.
     */
    public function update(ConversionRateStoreRequest $request, ConversionRate $conversionRate): JsonResponse
    {
        $conversionRate->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Conversion rate updated successfully.',
            'data'    => $conversionRate
        ], 200);
    }

    /**
     * Remove the specified conversion rate from storage.
     */
    public function destroy(ConversionRate $conversionRate): JsonResponse
    {
        $conversionRate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversion rate removed successfully.'
        ], 200);
    }
}
