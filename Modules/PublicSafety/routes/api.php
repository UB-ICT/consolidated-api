<?php

use Illuminate\Support\Facades\Route;
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
use Modules\PublicSafety\Http\Controllers\BombController;
use Modules\PublicSafety\Http\Controllers\IncidentLogController;
use Modules\PublicSafety\Http\Controllers\AnonymousController;
use Modules\PublicSafety\Http\Controllers\EmergencyController;

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
    Route::get('/generateIncidentReportPdf/{reportID}', [IncidentReportController::class, 'generateIncidentReportPdf']);
    Route::get('/unsubmittedIncidentReports', [IncidentReportController::class, 'getUnsubmittedIncidentReports']);
    Route::get('/incidentsByBuilding/{buildingName}', [IncidentReportController::class, 'getIncidentsByBuilding']); // Get all incident reports by building name
    Route::get('/recentIncidents', [IncidentReportController::class, 'getRecentIncidents']);
    Route::get('/getTotalActiveIncident', [IncidentReportController::class, 'getTotalActiveIncidents']);
    Route::get('/getTotalResolvedIncident', [IncidentReportController::class, 'getTotalResolvedIncidents']);
    Route::get('/getTotalPendingIncident', [IncidentReportController::class, 'getTotalPendingIncidents']);
    Route::get('/getTotalIncident', [IncidentReportController::class, 'getTotalIncidentCount']);
    Route::post('/mark-as-read/{id}', [IncidentReportController::class, 'markAsRead']);
    Route::get('/getUnreadIncidentReports', [IncidentReportController::class, 'getUnreadIncidentReports']);

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
    Route::get('/activeLostAndFoundTracking', [LostAndFoundTrackingController::class, 'getActiveLostAndFoundTracking']);
    Route::get('/resolvedLostAndFoundTracking', [LostAndFoundTrackingController::class, 'getResolvedLostAndFoundTracking']);
    Route::get('/pendingLostAndFoundTracking', [LostAndFoundTrackingController::class, 'getPendingLostAndFoundTracking']);

    Route::post('/initialize/lostProperty', [LostPropertyController::class, 'initialize']);
    Route::get('/lostProperty', [LostPropertyController::class, 'index']);
    Route::post('/lostProperty', [LostPropertyController::class, 'store']);
    Route::get('/lostProperty/{lostPropertyID}', [LostPropertyController::class, 'show']);
    Route::put('/lostProperty/{lostPropertyID}', [LostPropertyController::class, 'update']);
    Route::delete('/lostProperty/{lostPropertyID}', [LostPropertyController::class, 'destroy']);
    Route::get('/lostPropertyTotal', [LostPropertyController::class, 'getTotalLostProperty']);
    Route::get('/generateLostPropertyPdf/{reportID}', [LostPropertyController::class, 'generateLostPropertyPdf']);
    Route::get('/unsubmittedLostProperty', [LostPropertyController::class, 'getUnsubmittedlostProperty']);
    Route::get('/activeLostProperty', [LostPropertyController::class, 'getActiveLostProperty']);
    Route::get('/resolvedLostProperty', [LostPropertyController::class, 'getResolvedLostProperty']);
    Route::get('/pendingLostProperty', [LostPropertyController::class, 'getPendingLostProperty']);

    Route::post('/initialize/impoundedReport', [ImpoundedReportTrackingFormController::class, 'initialize']);
    Route::get('/impoundedReport', [ImpoundedReportTrackingFormController::class, 'index']);
    Route::post('/impoundedReport', [ImpoundedReportTrackingFormController::class, 'store']);
    Route::get('/impoundedReport/{impoundedReportID}', [ImpoundedReportTrackingFormController::class, 'show']);
    Route::put('/impoundedReport/{impoundedReportID}', [ImpoundedReportTrackingFormController::class, 'update']);
    Route::delete('/impoundedReport/{impoundedReportID}', [ImpoundedReportTrackingFormController::class, 'destroy']);
    Route::get('/impoundedReportTotal', [ImpoundedReportTrackingFormController::class, 'getTotalImpoundedReport']);
    Route::get('/generateImpoundedReportPdf/{reportID}', [ImpoundedReportTrackingFormController::class, 'generateImpoundedReportPdf']);
    Route::get('/unsubmittedImpoundedReport', [ImpoundedReportTrackingFormController::class, 'getUnsubmittedImpoundedReport']);
    Route::get('/activeImpoundedReport', [ImpoundedReportTrackingFormController::class, 'getActiveImpoundedReport']);
    Route::get('/resolvedImpoundedReport', [ImpoundedReportTrackingFormController::class, 'getResolvedImpoundedReport']);
    Route::get('/pendingImpoundedReport', [ImpoundedReportTrackingFormController::class, 'getPendingImpoundedReport']);

    //bomb threat
    Route::post('/initialize/bombThreats', [BombController::class, 'initialize']);
    Route::get('bombThreats', [BombController::class, 'index']);
    Route::post('bombThreats', [BombController::class, 'store']);
    Route::get('bombThreats/{bombThreatID}', [BombController::class, 'show']);
    Route::put('bombThreats/{bombThreatID}', [BombController::class, 'update']);
    Route::delete('bombThreats/{bombThreatID}', [BombController::class, 'destroy']);
    Route::get('bombThreatsTotal', [BombController::class, 'getTotalBombReport']);
    Route::get('/generateBombReportPdf/{reportID}', [BombController::class, 'generateBombReportPdf']);
    Route::get('/unsubmittedBombReports', [BombController::class, 'getUnsubmittedBombReports']);
    Route::get('/activeBombReports', [BombController::class, 'getActiveBombReports']);
    Route::get('/resolvedBombReports', [BombController::class, 'getResolvedBombReports']);
    Route::get('/pendingBombReports', [BombController::class, 'getPendingBombReports']);


    //Anonymous Reports
    Route::post('/initialize/anonymousReports', [AnonymousController::class, 'initialize']);
    Route::get('/anonymousReports', [AnonymousController::class, 'index']);
    Route::post('/anonymousReports', [AnonymousController::class, 'store']);
    Route::get('/anonymousReports/{anonymousReportID}', [AnonymousController::class, 'show']);
    Route::put('/anonymousReports/{anonymousReportID}', [AnonymousController::class, 'update']);
    Route::delete('/anonymousReports/{anonymousReportID}', [AnonymousController::class, 'destroy']);
    Route::get('/generateAnonymousReportPdf/{reportID}', [AnonymousController::class, 'generateAnonymousReportPdf']);
    Route::get('/unsubmittedAnonymousReports', [AnonymousController::class, 'getUnsubmittedAnonymousReports']);

    Route::post('/initialize/incidentLog', [IncidentLogController::class, 'initialize']);
    Route::get('/incidentLog', [IncidentLogController::class, 'index']);
    Route::post('/incidentLog', [IncidentLogController::class, 'store']);
    Route::get('/incidentLog/{incidentLogID}', [IncidentLogController::class, 'show']);
    Route::put('/incidentLog/{incidentLogID}', [IncidentLogController::class, 'update']);
    Route::delete('/incidentLog/{incidentLogID}', [IncidentLogController::class, 'destroy']);
    Route::get('/incidentLogTotal', [IncidentLogController::class, 'getTotalIncidentLog']);
    Route::get('/generateIncidentLogPdf/{reportID}', [IncidentLogController::class, 'generateIncidentLogPdf']);
    Route::get('/unsubmittedIncidentLog', [IncidentLogController::class, 'getUnsubmittedIncidentLog']);
    Route::get('/activeIncidentLog', [IncidentLogController::class, 'getActiveIncidentLog']);
    Route::get('/resolvedIncidentLog', [IncidentLogController::class, 'getResolvedIncidentLog']);
    Route::get('/pendingIncidentLog', [IncidentLogController::class, 'getPendingIncidentLog']);

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



Route::prefix('v1/public')->group(function () {
    //Anonymous Reports
    Route::post('/initialize/anonymousReports', [AnonymousController::class, 'initialize']);
    Route::get('/anonymousReports', [AnonymousController::class, 'index']);
    Route::post('/anonymousReports', [AnonymousController::class, 'store']);
    Route::get('/anonymousReports/{anonymousReportID}', [AnonymousController::class, 'show']);
    Route::put('/anonymousReports/{anonymousReportID}', [AnonymousController::class, 'update']);
    Route::delete('/anonymousReports/{anonymousReportID}', [AnonymousController::class, 'destroy']);
    Route::get('/generateAnonymousReportPdf/{reportID}', [AnonymousController::class, 'generateAnonymousReportPdf']);
    Route::get('/unsubmittedAnonymousReports', [AnonymousController::class, 'getUnsubmittedAnonymousReports']);

    //buildings routes students
    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::post('/buildings', [BuildingController::class, 'store']);
    Route::get('/buildings/{buildingID}', [BuildingController::class, 'show']);
    Route::put('/buildings/{buildingID}', [BuildingController::class, 'update']);
    Route::delete('/buildings/{buildingID}', [BuildingController::class, 'destroy']);


    //emergency
    Route::post('/initialize/emergency', [EmergencyController::class, 'initialize']);
    Route::get('/emergency', [EmergencyController::class, 'index']);
    Route::post('/emergency', [EmergencyController::class, 'store']);
    Route::get('/emergency/total', [EmergencyController::class, 'getTotalAlerts']);
    Route::get('/emergency/{emergencyReportID}', [EmergencyController::class, 'show']);
    Route::put('/emergency/{emergencyReportID}', [EmergencyController::class, 'update']);
    Route::delete('/emergency/{emergencyReportID}', [EmergencyController::class, 'destroy']);
    Route::get('/unread-count', [EmergencyController::class, 'unreadCount']);
    Route::post('/{reportID}/mark-as-read', [EmergencyController::class, 'markAsRead']);
});