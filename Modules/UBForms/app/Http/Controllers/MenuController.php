<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;

class MenuController extends Controller
{
    protected $database;


    public function index()
    {
        $menus = $this->database->getReference('menus')->getValue();
        return response()->json($menus ?: []);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);
        $newMenu = $this->database->getReference('menus')->push($validated);
        return response()->json(['id' => $newMenu->getKey(), ...$validated]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'path' => 'string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);

        $this->database->getReference('menus/' . $id)->update($validated);
        return response()->json(['id' => $id, ...$validated]);
    }

    public function destroy($id)
    {
        $this->database->getReference('menus/' . $id)->remove();
        return response()->json(['message' => 'Menu deleted']);
    }
}
