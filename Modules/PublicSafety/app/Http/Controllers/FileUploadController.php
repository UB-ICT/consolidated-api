<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads', 'public');
            return response()->json(['message' => 'File uploaded successfully', 'path' => $path]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }
}