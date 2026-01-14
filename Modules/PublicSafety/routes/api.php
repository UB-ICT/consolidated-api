<?php

use Illuminate\Support\Facades\Route;
use Modules\PublicSafety\Http\Controllers\PublicSafetyAuthController;
use Modules\PublicSafety\Http\Controllers\UserController;
use Modules\PublicSafety\Http\Controllers\CampusController;
use Modules\PublicSafety\Http\Controllers\BuildingController;
use Modules\PublicSafety\Http\Controllers\IncidentReportController;
use Modules\PublicSafety\Http\Controllers\IncidentStatusController;
use Modules\PublicSafety\Http\Controllers\MenuController;
use Modules\PublicSafety\Http\Controllers\FileUploadController;
use Modules\PublicSafety\Http\Controllers\IncidentTypeController;
use Modules\PublicSafety\Http\Controllers\EndOfShiftReportPatrolController;
use Modules\PublicSafety\Http\Controllers\EndOfShiftReportSupervisorController;
use Modules\PublicSafety\Http\Controllers\LostAndFoundTrackingController;
use Modules\PublicSafety\Http\Controllers\LostPropertyController;
use Modules\PublicSafety\Http\Controllers\ImpoundedReportTrackingFormController;
use Modules\PublicSafety\Http\Controllers\ConversationController;
use Modules\PublicSafety\Http\Controllers\MessageController;





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


// This will be the only unprotected route because this is used for authentication
Route::prefix('')->group(function () {

    // Google OAuth
    // Route::get('/auth/google/public-safety-redirect', [PublicSafetyAuthController::class, 'redirect']);
    // Route::get('/auth/google/public-safety-callback', [PublicSafetyAuthController::class, 'callback']);
});

Route::group([
    'prefix' => 'v1/publicSafety',
    'namespace' => 'Modules\PublicSafety\Http\Controllers',
    'middleware' => 'auth:sanctum',
], function () {

    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{userID}', [UserController::class, 'show']);
    Route::put('users/{userID}', [UserController::class, 'update']);
    Route::delete('users/{userID}', [UserController::class, 'destroy']);
    Route::get('usersTotal', [UserController::class, 'getTotalUsers']);
    Route::get('users/email/{email}', [UserController::class, 'getUserByEmail']);
    Route::post('users/device-token', [UserController::class, 'updateDeviceToken'])->middleware('auth:sanctum');
    Route::get('users/profile', [UserController::class, 'getProfile'])->middleware('auth:sanctum');


    // New routes for CampusController
    Route::get('campuses', [CampusController::class, 'index']);
    Route::post('campuses', [CampusController::class, 'store']);
    Route::get('campuses/{campusID}', [CampusController::class, 'show']);
    Route::put('campuses/{campusID}', [CampusController::class, 'update']);
    Route::delete('campuses/{campusID}', [CampusController::class, 'destroy']);

    Route::get('buildings', [BuildingController::class, 'index']);
    Route::post('buildings', [BuildingController::class, 'store']);
    Route::get('buildings/{buildingID}', [BuildingController::class, 'show']);
    Route::put('buildings/{buildingID}', [BuildingController::class, 'update']);
    Route::delete('buildings/{buildingID}', [BuildingController::class, 'destroy']);

    Route::get('incidentTypes', [IncidentTypeController::class, 'index']);
    Route::post('incidentTypes', [IncidentTypeController::class, 'store']);
    Route::put('incidentTypes/{id}', [IncidentTypeController::class, 'update']);
    Route::delete('incidentTypes/{id}', [IncidentTypeController::class, 'destroy']);

    Route::post('/initialize/incidentReports', [IncidentReportController::class, 'initialize']);
    Route::get('/incidentReports', [IncidentReportController::class, 'index']);
    Route::post('/incidentReports', [IncidentReportController::class, 'store']);
    Route::get('incidentReports/{incidentReportID}', [IncidentReportController::class, 'show']);
    Route::put('incidentReports/{incidentReportID}', [IncidentReportController::class, 'update']);
    Route::delete('incidentReports/{incidentReportID}', [IncidentReportController::class, 'destroy']);
    Route::get('incidentReportTotal', [IncidentReportController::class, 'getTotalIncidentReport']);
    Route::get('/generateIncidentReportPdf/{reportID}', [IncidentReportController::class, 'generateIncidentReportPdf']);
    Route::get('/unsubmittedIncidentReports', [IncidentReportController::class, 'getUnsubmittedIncidentReports']);

    //end of shift report (patrol officer)
    Route::post('/initialize/endOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'initialize']);
    Route::get('/endOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'index']);
    Route::post('/endOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'store']);
    Route::get('/endOfShiftReportPatrols/{endOfShiftReportPatrolID}', [EndOfShiftReportPatrolController::class, 'show']);
    Route::put('/endOfShiftReportPatrols/{endOfShiftReportPatrolID}', [EndOfShiftReportPatrolController::class, 'update']);
    Route::delete('/endOfShiftReportPatrols/{endOfShiftReportPatrolID}', [EndOfShiftReportPatrolController::class, 'destroy']);
    Route::get('/endOfShiftReportPatrolsTotal', [EndOfShiftReportPatrolController::class, 'getTotalEndOfShiftReportPatrol']);
    Route::get('/generateEndOfShiftReportPatrolPdf/{reportID}', [EndOfShiftReportPatrolController::class, 'generateEndOfShiftReportPatrolPdf']);
    Route::get('/unsubmittedEndOfShiftReportPatrols', [EndOfShiftReportPatrolController::class, 'getUnsubmittedEndOfShiftReportPatrols']);

    //end of shift report (Shift Supervisor)
    Route::post('/initialize/endOfShiftReportSupervisor', [EndOfShiftReportSupervisorController::class, 'initialize']);
    Route::get('/endOfShiftReportSupervisor', [EndOfShiftReportSupervisorController::class, 'index']);
    Route::post('/endOfShiftReportSupervisor', [EndOfShiftReportSupervisorController::class, 'store']);
    Route::get('/endOfShiftReportSupervisor/{ID}', [EndOfShiftReportSupervisorController::class, 'show']);
    Route::put('/endOfShiftReportSupervisor/{ID}', [EndOfShiftReportSupervisorController::class, 'update']);
    Route::delete('/endOfShiftReportSupervisor/{ID}', [EndOfShiftReportSupervisorController::class, 'destroy']);
    Route::get('/endOfShiftReportSupervisorTotal', [EndOfShiftReportSupervisorController::class, 'getTotalEndOfShiftReportSupervisor']);
    Route::get('/generateEndOfShiftReportSupervisorPdf/{ID}', [EndOfShiftReportSupervisorController::class, 'generateEndOfShiftReportSupervisorPdf']);
    Route::get('/unsubmittedEndOfShiftReportSupervisor', [EndOfShiftReportSupervisorController::class, 'getUnsubmittedEndOfShiftReportSupervisor']);

    //lost and found tracking report
    Route::post('/initialize/lostAndFoundTracking', [LostAndFoundTrackingController::class, 'initialize']);
    Route::get('/lostAndFoundTracking', [LostAndFoundTrackingController::class, 'index']);
    Route::post('/lostAndFoundTracking', [LostAndFoundTrackingController::class, 'store']);
    Route::get('/lostAndFoundTracking/{lostAndFoundTrackingID}', [LostAndFoundTrackingController::class, 'show']);
    Route::put('/lostAndFoundTracking/{lostAndFoundTrackingID}', [LostAndFoundTrackingController::class, 'update']);
    Route::delete('/lostAndFoundTracking/{lostAndFoundTrackingID}', [LostAndFoundTrackingController::class, 'destroy']);
    Route::get('/lostAndFoundTrackingTotal', [LostAndFoundTrackingController::class, 'getTotalLoandFoundTracking']);
    Route::get('/generateLostAndFoundPdf/{reportID}', [LostAndFoundTrackingController::class, 'generateLostAndFoundPdf']);
    Route::get('/unsubmittedLostAndFoundTracking', [LostAndFoundTrackingController::class, 'getUnsubmittedLostAndFoundTracking']);

    Route::post('/initialize/lostProperty', [LostPropertyController::class, 'initialize']);
    Route::get('/lostProperty', [LostPropertyController::class, 'index']);
    Route::post('/lostProperty', [LostPropertyController::class, 'store']);
    Route::get('/lostProperty/{lostPropertyID}', [LostPropertyController::class, 'show']);
    Route::put('/lostProperty/{lostPropertyID}', [LostPropertyController::class, 'update']);
    Route::delete('/lostProperty/{lostPropertyID}', [LostPropertyController::class, 'destroy']);
    Route::get('/lostPropertyTotal', [LostPropertyController::class, 'getTotalLostProperty']);
    Route::get('/generateLostPropertyPdf/{reportID}', [LostPropertyController::class, 'generateLostPropertyPdf']);
    Route::get('/unsubmittedLostProperty', [LostPropertyController::class, 'getUnsubmittedlostProperty']);

    Route::post('/initialize/impoundedReport', [ImpoundedReportTrackingFormController::class, 'initialize']);
    Route::get('/impoundedReport', [ImpoundedReportTrackingFormController::class, 'index']);
    Route::post('/impoundedReport', [ImpoundedReportTrackingFormController::class, 'store']);
    Route::get('/impoundedReport/{impoundedReportID}', [ImpoundedReportTrackingFormController::class, 'show']);
    Route::put('/impoundedReport/{impoundedReportID}', [ImpoundedReportTrackingFormController::class, 'update']);
    Route::delete('/impoundedReport/{impoundedReportID}', [ImpoundedReportTrackingFormController::class, 'destroy']);
    Route::get('/impoundedReportTotal', [ImpoundedReportTrackingFormController::class, 'getTotalImpoundedReport']);
    Route::get('/generateImpoundedReportPdf/{reportID}', [ImpoundedReportTrackingFormController::class, 'generateImpoundedReportPdf']);
    Route::get('/unsubmittedImpoundedReport', [ImpoundedReportTrackingFormController::class, 'getUnsubmittedImpoundedReport']);

    Route::get('incidentStatus', [IncidentStatusController::class, 'index']);
    Route::post('incidentStatus', [IncidentStatusController::class, 'store']);
    Route::put('incidentStatus/{id}', [IncidentStatusController::class, 'update']);
    Route::delete('incidentStatus/{id}', [IncidentStatusController::class, 'destroy']);

    Route::get('incidentTypes', [IncidentTypeController::class, 'index']);
    Route::post('incidentTypes', [IncidentTypeController::class, 'store']);
    Route::put('incidentTypes/{id}', [IncidentTypeController::class, 'update']);
    Route::delete('incidentTypes/{id}', [IncidentTypeController::class, 'destroy']);

    //menus
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/menu', [MenuController::class, 'store']);
    Route::put('/menu/{id}', [MenuController::class, 'update']);
    Route::delete('/menu/{id}', [MenuController::class, 'destroy']);

    Route::get('/getFile/{fileType}/{fileName}', [FileUploadController::class, 'downloadPublicSafetyFile']);
    Route::post('/uploadPublicSafetyPhoto/{reportID}', [FileUploadController::class, 'uploadPublicSafetyPhoto']);
    Route::post(
        '/uploadSignatureCanvas/{reportID}',
        [FileUploadController::class, 'uploadSignatureCanvas']
    );

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);

    // Marks isDeleted = true
    // Does NOT delete messages
    // Safe for emergency/public-safety audits
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);

    // Get messages for a conversation (chat window)
    Route::get(
        '/conversations/{conversationId}/messages',
        [MessageController::class, 'index']
    );

    // Send a message
    Route::post(
        '/messages',
        [MessageController::class, 'store']
    );

    Route::patch(
        '/messages/{messageId}/read',
        [MessageController::class, 'markAsRead']
    );

    Route::delete(
        '/messages/{messageId}',
        [MessageController::class, 'destroy']
    );

    // Get media, files, or links for a conversation
    Route::get(
        '/messages/{conversationId}/media',
        [MessageController::class, 'media']
    );
});

Route::get('/phpinfo', function () {
    phpinfo();
});