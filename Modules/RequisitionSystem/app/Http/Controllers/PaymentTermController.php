<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\PaymentTerm;
use Modules\RequisitionSystem\Http\Requests\PaymentTermStoreRequest;

class PaymentTermController extends Controller
{
    /**
     * List all payment terms (Great for loading your UI radio/select options)
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => PaymentTerm::orderBy('id', 'asc')->get(),
        ]);
    }

    /**
     * Store a new payment term option
     */
    public function store(PaymentTermStoreRequest $request)
    {
        $paymentTerm = PaymentTerm::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Payment term created successfully.',
            'data' => $paymentTerm,
        ], 201);
    }

    /**
     * Show an individual payment term along with its associated suppliers
     */
    public function show(PaymentTerm $paymentTerm)
    {
        return response()->json([
            'success' => true,
            'data' => $paymentTerm->load('suppliers'),
        ]);
    }

    /**
     * Update a payment term's name safely
     */
    public function update(PaymentTermStoreRequest $request, PaymentTerm $paymentTerm)
    {
        $paymentTerm->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Payment term updated successfully.',
            'data' => $paymentTerm,
        ]);
    }

    /**
     * Delete a payment term record
     */
    public function destroy(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->suppliers()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this payment term because it is currently linked to active suppliers.',
            ], 422);
        }

        $paymentTerm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment term deleted successfully.',
        ]);
    }
}
