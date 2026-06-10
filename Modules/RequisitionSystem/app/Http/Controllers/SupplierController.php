<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Http\Requests\SupplierStoreRequest;

class SupplierController extends Controller
{
    /**
     * List all suppliers
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Supplier::all(),
        ]);
    }

    /**
     * Create supplier
     */
    public function store(SupplierStoreRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Show supplier
     */
    public function show(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update supplier
     */
    public function update(SupplierStoreRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data' => $supplier,
        ]);
    }

    /**
     * Delete supplier
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
