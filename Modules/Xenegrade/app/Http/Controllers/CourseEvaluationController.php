<?php

namespace Modules\Xenegrade\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\FirestoreService;
use App\Services\GoogleSheetService;
use Google\Cloud\Firestore\FirestoreClient;

class CourseEvaluationController extends Controller
{
    protected $collectionName = 'cmon_courseMonitoring';

    /**
     * Normalize appendix document paths for comparison and disk lookup (private disk root).
     */
    private function normalizeDocumentPath(string $path): string
    {
        $path = urldecode($path);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'private/')) {
            $path = substr($path, strlen('private/'));
        }

        return $path;
    }

    /**
     * Resolve absolute path on the private disk for a stored relative path.
     */
    private function resolvePrivateDocumentAbsolutePath(string $relativePath): string
    {
        return storage_path('app/private/' . $this->normalizeDocumentPath($relativePath));
    }

    /**
     * Get course evaluation for a lecturer
     * Creates the document if it doesn't exist
     */
    public function getCourseEvaluation(Request $request, string $email)
    {
        try {
            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                // Omit `courses` until first course; readers use courses ?? [].
                $docRef->set([
                    'email' => $email,
                ]);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'email' => $email,
                        'courses' => []
                    ]
                ]);
            }

            $data = $snapshot->data();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting course evaluation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get course evaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create a specific course evaluation
     * Creates the course if it doesn't exist with default values from Google Sheet
     */
    public function getCourse(Request $request, string $email, string $courseCode, string $courseSection, string $academicYear, string $semester)
    {
        try {
            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            // Get or create lecturer document
            if (!$snapshot->exists()) {
                $docRef->set([
                    'email' => $email,
                ]);
                $courses = [];
            } else {
                $data = $snapshot->data();
                $courses = $data['courses'] ?? [];
            }

            // Find course in array
            $courseIndex = $this->findCourseIndex($courses, $courseCode, $courseSection, $academicYear, $semester);

            Log::info("courseIndex: " . $courseIndex);

            if ($courseIndex === -1) {
                // Course doesn't exist, create it with default values from Google Sheet
                $defaultCourse = $this->getDefaultCourseData($courseCode, $courseSection, $academicYear, $semester);
                $courses[] = $defaultCourse;
                $courseIndex = count($courses) - 1;
                
                // Update document
                $docRef->set([
                    'email' => $email,
                    'courses' => $courses
                ], ['merge' => true]);
            }

            return response()->json([
                'success' => true,
                'data' => $courses[$courseIndex]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a course evaluation
     */
    public function updateCourse(Request $request, string $email, string $courseCode, string $courseSection, string $academicYear, string $semester)
    {
        try {
            $validator = Validator::make($request->all(), [
                'courseDetails' => 'sometimes|array',
                'courseEvaluation' => 'sometimes|array',
                'assessmentOfLearningOutcomes' => 'sometimes|array',
                'studentResults' => 'sometimes|array',
                'resourcesAndFacilities' => 'sometimes|array',
                'administrativeIssues' => 'sometimes|array',
                'planningForImprovement' => 'sometimes|array',
                'appendix' => 'sometimes|array',
                'reviewNotes' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecturer document not found'
                ], 404);
            }

            $data = $snapshot->data();
            $courses = $data['courses'] ?? [];
            $courseIndex = $this->findCourseIndex($courses, $courseCode, $courseSection, $academicYear, $semester);

            if ($courseIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Get existing course
            $existingCourse = $courses[$courseIndex];

            // Fields that cannot be updated
            $protectedFields = [
                'courseCoordinator',
                'programCoordinator',
                'chair',
                'dean',
                'facultyCode',
                'campus',
                'email',
                'dateOfReport'
            ];

            // Update allowed fields
            $updateData = $request->only([
                'courseEvaluation',
                'assessmentOfLearningOutcomes',
                'studentResults',
                'resourcesAndFacilities',
                'administrativeIssues',
                'planningForImprovement',
                'appendix',
                'reviewNotes',
                'hasLecturerSubmitted',
                'hasCoordinatorApproved'
            ]);

            // Handle courseDetails separately (only allow certain fields)
            // Protected fields in courseDetails: facultyCode, campus, courseCode, courseSection, academicYear, semester, dateOfReport
            if ($request->has('courseDetails')) {
                $courseDetails = $existingCourse['courseDetails'] ?? [];
                $allowedCourseDetailsFields = [
                    'department',
                    'courseInstructorEmail',
                    'courseInstructorName',
                    'courseInstructorPosition',
                    'courseTitle',
                    'courseName'
                ];
                
                foreach ($allowedCourseDetailsFields as $field) {
                    if ($request->has("courseDetails.$field")) {
                        $courseDetails[$field] = $request->input("courseDetails.$field");
                    }
                }
                $updateData['courseDetails'] = $courseDetails;
            }

            // Set dateOfReport to current date on submission (in courseDetails)
            if ($request->has('hasLecturerSubmitted') && $request->input('hasLecturerSubmitted') === true) {
                if (!isset($updateData['courseDetails'])) {
                    $updateData['courseDetails'] = $existingCourse['courseDetails'] ?? [];
                }
                $updateData['courseDetails']['dateOfReport'] = now()->toDateString();
            }

            // Merge updates with existing course
            $updatedCourse = array_merge($existingCourse, $updateData);

            // Update the course in the array
            $courses[$courseIndex] = $updatedCourse;

            // Save to Firestore
            $docRef->set([
                'email' => $email,
                'courses' => $courses
            ], ['merge' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => $updatedCourse
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a course evaluation
     */
    public function deleteCourse(Request $request, string $email, string $courseCode, string $courseSection, string $academicYear, string $semester)
    {
        try {
            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecturer document not found'
                ], 404);
            }

            $data = $snapshot->data();
            $courses = $data['courses'] ?? [];
            $courseIndex = $this->findCourseIndex($courses, $courseCode, $courseSection, $academicYear, $semester);

            if ($courseIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Remove course from array
            array_splice($courses, $courseIndex, 1);

            // Update document
            $docRef->set([
                'email' => $email,
                'courses' => $courses
            ], ['merge' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find course index in courses array
     */
    private function findCourseIndex(array $courses, string $courseId, string $courseSection, string $academicYear, string $semester): int
    {
        foreach ($courses as $index => $course) {
            $courseDetails = $course['courseDetails'] ?? [];
            if (
                ($courseDetails['courseId'] ?? $course['courseId'] ?? $courseDetails['courseCode'] ?? $course['courseCode'] ?? null) === $courseId &&
                ($courseDetails['courseSection'] ?? null) === $courseSection &&
                ($courseDetails['academicYear'] ?? null) === $academicYear &&
                ($courseDetails['semester'] ?? null) === $semester
            ) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Get default course data from Google Sheet
     */
    private function getDefaultCourseData(string $courseCode, string $courseSection, string $academicYear, string $semester): array
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');

        if (!$spreadsheetId) {
            return $this->getEmptyCourseStructure($courseCode, $courseSection, $academicYear, $semester);
        }

        // Get course data from Google Sheet
        $rows = GoogleSheetService::getRowsByCourse($spreadsheetId, $range, $courseCode, $courseSection, $semester);

        if (empty($rows)) {
            return $this->getEmptyCourseStructure($courseCode, $courseSection, $academicYear, $semester);
        }

        $courseData = $rows[0]; // Get first matching row

        // Helper function to get value with multiple possible column name variations
        $getValue = function($data, $possibleKeys) {
            foreach ($possibleKeys as $key) {
                if (isset($data[$key]) && !empty($data[$key])) {
                    return $data[$key];
                }
            }
            return '';
        };

        // Get values with flexible column name matching
        $facultyCode = $getValue($courseData, ['FacultyCode']);
        $campus = $getValue($courseData, ['Campus']);
        $instructorEmail = $getValue($courseData, ['InstructorEmail']);
        $instructorName = $getValue($courseData, ['InstructorName']);
        $courseName = $getValue($courseData, ['CourseName']);
        $numberOfStudentsStarting = $getValue($courseData, ['NumberOfStudentsStarting']);
        $numberOfStudentsCompleting = $getValue($courseData, ['NumberOfStudentsCompleting']);
        $distributionOfGrades = $getValue($courseData, ['DistributionOfGrades']);
        // Get roles for the course 
        // $roles = GoogleSheetService::checkEmailRoles($spreadsheetId, $range, $instructorEmail);
        
        return [
            'courseCoordinator' => $getValue($courseData, ['CourseCoordinator']),
            'programCoordinator' => $getValue($courseData, ['ProgramCoordinator']),
            'chair' => $getValue($courseData, ['Chair']),
            'chair2' => $getValue($courseData, ['Chair2']),
            'dean' => $getValue($courseData, ['Dean']),
            'hasLecturerSubmitted' => false,
            'hasCoordinatorApproved' => false,
            'courseDetails' => [
                'facultyCode' => $facultyCode,
                'department' => $getValue($courseData, ['Department']),
                'campus' => $campus,
                'courseInstructorEmail' => $instructorEmail,
                'courseInstructorName' => $instructorName,
                'courseInstructorPosition' => 'Instructor',
                'courseName' => $courseName,
                'courseSection' => $courseSection,
                'courseCode' => $courseCode, // Also store in courseDetails for compatibility
                'academicYear' => $academicYear,
                'semester' => $semester,
            ],
            'courseEvaluation' => [
                'coverageOfPlannedProgram' => '',
                'consequencesForNonCoverageOfTopics' => '',
                'effectivenessOfPlannedTeachingStrategiesForIntededLearningOutcomes' => '',
                'recommendedChangesOrProcessesForImprovement' => '',
            ],
            'assessmentOfLearningOutcomes' => [
                'tableOfSpecifications' => '',
            ],
            'studentResults' => [
                'numberofStudentsStarting' => $numberOfStudentsStarting,
                'numberofStudentsCompleting' => $numberOfStudentsCompleting,
                'distributionOfGrades' => $distributionOfGrades,
                'resultSummaryPerCourse' => '',
                'resultSummaryPerLearningOutcome' => '',
                'specialFactors' => '',
                'variationsFromPlannedAssessmentProcesses' => '',
                'verificationFromPlannedAssessmentProcesses' => '',
                'verificationOfStandardAchievement' => '',
            ],
            'resourcesAndFacilities' => [
                'useOfRequiredTextsAndOtherResources' => '',
                'difficultiesInAccessingResourcesOrFacilities' => '',
                'consequencesOfDifficulties' => '',
            ],
            'administrativeIssues' => [
                'organizationalOrAdministrativeDifficulties' => '',
                'effectOfDifficultiesOnStudentLearning' => '',
            ],
            'planningForImprovement' => [
                'actionPlanForNextTimeTheCourseIsTaught' => '',
                'recommentationsForCourseCoordinatorOrChair' => '',
                'RecommendationsForProfessionalDevelopmentForTheInstructor' => '',
            ],
            'appendix' => [
                'documents' => []
            ],
        ];
    }

    /**
     * Get empty course structure when Google Sheet data is not available
     */
    private function getEmptyCourseStructure(string $courseCode, string $courseSection, string $academicYear, string $semester): array
    {
        return [
            'courseCoordinator' => '',
            'programCoordinator' => '',
            'chair' => '',
            'dean' => '',
            'hasLecturerSubmitted' => false,
            'hasCoordinatorApproved' => false,
            'courseId' => $courseCode, // Store courseId (CourseID) at top level
            'courseDetails' => [
                'facultyCode' => '',
                'department' => '',
                'campus' => '',
                'courseInstructorEmail' => '',
                'courseInstructorName' => '',
                'courseInstructorPosition' => '',
                'courseTitle' => '',
                'courseCode' => $courseCode,
                'courseId' => $courseCode, // Also store in courseDetails for compatibility
                'courseSection' => $courseSection,
                'courseName' => '',
                'academicYear' => $academicYear,
                'semester' => $semester,
                'dateOfReport' => null,
            ],
            'courseEvaluation' => [
                'coverageOfPlannedProgram' => '',
                'consequencesForNonCoverageOfTopics' => '',
                'effectivenessOfPlannedTeachingStrategiesForIntededLearningOutcomes' => '',
                'recommendedChangesOrProcessesForImprovement' => '',
            ],
            'assessmentOfLearningOutcomes' => [
                'tableOfSpecifications' => '',
            ],
            'studentResults' => [
                'numberofStudentsStarting' => 0,
                'numberofStudentsCompleting' => 0,
                'distributionOfGrades' => '',
                'resultSummaryPerCourse' => '',
                'resultSummaryPerLearningOutcome' => '',
                'specialFactors' => '',
                'variationsFromPlannedAssessmentProcesses' => '',
                'verificationFromPlannedAssessmentProcesses' => '',
                'verificationOfStandardAchievement' => '',
            ],
            'resourcesAndFacilities' => [
                'useOfRequiredTextsAndOtherResources' => '',
                'difficultiesInAccessingResourcesOrFacilities' => '',
                'consequencesOfDifficulties' => '',
            ],
            'administrativeIssues' => [
                'organizationalOrAdministrativeDifficulties' => '',
                'effectOfDifficultiesOnStudentLearning' => '',
            ],
            'planningForImprovement' => [
                'actionPlanForNextTimeTheCourseIsTaught' => '',
                'recommentationsForCourseCoordinatorOrChair' => '',
                'RecommendationsForProfessionalDevelopmentForTheInstructor' => '',
            ],
            'appendix' => [
                'documents' => []
            ],
        ];
    }

    /**
     * Upload document for course evaluation
     */
    public function uploadDocument(Request $request, string $email, string $courseCode, string $courseSection, string $academicYear, string $semester)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // Max 10MB
                'name' => 'sometimes|string|max:255', // Optional custom name
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded'
                ], 400);
            }

            $file = $request->file('file');
            
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file'
                ], 400);
            }

            // Get or create course
            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecturer document not found'
                ], 404);
            }

            $data = $snapshot->data();
            $courses = $data['courses'] ?? [];
            $courseIndex = $this->findCourseIndex($courses, $courseCode, $courseSection, $academicYear, $semester);

            if ($courseIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $customName = $request->input('name', $originalName); // Use custom name if provided, otherwise use original
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::random(40) . '.' . $extension;
            
            // Store file
            $filePath = $file->storeAs('uploads/courseMonitoring', $fileName, 'private');
            
            // Generate path for storage (relative to storage/app/private)
            $storagePath = 'uploads/courseMonitoring/' . $fileName;
            
            // Get existing course
            $existingCourse = $courses[$courseIndex];
            
            // Initialize appendix if it doesn't exist
            if (!isset($existingCourse['appendix'])) {
                $existingCourse['appendix'] = ['documents' => []];
            }
            
            if (!isset($existingCourse['appendix']['documents'])) {
                $existingCourse['appendix']['documents'] = [];
            }
            
            // Add document to appendix
            $documentInfo = [
                'name' => $customName,
                'path' => $storagePath
            ];
            
            $existingCourse['appendix']['documents'][] = $documentInfo;
            
            // Update course in array
            $courses[$courseIndex] = $existingCourse;
            
            // Save to Firestore
            $docRef->set([
                'email' => $email,
                'courses' => $courses
            ], ['merge' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $documentInfo
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a document from course evaluation
     */
    public function deleteDocument(Request $request, string $email, string $courseCode, string $courseSection, string $academicYear, string $semester, string $documentPath)
    {
        try {
            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecturer document not found'
                ], 404);
            }

            $data = $snapshot->data();
            $courses = $data['courses'] ?? [];
            $courseIndex = $this->findCourseIndex($courses, $courseCode, $courseSection, $academicYear, $semester);

            if ($courseIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $existingCourse = $courses[$courseIndex];
            
            if (!isset($existingCourse['appendix']['documents'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found'
                ], 404);
            }

            // Find and remove document
            $documents = $existingCourse['appendix']['documents'];
            $documentIndex = -1;
            
            $normalizedRequestPath = $this->normalizeDocumentPath($documentPath);

            foreach ($documents as $index => $doc) {
                if ($this->normalizeDocumentPath((string) ($doc['path'] ?? '')) === $normalizedRequestPath) {
                    $documentIndex = $index;
                    break;
                }
            }

            if ($documentIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            // Delete file from storage
            $filePath = $this->resolvePrivateDocumentAbsolutePath($normalizedRequestPath);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Remove document from array
            array_splice($documents, $documentIndex, 1);
            $existingCourse['appendix']['documents'] = $documents;
            
            // Update course in array
            $courses[$courseIndex] = $existingCourse;
            
            // Save to Firestore
            $docRef->set([
                'email' => $email,
                'courses' => $courses
            ], ['merge' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a document from course evaluation
     */
    public function downloadDocument(Request $request, string $email, string $courseCode, string $courseSection, string $academicYear, string $semester, string $documentPath)
    {
        try {
            $firestore = FirestoreService::firestore();
            $docRef = $firestore->collection($this->collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecturer document not found'
                ], 404);
            }

            $data = $snapshot->data();
            $courses = $data['courses'] ?? [];
            $courseIndex = $this->findCourseIndex($courses, $courseCode, $courseSection, $academicYear, $semester);

            if ($courseIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $existingCourse = $courses[$courseIndex];
            
            if (!isset($existingCourse['appendix']['documents'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found'
                ], 404);
            }

            // Verify document exists in the course
            $documents = $existingCourse['appendix']['documents'];
            $documentExists = false;
            $documentName = '';
            
            $normalizedRequestPath = $this->normalizeDocumentPath($documentPath);

            foreach ($documents as $doc) {
                if ($this->normalizeDocumentPath((string) ($doc['path'] ?? '')) === $normalizedRequestPath) {
                    $documentExists = true;
                    $documentName = $doc['name'] ?? basename($normalizedRequestPath);
                    break;
                }
            }

            if (!$documentExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            if (! Storage::disk('private')->exists($normalizedRequestPath)) {
                $filePath = $this->resolvePrivateDocumentAbsolutePath($normalizedRequestPath);
                Log::error('File not found', [
                    'expected_path' => $filePath,
                    'document_path' => $documentPath,
                    'normalized_path' => $normalizedRequestPath,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'File not found on server',
                ], 404);
            }

            return Storage::disk('private')->download($normalizedRequestPath, $documentName);
        } catch (\Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

