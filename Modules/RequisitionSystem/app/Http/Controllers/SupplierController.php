<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Http\Requests\SupplierQuickStoreRequest;
use Modules\RequisitionSystem\Http\Requests\SupplierReviewRequest;
use Modules\RequisitionSystem\Http\Requests\SupplierStoreRequest;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Support\GuardsSupplierReview;
use Modules\RequisitionSystem\Support\SupplierStatus;

class SupplierController extends Controller
{
    use GuardsSupplierReview;

    public function index(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $query = Supplier::with(['status', 'address', 'bankAccount.bank', 'paymentTerm'])->orderBy('name');

        if ($request->boolean('exclude_deleted')) {
            $query->selectable();
        }

        return response()->json([
            'success' => true,
            'meta'    => [
                'can_review_suppliers' => $this->userCanReviewSuppliers($user),
            ],
            'data' => $query->get(),
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
            'TAX'            => $validated['TAX'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'status_id'      => Status::where('name', 'Pending')->value('id') ?? 2,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => $supplier->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ], 201);
    }

    public function store(SupplierStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $supplier = DB::transaction(function () use ($request, $validated) {
            $supplier = Supplier::create([
                'name'            => $validated['name'],
                'contact_person'  => $validated['contact_person'],
                'phone_number'    => $validated['phone_number'],
                'email'           => $validated['email'],
                'TAX'             => $validated['TAX'],
                'notes'           => $validated['notes'] ?? null,
                'status_id'       => $validated['status_id']
                    ?? Status::where('name', 'Pending')->value('id')
                    ?? 2,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'prepared_by'     => $validated['prepared_by'] ?? null,
            ]);

            $this->syncAddress($supplier, $request, $validated);
            $this->syncBankAccount($supplier, $request, $validated);

            return $supplier;
        });

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => $supplier->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ], 201);
    }

    /**
     * Create or update the supplier's address from the nested "address" payload.
     */
    private function syncAddress(Supplier $supplier, Request $request, array $validated): void
    {
        if (!$request->filled('address.street')) {
            return;
        }

        $supplier->address()->updateOrCreate(
            ['supplier_id' => $supplier->id],
            ['street' => $validated['address']['street']]
        );
    }

    /**
     * Create or update the supplier's bank account from the nested "bank" payload.
     */
    private function syncBankAccount(Supplier $supplier, Request $request, array $validated): void
    {
        if (!$request->filled('bank.bank_id')) {
            return;
        }

        $supplier->bankAccount()->updateOrCreate(
            ['supplier_id' => $supplier->id],
            [
                'bank_id'        => $validated['bank']['bank_id'],
                'account_number' => $validated['bank']['account_number'],
                'account_name'   => $supplier->name,
                'routing_number' => $validated['bank']['routing_number'] ?? null,
            ]
        );
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $supplier->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ]);
    }

    public function update(SupplierStoreRequest $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->isDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Deleted suppliers cannot be updated.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($request, $supplier, $validated) {
            $supplier->update([
                'name'            => $validated['name'],
                'contact_person'  => $validated['contact_person'],
                'phone_number'    => $validated['phone_number'],
                'email'           => $validated['email'],
                'TAX'             => $validated['TAX'],
                'notes'           => $validated['notes'] ?? null,
                'status_id'       => $validated['status_id'] ?? $supplier->status_id,
                'payment_term_id' => $validated['payment_term_id'] ?? $supplier->payment_term_id,
                'prepared_by'     => $validated['prepared_by'] ?? $supplier->prepared_by,
            ]);

            $this->syncAddress($supplier, $request, $validated);
            $this->syncBankAccount($supplier, $request, $validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data'    => $supplier->refresh()->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->isDeleted()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier is already deleted.',
                'data'    => $supplier->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
            ]);
        }

        $deletedStatusId = Status::where('name', SupplierStatus::DELETED)->value('id')
            ?? SupplierStatus::DELETED_ID;

        $supplier->update([
            'status_id' => $deletedStatusId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
            'data'    => $supplier->refresh()->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ]);
    }

    public function approve(
        SupplierReviewRequest $request,
        Supplier $supplier
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertUserCanReviewSuppliers($user);

        if ($supplier->isDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Deleted suppliers cannot be approved.',
            ], 422);
        }

        $approvedStatusId = Status::where('name', 'Approved')->value('id') ?? 3;

        $supplier->update([
            'status_id'           => $approvedStatusId,
            'approved_by_user_id' => $user?->id,
            'notes'               => $request->validated('comments') ?? $supplier->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier approved successfully.',
            'data'    => $supplier->refresh()->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ]);
    }

    public function reject(
        SupplierReviewRequest $request,
        Supplier $supplier
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertUserCanReviewSuppliers($user);

        if ($supplier->isDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Deleted suppliers cannot be rejected.',
            ], 422);
        }

        $rejectedStatusId = Status::where('name', 'Rejected')->value('id') ?? 4;

        $supplier->update([
            'status_id'           => $rejectedStatusId,
            'approved_by_user_id' => $user?->id,
            'notes'               => $request->validated('comments') ?? $supplier->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier rejected successfully.',
            'data'    => $supplier->refresh()->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
        ]);
    }

    public function activate(Supplier $supplier): JsonResponse
    {
        if (!$supplier->isDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Only deleted suppliers can be set to active.',
            ], 422);
        }

        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $approvedStatusId = Status::where('name', SupplierStatus::APPROVED)->value('id')
            ?? SupplierStatus::APPROVED_ID;

        $supplier->update([
            'status_id'           => $approvedStatusId,
            'approved_by_user_id' => $user?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier set to active successfully.',
            'data'    => $supplier->refresh()->load(['status', 'address', 'bankAccount.bank', 'paymentTerm']),
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
            'deleted'      => $counts->get(
                Status::where('name', SupplierStatus::DELETED)->value('id') ?? 7,
                0
            ),
        ];

        return response()->json([
            'success' => true,
            'data'    => $statusMap,
        ]);
    }
}
