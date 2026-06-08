<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Xenegrade\Services\AnnualFormsDashboardService;

class CourseCoordinatorReportController extends Controller
{
    protected string $collectionName = 'cmon_courseCoordinatorReport';

    public function listReports(string $coordinatorEmail)
    {
        $doc = FirestoreService::getDocument($this->collectionName, $coordinatorEmail);

        return response()->json([
            'success' => true,
            'data' => is_array($doc) ? ($doc['reports'] ?? []) : [],
        ]);
    }

    public function getReport(string $coordinatorEmail, string $academicYear, string $semester)
    {
        $doc = FirestoreService::getDocument($this->collectionName, $coordinatorEmail);
        $reports = is_array($doc) ? ($doc['reports'] ?? []) : [];
        $idx = $this->findReportIndex($reports, $academicYear, $semester);
        if ($idx === -1) {
            return response()->json([
                'success' => false,
                'message' => 'No stored aggregate for this academic year and semester. Use PUT to compute and save from the courses sheet and lecturer courseMonitoring data.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reports[$idx],
            'meta' => ['persisted' => true],
        ]);
    }

    public function upsertReport(Request $request, string $coordinatorEmail, string $academicYear, string $semester)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'sometimes|nullable|string|max:5000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $service = new AnnualFormsDashboardService;
        $computed = $service->computeCourseCoordinatorReportSnapshot($coordinatorEmail, $academicYear, $semester);
        if ($computed === null) {
            return response()->json([
                'success' => false,
                'message' => 'GOOGLE_SHEET_ID is not configured or course rows could not be loaded.',
            ], 422);
        }

        $doc = $this->getOrCreateOwnerDocument($coordinatorEmail);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndex($reports, $academicYear, $semester);
        $prev = ($idx !== -1 && is_array($reports[$idx])) ? $reports[$idx] : [];
        $notesOut = array_key_exists('notes', $request->all())
            ? (string) $request->input('notes', '')
            : (string) ($prev['notes'] ?? '');

        $row = array_merge($computed, [
            'notes' => $notesOut,
        ]);
        if ($idx === -1) {
            $reports[] = $row;
        } else {
            $reports[$idx] = array_merge(is_array($reports[$idx]) ? $reports[$idx] : [], $row);
        }

        $this->saveOwnerReports($coordinatorEmail, $reports);

        $outIdx = $idx === -1 ? count($reports) - 1 : $idx;

        return response()->json([
            'success' => true,
            'data' => $reports[$outIdx],
        ]);
    }

    public function deleteReport(string $coordinatorEmail, string $academicYear, string $semester)
    {
        $doc = FirestoreService::getDocument($this->collectionName, $coordinatorEmail);
        if ($doc === null) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndex($reports, $academicYear, $semester);
        if ($idx === -1) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        array_splice($reports, $idx, 1);
        $this->saveOwnerReports($coordinatorEmail, $reports);

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    /**
     * @param  list<mixed>  $reports
     */
    private function findReportIndex(array $reports, string $academicYear, string $semester): int
    {
        $wantY = trim($academicYear);
        $wantS = trim($semester);
        foreach ($reports as $index => $report) {
            if (! is_array($report)) {
                continue;
            }
            $y = trim((string) ($report['academicYear'] ?? ''));
            $s = trim((string) ($report['semester'] ?? ''));
            if (strcasecmp($y, $wantY) === 0 && strcasecmp($s, $wantS) === 0) {
                return (int) $index;
            }
        }

        return -1;
    }

    private function getOrCreateOwnerDocument(string $email): array
    {
        $doc = FirestoreService::getDocument($this->collectionName, $email);
        if ($doc !== null) {
            return $doc;
        }

        $payload = ['email' => $email, 'reports' => []];
        FirestoreService::firestore()
            ->collection($this->collectionName)
            ->document($email)
            ->set($payload, ['merge' => true]);

        return $payload;
    }

    private function saveOwnerReports(string $email, array $reports): void
    {
        FirestoreService::firestore()
            ->collection($this->collectionName)
            ->document($email)
            ->set(['email' => $email, 'reports' => $reports], ['merge' => true]);
    }
}
