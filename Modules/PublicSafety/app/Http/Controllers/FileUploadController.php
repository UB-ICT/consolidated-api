<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;



class FileUploadController extends Controller
{
    public function uploadPublicSafetyPhoto(Request $request, string $reportId)
    {
        try {
            $result = [];
            // Validate required parameters
            if (!$reportId) {
                throw new \Exception('Incident Report ID required');
            }


            if (!$request->hasFile('file')) {
                throw new \Exception('No files were uploaded');
            }

            $files = $request->file('file');
            if (!is_array($files)) {
                $files = [$files];
            }


            foreach ($files as $file) {
                if ($file->isValid()) {
                    $fileName = Str::random(75) . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('uploads/photos', $fileName);

                    // Generate a public URL for the file
                    $fileUrl = 'app/private/uploads/photos/' . $fileName;


                    $result[] = [
                        "generated_name" => $fileName,
                        "original_name" => $file->getClientOriginalName(),
                        "url" => $fileUrl,
                        "displayURL" => $fileUrl
                    ];
                    Log::info($fileName);
                    Log::info($file->getClientOriginalName());
                    Log::info($fileUrl);

                }

            }
            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('File upload errorssds: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function uploadSignatureCanvas(Request $request, string $reportId)
    {
        try {
            if (!$reportId) {
                throw new \Exception("Incident Report ID required");
            }

            if (!$request->signature) {
                throw new \Exception("Signature data missing");
            }

            $signatureData = $request->signature;

            // Remove base64 prefix: "data:image/png;base64,"
            $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
            $signatureData = str_replace(' ', '+', $signatureData);

            $fileName = Str::random(75) . '.png';
            $filePath = storage_path("app/private/uploads/signatures/{$fileName}");

            // Save decoded file
            file_put_contents($filePath, base64_decode($signatureData));

            $url = "app/private/uploads/signatures/{$fileName}";

            return response()->json([
                'success' => true,
                'message' => 'Signature saved successfully',
                'data' => [
                    'generated_name' => $fileName,
                    'url' => $url,
                    'displayURL' => $url
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Signature canvas upload error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    public function downloadPublicSafetyFile(Request $request, string $fileType, string $fileName)
    {
        try {
            $filePath = storage_path('app/private/uploads/' . $fileType . '/' . $fileName);

            if (file_exists($filePath)) {
                return response()->download($filePath);
            } else {
                // abort(404, 'File not found');
                $response = [
                    'success' => false,
                    'message' => 'File not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }




}
