<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Bank;
use Modules\RequisitionSystem\Http\Requests\BankStoreRequest;

class BankController extends Controller
{
    /**
     * List all banks (Great for loading your UI dropdown selectors)
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Bank::orderBy('name', 'asc')->get(),
        ]);
    }

    /**
     * Store a new bank option
     */
    public function store(BankStoreRequest $request)
    {
        $bank = Bank::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bank created successfully.',
            'data' => $bank,
        ], 201);
    }

    /**
     * Show an individual bank along with its associated supplier accounts
     */
    public function show(Bank $bank)
    {
        return response()->json([
            'success' => true,
            'data' => $bank->load('supplierBanks.supplier'),
        ]);
    }

    /**
     * Update a bank's name safely
     */
    public function update(BankStoreRequest $request, Bank $bank)
    {
        $bank->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bank updated successfully.',
            'data' => $bank,
        ]);
    }

    /**
     * Delete a bank record
     */
    public function destroy(Bank $bank)
    {
        // Optional Check: Prevent deleting a bank if suppliers are actively linked to it
        if ($bank->supplierBanks()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this bank because it is currently linked to active supplier accounts.',
            ], 422);
        }

        $bank->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank deleted successfully.',
        ]);
    }
}
