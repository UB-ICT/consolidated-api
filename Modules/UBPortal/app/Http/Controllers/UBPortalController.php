<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UBPortalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('ubportal::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ubportal::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('ubportal::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('ubportal::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
