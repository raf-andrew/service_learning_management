<?php

use Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\ApiController;
use Modules\Api\Controllers\HealthController;
use Modules\Api\Controllers\VersionController;
use Modules\Api\Controllers\DocumentationController;

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

// Public API routes (no authentication required)
Route::prefix('api')->name('api.public.')->group(function () {
    
    // Health check endpoint
    Route::get('/health', [HealthController::class, 'check'])->name('health');
    
    // API status endpoint
    Route::get('/status', [HealthController::class, 'status'])->name('status');
    
    // API version information
    Route::get('/version', [VersionController::class, 'info'])->name('version');
    
    // API documentation
    Route::get('/docs', [DocumentationController::class, 'index'])->name('docs');
    Route::get('/docs/openapi', [DocumentationController::class, 'openapi'])->name('docs.openapi');
    
    // Public endpoints
    Route::prefix('public')->name('public.')->group(function () {
        Route::get('/info', [ApiController::class, 'publicInfo'])->name('info');
        Route::get('/features', [ApiController::class, 'publicFeatures'])->name('features');
    });
});

// Protected API routes (authentication required)
Route::prefix('api')->name('api.protected.')->middleware(['api.auth', 'api.rate.limit'])->group(function () {
    
    // API information
    Route::get('/info', [ApiController::class, 'info'])->name('info');
    Route::get('/stats', [ApiController::class, 'stats'])->name('stats');
    Route::get('/limits', [ApiController::class, 'limits'])->name('limits');
    
    // User management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [ApiController::class, 'users'])->name('index');
        Route::get('/{user}', [ApiController::class, 'user'])->name('show');
        Route::post('/', [ApiController::class, 'createUser'])->name('store');
        Route::put('/{user}', [ApiController::class, 'updateUser'])->name('update');
        Route::delete('/{user}', [ApiController::class, 'deleteUser'])->name('delete');
    });
    
    // Profile management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ApiController::class, 'profile'])->name('show');
        Route::put('/', [ApiController::class, 'updateProfile'])->name('update');
        Route::put('/password', [ApiController::class, 'updatePassword'])->name('password');
    });
    
    // Authentication
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [ApiController::class, 'login'])->name('login');
        Route::post('/logout', [ApiController::class, 'logout'])->name('logout');
        Route::post('/refresh', [ApiController::class, 'refresh'])->name('refresh');
        Route::post('/verify', [ApiController::class, 'verify'])->name('verify');
    });
    
    // API keys management
    Route::prefix('keys')->name('keys.')->group(function () {
        Route::get('/', [ApiController::class, 'apiKeys'])->name('index');
        Route::post('/', [ApiController::class, 'createApiKey'])->name('store');
        Route::delete('/{key}', [ApiController::class, 'revokeApiKey'])->name('delete');
    });
    
    // System information
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/info', [ApiController::class, 'systemInfo'])->name('info');
        Route::get('/config', [ApiController::class, 'systemConfig'])->name('config');
        Route::get('/modules', [ApiController::class, 'modules'])->name('modules');
    });
});

// Admin API routes (admin authentication required)
Route::prefix('api/admin')->name('api.admin.')->middleware(['api.auth', 'api.rate.limit', 'admin'])->group(function () {
    
    // Admin dashboard
    Route::get('/dashboard', [ApiController::class, 'adminDashboard'])->name('dashboard');
    
    // System management
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/logs', [ApiController::class, 'systemLogs'])->name('logs');
        Route::get('/cache', [ApiController::class, 'cacheInfo'])->name('cache');
        Route::post('/cache/clear', [ApiController::class, 'clearCache'])->name('cache.clear');
        Route::get('/queue', [ApiController::class, 'queueInfo'])->name('queue');
        Route::post('/queue/restart', [ApiController::class, 'restartQueue'])->name('queue.restart');
    });
    
    // User management (admin)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [ApiController::class, 'adminUsers'])->name('index');
        Route::post('/', [ApiController::class, 'adminCreateUser'])->name('store');
        Route::put('/{user}', [ApiController::class, 'adminUpdateUser'])->name('update');
        Route::delete('/{user}', [ApiController::class, 'adminDeleteUser'])->name('delete');
        Route::post('/{user}/activate', [ApiController::class, 'activateUser'])->name('activate');
        Route::post('/{user}/deactivate', [ApiController::class, 'deactivateUser'])->name('deactivate');
    });
    
    // API management
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/keys', [ApiController::class, 'adminApiKeys'])->name('keys');
        Route::post('/keys', [ApiController::class, 'adminCreateApiKey'])->name('keys.store');
        Route::delete('/keys/{key}', [ApiController::class, 'adminRevokeApiKey'])->name('keys.delete');
        Route::get('/usage', [ApiController::class, 'apiUsage'])->name('usage');
        Route::get('/analytics', [ApiController::class, 'apiAnalytics'])->name('analytics');
    });
});

// Module-specific API routes
Route::prefix('api/modules')->name('api.modules.')->middleware(['api.auth', 'api.rate.limit'])->group(function () {
    
    // E2EE module routes
    Route::prefix('e2ee')->name('e2ee.')->group(function () {
        Route::get('/status', [ApiController::class, 'e2eeStatus'])->name('status');
        Route::post('/keys/generate', [ApiController::class, 'generateE2eeKey'])->name('keys.generate');
        Route::get('/keys/status', [ApiController::class, 'e2eeKeyStatus'])->name('keys.status');
    });
    
    // SOC2 module routes
    Route::prefix('soc2')->name('soc2.')->group(function () {
        Route::get('/status', [ApiController::class, 'soc2Status'])->name('status');
        Route::get('/certifications', [ApiController::class, 'soc2Certifications'])->name('certifications');
        Route::get('/reports', [ApiController::class, 'soc2Reports'])->name('reports');
    });
    
    // Auth module routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/roles', [ApiController::class, 'authRoles'])->name('roles');
        Route::get('/permissions', [ApiController::class, 'authPermissions'])->name('permissions');
        Route::post('/roles/assign', [ApiController::class, 'assignRole'])->name('roles.assign');
        Route::post('/permissions/assign', [ApiController::class, 'assignPermission'])->name('permissions.assign');
    });
    
    // MCP module routes
    Route::prefix('mcp')->name('mcp.')->group(function () {
        Route::get('/status', [ApiController::class, 'mcpStatus'])->name('status');
        Route::get('/connections', [ApiController::class, 'mcpConnections'])->name('connections');
    });
    
    // Web3 module routes
    Route::prefix('web3')->name('web3.')->group(function () {
        Route::get('/status', [ApiController::class, 'web3Status'])->name('status');
        Route::get('/contracts', [ApiController::class, 'web3Contracts'])->name('contracts');
        Route::post('/deploy', [ApiController::class, 'deployContract'])->name('deploy');
    });
});

// Versioned API routes
Route::prefix('api/v1')->name('api.v1.')->middleware(['api.auth', 'api.rate.limit', 'api.version'])->group(function () {
    
    // V1 specific endpoints
    Route::get('/legacy', [ApiController::class, 'legacyEndpoint'])->name('legacy');
    
});

Route::prefix('api/v2')->name('api.v2.')->middleware(['api.auth', 'api.rate.limit', 'api.version'])->group(function () {
    
    // V2 specific endpoints
    Route::get('/enhanced', [ApiController::class, 'enhancedEndpoint'])->name('enhanced');
    
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'code' => 404,
        'timestamp' => now()->toISOString(),
    ], 404);
}); 