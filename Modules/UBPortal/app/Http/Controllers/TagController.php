<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\UBPortal\Models\Tag;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Tag::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $tag = Tag::create($data);

        return response()->json($tag, 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json($tag->load('posts'));
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tag->id)],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tag->id)],
        ]);

        if (array_key_exists('name', $data) && !array_key_exists('slug', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return response()->json($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully']);
    }
}
