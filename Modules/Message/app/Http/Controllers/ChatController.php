<?php

namespace Modules\Message\Http\Controllers;
use Modules\Message\Models\Chat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $query = Chat::query();
        
        // Filter by category if needed
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Search by name if needed
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        
        return $query->get();
    }
    
    public function show($id)
    {
        return Chat::findOrFail($id);
    }
}
