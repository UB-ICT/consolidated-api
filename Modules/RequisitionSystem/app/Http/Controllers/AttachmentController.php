<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\RequisitionSystem\Models\Attachment;
use Modules\RequisitionSystem\Models\Requisition;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function index(Requisition $requisition): JsonResponse
    {
        $attachments = $requisition->attachments()
            ->with('supplier.status')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attachments,
        ]);
    }

    public function store(Request $request, Requisition $requisition): JsonResponse
    {
        $validated = $request->validate([
            'file'        => 'required|file|mimes:pdf|max:10240',
            'supplier_id' => 'required|integer|exists:porsql.suppliers,id',
        ]);

        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $file = $validated['file'];
        $fileName = Str::uuid() . '.pdf';
        $storedPath = $file->storeAs('uploads/quotes', $fileName, 'local');

        $attachment = Attachment::create([
            'file_name'      => $file->getClientOriginalName(),
            'file_path'      => $storedPath,
            'uploaded_by'    => $user->id,
            'requisition_id' => $requisition->id,
            'supplier_id'    => $validated['supplier_id'],
        ]);

        $requisition->suppliers()->syncWithoutDetaching([
            $validated['supplier_id'] => [
                'is_recommended' => false,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quote uploaded successfully.',
            'data' => $attachment->load('supplier.status'),
        ], 201);
    }

    public function show(Attachment $attachment): StreamedResponse|JsonResponse
    {
        return $this->streamFile($attachment, inline: true);
    }

    public function download(Attachment $attachment): StreamedResponse|JsonResponse
    {
        return $this->streamFile($attachment, inline: false);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        DB::connection('porsql')->transaction(function () use ($attachment) {
            $requisitionId = $attachment->requisition_id;
            $supplierId = $attachment->supplier_id;

            if (Storage::disk('local')->exists($attachment->file_path)) {
                Storage::disk('local')->delete($attachment->file_path);
            }

            $attachment->delete();

            $hasOtherAttachments = Attachment::query()
                ->where('requisition_id', $requisitionId)
                ->where('supplier_id', $supplierId)
                ->exists();

            if (!$hasOtherAttachments) {
                Requisition::find($requisitionId)?->suppliers()->detach($supplierId);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Quote removed successfully.',
        ]);
    }

    private function streamFile(
        Attachment $attachment,
        bool $inline
    ): StreamedResponse|JsonResponse {
        if (!Storage::disk('local')->exists($attachment->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        $disposition = $inline ? 'inline' : 'attachment';

        return Storage::disk('local')->response(
            $attachment->file_path,
            $attachment->file_name,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$attachment->file_name}\"",
            ]
        );
    }
}
