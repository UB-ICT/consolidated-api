<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Xenegrade\Services\CourseMonitoringFormAccessService;

class CourseMonitoringSettingsController extends Controller
{
    protected string $collectionName = 'cmon_courseMonitoringSettings';

    protected string $documentId = 'global';

    public function getSettings()
    {
        $existing = FirestoreService::getDocument($this->collectionName, $this->documentId);
        if ($existing === null) {
            return response()->json([
                'success' => false,
                'message' => 'Course monitoring settings not found. Create them first.',
            ], 404);
        }

        $flags = CourseMonitoringFormAccessService::flagsFromDocument($existing);
        $data = array_merge($existing, $flags);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Lightweight read of form visibility flags from `cmon_courseMonitoringSettings/global`.
     */
    public function getFormAccess()
    {
        $existing = FirestoreService::getDocument($this->collectionName, $this->documentId);
        if ($existing === null) {
            return response()->json([
                'success' => false,
                'message' => 'Course monitoring settings not found. Create them first.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => CourseMonitoringFormAccessService::flagsFromDocument($existing),
        ]);
    }

    public function upsertSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'defaultYearColumns' => 'sometimes|array|min:1',
            'defaultYearColumns.*' => 'string',
            'currentYear' => 'sometimes|string',
            'currentSemester' => 'sometimes|string',
            'defaultGroupACategories' => 'sometimes|array',
            'defaultGroupACategories.*' => 'string',
            'enableCourseMonitoringForm' => 'sometimes|boolean',
            'enableCourseCoordinatorForm' => 'sometimes|boolean',
            'enableProgramCoordinatorForm' => 'sometimes|boolean',
            'enableAnnualChairForm' => 'sometimes|boolean',
            'enableAnnualDeanForm' => 'sometimes|boolean',
            'enableAnnualVpForm' => 'sometimes|boolean',
            'enableAnnualVPForm' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $current = FirestoreService::getDocument($this->collectionName, $this->documentId) ?? [];
        $payload = array_merge($current, $request->only([
            'defaultYearColumns',
            'currentYear',
            'currentSemester',
            'defaultGroupACategories',
            'enableCourseMonitoringForm',
            'enableCourseCoordinatorForm',
            'enableProgramCoordinatorForm',
            'enableAnnualChairForm',
            'enableAnnualDeanForm',
            'enableAnnualVpForm',
            'enableAnnualVPForm',
        ]));
        $payload['updatedAt'] = now()->toIso8601String();

        $docRef = FirestoreService::firestore()->collection($this->collectionName)->document($this->documentId);
        $docRef->set($payload, ['merge' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Course monitoring settings updated successfully',
            'data' => $payload,
        ]);
    }

    public function deleteSettings()
    {
        $deleted = FirestoreService::deleteDocument($this->collectionName, $this->documentId);
        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Settings document not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Course monitoring settings deleted successfully',
        ]);
    }
}
