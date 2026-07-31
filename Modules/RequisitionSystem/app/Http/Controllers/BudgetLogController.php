<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Http\Requests\BudgetLogStoreRequest;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Services\BudgetLogService;
use Modules\RequisitionSystem\Support\GuardsBudgetAccess;

class BudgetLogController extends Controller
{
    use GuardsBudgetAccess;

    public function __construct(
        private readonly BudgetLogService $logService
    ) {}

    public function index(Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanViewBudget($budget, $user);

        $logs = $budget->logs()->latest()->get();

        $users = User::query()
            ->whereIn('id', $logs->pluck('user_id')->unique())
            ->get()
            ->keyBy('id');

        $data = $logs->map(function ($log) use ($users) {
            $logUser = $users->get($log->user_id);

            return [
                'id'         => $log->id,
                'budget_id'  => $log->budget_id,
                'user_id'    => $log->user_id,
                'action'     => $log->action,
                'summary'    => $log->summary,
                'comments'   => $log->comments,
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
                'user'       => $logUser ? [
                    'id'    => $logUser->id,
                    'name'  => $logUser->name,
                    'email' => $logUser->email,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    public function store(BudgetLogStoreRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanViewBudget($budget, $user);

        $log = $this->logService->recordComment(
            $budget,
            $user,
            $request->validated('comments')
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment logged successfully.',
            'data'    => $log,
        ], 201);
    }
}
