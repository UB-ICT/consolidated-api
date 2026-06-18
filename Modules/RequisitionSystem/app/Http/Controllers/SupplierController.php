<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Http\Requests\SupplierQuickStoreRequest;
use Modules\RequisitionSystem\Http\Requests\SupplierReviewRequest;
use Modules\RequisitionSystem\Http\Requests\SupplierStoreRequest;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Support\GuardsSupplierReview;

class SupplierController extends Controller
{
    use GuardsSupplierReview;

    public function index(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'meta'    => [
                'can_review_suppliers' => $this->userCanReviewSuppliers($user),
            ],
            'data' => Supplier::with('status')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function quickStore(SupplierQuickStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $supplier = Supplier::create([
            'name'           => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? '',
            'phone_number'   => $validated['phone_number'] ?? '',
            'email'          => $validated['email'] ?? sprintf(
                'supplier-%s@pending.local',
                now()->format('YmdHis')
            ),
            'TIN'            => $validated['TIN'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'status_id'      => Status::where('name', 'Pending')->value('id') ?? 2,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => $supplier->load('status'),
        ], 201);
    }

    public function store(SupplierStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $supplier = Supplier::create([
            'name'           => $validated['name'],
            'contact_person' => $validated['contact_person'],
            'phone_number'   => $validated['phone_number'],
            'email'          => $validated['email'],
            'TIN'            => $validated['TIN'],
            'notes'          => $validated['notes'] ?? null,
            'status_id'      => $validated['status_id']
                ?? Status::where('name', 'Pending')->value('id')
                ?? 2,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => $supplier->load('status'),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $supplier->load('status'),
        ]);
    }

    public function update(SupplierStoreRequest $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validated();

        $supplier->update([
            'name'           => $validated['name'],
            'contact_person' => $validated['contact_person'],
            'phone_number'   => $validated['phone_number'],
            'email'          => $validated['email'],
            'TIN'            => $validated['TIN'],
            'notes'          => $validated['notes'] ?? null,
            'status_id'      => $validated['status_id'] ?? $supplier->status_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data'    => $supplier->refresh()->load('status'),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }

    public function approve(
        SupplierReviewRequest $request,
        Supplier $supplier
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertUserCanReviewSuppliers($user);

        $approvedStatusId = Status::where('name', 'Approved')->value('id') ?? 3;

        $supplier->update([
            'status_id'           => $approvedStatusId,
            'approved_by_user_id' => $user?->id,
            'notes'               => $request->validated('comments') ?? $supplier->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier approved successfully.',
            'data'    => $supplier->refresh()->load('status'),
        ]);
    }

    public function reject(
        SupplierReviewRequest $request,
        Supplier $supplier
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertUserCanReviewSuppliers($user);

        $rejectedStatusId = Status::where('name', 'Rejected')->value('id') ?? 4;

        $supplier->update([
            'status_id'           => $rejectedStatusId,
            'approved_by_user_id' => $user?->id,
            'notes'               => $request->validated('comments') ?? $supplier->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier rejected successfully.',
            'data'    => $supplier->refresh()->load('status'),
        ]);
    }

    public function getStatusCounts(): JsonResponse
    {
        $counts = Supplier::select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->get()
            ->pluck('total', 'status_id');

        $statusMap = [
            'draft'        => $counts->get(1, 0),
            'pending'      => $counts->get(2, 0),
            'approved'     => $counts->get(3, 0),
            'rejected'     => $counts->get(4, 0),
            'under_review' => $counts->get(5, 0),
        ];

        return response()->json([
            'success' => true,
            'data'    => $statusMap,
        ]);
    }
}
