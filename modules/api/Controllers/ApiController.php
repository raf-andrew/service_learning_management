<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    /**
     * Get API information
     */
    public function info(): JsonResponse
    {
        return response()->apiSuccess([
            'name' => config('app.name'),
            'version' => config('modules.api.versioning.current', 'v1'),
            'environment' => config('app.env'),
            'timestamp' => now()->toISOString(),
            'uptime' => $this->getUptime(),
            'modules' => $this->getActiveModules(),
        ], 'API Information retrieved successfully');
    }

    /**
     * Get API statistics
     */
    public function stats(): JsonResponse
    {
        return response()->apiSuccess([
            'requests' => $this->getRequestStats(),
            'users' => $this->getUserStats(),
            'performance' => $this->getPerformanceStats(),
            'errors' => $this->getErrorStats(),
        ], 'API Statistics retrieved successfully');
    }

    /**
     * Get API rate limits
     */
    public function limits(): JsonResponse
    {
        $user = Auth::user();
        $userType = $this->getUserType($user);
        $limits = config('modules.api.rate_limiting.limits', []);
        
        return response()->apiSuccess([
            'user_type' => $userType,
            'limits' => $limits[$userType] ?? config('modules.api.rate_limiting.default_limit', 60),
            'window' => $limits[$userType]['window'] ?? config('modules.api.rate_limiting.default_window', 60),
            'current_usage' => $this->getCurrentUsage($user),
        ], 'Rate limits retrieved successfully');
    }

    /**
     * Get public API information
     */
    public function publicInfo(): JsonResponse
    {
        return response()->apiSuccess([
            'name' => config('app.name'),
            'description' => 'Service Learning Management API',
            'version' => config('modules.api.versioning.current', 'v1'),
            'documentation_url' => route('api.docs.index'),
            'status_url' => route('api.status.index'),
            'features' => $this->getPublicFeatures(),
        ], 'Public API information retrieved successfully');
    }

    /**
     * Get public features
     */
    public function publicFeatures(): JsonResponse
    {
        return response()->apiSuccess([
            'authentication' => [
                'methods' => ['bearer_token', 'api_key', 'basic'],
                'oauth' => config('modules.api.integrations.oauth.enabled', false),
            ],
            'rate_limiting' => config('modules.api.rate_limiting.enabled', true),
            'versioning' => config('modules.api.versioning.enabled', true),
            'documentation' => config('modules.api.documentation.enabled', true),
            'modules' => $this->getPublicModules(),
        ], 'Public features retrieved successfully');
    }

    /**
     * Get users list
     */
    public function users(Request $request): JsonResponse
    {
        $users = \App\Models\User::with(['roles', 'permissions'])
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate($request->per_page ?? 15);

        return response()->apiPaginated($users, 'Users retrieved successfully');
    }

    /**
     * Get specific user
     */
    public function user(\App\Models\User $user): JsonResponse
    {
        $user->load(['roles', 'permissions']);
        
        return response()->apiSuccess($user, 'User retrieved successfully');
    }

    /**
     * Create new user
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|strong_password',
            'role' => 'nullable|string|role_exists',
        ]);

        if ($validator->fails()) {
            return response()->apiError('Validation failed', 422, $validator->errors());
        }

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        if ($request->role) {
            $user->assignRole($request->role);
        }

        return response()->apiSuccess($user, 'User created successfully', 201);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, \App\Models\User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'nullable|string|role_exists',
        ]);

        if ($validator->fails()) {
            return response()->apiError('Validation failed', 422, $validator->errors());
        }

        $user->update($request->only(['name', 'email']));

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->apiSuccess($user, 'User updated successfully');
    }

    /**
     * Delete user
     */
    public function deleteUser(\App\Models\User $user): JsonResponse
    {
        $user->delete();
        
        return response()->apiSuccess(null, 'User deleted successfully');
    }

    /**
     * Get user profile
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['roles', 'permissions']);
        
        return response()->apiSuccess($user, 'Profile retrieved successfully');
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->apiError('Validation failed', 422, $validator->errors());
        }

        $user->update($request->only(['name', 'email']));

        return response()->apiSuccess($user, 'Profile updated successfully');
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|strong_password|different:current_password',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->apiError('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();

        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->apiError('Current password is incorrect', 422);
        }

        $user->update(['password' => bcrypt($request->new_password)]);

        return response()->apiSuccess(null, 'Password updated successfully');
    }

    /**
     * API login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->apiError('Validation failed', 422, $validator->errors());
        }

        if (Auth::attempt($request->only(['email', 'password']))) {
            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->apiSuccess([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration', 60 * 24 * 60), // 60 days
            ], 'Login successful');
        }

        return response()->apiError('Invalid credentials', 401);
    }

    /**
     * API logout
     */
    public function logout(): JsonResponse
    {
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->apiSuccess(null, 'Logout successful');
    }

    /**
     * Refresh token
     */
    public function refresh(): JsonResponse
    {
        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->apiSuccess([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 60 * 24 * 60),
        ], 'Token refreshed successfully');
    }

    /**
     * Verify token
     */
    public function verify(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->apiSuccess([
            'valid' => true,
            'user' => $user,
        ], 'Token is valid');
    }

    /**
     * Get API keys
     */
    public function apiKeys(): JsonResponse
    {
        $user = Auth::user();
        // Implement API key retrieval logic here
        
        return response()->apiSuccess([], 'API keys retrieved successfully');
    }

    /**
     * Create API key
     */
    public function createApiKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->apiError('Validation failed', 422, $validator->errors());
        }

        // Implement API key creation logic here
        
        return response()->apiSuccess([], 'API key created successfully', 201);
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(string $key): JsonResponse
    {
        // Implement API key revocation logic here
        
        return response()->apiSuccess(null, 'API key revoked successfully');
    }

    /**
     * Get system information
     */
    public function systemInfo(): JsonResponse
    {
        return response()->apiSuccess([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => $this->getDatabaseInfo(),
            'cache' => $this->getCacheInfo(),
            'queue' => $this->getQueueInfo(),
            'storage' => $this->getStorageInfo(),
        ], 'System information retrieved successfully');
    }

    /**
     * Get system configuration
     */
    public function systemConfig(): JsonResponse
    {
        return response()->apiSuccess([
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
                'url' => config('app.url'),
            ],
            'api' => [
                'enabled' => config('modules.api.enabled', true),
                'versioning' => config('modules.api.versioning.enabled', true),
                'rate_limiting' => config('modules.api.rate_limiting.enabled', true),
                'authentication' => config('modules.api.authentication.enabled', true),
            ],
        ], 'System configuration retrieved successfully');
    }

    /**
     * Get modules information
     */
    public function modules(): JsonResponse
    {
        return response()->apiSuccess([
            'e2ee' => $this->getModuleInfo('e2ee'),
            'soc2' => $this->getModuleInfo('soc2'),
            'auth' => $this->getModuleInfo('auth'),
            'mcp' => $this->getModuleInfo('mcp'),
            'web3' => $this->getModuleInfo('web3'),
            'shared' => $this->getModuleInfo('shared'),
        ], 'Modules information retrieved successfully');
    }

    // Helper methods

    /**
     * Get uptime
     */
    protected function getUptime(): string
    {
        $startTime = Cache::get('app_start_time');
        if (!$startTime) {
            $startTime = now();
            Cache::put('app_start_time', $startTime, 86400);
        }
        
        return $startTime->diffForHumans();
    }

    /**
     * Get active modules
     */
    protected function getActiveModules(): array
    {
        return [
            'e2ee' => class_exists('App\Modules\E2ee\Services\EncryptionService'),
            'soc2' => class_exists('Modules\Soc2\Services\ValidationService'),
            'auth' => class_exists('Modules\Auth\Services\AuthenticationService'),
            'mcp' => class_exists('App\Modules\MCP\Services\MCPConnectionService'),
            'web3' => class_exists('App\Modules\Web3\Services\Web3Service'),
            'shared' => class_exists('Modules\Shared\AuditService'),
        ];
    }

    /**
     * Get user type
     */
    protected function getUserType($user): string
    {
        if (!$user) {
            return 'guest';
        }

        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return 'admin';
        }

        return 'user';
    }

    /**
     * Get current usage
     */
    protected function getCurrentUsage($user): array
    {
        // Implement usage tracking logic here
        return [
            'requests_today' => 0,
            'requests_this_hour' => 0,
            'limit_remaining' => 60,
        ];
    }

    /**
     * Get request statistics
     */
    protected function getRequestStats(): array
    {
        // Implement request statistics logic here
        return [
            'total_requests' => 0,
            'requests_today' => 0,
            'requests_this_hour' => 0,
            'average_response_time' => 0,
        ];
    }

    /**
     * Get user statistics
     */
    protected function getUserStats(): array
    {
        return [
            'total_users' => \App\Models\User::count(),
            'active_users' => \App\Models\User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'new_users_today' => \App\Models\User::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get performance statistics
     */
    protected function getPerformanceStats(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'database_connections' => DB::connection()->getPdo() ? 1 : 0,
        ];
    }

    /**
     * Get error statistics
     */
    protected function getErrorStats(): array
    {
        // Implement error statistics logic here
        return [
            'total_errors' => 0,
            'errors_today' => 0,
            'error_rate' => 0,
        ];
    }

    /**
     * Get public modules
     */
    protected function getPublicModules(): array
    {
        return [
            'e2ee' => 'End-to-End Encryption',
            'soc2' => 'SOC2 Compliance',
            'auth' => 'Authentication & Authorization',
            'mcp' => 'Model Context Protocol',
            'web3' => 'Web3 Integration',
        ];
    }

    /**
     * Get database information
     */
    protected function getDatabaseInfo(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            return [
                'connected' => true,
                'driver' => DB::connection()->getDriverName(),
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache information
     */
    protected function getCacheInfo(): array
    {
        try {
            Cache::put('test_key', 'test_value', 1);
            $value = Cache::get('test_key');
            Cache::forget('test_key');
            
            return [
                'connected' => $value === 'test_value',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get queue information
     */
    protected function getQueueInfo(): array
    {
        return [
            'driver' => config('queue.default'),
            'connections' => array_keys(config('queue.connections')),
        ];
    }

    /**
     * Get storage information
     */
    protected function getStorageInfo(): array
    {
        return [
            'driver' => config('filesystems.default'),
            'disks' => array_keys(config('filesystems.disks')),
        ];
    }

    /**
     * Get module information
     */
    protected function getModuleInfo(string $module): array
    {
        $serviceProviders = [
            'e2ee' => 'E2eeServiceProvider',
            'soc2' => 'Soc2ServiceProvider',
            'auth' => 'AuthServiceProvider',
            'mcp' => 'MCPServiceProvider',
            'web3' => 'Web3ServiceProvider',
            'shared' => 'SharedServiceProvider',
        ];

        $provider = $serviceProviders[$module] ?? null;
        
        return [
            'enabled' => $provider && class_exists("Modules\\{$module}\\{$provider}"),
            'version' => '1.0.0',
            'status' => 'active',
        ];
    }
} 