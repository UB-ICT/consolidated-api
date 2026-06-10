<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Http\Requests\CurrencyStoreRequest;

class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies.
     */
    public function index(): JsonResponse
    {
        $currencies = Currency::all();

        return response()->json([
            'success' => true,
            'data'    => $currencies
        ], 200);
    }

    /**
     * Store a newly created currency in storage.
     */
    public function store(CurrencyStoreRequest $request): JsonResponse
    {
        $currency = Currency::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Currency created successfully.',
            'data'    => $currency
        ], 201);
    }

    /**
     * Display the specified currency.
     */
    public function show(Currency $currency): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $currency
        ], 200);
    }

    /**
     * Update the specified currency in storage.
     */
    public function update(CurrencyStoreRequest $request, Currency $currency): JsonResponse
    {
        $currency->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Currency updated successfully.',
            'data'    => $currency
        ], 200);
    }

    /**
     * Remove the specified currency from storage.
     */
    public function destroy(Currency $currency): JsonResponse
    {
        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Currency deleted successfully.'
        ], 200);
    }
}
