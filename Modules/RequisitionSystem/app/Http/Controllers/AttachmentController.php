<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\RequisitionSystem\Models\Attachment;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileUploadController extends Controller
{
    /**
     * Upload photos or documents associated with a requisition or supplier.
     */
    public function uploadRequisitionSystemPhoto(Request $request): JsonResponse
    {
        try {
            // Validate incoming payload
            $request->validate([
                'file'           => 'required', // Can be an array or a single file
                'requisition_id' => 'nullable|integer',
                'supplier_id'    => 'nullable|integer',
                'uploaded_by'    => 'required|integer', // e.g., auth()->id() passed from front-end
            ]);

            if (!$request->hasFile('file')) {
                throw new \Exception('No files were uploaded.');
            }

            $files = $request->file('file');
            if (!is_array($files)) {
                $files = [$files];
            }

            $result = [];

            foreach ($files as $file) {
                if ($file->isValid()) {
                    // Generate a safe unique name
                    $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();

                    // Laravel Store: saves to storage/app/private/uploads/photos (secured)
                    $storedPath = $file->storeAs('uploads/photos', $fileName, 'local');

                    // Save the metadata record into the Database via Attachment Model
                    $attachment = Attachment::create([
                        'file_name'      => $file->getClientOriginalName(),
                        'file_path'      => $storedPath,
                        'uploaded_by'    => $request->input('uploaded_by'),
                        'requisition_id' => $request->input('requisition_id'),
                        'supplier_id'    => $request->input('supplier_id'),
                    ]);

                    $result[] = [
                        "id"             => $attachment->id,
                        "original_name"  => $attachment->file_name,
                        "generated_name" => $fileName,
                        "file_path"      => $attachment->file_path,
                    ];

                    Log::info("Attachment saved to DB ID: {$attachment->id} - {$attachment->file_name}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded and attached successfully.',
                'data'    => $result
            ], 201);
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    /**
     * Store base64 digital signatures directly linked to an Attachment record.
     */
    public function uploadSignatureCanvas(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'signature'      => 'required|string',
                'requisition_id' => 'nullable|integer',
                'uploaded_by'    => 'required|integer',
            ]);

            $signatureData = $request->signature;
            $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
            $signatureData = str_replace(' ', '+', $signatureData);
            $decodedSignature = base64_decode($signatureData, true);

            if ($decodedSignature === false) {
                throw new \Exception("Invalid signature format");
            }

            // Define folder layout safely within Laravel framework storage
            $fileName = Str::uuid() . '.png';
            $relativePath = 'uploads/signatures/' . $fileName;

            // Save the raw file directly via Storage system
            Storage::disk('local')->put($relativePath, $decodedSignature);

            // Persist the signature as an item in your attachment registry table
            $attachment = Attachment::create([
                'file_name'      => 'signature_' . time() . '.png',
                'file_path'      => $relativePath,
                'uploaded_by'    => $request->input('uploaded_by'),
                'requisition_id' => $request->input('requisition_id'),
                'supplier_id'    => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Signature saved and registered successfully',
                'data'    => [
                    'id'        => $attachment->id,
                    'file_path' => $attachment->file_path
                ]
            ], 201);
        } catch (\Throwable $e) {
            Log::error("Signature storage failure: ", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download secure files via file path strings.
     */
    public function downloadRequisitionSystemFile(Request $request, string $fileType, string $fileName)
    {
        try {
            // Reconstructs target string matching your store layouts safely
            $filePath = storage_path('app/uploads/' . $fileType . '/' . $fileName);

            if (file_exists($filePath)) {
                return response()->download($filePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Requested file asset could not be located.',
                'data'    => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}
