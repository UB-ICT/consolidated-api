<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\RequisitionSystem\Http\Requests\TagStoreRequest;
use Modules\RequisitionSystem\Models\Tag;
use Modules\RequisitionSystem\Support\GuardsTagAccess;

class TagController extends Controller
{
    use GuardsTagAccess;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'cost_center_id' => 'required|integer|exists:porsql.cost_centers,id',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Tag::query()
            ->where('cost_center_id', (int) $request->input('cost_center_id'))
            ->orderBy('name');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(['id', 'name', 'cost_center_id']),
        ]);
    }

    public function store(TagStoreRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $validated = $request->validated();

        $this->assertCanManageTagsForCostCenter($user, (int) $validated['cost_center_id']);

        $existing = Tag::query()
            ->where('cost_center_id', $validated['cost_center_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Tag already exists for this cost center.',
                'data' => $existing->only(['id', 'name', 'cost_center_id']),
            ]);
        }

        $tag = Tag::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully.',
            'data' => $tag->only(['id', 'name', 'cost_center_id']),
        ], 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $tag->only(['id', 'name', 'cost_center_id']),
        ]);
    }

    public function update(TagStoreRequest $request, Tag $tag): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanManageTag($user, $tag);

        $validated = $request->validated();
        unset($validated['cost_center_id']);

        $duplicate = Tag::query()
            ->where('cost_center_id', $tag->cost_center_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->where('id', '!=', $tag->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'A tag with that name already exists for this cost center.',
            ], 422);
        }

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully.',
            'data' => $tag->fresh()->only(['id', 'name', 'cost_center_id']),
        ]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanManageTag($user, $tag);

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully.',
        ]);
    }
}
