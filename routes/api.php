<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImportExportController;
use App\Http\Controllers\Api\SystemPartController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamPartController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users (System Admin only)
    Route::apiResource('users', UserController::class);

    // Teams
    Route::apiResource('teams', TeamController::class);

    // System Parts
    Route::get('/system-parts/part-types', [SystemPartController::class, 'partTypes']);
    Route::get('/system-parts/manufacturers', [SystemPartController::class, 'manufacturers']);
    Route::apiResource('system-parts', SystemPartController::class);

    // Team Parts
    Route::post('/team-parts/bulk-associate', [TeamPartController::class, 'bulkAssociate']);
    Route::apiResource('team-parts', TeamPartController::class);

    // Import/Export
    Route::prefix('import-export')->group(function () {
        // System Parts
        Route::post('/system-parts/import', [ImportExportController::class, 'importSystemParts']);
        Route::get('/system-parts/export', [ImportExportController::class, 'exportSystemParts']);

        // Team Pricing
        Route::post('/team-pricing/import', [ImportExportController::class, 'importTeamPricing']);
        Route::get('/team-pricing/export', [ImportExportController::class, 'exportTeamPricing']);
    });
});
