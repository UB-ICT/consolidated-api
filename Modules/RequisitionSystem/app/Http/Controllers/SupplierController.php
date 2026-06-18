<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Http\Requests\SupplierStoreRequest;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * List all suppliers
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Supplier::with('status')->orderBy('name')->get(),
        ]);
    }

    public function store(SupplierStoreRequest $request)
    {
        $validated = $request->validated();

        $supplier = DB::connection('porsql')->transaction(function () use ($validated) {
            $supplier = Supplier::create($validated);

            $supplier->bankAccount()->create([
                'bank_id'        => $validated['bank_id'],
                'account_number' => $validated['account_number'],
                'account_name'   => $validated['account_name'] ?? $supplier->name,
                'address'        => $validated['address'] ?? null,
            ]);

            return $supplier;
        });

        // 🔥 Change load() to refresh() with relations to pull directly from porsql
        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => $supplier->refresh()->load('bankAccount.bank'),
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
        $validated = $request->validated();

        DB::connection('porsql')->transaction(function () use ($validated, $supplier) {
            $supplier->update($validated);

            $supplier->bankAccount()->updateOrCreate(
                ['supplier_id' => $supplier->id],
                [
                    'bank_id'        => $validated['bank_id'],
                    'account_number' => $validated['account_number'],
                    'account_name'   => $validated['account_name'] ?? $supplier->name,
                    'address'        => $validated['address'] ?? null,
                ]
            );
        });

        // 🔥 Force a refresh here too to show the modified banking rows
        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data'    => $supplier->refresh()->load('bankAccount.bank'),
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

    /**
     * Get aggregated supplier counts grouped by their active system status.
     * (Feeds dashboard metrics: Approved 5, Pending review 2)
     */
    public function getStatusCounts()
    {
        // 1. Query database for totals grouped by status_id on your 'porsql' connection
        $counts = Supplier::select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->get()
            ->pluck('total', 'status_id'); // Formats array as [status_id => total]

        // 2. Map totals out to match the semantic names defined in your Status model
        $statusMap = [
            'draft'        => $counts->get(1, 0), // Default to 0 if no matching records exist
            'pending'      => $counts->get(2, 0),
            'approved'     => $counts->get(3, 0),
            'rejected'     => $counts->get(4, 0),
            'under_review' => $counts->get(5, 0),
        ];

        return response()->json([
            'success' => true,
            'data'    => $statusMap
        ]);
    }
}
