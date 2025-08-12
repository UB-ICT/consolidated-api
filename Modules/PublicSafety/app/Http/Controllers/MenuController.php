<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\PublicSafety\Models\Menu;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
       
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
       
    }

    public function getMenus(Request $request)
    {

    }

    private function getMenusByRole($roleId)
    {
    //     // //$role receives a role and with(['menus.subMenus']) ensures menus and their submenus are loaded in a single query
    //     $role = Role::with(['menus.subMenus'])->find($roleId);

    //     if (!$role) {
    //         return [];
    //     }

    //     return $role->menus->map(function ($menu) {
    //         return [
    //             'id' => $menu->id,
    //             'icon' => $menu->icon,
    //             'name' => $menu->name,
    //             'path' => $menu->path,
    //             'subMenu' => $menu->subMenus->map(function ($subMenu) {
    //                 return [
    //                     'id' => $subMenu->id,
    //                     'icon' => $subMenu->icon,
    //                     'name' => $subMenu->name,
    //                     'path' => $subMenu->path,
    //                     'menuId' => $subMenu->menu_id,
    //                 ];
    //             }),
    //         ];
    //     });
    // }
}
