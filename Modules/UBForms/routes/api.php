<?php

use Illuminate\Support\Facades\Route;
use Modules\UBForms\Http\Controllers\FacultyController;
use Modules\UBForms\Http\Controllers\StaffController;
use Modules\UBForms\Http\Controllers\HRStatistics;
use Modules\UBForms\Http\Controllers\FinanceStatistics;
use Modules\UBForms\Http\Controllers\RecordsStatistics;
use Modules\UBForms\Http\Controllers\FileUploadsController;
use Modules\UBForms\Http\Controllers\ReportController;
use Modules\UBForms\Http\Controllers\MenuController;
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

//This will be the only unprotected route because this is used for authentication



Route::group([
    'prefix' => 'v1/UBForms',
    'namespace' => 'Modules\UBForms\Http\Controllers',
    'middleware' => ['auth:sanctum', 'ubforms.user'],
], function () {
    //Initialize
    Route::post('/facultyInitialize', [FacultyController::class, 'initialize']); //This route should get the data that is passed in the UI 
    Route::post('/staffInitialize', [StaffController::class, 'initialize']); //This route should get the data that is passed in the UI 
    Route::post('/recordsInitialize', [RecordsStatistics::class, 'initialize']); //This route should get the data that is passed in the UI 
    Route::post('/HRInitialize', [HRStatistics::class, 'initialize']); //This route should get the data that is passed in the UI 
    Route::post('/financeInitialize', [FinanceStatistics::class, 'initialize']); //This route should get the data that is passed in the UI 

    //Create
    Route::post('/facultyReport', [FacultyController::class, 'store']); //This route should get the data that is passed in the UI 
    Route::post('/staffReport', [StaffController::class, 'store']); //This route should get the data that is passed in the UI 
    Route::post('/recordsReport', [RecordsStatistics::class, 'store']); //This route should get the data that is passed in the UI 
    Route::post('/HRReport', [HRStatistics::class, 'store']); //This route should get the data that is passed in the UI 
    Route::post('/financeReport', [FinanceStatistics::class, 'store']); //This route should get the data that is passed in the UI 

    //Read 
    Route::get('/facultyReport/{reportID}', [FacultyController::class, 'getReport']); //This route should get the data that is passed in the UI 
    Route::get('/facultyReportByUser', [FacultyController::class, 'getReportByUser']); //This route should get the data that is passed in the UI 
    Route::get('/staffReport/{reportID}', [StaffController::class, 'getReport']); //This route should get the data that is passed in the UI 
    Route::get('/staffReportByUser', [StaffController::class, 'getReportByUser']); //This route should get the data that is passed in the UI 
    Route::get('/recordsReport/{reportID}', [RecordsStatistics::class, 'getReport']); //This route should get the data that is passed in the UI 
    Route::get('/recordsReportByUser', [RecordsStatistics::class, 'getReportByUser']); //This route should get the data that is passed in the UI 
    Route::get('/HRReport/{reportID}', [HRStatistics::class, 'getReport']); //This route should get the data that is passed in the UI 
    Route::get('/HRReportByUser', [HRStatistics::class, 'getReportByUser']); //This route should get the data that is passed in the UI H
    Route::get('/financeReport/{reportID}', [FinanceStatistics::class, 'getReport']); //This route should get the data that is passed in the UI 
    Route::get('/financeReportByUser', [FinanceStatistics::class, 'getReportByUser']); //This route should get the data that is passed in the UI H

    //Update
    Route::put('/facultyReport', [FacultyController::class, 'updateReport']);
    Route::put('/staffReport', [StaffController::class, 'updateReport']);
    Route::put('/recordsReport', [RecordsStatistics::class, 'updateReport']);
    Route::put('/HRReport', [HRStatistics::class, 'updateReport']);
    Route::put('/financeReport', [FinanceStatistics::class, 'updateReport']);

    //Delete
    Route::delete('/facultyReport', [FacultyController::class, 'delReport']);
    Route::delete('/staffReport', [StaffController::class, 'delReport']);
    Route::delete('/recordsReport', [RecordsStatistics::class, 'delReport']);
    Route::delete('/HRReport', [HRStatistics::class, 'delReport']);
    Route::delete('/financeReport', [FinanceStatistics::class, 'delReport']);

    //Upload files
    Route::post('/uploadPhoto', [FileUploadsController::class, 'uploadEventPhoto']); //This route should get the data that is passed in the UI 
    Route::post('/uploadMeetings', [FileUploadsController::class, 'uploadMeetingMinutes']); //This route should get the data that is passed in the UI 

    /*Download files
     The name of the contoller if FileUploadsController but I thought I could add in the download function in there one time 
     as opposed to creating another controller for one function
     You will pass the kind of file to download, by this I mean, you will pass if its a meeting or photo file, and file name(to download)*/
    Route::get('/getFile/{fileType}/{fileName}', [FileUploadsController::class, 'downloadFile']);

    /*Generate pdf file*/
    Route::get('/generateStaffPdf/{reportID}', [StaffController::class, 'generateStaffPdf']);
    Route::get('/generateFacultyPdf/{reportID}', [FacultyController::class, 'generateFacultyPdf']);
    Route::get('/generateHRPdf/{reportID}', [HRStatistics::class, 'generateHRPdf']);
    Route::get('/generateFinancePdf/{reportID}', [FinanceStatistics::class, 'generateFinancePdf']);
    Route::get('/generateRecordsPdf/{reportID}', [RecordsStatistics::class, 'generateRecordsPdf']);

    /*Return list of Reports*/
    Route::get('/allReports/{reportTypes}', [ReportController::class, 'getReports']);
    Route::get('reportsByAcademicYear/{reportTypes}/{academicYear}', [ReportController::class, 'getReportsByAcademicYear']);
    Route::get('TotalFormSubmissionsByAcademicYear/{reportTypes}/{academicYear}', [ReportController::class, 'getTotalFormSubmissionsByAcademicYear']);

    //menus
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/menu', [MenuController::class, 'store']);
    Route::put('/menu/{id}', [MenuController::class, 'update']);
    Route::delete('/menu/{id}', [MenuController::class, 'destroy']);
});
