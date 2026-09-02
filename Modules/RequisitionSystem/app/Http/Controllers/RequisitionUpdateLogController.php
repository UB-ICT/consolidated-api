<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Support\GuardsRequisitionVisibility;

class RequisitionUpdateLogController extends Controller
{
    use GuardsRequisitionVisibility;

    public function index(Requisition $requisition): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertUserCanViewRequisition($requisition->loadMissing('status'), $user);

        $logs = $requisition->updateLogs()
            ->latest()
            ->get();

        $users = User::query()
            ->whereIn('id', $logs->pluck('user_id')->unique())
            ->get()
            ->keyBy('id');

        $data = $logs->map(function ($log) use ($users) {
            $actor = $users->get($log->user_id);

            return [
                'id'               => $log->id,
                'requisition_id'   => $log->requisition_id,
                'user_id'          => $log->user_id,
                'event'            => $log->event,
                'submitted'        => $log->submitted,
                'summary'          => $log->summary,
                'changes'          => $log->changes,
                'activity_comment' => $log->activity_comment,
                'created_at'       => $log->created_at,
                'updated_at'       => $log->updated_at,
                'user'             => $actor ? [
                    'id'    => $actor->id,
                    'name'  => $actor->name,
                    'email' => $actor->email,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }
}
