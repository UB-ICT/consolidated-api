<?php

use Illuminate\Support\Facades\Route;
use Modules\Xenegrade\Http\Controllers\XenegradeController;
use Modules\Xenegrade\Http\Controllers\LecturerCourseController;
use Modules\Xenegrade\Http\Controllers\CourseSectionController;
use Modules\Xenegrade\Http\Controllers\CourseEvaluationController;

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
        '/{courseCode}/{courseSection}',
        [CourseSectionController::class, 'getCourseRows']
    );

    // Course Evaluation CRUD routes
    Route::get(
        '/courseEvaluation/{email}',
        [CourseEvaluationController::class, 'getCourseEvaluation']
    );
    Route::get(
        '/courseEvaluation/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}',
        [CourseEvaluationController::class, 'getCourse']
    );
    Route::put(
        '/courseEvaluation/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}',
        [CourseEvaluationController::class, 'updateCourse']
    );
    Route::delete(
        '/courseEvaluation/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}',
        [CourseEvaluationController::class, 'deleteCourse']
    );
    Route::post(
        '/courseEvaluation/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}/upload',
        [CourseEvaluationController::class, 'uploadDocument']
    );
    Route::delete(
        '/courseEvaluation/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}/document/{documentPath}',
        [CourseEvaluationController::class, 'deleteDocument']
    );
    Route::get(
        '/courseEvaluation/{email}/{courseCode}/{courseSection}/{academicYear}/{semester}/document/{documentPath}/download',
        [CourseEvaluationController::class, 'downloadDocument']
    )->where('documentPath', '.*');
});