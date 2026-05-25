<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\UBPortal\Enums\PostStatus;
use Modules\UBPortal\Models\Post;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = Post::with(['author', 'approver', 'tags'])
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'cover_image' => 'nullable|string|max:500',
            'author_id' => 'required|uuid|exists:users,id',
            'status' => ['sometimes', Rule::in(array_map(fn (PostStatus $status) => $status->value, PostStatus::cases()))],
            'approved_by' => 'nullable|uuid|exists:users,id',
            'approved_at' => 'nullable|date',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'uuid|exists:tags,id',
        ]);

        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        $post = Post::create($data);

        if (!empty($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        return response()->json($post->load(['author', 'approver', 'tags']), 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post->load(['author', 'approver', 'tags', 'views']));
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'cover_image' => 'nullable|string|max:500',
            'author_id' => 'sometimes|required|uuid|exists:users,id',
            'status' => ['sometimes', Rule::in(array_map(fn (PostStatus $status) => $status->value, PostStatus::cases()))],
            'approved_by' => 'nullable|uuid|exists:users,id',
            'approved_at' => 'nullable|date',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'uuid|exists:tags,id',
        ]);

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $post->update($data);

        if (is_array($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        return response()->json($post->fresh()->load(['author', 'approver', 'tags']));
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
