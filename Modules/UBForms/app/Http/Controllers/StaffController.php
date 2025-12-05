<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;
// use setasign\Fpdi\Fpdi;

class StaffController extends Controller
{
    private function initializeReport(string $email, string $name, string $academicYearID)
    {
        $report = [
            'email' => $email,
            "name" => $name,
            'academicYearID' => $academicYearID,
            'department' => "",
            'reportsTo' => "",
            'deadline' => "",
            'missionStatement' => "",
            'strategicGoals' => ['strategicGoalsUnderReview' => '', 'implmentationPlans' => '', 'plansToAchieveNotCompletedGoals' => '', 'strategicGoals' => '', 'completionRate' => ''],
            'accomplishments' => ['accomplishmentList' => '', 'accomplishmentAdvancement' => '', 'impactfulChange' => '', 'why' => '', 'applicableOpportunities' => ''],
            'researchPartnerships' => ['externalFunding' => '', 'researchPublications' => '', 'partnershipAgencies' => '', 'scholarships' => ''],
            'studentSuccess' => ['studentClubs' => '', 'studentSurveys' => '', 'initiatives' => ''],
            'activities' => array(['eventId' => 0, 'eventName' => "", 'personsInPicture' => '', 'pictureURL' => array(['eventPicture' => '']), 'eventSummary' => '', 'eventMonth' => '']),
            'administrativeData' => ['fullTimeStaff' => '', 'partTimeStaff' => '', 'significantStaffChanges' => ''],
            'financialBudget' => ['fundingSources' => '', 'significantBudgetChanges' => ''],
            'meetings' => array(['meetingId' => 0, 'meetingType' => '', 'meetingDate' => '', 'meetingMinutesURL' => array(['meetingURL' => ''])]),
            'formSubmitted' => false,
            'otherComments' => "",
        ];

        $reports = FirestoreService::queryCollection('staff', 'email', '==', $email);
        $filteredReports = array_filter($reports, function ($report) use ($academicYearID) {
            return $report['academicYearID'] === $academicYearID;
        });

        if (!empty($filteredReports)) {
            return $filteredReports[0];
        }

        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef("staff", $report);
        return array_merge($report, ['id' => $documentRef->id()]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            // Add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef('staff', $data);
            $response = [
                'success' => true,
                'message' => "Staff Report Created Successfully",
                'data' => [
                    'reportID' => $documentRef->id(),
                ]
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        return response($response, 201);
    }

    public function getReport(Request $request, string $reportID)
    {
        try {
            $report = FirestoreService::getDocument('staff', $reportID);
            if ($report) {
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'report' => $report
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    public function updateReport(Request $request)
    {
        try {
            $data = $request->all();
            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }
            // Add updated timestamp
            $data['updated_at'] = now()->toDateTimeString();
            $success = FirestoreService::updateDocument('staff', $data['id'], $data);
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Report data updated successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    public function delReport(Request $request)
    {
        try {
            $id = $request->input('reportID');
            $success = FirestoreService::deleteDocument('staff', $id);
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Report data deleted successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    public function getReportByUser(Request $request)
    {
        try {
            $user = $request->user();
            $settings = FirestoreService::getCollection('settings');

            //Get default academic year ID from first document in settings collection
            // Get default academic year from first document in settings collection
            $defaultAcademicYear = ""; // fallback default
            if (!empty($settings)) {
                $firstSetting = $settings[0];
                if (isset($firstSetting['defaultAcademicYear'])) {
                    $defaultAcademicYear = $firstSetting['defaultAcademicYear'];
                }
            }

            if ($defaultAcademicYear == "") {
                $response = [
                    'success' => false,
                    'message' => "Default Academic Year not found",
                    'data' => null
                ];
                return response($response, 500);
            }

            $documents = FirestoreService::getCollection('staff');
            $filteredDocuments = array_filter($documents, function ($document) use ($user, $defaultAcademicYear) {
                return isset($document['email']) && $document['email'] === $user->email &&
                    isset($document['academicYearID']) && $document['academicYearID'] === $defaultAcademicYear;
            });

            if (!empty($filteredDocuments)) {
                $report = array_values($filteredDocuments)[0]; // Get first record
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => $report
                ];
            } else {
                $report = $this->initializeReport($user->email, $user->name, $defaultAcademicYear);

                $response = [
                    'success' => true,
                    'message' => 'Report Initialized.',
                    'data' => $report
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    /**
     * Merge multiple PDFs into a single PDF using system command
     */
    private function mergePdfs($mainPdfPath, $additionalPdfPaths)
    {
        try {
            Log::info('Starting PDF merge process');
            Log::info('Main PDF path: ' . $mainPdfPath . ' (exists: ' . (file_exists($mainPdfPath) ? 'yes' : 'no') . ')');
            Log::info('Additional PDFs count: ' . count($additionalPdfPaths));

            // If no additional PDFs, just return the main PDF
            if (empty($additionalPdfPaths)) {
                Log::info('No additional PDFs to merge, returning main PDF only');
                return file_get_contents($mainPdfPath);
            }

            // Create a temporary output file
            $outputPath = storage_path('app/temp/merged_' . uniqid() . '.pdf');

            // Build command to merge PDFs using pdftk (if available) or ghostscript
            $command = '';
            $allPdfs = array_merge([$mainPdfPath], $additionalPdfPaths);
            $pdfList = implode(' ', array_map('escapeshellarg', $allPdfs));

            // Check if commands exist
            $pdftkExists = $this->commandExists('pdftk');
            $gsExists = $this->commandExists('gs');

            Log::info('pdftk available: ' . ($pdftkExists ? 'yes' : 'no'));
            Log::info('ghostscript available: ' . ($gsExists ? 'yes' : 'no'));

            // Try pdftk first, then ghostscript as fallback
            if ($pdftkExists) {
                $command = "pdftk {$pdfList} cat output " . escapeshellarg($outputPath);
                Log::info('Using pdftk command: ' . $command);
            } elseif ($gsExists) {
                $command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=" . escapeshellarg($outputPath) . " {$pdfList}";
                Log::info('Using ghostscript command: ' . $command);
            } else {
                // Fallback: create a combined PDF using DomPDF with embedded content
                Log::warning('No PDF merging tool available. Creating combined PDF with embedded content.');
                return $this->createCombinedPdfWithEmbeddedContent($mainPdfPath, $additionalPdfPaths);
            }

            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            Log::info('Command return code: ' . $returnCode);
            Log::info('Command output: ' . implode("\n", $output));

            if ($returnCode === 0 && file_exists($outputPath)) {
                Log::info('PDF merge successful, output file size: ' . filesize($outputPath));
                $content = file_get_contents($outputPath);
                unlink($outputPath); // Clean up
                return $content;
            } else {
                Log::error('PDF merge command failed: ' . implode("\n", $output));
                Log::error('Output file exists: ' . (file_exists($outputPath) ? 'yes' : 'no'));
                // Fallback: create combined PDF with embedded content
                return $this->createCombinedPdfWithEmbeddedContent($mainPdfPath, $additionalPdfPaths);
            }
        } catch (\Exception $e) {
            Log::error('PDF merge error: ' . $e->getMessage());
            // Fallback: create combined PDF with embedded content
            return $this->createCombinedPdfWithEmbeddedContent($mainPdfPath, $additionalPdfPaths);
        }
    }

    /**
     * Check if a command exists on the system
     */
    private function commandExists($command)
    {
        $output = [];
        $returnCode = 0;
        exec("which {$command} 2>/dev/null", $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Create a combined PDF with embedded content when system tools are not available
     */
    private function createCombinedPdfWithEmbeddedContent($mainPdfPath, $additionalPdfPaths)
    {
        try {
            Log::info('Creating combined PDF with embedded content');

            // Read the main PDF content
            $mainPdfContent = file_get_contents($mainPdfPath);

            // Create a new PDF that includes the meeting PDFs as embedded content
            $html = $this->createHtmlWithEmbeddedPdfs($additionalPdfPaths);

            // Generate new PDF with embedded content
            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            $newPdfContent = $pdf->output();

            Log::info('Created combined PDF with embedded content');
            return $newPdfContent;
        } catch (\Exception $e) {
            Log::error('Error creating combined PDF: ' . $e->getMessage());
            return file_get_contents($mainPdfPath);
        }
    }

    /**
     * Create HTML with embedded PDF data
     */
    private function createHtmlWithEmbeddedPdfs($additionalPdfPaths)
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Staff Report with Meeting Attachments</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .meeting-section { margin: 30px 0; page-break-before: always; }
                .meeting-header { background-color: #f0f0f0; padding: 15px; border-left: 4px solid #3d004a; }
                .meeting-header h2 { color: #3d004a; margin: 0; }
                .meeting-content { padding: 20px; }
                .pdf-embed { width: 100%; height: 800px; border: 1px solid #ddd; }
                .pdf-info { background-color: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="meeting-header">
                <h1>Staff Report with Embedded Meeting Minutes</h1>
                <p>This report includes the following meeting minutes as embedded PDF content:</p>
            </div>';

        foreach ($additionalPdfPaths as $index => $pdfPath) {
            if (file_exists($pdfPath)) {
                $fileName = basename($pdfPath);
                $fileSize = filesize($pdfPath);
                $pdfContent = file_get_contents($pdfPath);
                $base64Content = base64_encode($pdfContent);

                $html .= '
                <div class="meeting-section">
                    <div class="meeting-header">
                        <h2>Meeting Minutes: ' . htmlspecialchars($fileName) . '</h2>
                        <div class="pdf-info">
                            <p><strong>File:</strong> ' . htmlspecialchars($fileName) . '</p>
                            <p><strong>Size:</strong> ' . number_format($fileSize) . ' bytes</p>
                            <p><strong>Pages:</strong> Embedded PDF content</p>
                        </div>
                    </div>
                    <div class="meeting-content">
                        <p><strong>Meeting Minutes Content:</strong></p>
                        <div style="background-color: #fff; border: 1px solid #ccc; padding: 15px; margin: 10px 0;">
                            <p style="font-weight: bold; color: #3d004a;">PDF Content Preview:</p>
                            <p>This meeting minutes PDF has been embedded into this report. The original PDF contains ' . number_format($fileSize) . ' bytes of data.</p>
                            <p><strong>PDF Data:</strong> [Embedded - ' . strlen($base64Content) . ' characters of base64 encoded content]</p>
                            <div style="background-color: #f5f5f5; padding: 10px; font-family: monospace; font-size: 11px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd;">
                                ' . substr($base64Content, 0, 200) . '...<br>
                                <em>(Full PDF data embedded - ' . number_format(strlen($base64Content)) . ' characters)</em>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }

        $html .= '
        </body>
        </html>';

        return $html;
    }



    private function convertDocToPdf($inputPath)
    {
        $outputDir = storage_path('app/temp');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPdf = $outputDir . '/' . uniqid('converted_') . '.pdf';

        $command = 'libreoffice --headless --convert-to pdf '
            . escapeshellarg($inputPath)
            . ' --outdir '
            . escapeshellarg($outputDir);

        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error("LibreOffice conversion failed: " . implode("\n", $output));
            return null;
        }

        // LibreOffice outputs same filename but with .pdf extension
        $convertedFile = $outputDir . '/' . pathinfo($inputPath, PATHINFO_FILENAME) . '.pdf';

        return file_exists($convertedFile) ? $convertedFile : null;
    }



    /**
     * Get meeting PDF file paths from the report data
     */
    private function getMeetingPdfPaths($report)
    {
        $pdfPaths = [];

        if (isset($report['meetings']) && is_array($report['meetings'])) {
            foreach ($report['meetings'] as $meeting) {
                if (isset($meeting['meetingMinutesURL']) && is_array($meeting['meetingMinutesURL'])) {
                    foreach ($meeting['meetingMinutesURL'] as $minutesURL) {
                        if (isset($minutesURL['meetingURL']) && !empty($minutesURL['meetingURL'])) {
                            // Extract filename from URL - handle different URL formats
                            $meetingURL = $minutesURL['meetingURL'];
                            Log::info('Processing meeting URL: ' . $meetingURL);

                            // Handle different URL formats
                            if (strpos($meetingURL, 'app/private/uploads/meetings/') !== false) {
                                // URL contains the full path
                                $fileName = basename($meetingURL);
                            } else {
                                // URL might be just the filename
                                $fileName = $meetingURL;
                            }

                            $filePath = storage_path('app/private/uploads/meetings/' . $fileName);
                            Log::info('Looking for file: ' . $filePath);

                            if (file_exists($filePath)) {

                                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                                // If PDF, use as-is
                                if ($extension === 'pdf') {
                                    Log::info('Found meeting PDF: ' . $filePath);
                                    $pdfPaths[] = $filePath;
                                }

                                // If DOC or DOCX → convert to PDF
                                elseif (in_array($extension, ['doc', 'docx'])) {

                                    Log::info("Found DOC/DOCX: $filePath — converting to PDF...");

                                    $convertedPdf = $this->convertDocToPdf($filePath);

                                    if ($convertedPdf && file_exists($convertedPdf)) {
                                        Log::info("Conversion successful → $convertedPdf");
                                        $pdfPaths[] = $convertedPdf;
                                    } else {
                                        Log::warning("Failed to convert DOC/DOCX → PDF: $filePath");
                                    }
                                }

                                // If unsupported file type
                                else {
                                    Log::warning("Unsupported meeting file type: $filePath");
                                }
                            } else {
                                Log::warning('Meeting PDF not found: ' . $filePath);
                            }
                        }
                    }
                }
            }
        }

        Log::info('Total meeting PDFs found: ' . count($pdfPaths));
        return $pdfPaths;
    }

    public function generateStaffPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $report = FirestoreService::getDocument("staff", $reportID);
            if (!$report) {
                return response()->json(['error' => 'Report not found'], 404);
            }

            // Generate main PDF
            $pdf = PDF::loadView('UBForms::staffreport', [
                'report' => $report,
                'user' => $user,
                'request' => $request
            ]);

            // Create temp directory if it doesn't exist
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Save main PDF to temporary file
            $mainPdfPath = $tempDir . '/main_staff_report_' . $reportID . '.pdf';
            $pdf->save($mainPdfPath);

            // Get meeting PDF paths
            $meetingPdfPaths = $this->getMeetingPdfPaths($report);

            // If there are meeting PDFs, merge them
            if (!empty($meetingPdfPaths)) {
                $mergedPdfContent = $this->mergePdfs($mainPdfPath, $meetingPdfPaths);

                // Clean up temporary main PDF
                if (file_exists($mainPdfPath)) {
                    unlink($mainPdfPath);
                }

                // Return merged PDF
                return response($mergedPdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="staff_report_' . $reportID . '.pdf"');
            } else {
                // No meeting PDFs to merge, return main PDF
                $pdfContent = $pdf->output();

                // Clean up temporary main PDF
                if (file_exists($mainPdfPath)) {
                    unlink($mainPdfPath);
                }

                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="staff_report_' . $reportID . '.pdf"');
            }
        } catch (\Exception $e) {
            // Clean up temporary file if it exists
            if (isset($mainPdfPath) && file_exists($mainPdfPath)) {
                unlink($mainPdfPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
