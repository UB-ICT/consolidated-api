<?php

use Illuminate\Support\Facades\Route;
use Modules\RequisitionSystem\Http\Controllers\AddressController;
use Modules\RequisitionSystem\Http\Controllers\ApprovalController;
use Modules\RequisitionSystem\Http\Controllers\AttachmentController;
use Modules\RequisitionSystem\Http\Controllers\BankController;
use Modules\RequisitionSystem\Http\Controllers\ConversionRateController;
use Modules\RequisitionSystem\Http\Controllers\RequisitionSystemController;
use Modules\RequisitionSystem\Http\Controllers\CostCenterController;
use Modules\RequisitionSystem\Http\Controllers\CountryController;
use Modules\RequisitionSystem\Http\Controllers\CurrencyController;
use Modules\RequisitionSystem\Http\Controllers\ItemController;
use Modules\RequisitionSystem\Http\Controllers\PipelineController;
use Modules\RequisitionSystem\Http\Controllers\RequisitionController;
use Modules\RequisitionSystem\Http\Controllers\StageController;
use Modules\RequisitionSystem\Http\Controllers\StatusController;
use Modules\RequisitionSystem\Http\Controllers\SupplierBankController;
use Modules\RequisitionSystem\Http\Controllers\SupplierController;
use Modules\RequisitionSystem\Http\Controllers\UserController;
use Modules\RequisitionSystem\Http\Controllers\UserStageController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('requisitionsystem', RequisitionSystemController::class)->names('requisitionsystem');
});

// The "Read" route (GET)
Route::get('/addresses', [AddressController::class, 'index']);
Route::get('/addresses/{id}', [AddressController::class, 'show']);
Route::get('/approvals', [ApprovalController::class, 'index']);
Route::get('/approvals/{id}', [ApprovalController::class, 'show']);
Route::get('/attachments', [AttachmentController::class, 'index']);
Route::get('/attachments/{id}', [AttachmentController::class, 'show']);
Route::get('/banks', [BankController::class, 'index']);
Route::get('/banks/{id}', [BankController::class, 'show']);
Route::get('/conversion-rates', [ConversionRateController::class, 'index']);
Route::get('/conversion-rates/{id}', [ConversionRateController::class, 'show']);
Route::get('/cost-centers', [CostCenterController::class, 'index']);
Route::get('/cost-centers/{id}', [CostCenterController::class, 'show']);
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{id}', [CountryController::class, 'show']);
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::get('/currencies/{id}', [CurrencyController::class, 'show']);
Route::get('/items', [ItemController::class, 'index']);
Route::get('/items/{id}', [ItemController::class, 'show']);
Route::get('/pipelines', [PipelineController::class, 'index']);
Route::get('/pipelines/{id}', [PipelineController::class, 'show']);
Route::get('/requisitions', [RequisitionController::class, 'index']);
Route::get('/requisitions/{id}', [RequisitionController::class, 'show']);
Route::get('/stages', [StageController::class, 'index']);
Route::get('/stages/{id}', [StageController::class, 'show']);
Route::get('/statuses', [StatusController::class, 'index']);
Route::get('/statuses/{id}', [StatusController::class, 'show']);
Route::get('/supplier-banks', [SupplierBankController::class, 'index']);
Route::get('/supplier-banks/{id}', [SupplierBankController::class, 'show']);
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/user-stages', [UserStageController::class, 'index']);
Route::get('/user-stages/{id}', [UserStageController::class, 'show']);

// The "Create" route (POST)
Route::post('/addresses', [AddressController::class, 'store']);
Route::post('/approvals', [ApprovalController::class, 'store']);
Route::post('/attachments', [AttachmentController::class, 'store']);
Route::post('/banks', [BankController::class, 'store']);
Route::post('/conversion-rates', [ConversionRateController::class, 'store']);
Route::post('/cost-centers', [CostCenterController::class, 'store']);
Route::post('/countries', [CountryController::class, 'store']);
Route::post('/currencies', [CurrencyController::class, 'store']);
Route::post('/items', [ItemController::class, 'store']);
Route::post('/pipelines', [PipelineController::class, 'store']);
Route::post('/requisitions', [RequisitionController::class, 'store']);
Route::post('/stages', [StageController::class, 'store']);
Route::post('/statuses', [StatusController::class, 'store']);
Route::post('/supplier-banks', [SupplierBankController::class, 'store']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::post('/users', [UserController::class, 'store']);
Route::post('/user-stages', [UserStageController::class, 'store']);

// The "Update" route (PUT/PATCH)
Route::put('/addresses/{id}', [AddressController::class, 'update']);
Route::patch('/addresses/{id}', [AddressController::class, 'update']);
Route::put('/approvals/{id}', [ApprovalController::class, 'update']);
Route::patch('/approvals/{id}', [ApprovalController::class, 'update']);
Route::put('/attachments/{id}', [AttachmentController::class, 'update']);
Route::patch('/attachments/{id}', [AttachmentController::class, 'update']);
Route::put('/banks/{id}', [BankController::class, 'update']);
Route::patch('/banks/{id}', [BankController::class, 'update']);
Route::put('/conversion-rates/{id}', [ConversionRateController::class, 'update']);
Route::patch('/conversion-rates/{id}', [ConversionRateController::class, 'update']);
Route::put('/cost-centers/{id}', [CostCenterController::class, 'update']);
Route::patch('/cost-centers/{id}', [CostCenterController::class, 'update']);
Route::put('/countries/{id}', [CountryController::class, 'update']);
Route::patch('/countries/{id}', [CountryController::class, 'update']);
Route::put('/currencies/{id}', [CurrencyController::class, 'update']);
Route::patch('/currencies/{id}', [CurrencyController::class, 'update']);
Route::put('/items/{id}', [ItemController::class, 'update']);
Route::patch('/items/{id}', [ItemController::class, 'update']);
Route::put('/pipelines/{id}', [PipelineController::class, 'update']);
Route::patch('/pipelines/{id}', [PipelineController::class, 'update']);
Route::put('/requisitions/{id}', [RequisitionController::class, 'update']);
Route::patch('/requisitions/{id}', [RequisitionController::class, 'update']);
Route::put('/stages/{id}', [StageController::class, 'update']);
Route::patch('/stages/{id}', [StageController::class, 'update']);
Route::put('/statuses/{id}', [StatusController::class, 'update']);
Route::patch('/statuses/{id}', [StatusController::class, 'update']);
Route::put('/supplier-banks/{id}', [SupplierBankController::class, 'update']);
Route::patch('/supplier-banks/{id}', [SupplierBankController::class, 'update']);
Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
Route::patch('/suppliers/{id}', [SupplierController::class, 'update']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}', [UserController::class, 'update']);
Route::put('/user-stages/{id}', [UserStageController::class, 'update']);
Route::patch('/user-stages/{id}', [UserStageController::class, 'update']);

// The "Delete" route (DELETE)
Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
Route::delete('/approvals/{id}', [ApprovalController::class, 'destroy']);
Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);
Route::delete('/banks/{id}', [BankController::class, 'destroy']);
Route::delete('/conversion-rates/{id}', [ConversionRateController::class, 'destroy']);
Route::delete('/cost-centers/{id}', [CostCenterController::class, 'destroy']);
Route::delete('/countries/{id}', [CountryController::class, 'destroy']);
Route::delete('/currencies/{id}', [CurrencyController::class, 'destroy']);
Route::delete('/items/{id}', [ItemController::class, 'destroy']);
Route::delete('/pipelines/{id}', [PipelineController::class, 'destroy']);
Route::delete('/requisitions/{id}', [RequisitionController::class, 'destroy']);
Route::delete('/stages/{id}', [StageController::class, 'destroy']);
Route::delete('/statuses/{id}', [StatusController::class, 'destroy']);
Route::delete('/supplier-banks/{id}', [SupplierBankController::class, 'destroy']);
Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::delete('/user-stages/{id}', [UserStageController::class, 'destroy']);
