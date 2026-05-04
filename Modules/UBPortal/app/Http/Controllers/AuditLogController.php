<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        // Use pagination because audit logs can grow into the thousands quickly
        $auditLogs = AuditLog::with(['actor', 'target', 'application'])
            ->latest() // Shortcut for orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($auditLogs);
    }

    /**
     * Usually, store() is called internally by the system, 
     * but having an API endpoint is fine for external app logging.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'actor_id'  => 'nullable|uuid|exists:users,id',
            'target_id' => 'nullable|uuid|exists:users,id',
            'app_id'    => 'nullable|uuid|exists:applications,id',
            'action'    => 'required|string|max:255',
            'severity'  => 'required|string|in:low,medium,high,critical',
        ]);

        $auditLog = AuditLog::create($data);

        return response()->json($auditLog, 201);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json($auditLog->load(['actor', 'target', 'application']));
    }

    /* NOTE: We purposely exclude update() and destroy() to maintain 
       the integrity of the audit trail. Data should be immutable.
    */
}
