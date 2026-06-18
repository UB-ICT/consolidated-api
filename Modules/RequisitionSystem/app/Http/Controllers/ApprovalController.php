<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Http\Requests\ApprovalStoreRequest;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly RequisitionLogService $logService
    ) {}

    public function index(): JsonResponse
    {
        $approvals = Approval::all();

        return response()->json([
            'success' => true,
            'data'    => $approvals
        ], 200);
    }

    public function store(ApprovalStoreRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['signed_at'] = now();

        $approval = Approval::create($data);
        $requisition = Requisition::findOrFail($data['requisition_id']);
        $stageName = isset($data['stage_id'])
            ? Stage::find($data['stage_id'])?->name
            : null;

        if (in_array($approval->status, ['approved', 'rejected'], true)) {
            $this->logService->recordApprovalDecision(
                $requisition,
                $user,
                $approval->status === 'approved'
                    ? RequisitionLogAction::APPROVED
                    : RequisitionLogAction::REJECTED,
                $approval->comments,
                $stageName
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Approval recorded successfully.',
            'data'    => $approval
        ], 201);
    }

    public function show(Approval $approval): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $approval
        ], 200);
    }

    public function update(ApprovalStoreRequest $request, Approval $approval): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $previousStatus = $approval->status;
        $approval->update($request->validated());

        if (
            $previousStatus !== $approval->status
            && in_array($approval->status, ['approved', 'rejected'], true)
        ) {
            $requisition = Requisition::findOrFail($approval->requisition_id);
            $stageName = $approval->stage_id
                ? Stage::find($approval->stage_id)?->name
                : null;

            $this->logService->recordApprovalDecision(
                $requisition,
                $user,
                $approval->status === 'approved'
                    ? RequisitionLogAction::APPROVED
                    : RequisitionLogAction::REJECTED,
                $approval->comments,
                $stageName
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Approval updated successfully.',
            'data'    => $approval
        ], 200);
    }

    public function destroy(Approval $approval): JsonResponse
    {
        $approval->delete();

        return response()->json([
            'success' => true,
            'message' => 'Approval deleted successfully.'
        ], 200);
    }
}
