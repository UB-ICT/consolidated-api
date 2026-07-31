<?php

use Illuminate\Support\Facades\Route;
use Modules\RequisitionSystem\Http\Controllers\RequisitionController;
use Modules\RequisitionSystem\Http\Controllers\RequisitionDashboardController;
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
use Modules\RequisitionSystem\Http\Controllers\BudgetController;
use Modules\RequisitionSystem\Http\Controllers\BudgetLogController;
use Modules\RequisitionSystem\Http\Controllers\BudgetYearController;
use Modules\RequisitionSystem\Http\Controllers\AttachmentController;
use Modules\RequisitionSystem\Http\Controllers\AddressController;
use Modules\RequisitionSystem\Http\Controllers\SupplierBankController;
use Modules\RequisitionSystem\Http\Controllers\ApprovalController;
use Modules\RequisitionSystem\Http\Controllers\ChartOfAccountController;
use Modules\RequisitionSystem\Http\Controllers\RequisitionLogController;
use Modules\RequisitionSystem\Http\Controllers\TagController;

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
    Route::put('pipelines/{pipeline}/stages', [PipelineController::class, 'syncStages']);
    Route::put('stages/{stage}/users', [PipelineController::class, 'syncStageUsers']);

    // Static paths must be registered before apiResource so they are not
    // captured as {requisition} route-model binding (e.g. "dashboard-metrics").
    Route::get('requisitions/dashboard-metrics', [RequisitionDashboardController::class, 'metrics']);
    Route::get('requisitions/recent', [RequisitionDashboardController::class, 'recent']);
    Route::get('requisitions/summary-by-cost-center', [RequisitionDashboardController::class, 'summaryByCostCenter']);

    Route::apiResource('requisitions', RequisitionController::class);
    Route::post('requisitions/{requisition}/approve', [RequisitionController::class, 'approve']);
    Route::post('requisitions/{requisition}/reject', [RequisitionController::class, 'reject']);
    Route::post('requisitions/{requisition}/request-review', [RequisitionController::class, 'requestReview']);
    Route::post('requisitions/{requisition}/cancel', [RequisitionController::class, 'cancel']);
    Route::post('requisitions/{requisition}/close', [RequisitionController::class, 'close']);
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
    Route::apiResource('chartOfAccounts', ChartOfAccountController::class);
    Route::apiResource('tags', TagController::class);

    Route::post('budgetYears/{budget_year}/open-submissions', [BudgetYearController::class, 'openSubmissions']);
    Route::post('budgetYears/{budget_year}/close-submissions', [BudgetYearController::class, 'closeSubmissions']);
    Route::apiResource('budgetYears', BudgetYearController::class);

    Route::get('budgets/{budget}/comparison', [BudgetController::class, 'comparison']);
    Route::post('budgets/import', [BudgetController::class, 'import']);
    Route::post('budgets/{budget}/submit', [BudgetController::class, 'submit']);
    Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve']);
    Route::post('budgets/{budget}/reject', [BudgetController::class, 'reject']);
    Route::post('budgets/{budget}/request-review', [BudgetController::class, 'requestReview']);
    Route::post('budgets/{budget}/activate', [BudgetController::class, 'activate']);
    Route::post('budgets/{budget}/deactivate', [BudgetController::class, 'deactivate']);
    Route::get('budgets/{budget}/logs', [BudgetLogController::class, 'index']);
    Route::post('budgets/{budget}/logs', [BudgetLogController::class, 'store']);
    Route::apiResource('budgets', BudgetController::class);

    Route::apiResource('addresses', AddressController::class);
    Route::apiResource('approvals', ApprovalController::class);
});
