<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AnonymousController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'anonymousReports';

    /**
     * ✅ NO DATABASE WRITE HERE
     * Only used to prepare frontend (optional)
     */
    public function initialize()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'category' => '',
                'location' => '',
                'reports' => '',
            ]
        ]);
    }

    public function index()
    {
        try {
            $anonymousReport = FirestoreService::getCollection($this->collectionName);

            return response()->json([
                'success' => true,
                'message' => 'Anonymous reports retrieved successfully',
                'data' => $anonymousReport
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * ✅ CREATE ONLY WHEN SUBMITTED
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'category' => 'required|string',
                'location' => 'required|string',
                'reports' => 'required|string',
                'formSubmitted' => 'required|boolean',
            ]);

            // ❗ Prevent saving drafts
            if ($request->formSubmitted !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not submitted',
                    'data' => null
                ], 400);
            }

            $anonymousData = [
                'category' => $request->category,
                'location' => $request->location,
                'reports' => $request->reports,
                'isRead' => false,
                'formSubmitted' => true,
                'caseNumber' => $this->generateCaseNumber(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];

            $documentRef = FirestoreService::syncDocumentAndGetRef(
                $this->collectionName,
                $anonymousData
            );

            $anonymousData['id'] = $documentRef->id();

            $documentRef->update([
                ['path' => 'id', 'value' => $anonymousData['id']]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anonymous Report Created Successfully',
                'data' => $anonymousData
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function show(string $anonymousReportID)
    {
        try {
            $anonymousReport = FirestoreService::getDocument($this->collectionName, $anonymousReportID);

            if ($anonymousReport) {
                return response()->json([
                    'success' => true,
                    'message' => 'Anonymous Report found',
                    'data' => $anonymousReport
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Anonymous Report not found',
                'data' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'category',
                'location',
                'reports',
                'isRead',
            ]);

            $data['updated_at'] = now()->toDateTimeString();

            $success = FirestoreService::updateDocument($this->collectionName, $id, $data);

            if ($success) {
                $updatedReport = FirestoreService::getDocument($this->collectionName, $id);

                return response()->json([
                    'success' => true,
                    'message' => 'Anonymous report updated successfully',
                    'data' => $updatedReport
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Anonymous report not found',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $success = FirestoreService::deleteDocument($this->collectionName, $id);

            return response()->json([
                'success' => $success,
                'message' => $success
                    ? 'Anonymous report deleted successfully'
                    : 'Anonymous report not found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateCaseNumber(): string
    {
        $prefix = "ANON-";
        $reports = FirestoreService::getCollection($this->collectionName);

        $lastNumber = 0;

        if (is_array($reports)) {
            foreach ($reports as $report) {
                if (
                    isset($report['caseNumber']) &&
                    str_starts_with($report['caseNumber'], $prefix)
                ) {
                    $number = (int) substr($report['caseNumber'], -4);
                    $lastNumber = max($lastNumber, $number);
                }
            }
        }

        return sprintf('%s%04d', $prefix, $lastNumber + 1);
    }

    public function generateAnonymousReportPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $anonymousReport = FirestoreService::getDocument($this->collectionName, $reportID);

            if (!$anonymousReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anonymous Report not found',
                ], 404);
            }

            $pdf = Pdf::loadView('publicsafety::anonymousreport', [
                'anonymousReport' => $anonymousReport,
                'user' => $user,
            ]);

            return $pdf->download('anonymous_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
