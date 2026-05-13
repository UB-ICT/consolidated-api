<?php

use Illuminate\Support\Facades\Route;
use Modules\Xenegrade\Http\Controllers\XenegradeController;
use Modules\Xenegrade\Http\Controllers\LecturerCourseController;
use Modules\Xenegrade\Http\Controllers\CourseSectionController;
use Modules\Xenegrade\Http\Controllers\CourseEvaluationController;
use Modules\Xenegrade\Http\Controllers\AggregatedCourseMonitoringController;
use Modules\Xenegrade\Http\Controllers\CourseMonitoringSettingsController;
use Modules\Xenegrade\Http\Controllers\ProgramCoordinatorReportController;
use Modules\Xenegrade\Http\Controllers\ChairAnnualProgramValidationReportController;
use Modules\Xenegrade\Http\Controllers\AnnualFormsDashboardController;
use Modules\Xenegrade\Http\Controllers\CourseCoordinatorReportController;
use Modules\Xenegrade\Http\Controllers\GradeDistributionController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
 */

Route::group([
    'prefix' => 'v1/Xenegrade',
    'namespace' => 'Modules\Xenegrade\Http\Controllers',
    // 'middleware' => ['auth:sanctum'],
], function () {
    Route::get(
        '/lecturer/{email}/{semester}/courses',
        [LecturerCourseController::class, 'getLecturerCourses']
    );
    Route::get(
        '/lecturers/{email}/{semester}',
        [LecturerCourseController::class, 'getLecturers']
    );
    Route::get(
        '/course-monitoring-menu/{email}/{semester}',
        [LecturerCourseController::class, 'getCourseMonitoringMenu']
    );
    Route::get(
        '/roles/{email}',
        [XenegradeController::class, 'checkEmailRoles']
    );
    Route::get(
        '/coordinator/{email}/courses',
        [XenegradeController::class, 'getCoursesByCoordinator']
    );
    Route::get(
        '/programCoordinator/{email}/courses',
        [XenegradeController::class, 'getCoursesByProgramCoordinator']
    );
    Route::get(
        '/aggregated-monitoring-courses/{email}/{academicYear}/{semester}',
        [AggregatedCourseMonitoringController::class, 'listCourses']
    );
    Route::get(
        '/aggregated-monitoring-course/{email}/{courseCode}/{academicYear}/{semester}',
        [AggregatedCourseMonitoringController::class, 'getAggregatedCourse']
    );
    Route::get(
        '/annualFormsDashboard/{email}',
        [AnnualFormsDashboardController::class, 'show']
    );
    Route::get(
        '/courseCoordinatorReport/{coordinatorEmail}',
        [CourseCoordinatorReportController::class, 'listReports']
    );
    Route::get(
        '/courseCoordinatorReport/{coordinatorEmail}/{academicYear}/{semester}',
        [CourseCoordinatorReportController::class, 'getReport']
    );
    Route::put(
        '/courseCoordinatorReport/{coordinatorEmail}/{academicYear}/{semester}',
        [CourseCoordinatorReportController::class, 'upsertReport']
    );
    Route::delete(
        '/courseCoordinatorReport/{coordinatorEmail}/{academicYear}/{semester}',
        [CourseCoordinatorReportController::class, 'deleteReport']
    );
    Route::get(
        '/courseMonitoringSettings',
        [CourseMonitoringSettingsController::class, 'getSettings']
    );
    Route::get(
        '/courseMonitoringSettings/form-access',
        [CourseMonitoringSettingsController::class, 'getFormAccess']
    );
    Route::put(
        '/courseMonitoringSettings',
        [CourseMonitoringSettingsController::class, 'upsertSettings']
    );
    Route::delete(
        '/courseMonitoringSettings',
        [CourseMonitoringSettingsController::class, 'deleteSettings']
    );
    Route::get(
        '/programCoordinatorReport/{email}',
        [ProgramCoordinatorReportController::class, 'listProgramReports']
    );
    Route::get(
        '/programCoordinatorReport/programs/{email}',
        [ProgramCoordinatorReportController::class, 'getProgramCoordinatorSheetRows']
    );
    Route::get(
        '/programCoordinatorReport/{email}/{programCode}/{academicYear}',
        [ProgramCoordinatorReportController::class, 'getProgramReport']
    );
    Route::put(
        '/programCoordinatorReport/{email}/{programCode}/{academicYear}',
        [ProgramCoordinatorReportController::class, 'upsertProgramReport']
    );
    Route::delete(
        '/programCoordinatorReport/{email}/{programCode}/{academicYear}',
        [ProgramCoordinatorReportController::class, 'deleteProgramReport']
    );
    Route::get(
        '/chair/programCoordinatorReport/programs/{chairEmail}',
        [ProgramCoordinatorReportController::class, 'getChairProgramCoordinatorPrograms']
    );
    Route::get(
        '/chair/programCoordinatorReport/{chairEmail}/{pcEmail}/{programCode}/{academicYear}',
        [ProgramCoordinatorReportController::class, 'getReportForChair']
    );
    Route::put(
        '/chair/programCoordinatorReport/{chairEmail}/{pcEmail}/{programCode}/{academicYear}',
        [ProgramCoordinatorReportController::class, 'upsertChairReview']
    );
    Route::get(
        '/chairAnnualProgramValidationReport/{chairEmail}',
        [ChairAnnualProgramValidationReportController::class, 'listReports']
    );
    Route::get(
        '/chairAnnualProgramValidationReport/{chairEmail}/{programCode}/{academicYear}',
        [ChairAnnualProgramValidationReportController::class, 'getReportLegacy']
    );
    Route::put(
        '/chairAnnualProgramValidationReport/{chairEmail}/{programCode}/{academicYear}',
        [ChairAnnualProgramValidationReportController::class, 'upsertReportLegacy']
    );
    Route::delete(
        '/chairAnnualProgramValidationReport/{chairEmail}/{programCode}/{academicYear}',
        [ChairAnnualProgramValidationReportController::class, 'deleteReportLegacy']
    );
    Route::get(
        '/chairAnnualProgramValidationReport/{chairEmail}/{academicYear}',
        [ChairAnnualProgramValidationReportController::class, 'getReportByYear']
    );
    Route::put(
        '/chairAnnualProgramValidationReport/{chairEmail}/{academicYear}',
        [ChairAnnualProgramValidationReportController::class, 'upsertReportByYear']
    );
    Route::delete(
        '/chairAnnualProgramValidationReport/{chairEmail}/{academicYear}',
        [ChairAnnualProgramValidationReportController::class, 'deleteReportByYear']
    );
    Route::get(
        '/gradeDistribution/{courseCode}/{courseSection}',
        [GradeDistributionController::class, 'show']
    );
    Route::get(
        '/{courseCode}/{courseSection}',
        [CourseSectionController::class, 'getCourseRows']
    );

    Route::get(
        '/courseMonitoring/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}',
        [CourseEvaluationController::class, 'getCourse']
    );
    // Course Evaluation CRUD routes
    // Route::get(
    //     '/courseMonitoring/{email}',
    //     [CourseEvaluationController::class, 'getCourseEvaluation']
    // );
    Route::put(
        '/courseMonitoring/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}',
        [CourseEvaluationController::class, 'updateCourse']
    );
    Route::delete(
        '/courseMonitoring/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}',
        [CourseEvaluationController::class, 'deleteCourse']
    );
    Route::post(
        '/courseMonitoring/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}/upload',
        [CourseEvaluationController::class, 'uploadDocument']
    );
    Route::delete(
        '/courseMonitoring/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}/document/{documentPath}',
        [CourseEvaluationController::class, 'deleteDocument']
    );
    // Route::get(
    //     '/courseMonitoring/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}/document/{documentPath}/download',
    //     [CourseEvaluationController::class, 'downloadDocument']
    // )->where('documentPath', '.*');
});