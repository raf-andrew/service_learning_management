<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CodespaceController;
use App\Http\Controllers\DeveloperCredentialController;

/*
|--------------------------------------------------------------------------
| Codespace Routes
|--------------------------------------------------------------------------
|
| These routes handle all Codespace-related functionality, including
| management, deployment, and developer credential management.
|
*/

// Codespace Management Routes
Route::prefix('codespaces')->middleware(['auth:api', 'developer.credentials'])->group(function () {
    Route::get('/', [CodespaceController::class, 'index']);
    Route::post('/', [CodespaceController::class, 'create']);
    Route::delete('/{name}', [CodespaceController::class, 'delete']);
    Route::post('/{name}/rebuild', [CodespaceController::class, 'rebuild']);
    Route::get('/{name}/status', [CodespaceController::class, 'status']);
    Route::post('/{name}/connect', [CodespaceController::class, 'connect']);
    Route::get('/regions', [CodespaceController::class, 'getRegions']);
    Route::get('/machines', [CodespaceController::class, 'getMachines']);
});

// Developer Credentials Routes
Route::prefix('developer-credentials')->middleware(['auth:api'])->group(function () {
    Route::get('/', [DeveloperCredentialController::class, 'index']);
    Route::post('/', [DeveloperCredentialController::class, 'store']);
    Route::put('/{id}', [DeveloperCredentialController::class, 'update']);
    Route::delete('/{id}', [DeveloperCredentialController::class, 'destroy']);
    Route::post('/{id}/activate', [DeveloperCredentialController::class, 'activate']);
    Route::post('/{id}/deactivate', [DeveloperCredentialController::class, 'deactivate']);
    Route::get('/active', [DeveloperCredentialController::class, 'getActive']);
}); 