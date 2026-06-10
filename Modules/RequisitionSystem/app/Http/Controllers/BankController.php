<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RequisitionSystem\Models\Bank;
use Modules\RequisitionSystem\Http\Requests\BankStoreRequest;

class BankController extends Controller
{
    /**
     * Display a listing of the banks.
     */
    public function index(): JsonResponse
    {
        $banks = Bank::all();

        return response()->json([
            'success' => true,
            'data'    => $banks
        ], 200);
    }

    /**
     * Store a newly created bank in storage.
     */
    public function store(BankStoreRequest $request): JsonResponse
    {
        $bank = Bank::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bank created successfully.',
            'data'    => $bank
        ], 201);
    }

    /**
     * Display the specified bank.
     */
    public function show(Bank $bank): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $bank
        ], 200);
    }

    /**
     * Update the specified bank in storage.
     */
    public function update(BankStoreRequest $request, Bank $bank): JsonResponse
    {
        $bank->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bank updated successfully.',
            'data'    => $bank
        ], 200);
    }

    /**
     * Remove the specified bank from storage.
     */
    public function destroy(Bank $bank): JsonResponse
    {
        $bank->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank deleted successfully.'
        ], 200);
    }
}
