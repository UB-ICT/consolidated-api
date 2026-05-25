<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\AuditLog;

/**
 * Handles read and create operations for audit logs.
 *
 * Audit entries are treated as immutable records to preserve
 * system activity history for troubleshooting and compliance.
 */
class AuditLogController extends Controller
{
    /**
     * Display paginated audit logs.
     *
     * Related actor, target, and application records are eager-loaded
     * to avoid additional queries in API consumers.
     */
    public function index(): JsonResponse
    {
        // Paginate for performance as audit data can grow very quickly.
        $auditLogs = AuditLog::with(['actor', 'target', 'application'])
            // Latest entries first.
            ->latest()
            ->paginate(50);

        return response()->json($auditLogs);
    }

    /**
     * Store a newly created audit log entry.
     *
     * This endpoint supports internal and external producers
     * that need to record audited actions.
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

        // Persist the immutable audit event.
        $auditLog = AuditLog::create($data);

        return response()->json($auditLog, 201);
    }

    /**
     * Display a specific audit log with related entities.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json($auditLog->load(['actor', 'target', 'application']));
    }

    /**
     * Update and delete operations are intentionally omitted.
     *
     * Audit logs are immutable to protect trail integrity.
     */
}
