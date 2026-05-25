<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\SupplierBank;

class SupplierBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $supplierBanks = SupplierBank::all();
        return response()->json($supplierBanks);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('requisitionsystem::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payload = $request->all();

        if (isset($payload[0]) && is_array($payload[0])) {
            foreach ($payload as $item) {
                SupplierBank::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $supplierBank = SupplierBank::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $supplierBank], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return SupplierBank::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('requisitionsystem::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $supplierBank = SupplierBank::findOrFail($id);
        $supplierBank->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $supplierBank]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $supplierBank = SupplierBank::findOrFail($id);
        $supplierBank->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
