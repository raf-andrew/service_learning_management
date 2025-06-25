<?php

use Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\DocumentationController;
use Modules\Api\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Web Routes for API Module
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the API module. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// API Documentation Web Interface
Route::prefix('api-docs')->name('api.docs.')->group(function () {
    
    // Main documentation page
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    
    // Interactive API documentation
    Route::get('/interactive', [DocumentationController::class, 'interactive'])->name('interactive');
    
    // API reference
    Route::get('/reference', [DocumentationController::class, 'reference'])->name('reference');
    
    // Getting started guide
    Route::get('/getting-started', [DocumentationController::class, 'gettingStarted'])->name('getting-started');
    
    // Authentication guide
    Route::get('/authentication', [DocumentationController::class, 'authentication'])->name('authentication');
    
    // Rate limiting guide
    Route::get('/rate-limiting', [DocumentationController::class, 'rateLimiting'])->name('rate-limiting');
    
    // Error codes
    Route::get('/errors', [DocumentationController::class, 'errors'])->name('errors');
    
    // Examples
    Route::get('/examples', [DocumentationController::class, 'examples'])->name('examples');
    
    // SDK downloads
    Route::get('/sdk', [DocumentationController::class, 'sdk'])->name('sdk');
    
    // Changelog
    Route::get('/changelog', [DocumentationController::class, 'changelog'])->name('changelog');
});

// API Management Web Interface (Admin only)
Route::prefix('api-management')->name('api.management.')->middleware(['auth', 'admin'])->group(function () {
    
    // API dashboard
    Route::get('/', [ApiController::class, 'dashboard'])->name('dashboard');
    
    // API keys management
    Route::prefix('keys')->name('keys.')->group(function () {
        Route::get('/', [ApiController::class, 'keysIndex'])->name('index');
        Route::get('/create', [ApiController::class, 'keysCreate'])->name('create');
        Route::post('/', [ApiController::class, 'keysStore'])->name('store');
        Route::get('/{key}/edit', [ApiController::class, 'keysEdit'])->name('edit');
        Route::put('/{key}', [ApiController::class, 'keysUpdate'])->name('update');
        Route::delete('/{key}', [ApiController::class, 'keysDelete'])->name('delete');
    });
    
    // API usage analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [ApiController::class, 'analyticsIndex'])->name('index');
        Route::get('/usage', [ApiController::class, 'analyticsUsage'])->name('usage');
        Route::get('/performance', [ApiController::class, 'analyticsPerformance'])->name('performance');
        Route::get('/errors', [ApiController::class, 'analyticsErrors'])->name('errors');
        Route::get('/users', [ApiController::class, 'analyticsUsers'])->name('users');
    });
    
    // API settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [ApiController::class, 'settingsIndex'])->name('index');
        Route::put('/general', [ApiController::class, 'settingsGeneral'])->name('general');
        Route::put('/rate-limiting', [ApiController::class, 'settingsRateLimiting'])->name('rate-limiting');
        Route::put('/authentication', [ApiController::class, 'settingsAuthentication'])->name('authentication');
        Route::put('/versioning', [ApiController::class, 'settingsVersioning'])->name('versioning');
    });
    
    // API logs
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [ApiController::class, 'logsIndex'])->name('index');
        Route::get('/requests', [ApiController::class, 'logsRequests'])->name('requests');
        Route::get('/errors', [ApiController::class, 'logsErrors'])->name('errors');
        Route::get('/security', [ApiController::class, 'logsSecurity'])->name('security');
    });
});

// API Testing Interface
Route::prefix('api-testing')->name('api.testing.')->middleware(['auth'])->group(function () {
    
    // API testing console
    Route::get('/', [ApiController::class, 'testingIndex'])->name('index');
    
    // Test specific endpoints
    Route::prefix('endpoints')->name('endpoints.')->group(function () {
        Route::get('/users', [ApiController::class, 'testingUsers'])->name('users');
        Route::get('/auth', [ApiController::class, 'testingAuth'])->name('auth');
        Route::get('/system', [ApiController::class, 'testingSystem'])->name('system');
        Route::get('/modules', [ApiController::class, 'testingModules'])->name('modules');
    });
    
    // Test results
    Route::get('/results', [ApiController::class, 'testingResults'])->name('results');
    Route::get('/history', [ApiController::class, 'testingHistory'])->name('history');
});

// API Status Page (Public)
Route::prefix('api-status')->name('api.status.')->group(function () {
    
    // Public status page
    Route::get('/', [ApiController::class, 'statusIndex'])->name('index');
    
    // Detailed status
    Route::get('/detailed', [ApiController::class, 'statusDetailed'])->name('detailed');
    
    // Historical status
    Route::get('/history', [ApiController::class, 'statusHistory'])->name('history');
    
    // Incident reports
    Route::get('/incidents', [ApiController::class, 'statusIncidents'])->name('incidents');
});

// API Developer Portal
Route::prefix('api-portal')->name('api.portal.')->middleware(['auth'])->group(function () {
    
    // Developer dashboard
    Route::get('/', [ApiController::class, 'portalIndex'])->name('index');
    
    // My API keys
    Route::prefix('keys')->name('keys.')->group(function () {
        Route::get('/', [ApiController::class, 'portalKeys'])->name('index');
        Route::post('/', [ApiController::class, 'portalCreateKey'])->name('store');
        Route::delete('/{key}', [ApiController::class, 'portalDeleteKey'])->name('delete');
    });
    
    // My usage
    Route::prefix('usage')->name('usage.')->group(function () {
        Route::get('/', [ApiController::class, 'portalUsage'])->name('index');
        Route::get('/analytics', [ApiController::class, 'portalAnalytics'])->name('analytics');
        Route::get('/limits', [ApiController::class, 'portalLimits'])->name('limits');
    });
    
    // My applications
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApiController::class, 'portalApplications'])->name('index');
        Route::get('/create', [ApiController::class, 'portalCreateApp'])->name('create');
        Route::post('/', [ApiController::class, 'portalStoreApp'])->name('store');
        Route::get('/{app}/edit', [ApiController::class, 'portalEditApp'])->name('edit');
        Route::put('/{app}', [ApiController::class, 'portalUpdateApp'])->name('update');
        Route::delete('/{app}', [ApiController::class, 'portalDeleteApp'])->name('delete');
    });
}); 