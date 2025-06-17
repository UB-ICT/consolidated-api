<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\SubMenuCollection;
use Modules\PublicSafety\Transformers\SubMenuResource;
use Modules\PublicSafety\Http\Requests\StoreSubMenuRequest;
use Modules\PublicSafety\Http\Requests\UpdateSubMenuRequest;
use Modules\PublicSafety\Models\SubMenu;

class SubMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new SubMenuCollection(SubMenu::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubMenuRequest $request)
    {
        return new SubMenuResource(SubMenu::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(SubMenu $subMenu)
    {
        return new SubMenuResource($subMenu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubMenuRequest $request, SubMenu $subMenu)
    {
        $subMenu->update($request->all());
        return response()->json(['message' => 'SubMenu updated successfully', 'data' => $subMenu], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubMenu $subMenu)
    {
        $subMenu->delete();
        return response()->json(['message' => 'submenu deleted successfully'], 200);
    }
}
