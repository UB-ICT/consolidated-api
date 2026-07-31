<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Http\Requests\PipelineStagesSyncRequest;
use Modules\RequisitionSystem\Http\Requests\PipelineStoreRequest;
use Modules\RequisitionSystem\Http\Requests\StageUsersSyncRequest;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\UserStage;

class PipelineController extends Controller
{
    public function index(): JsonResponse
    {
        $pipelines = Pipeline::with('stages')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $pipelines->map(fn (Pipeline $pipeline) => $this->formatPipeline($pipeline)),
        ]);
    }

    public function store(PipelineStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pipeline = DB::connection('porsql')->transaction(function () use ($validated) {
            $pipeline = Pipeline::create(['name' => $validated['name']]);

            if (!empty($validated['stages'])) {
                $this->syncPipelineStages($pipeline, $validated['stages']);
            }

            return $pipeline->fresh('stages');
        });

        return response()->json([
            'success' => true,
            'message' => 'Pipeline created successfully.',
            'data'    => $this->formatPipeline($pipeline),
        ], 201);
    }

    public function show(Pipeline $pipeline): JsonResponse
    {
        $pipeline->load('stages');

        return response()->json([
            'success' => true,
            'data'    => $this->formatPipeline($pipeline),
        ]);
    }

    public function update(PipelineStoreRequest $request, Pipeline $pipeline): JsonResponse
    {
        $validated = $request->validated();

        $pipeline = DB::connection('porsql')->transaction(function () use ($pipeline, $validated) {
            $pipeline->update(['name' => $validated['name']]);

            if (array_key_exists('stages', $validated)) {
                $this->syncPipelineStages($pipeline, $validated['stages'] ?? []);
            }

            return $pipeline->fresh('stages');
        });

        return response()->json([
            'success' => true,
            'message' => 'Pipeline updated successfully.',
            'data'    => $this->formatPipeline($pipeline),
        ]);
    }

    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $pipeline->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pipeline deleted successfully.',
        ]);
    }

    public function syncStages(
        PipelineStagesSyncRequest $request,
        Pipeline $pipeline
    ): JsonResponse {
        $pipeline = DB::connection('porsql')->transaction(function () use ($pipeline, $request) {
            $this->syncPipelineStages($pipeline, $request->validated('stages'));

            return $pipeline->fresh('stages');
        });

        return response()->json([
            'success' => true,
            'message' => 'Pipeline stages updated successfully.',
            'data'    => $this->formatPipeline($pipeline),
        ]);
    }

    public function syncStageUsers(
        StageUsersSyncRequest $request,
        Stage $stage
    ): JsonResponse {
        $userIds = collect($request->validated('user_ids'))->unique()->values();

        DB::connection('porsql')->transaction(function () use ($stage, $userIds) {
            UserStage::query()
                ->where('stage_id', $stage->id)
                ->whereNotIn('user_id', $userIds)
                ->delete();

            foreach ($userIds as $userId) {
                UserStage::firstOrCreate([
                    'stage_id' => $stage->id,
                    'user_id'  => $userId,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stage users updated successfully.',
            'data'    => [
                'stage_id' => $stage->id,
                'users'    => $this->usersForStageIds([$stage->id])[$stage->id] ?? [],
            ],
        ]);
    }

    /**
     * @param  array<int, array{id?: int|null, name: string, sequence: int, user_ids?: array<int, string>}>  $stages
     */
    private function syncPipelineStages(Pipeline $pipeline, array $stages): void
    {
        $syncData = [];

        foreach ($stages as $stageData) {
            if (!empty($stageData['id'])) {
                $stage = Stage::findOrFail($stageData['id']);
                if (!empty($stageData['name']) && $stage->name !== $stageData['name']) {
                    $stage->update(['name' => $stageData['name']]);
                }
            } else {
                $stage = Stage::firstOrCreate(['name' => $stageData['name']]);
            }

            $syncData[$stage->id] = [
                'sequence' => (int) $stageData['sequence'],
            ];

            if (array_key_exists('user_ids', $stageData)) {
                $userIds = collect($stageData['user_ids'] ?? [])->unique()->values();

                UserStage::query()
                    ->where('stage_id', $stage->id)
                    ->whereNotIn('user_id', $userIds)
                    ->delete();

                foreach ($userIds as $userId) {
                    UserStage::firstOrCreate([
                        'stage_id' => $stage->id,
                        'user_id'  => $userId,
                    ]);
                }
            }
        }

        $pipeline->stages()->sync($syncData);
    }

    private function formatPipeline(Pipeline $pipeline): array
    {
        $pipeline->loadMissing('stages');

        $stageIds = $pipeline->stages->pluck('id')->all();
        $usersByStage = $this->usersForStageIds($stageIds);

        $stages = $pipeline->stages
            ->sortBy(fn (Stage $stage) => (int) ($stage->pivot->sequence ?? 0))
            ->values()
            ->map(function (Stage $stage) use ($usersByStage) {
                return [
                    'id'       => $stage->id,
                    'name'     => $stage->name,
                    'sequence' => (int) ($stage->pivot->sequence ?? 0),
                    'users'    => $usersByStage[$stage->id] ?? [],
                    'pivot'    => [
                        'pipeline_id' => (int) ($stage->pivot->pipeline_id ?? 0),
                        'stage_id'    => $stage->id,
                        'sequence'    => (int) ($stage->pivot->sequence ?? 0),
                    ],
                ];
            })
            ->all();

        return [
            'id'     => $pipeline->id,
            'name'   => $pipeline->name,
            'stages' => $stages,
        ];
    }

    /**
     * @param  array<int, int>  $stageIds
     * @return array<int, array<int, array{id: string, name: string, email: string}>>
     */
    private function usersForStageIds(array $stageIds): array
    {
        if ($stageIds === []) {
            return [];
        }

        $assignments = UserStage::query()
            ->whereIn('stage_id', $stageIds)
            ->get(['stage_id', 'user_id']);

        $users = User::query()
            ->whereIn('id', $assignments->pluck('user_id')->unique())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $grouped = [];

        foreach ($assignments as $assignment) {
            $user = $users->get($assignment->user_id);

            if (!$user) {
                continue;
            }

            $grouped[$assignment->stage_id][] = [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ];
        }

        return $grouped;
    }
}
