<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;
use exception;


/*

This is one controller that handles both upload and download functionality.

The uploadMeetingMinutes() function, allows you to upload files. This is 
stored under storage > uploads > meetings 

The uploadEventPhoto() function, allows you to upload files. This is 
stored under storage > uploads > photos

The downloadFile() function,  allows you to download files but requires two 
parameters, pass the file type and the file name. The file type is 
photos or meeting.

Author: SW

*/

class FileUploadsController extends Controller
{
    //New upload Meeting Minutes function
    public function uploadMeetingMinutes(Request $request, string $reportId, int $meetingId)
    {
        try {
            $result = [];
            // Validate required parameters
            if (!$reportId) {
                throw new \Exception('Report ID required');
            }

            if ($meetingId === null) {
                throw new \Exception('Event ID is required');
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
                    $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('uploads/meetings', $fileName);

                    // Generate a public URL for the file
                    $fileUrl = 'app/private/uploads/meetings/' . $fileName;

                    $result[] = [
                        "generated_name" => $fileName,
                        "original_name" => $file->getClientOriginalName(),
                        "url" => $fileUrl
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function uploadEventPhoto(Request $request, string $reportId, int $eventId)
    {
        try {
            $result = [];
            // Validate required parameters
            if (!$reportId) {
                throw new \Exception('Report ID required');
            }

            if ($eventId === null) {
                throw new \Exception('Event ID is required');
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
                    $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('uploads/photos', $fileName);

                    // Generate a public URL for the file
                    $fileUrl = 'app/private/uploads/photos/' . $fileName;

                    $result[] = [
                        "generated_name" => $fileName,
                        "original_name" => $file->getClientOriginalName(),
                        "url" => $fileUrl
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    public function downloadFile(string $fileType, string $fileName)
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
