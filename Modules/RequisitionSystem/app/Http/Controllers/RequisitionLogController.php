<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Http\Requests\RequisitionLogStoreRequest;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

class RequisitionLogController extends Controller
{
    public function __construct(
        private readonly RequisitionLogService $logService
    ) {}

    public function index(Requisition $requisition): JsonResponse
    {
        $logs = $requisition->logs()
            ->latest()
            ->get();

        $users = User::query()
            ->whereIn('id', $logs->pluck('user_id')->unique())
            ->get()
            ->keyBy('id');

        $data = $logs->map(function ($log) use ($users) {
            $user = $users->get($log->user_id);

            return [
                'id'              => $log->id,
                'requisition_id'  => $log->requisition_id,
                'user_id'         => $log->user_id,
                'action'          => $log->action,
                'summary'         => $log->summary,
                'comments'        => $log->comments,
                'created_at'      => $log->created_at,
                'updated_at'      => $log->updated_at,
                'user'            => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    public function store(
        RequisitionLogStoreRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $log = $this->logService->recordComment(
            $requisition,
            $user,
            $request->validated('comments')
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment logged successfully.',
            'data'    => $this->formatLog($log, $user),
        ], 201);
    }

    private function formatLog($log, ?User $user = null): array
    {
        return [
            'id'             => $log->id,
            'requisition_id' => $log->requisition_id,
            'user_id'        => $log->user_id,
            'action'         => $log->action,
            'summary'        => $log->summary,
            'comments'       => $log->comments,
            'created_at'     => $log->created_at,
            'updated_at'     => $log->updated_at,
            'user'           => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ] : null,
        ];
    }
}
