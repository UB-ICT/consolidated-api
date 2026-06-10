<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\SupplierBank;
use Modules\RequisitionSystem\Http\Requests\SupplierBankStoreRequest;

class SupplierBankController extends Controller
{
    /**
     * List all supplier bank records
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SupplierBank::with(['supplier', 'bank'])->get(),
        ]);
    }

    /**
     * Store supplier bank details
     */
    public function store(SupplierBankStoreRequest $request)
    {
        $bank = SupplierBank::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier bank details created successfully.',
            'data' => $bank,
        ], 201);
    }

    /**
     * Show single record
     */
    public function show(SupplierBank $supplierBank)
    {
        return response()->json([
            'success' => true,
            'data' => $supplierBank->load(['supplier', 'bank']),
        ]);
    }

    /**
     * Update record
     */
    public function update(SupplierBankStoreRequest $request, SupplierBank $supplierBank)
    {
        $supplierBank->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier bank updated successfully.',
            'data' => $supplierBank,
        ]);
    }

    /**
     * Delete record
     */
    public function destroy(SupplierBank $supplierBank)
    {
        $supplierBank->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier bank deleted successfully.',
        ]);
    }
}
