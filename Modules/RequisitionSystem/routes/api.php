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
use Modules\RequisitionSystem\Http\Controllers\RequisitionLogController;

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

    Route::get('requisitions/dashboard-metrics', [RequisitionController::class, '__invoke']);
    Route::get('requisitions/recent', [RequisitionController::class, 'recent']);
    Route::get('requisitions/summary-by-cost-center', [RequisitionController::class, 'byCostCenter']);
    Route::apiResource('requisitions', RequisitionController::class);
    Route::post('requisitions/{requisition}/approve', [RequisitionController::class, 'approve']);
    Route::post('requisitions/{requisition}/reject', [RequisitionController::class, 'reject']);
    Route::post('requisitions/{requisition}/request-review', [RequisitionController::class, 'requestReview']);
    Route::post('requisitions/{requisition}/cancel', [RequisitionController::class, 'cancel']);
    Route::patch('requisitions/{requisition}/purchase-order-number', [RequisitionController::class, 'updatePurchaseOrderNumber']);
    Route::get('requisitions/{requisition}/logs', [RequisitionLogController::class, 'index']);
    Route::post('requisitions/{requisition}/logs', [RequisitionLogController::class, 'store']);

    Route::get('requisitions/{requisition}/attachments', [AttachmentController::class, 'index']);
    Route::post('requisitions/{requisition}/attachments', [AttachmentController::class, 'store']);
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show']);
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

    Route::apiResource('stages', StageController::class);
    Route::apiResource('statuses', StatusController::class);

    Route::get('suppliers/status-counts', [SupplierController::class, 'getStatusCounts']);
    Route::post('suppliers/quick', [SupplierController::class, 'quickStore']);
    Route::post('suppliers/{supplier}/approve', [SupplierController::class, 'approve']);
    Route::post('suppliers/{supplier}/reject', [SupplierController::class, 'reject']);
    Route::post('suppliers/{supplier}/activate', [SupplierController::class, 'activate']);
    Route::apiResource('suppliers', SupplierController::class);

    Route::apiResource('userStages', UserStageController::class);
    Route::apiResource('banks', BankController::class);
    Route::apiResource('addresses', AddressController::class);
    Route::apiResource('approvals', ApprovalController::class);
});
