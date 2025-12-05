<?php

use Illuminate\Support\Facades\Route;
// use Modules\Xenegrade\Http\Controllers\XenegradeController;
use Modules\Xenegrade\Http\Controllers\LecturerCourseController;
use Modules\Xenegrade\Http\Controllers\CourseSectionController;

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
        '/{courseCode}/{courseSection}',
        [CourseSectionController::class, 'getCourseRows']
    );
});