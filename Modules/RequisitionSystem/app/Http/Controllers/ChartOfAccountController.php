<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Http\Requests\ChartOfAccountStoreRequest;
use Modules\RequisitionSystem\Models\ChartOfAccount;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of chart of accounts, optionally filtered by
     * account number or description for the line item picker.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChartOfAccount::query()
            ->with(['parent:id,account_no,description'])
            ->orderBy('account_no');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($query) use ($search) {
                $query->where('account_no', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(),
        ], 200);
    }

    /**
     * Store a newly created chart of account in storage.
     */
    public function store(ChartOfAccountStoreRequest $request): JsonResponse
    {
        $chartOfAccount = ChartOfAccount::create($request->validated());
        $chartOfAccount->load(['parent:id,account_no,description']);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data'    => $chartOfAccount,
        ], 201);
    }

    /**
     * Display the specified chart of account.
     */
    public function show(ChartOfAccount $chartOfAccount): JsonResponse
    {
        $chartOfAccount->load([
            'parent:id,account_no,description',
            'children:id,parent_id,account_no,description',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $chartOfAccount,
        ], 200);
    }

    /**
     * Update the specified chart of account in storage.
     */
    public function update(ChartOfAccountStoreRequest $request, ChartOfAccount $chartOfAccount): JsonResponse
    {
        $chartOfAccount->update($request->validated());
        $chartOfAccount->load(['parent:id,account_no,description']);

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully.',
            'data'    => $chartOfAccount,
        ], 200);
    }

    /**
     * Remove the specified chart of account from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount): JsonResponse
    {
        if ($chartOfAccount->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this account because it has nested child accounts. Move or delete the children first.',
            ], 422);
        }

        if ($chartOfAccount->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this account because it is linked to requisition line items.',
            ], 422);
        }

        if ($chartOfAccount->budgetLineItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this account because it is linked to budget line items.',
            ], 422);
        }

        $chartOfAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ], 200);
    }
}
