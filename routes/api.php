<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\ReportTypeController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('reports/ip-eligibility', [ReportController::class, 'ipEligibility']);
Route::get('reports/eligibility', [ReportController::class, 'eligibility']);

Route::post('reports', [ReportController::class, 'store'])
    ->middleware(['throttle:6,1', 'lagos.only']);

Route::post('admin/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('reports', AdminReportController::class)->except(['store']);
    Route::apiResource('report-types', ReportTypeController::class)->except(['show']);
});
