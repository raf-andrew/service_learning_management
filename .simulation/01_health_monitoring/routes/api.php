<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;

/*
|--------------------------------------------------------------------------
| Health Monitoring API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoints
Route::prefix('health')->group(function () {
    Route::get('/', [HealthCheckController::class, 'check']);
    Route::get('/{serviceName}', [HealthCheckController::class, 'serviceStatus']);
    Route::get('/{serviceName}/metrics', [HealthCheckController::class, 'metrics']);
    Route::get('/{serviceName}/alerts', [HealthCheckController::class, 'alerts']);
});

// Alert management endpoints
Route::prefix('alerts')->group(function () {
    Route::post('/{alertId}/acknowledge', [HealthCheckController::class, 'acknowledgeAlert']);
    Route::post('/{alertId}/resolve', [HealthCheckController::class, 'resolveAlert']);
}); 