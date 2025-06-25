<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GitHub\ConfigController;
use App\Http\Controllers\GitHub\FeatureController;
use App\Http\Controllers\GitHub\RepositoryController;
use App\Http\Controllers\Search\SearchController;
use App\Http\Controllers\CodespacesController;
use App\Http\Controllers\DeveloperCredentialController;
use App\Http\Controllers\Api\CodespacesController as ApiCodespacesController;
use App\Http\Controllers\Api\SniffingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// GitHub Integration Routes
Route::prefix('github')->middleware(['auth:sanctum'])->group(function () {
    // Config routes
    Route::get('/config', [ConfigController::class, 'index']);
    Route::post('/config', [ConfigController::class, 'store']);
    Route::put('/config/{key}', [ConfigController::class, 'update']);
    Route::delete('/config/{key}', [ConfigController::class, 'destroy']);

    // Feature routes
    Route::get('/features', [FeatureController::class, 'index']);
    Route::post('/features', [FeatureController::class, 'store']);
    Route::put('/features/{name}', [FeatureController::class, 'update']);
    Route::delete('/features/{name}', [FeatureController::class, 'destroy']);
    Route::post('/features/{name}/toggle', [FeatureController::class, 'toggle']);

    // Repository routes
    Route::get('/repositories', [RepositoryController::class, 'index']);
    Route::post('/repositories', [RepositoryController::class, 'store']);
    Route::put('/repositories/{name}', [RepositoryController::class, 'update']);
    Route::delete('/repositories/{name}', [RepositoryController::class, 'destroy']);
    Route::post('/repositories/{name}/sync', [RepositoryController::class, 'sync']);
});

// Web3 Modular Search Route
Route::post('/search', [SearchController::class, 'search']);

// Codespace Management Routes
Route::prefix('codespaces')->middleware(['auth:api', 'developer.credentials'])->group(function () {
    Route::get('/', [CodespacesController::class, 'index']);
    Route::post('/', [CodespacesController::class, 'create']);
    Route::delete('/{name}', [CodespacesController::class, 'delete']);
    Route::post('/{name}/rebuild', [CodespacesController::class, 'rebuild']);
    Route::get('/{name}/status', [CodespacesController::class, 'status']);
    Route::post('/{name}/connect', [CodespacesController::class, 'connect']);
});

// Developer Credentials Routes
Route::prefix('developer-credentials')->middleware(['auth:api'])->group(function () {
    Route::get('/', [DeveloperCredentialController::class, 'index']);
    Route::post('/', [DeveloperCredentialController::class, 'store']);
    Route::put('/{id}', [DeveloperCredentialController::class, 'update']);
    Route::delete('/{id}', [DeveloperCredentialController::class, 'destroy']);
    Route::post('/{id}/activate', [DeveloperCredentialController::class, 'activate']);
    Route::post('/{id}/deactivate', [DeveloperCredentialController::class, 'deactivate']);
});

// Codespaces API Routes
Route::prefix('codespaces')->group(function () {
    Route::get('health', [ApiCodespacesController::class, 'health']);
    Route::post('tests', [ApiCodespacesController::class, 'runTests']);
    Route::post('reports/generate', [ApiCodespacesController::class, 'generateReport']);
    Route::post('reports/save', [ApiCodespacesController::class, 'saveReport']);
});

// Sniffing System API Routes
Route::prefix('sniffing')->group(function () {
    Route::post('run', [SniffingController::class, 'run']);
    Route::get('results', [SniffingController::class, 'results']);
    Route::post('analyze', [SniffingController::class, 'analyze']);
    Route::post('rules', [SniffingController::class, 'rules']);
    Route::post('clear', [SniffingController::class, 'clear']);
}); 