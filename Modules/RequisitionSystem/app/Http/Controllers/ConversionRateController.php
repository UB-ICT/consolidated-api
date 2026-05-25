<?php

namespace Modules\RequisitionSystem\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\ConversionRate;

class ConversionRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $conversionRates = ConversionRate::all();
        return response()->json($conversionRates);
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
                ConversionRate::create($item);
            }

            return response()->json(['message' => 'All items created successfully!'], 201);
        }

        $conversionRate = ConversionRate::create($payload);
        return response()->json(['message' => 'Item created successfully!', 'data' => $conversionRate], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return ConversionRate::findOrFail($id);
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
        $conversionRate = ConversionRate::findOrFail($id);
        $conversionRate->update($request->all());
        return response()->json(['message' => 'Updated!', 'data' => $conversionRate]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $conversionRate = ConversionRate::findOrFail($id);
        $conversionRate->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
