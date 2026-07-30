<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\ChartOfAccount;
use Modules\RequisitionSystem\Http\Requests\ChartOfAccountStoreRequest;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of chart of accounts, optionally filtered by
     * account number or description for the line item picker.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChartOfAccount::query()->orderBy('account_no');

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

        return response()->json([
            'success' => true,
            'message' => 'Chart of account created successfully.',
            'data'    => $chartOfAccount
        ], 201);
    }

    /**
     * Display the specified chart of account.
     */
    public function show(ChartOfAccount $chartOfAccount): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $chartOfAccount
        ], 200);
    }

    /**
     * Update the specified chart of account in storage.
     */
    public function update(ChartOfAccountStoreRequest $request, ChartOfAccount $chartOfAccount): JsonResponse
    {
        $chartOfAccount->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Chart of account updated successfully.',
            'data'    => $chartOfAccount
        ], 200);
    }

    /**
     * Remove the specified chart of account from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount): JsonResponse
    {
        $chartOfAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chart of account deleted successfully.'
        ], 200);
    }
}
