<?php

use App\Http\Controllers\BranchSyncController;
use App\Http\Middleware\AuthenticateBranchSync;
use Illuminate\Support\Facades\Route;

Route::prefix('api/branch-sync/v1')->middleware([AuthenticateBranchSync::class, 'throttle:120,1'])->group(function () {
    Route::get('references', [BranchSyncController::class, 'references']);
    Route::get('bootstrap', [BranchSyncController::class, 'bootstrap']);
    Route::post('ready', [BranchSyncController::class, 'ready']);
    Route::post('snapshots', [BranchSyncController::class, 'push']);
    Route::post('files/check', [BranchSyncController::class, 'missingFiles']);
    Route::post('files/{hash}', [BranchSyncController::class, 'upload']);
    Route::get('files/{hash}', [BranchSyncController::class, 'download']);
});
