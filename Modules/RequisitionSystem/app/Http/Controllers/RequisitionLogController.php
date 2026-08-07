<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Http\Requests\RequisitionLogStoreRequest;
use Modules\RequisitionSystem\Models\Logs;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Support\GuardsRequisitionVisibility;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequisitionLogController extends Controller
{
    use GuardsRequisitionVisibility;

    public function __construct(
        private readonly RequisitionLogService $logService
    ) {}

    public function index(Requisition $requisition): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertUserCanViewRequisition($requisition->loadMissing('status'), $user);

        $logs = $requisition->logs()
            ->latest()
            ->get();

        $users = User::query()
            ->whereIn('id', $logs->pluck('user_id')->unique())
            ->get()
            ->keyBy('id');

        $data = $logs->map(function ($log) use ($users) {
            return $this->formatLog($log, $users->get($log->user_id));
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

        $this->assertUserCanViewRequisition($requisition->loadMissing('status'), $user);

        $fileName = null;
        $filePath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $storedName = Str::uuid() . '.pdf';
            $filePath = $file->storeAs('uploads/log-comments', $storedName, 'local');
            $fileName = $file->getClientOriginalName();
        }

        $log = $this->logService->recordComment(
            $requisition,
            $user,
            $request->validated('comments'),
            $fileName,
            $filePath
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment logged successfully.',
            'data'    => $this->formatLog($log, $user),
        ], 201);
    }

    public function showAttachment(Logs $log): StreamedResponse|JsonResponse
    {
        return $this->streamLogAttachment($log, inline: true);
    }

    public function downloadAttachment(Logs $log): StreamedResponse|JsonResponse
    {
        return $this->streamLogAttachment($log, inline: false);
    }

    private function streamLogAttachment(
        Logs $log,
        bool $inline
    ): StreamedResponse|JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $requisition = $log->requisition()->first();

        if (!$requisition) {
            return response()->json([
                'success' => false,
                'message' => 'Requisition not found.',
            ], 404);
        }

        $this->assertUserCanViewRequisition($requisition->loadMissing('status'), $user);

        if (!$log->hasAttachment() || !Storage::disk('local')->exists($log->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found.',
            ], 404);
        }

        $disposition = $inline ? 'inline' : 'attachment';

        return Storage::disk('local')->response(
            $log->file_path,
            $log->file_name,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$log->file_name}\"",
            ]
        );
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
            'file_name'      => $log->file_name,
            'has_attachment' => $log->hasAttachment(),
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
