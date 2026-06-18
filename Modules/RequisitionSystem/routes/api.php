<?php

use Illuminate\Support\Facades\Route;
use Modules\RequisitionSystem\Http\Controllers\RequisitionController;
use Modules\RequisitionSystem\Http\Controllers\StageController;
use Modules\RequisitionSystem\Http\Controllers\StatusController;
use Modules\RequisitionSystem\Http\Controllers\CurrencyController;
use Modules\RequisitionSystem\Http\Controllers\CountryController;
use Modules\RequisitionSystem\Http\Controllers\CostCenterController;
use Modules\RequisitionSystem\Http\Controllers\ItemController;
use Modules\RequisitionSystem\Http\Controllers\PipelineController;
use Modules\RequisitionSystem\Http\Controllers\SupplierController;
use Modules\RequisitionSystem\Http\Controllers\UserStageController;
use Modules\RequisitionSystem\Http\Controllers\BankController;
use Modules\RequisitionSystem\Http\Controllers\AttachmentController;
use Modules\RequisitionSystem\Http\Controllers\AddressController;
use Modules\RequisitionSystem\Http\Controllers\SupplierBankController;
use Modules\RequisitionSystem\Http\Controllers\ApprovalController;
use Modules\RequisitionSystem\Http\Controllers\ConversionRateController;

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
    'prefix' => 'v1/requisitionSystem',
    'namespace' => 'Modules\RequisitionSystem\Http\Controllers',
    'middleware' => 'auth:sanctum',
], function () {
    Route::get('costCenters/assigned/me', [CostCenterController::class, 'assignedToMe']);
    Route::apiResource('countries', CountryController::class);
    Route::apiResource('currencies', CurrencyController::class);
    Route::apiResource('costCenters', CostCenterController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('pipelines', PipelineController::class);

    Route::apiResource('requisitions', RequisitionController::class);
    Route::get('/cost-center', [RequisitionController::class, 'byCostCenter']);

    Route::apiResource('stages', StageController::class);
    Route::apiResource('statuses', StatusController::class);
    
    Route::get('suppliers/status-counts', [SupplierController::class, 'getStatusCounts']);
    Route::apiResource('suppliers', SupplierController::class);

    Route::apiResource('userStages', UserStageController::class);
    Route::apiResource('banks', BankController::class);
    Route::apiResource('addresses', AddressController::class);
    Route::apiResource('approvals', ApprovalController::class);
    Route::apiResource('conversionRates', ConversionRateController::class);

    Route::post('/upload', [AttachmentController::class, 'uploadRequisitionSystemPhoto']);
    Route::post('/signature', [AttachmentController::class, 'uploadSignatureCanvas']);
    Route::get('/download/{fileType}/{fileName}', [AttachmentController::class, 'downloadRequisitionSystemFile']);
});
