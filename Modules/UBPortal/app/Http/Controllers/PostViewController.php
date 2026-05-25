<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UBPortal\Models\PostView;

class PostViewController extends Controller
{
    public function index(): JsonResponse
    {
        $views = PostView::with(['post', 'user'])
            ->latest('viewed_at')
            ->paginate(50);

        return response()->json($views);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => 'required|uuid|exists:posts,id',
            'user_id' => 'nullable|uuid|exists:users,id',
            'ip_address' => 'nullable|ip',
            'viewed_at' => 'nullable|date',
        ]);

        if (!array_key_exists('ip_address', $data) || empty($data['ip_address'])) {
            $data['ip_address'] = $request->ip();
        }

        $view = PostView::create($data);

        return response()->json($view->load(['post', 'user']), 201);
    }

    public function show(PostView $postView): JsonResponse
    {
        return response()->json($postView->load(['post', 'user']));
    }

    public function update(Request $request, PostView $postView): JsonResponse
    {
        $data = $request->validate([
            'post_id' => 'sometimes|required|uuid|exists:posts,id',
            'user_id' => 'nullable|uuid|exists:users,id',
            'ip_address' => 'nullable|ip',
            'viewed_at' => 'nullable|date',
        ]);

        $postView->update($data);

        return response()->json($postView->fresh()->load(['post', 'user']));
    }

    public function destroy(PostView $postView): JsonResponse
    {
        $postView->delete();

        return response()->json(['message' => 'Post view deleted successfully']);
    }
}
