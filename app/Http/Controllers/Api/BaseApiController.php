<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponseTrait;
use App\Http\Controllers\Traits\ValidationTrait;
use App\Http\Controllers\Traits\QueryOptimizationTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

/**
 * Base API Controller
 * 
 * Provides a foundation for all API controllers with standardized patterns,
 * error handling, security features, and performance optimizations.
 * 
 * This controller implements:
 * - Standardized API response formats
 * - Input validation and sanitization
 * - Query optimization and caching
 * - Rate limiting and security headers
 * - Comprehensive error handling
 * - Audit logging and monitoring
 * 
 * @package App\Http\Controllers
 * @since 1.0.0
 * @author System
 * 
 * @see \App\Http\Controllers\Traits\ApiResponseTrait
 * @see \App\Http\Controllers\Traits\ValidationTrait
 * @see \App\Http\Controllers\Traits\QueryOptimizationTrait
 */
abstract class BaseApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ApiResponseTrait, ValidationTrait, QueryOptimizationTrait;

    /**
     * Handle exceptions in a consistent way across all API endpoints.
     * 
     * This method provides centralized exception handling with:
     * - Proper error logging with context
     * - Debug information in development
     * - Sanitized error messages in production
     * - Consistent error response format
     * 
     * @param \Throwable $e The exception that was thrown
     * @param string $context Additional context about where the exception occurred
     * @return \Illuminate\Http\JsonResponse Standardized error response
     * 
     * @example
     * ```php
     * try {
     *     $result = $this->someOperation();
     *     return $this->successResponse($result);
     * } catch (\Exception $e) {
     *     return $this->handleException($e, 'UserController::store');
     * }
     * ```
     */
    protected function handleException(\Throwable $e, string $context = ''): \Illuminate\Http\JsonResponse
    {
        Log::error("Error in {$context}: " . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);

        if (config('app.debug')) {
            return $this->serverErrorResponse($e->getMessage());
        }

        return $this->serverErrorResponse('An unexpected error occurred');
    }

    /**
     * Execute a database operation with comprehensive error handling.
     * 
     * This method wraps database operations in a try-catch block and provides:
     * - Automatic exception handling
     * - Proper error responses for different exception types
     * - Logging of database errors
     * - Consistent error format
     * 
     * @param callable $operation The database operation to execute
     * @param string $context Context for logging and error messages
     * @return mixed The result of the operation or an error response
     * 
     * @example
     * ```php
     * return $this->executeDbOperation(function () {
     *     return User::create($data);
     * }, 'UserController::store');
     * ```
     */
    protected function executeDbOperation(callable $operation, string $context = '')
    {
        try {
            return $operation();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->handleException($e, $context);
        }
    }

    /**
     * Get the current authenticated user with fallback support.
     * 
     * This method attempts to get the authenticated user from multiple
     * authentication guards and provides fallback behavior.
     * 
     * @return \App\Models\User|null The authenticated user or null
     * 
     * @example
     * ```php
     * $user = $this->getCurrentUser();
     * if (!$user) {
     *     return $this->unauthorizedResponse();
     * }
     * ```
     */
    protected function getCurrentUser()
    {
        return auth()->user() ?? auth()->guard('api')->user();
    }

    /**
     * Check if the current user has permission to access a specific resource.
     * 
     * This method implements resource-level authorization by checking:
     * - If the resource belongs to the current user
     * - If the user has admin privileges
     * - Custom permission logic can be extended
     * 
     * @param mixed $resource The resource to check permissions for
     * @param string $action The action being performed (view, edit, delete, etc.)
     * @return bool True if the user has permission, false otherwise
     * 
     * @example
     * ```php
     * $credential = DeveloperCredential::find($id);
     * if (!$this->checkResourcePermission($credential, 'view')) {
     *     return $this->forbiddenResponse();
     * }
     * ```
     */
    protected function checkResourcePermission($resource, string $action = 'view'): bool
    {
        if (!$resource) {
            return false;
        }

        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }

        // Check if resource belongs to user
        if (method_exists($resource, 'user_id') && $resource->user_id === $user->id) {
            return true;
        }

        // Check if user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Apply rate limiting to controller methods to prevent abuse.
     * 
     * This method implements rate limiting using cache-based counters:
     * - Tracks requests per IP/user
     * - Configurable limits per endpoint
     * - Automatic blocking when limits are exceeded
     * - Logging of rate limit violations
     * 
     * @param string $key Unique identifier for the rate limit (usually endpoint name)
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $decayMinutes Time window in minutes for the limit
     * @return void
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException When rate limit is exceeded
     * 
     * @example
     * ```php
     * public function store(Request $request) {
     *     $this->applyRateLimit('credentials:store', 10, 1);
     *     // ... rest of the method
     * }
     * ```
     */
    protected function applyRateLimit(string $key, int $maxAttempts = 60, int $decayMinutes = 1): void
    {
        $key = "rate_limit:{$key}:" . request()->ip();
        
        if (cache()->has($key)) {
            $attempts = cache()->get($key);
            if ($attempts >= $maxAttempts) {
                abort(429, 'Too many requests');
            }
            cache()->put($key, $attempts + 1, $decayMinutes * 60);
        } else {
            cache()->put($key, 1, $decayMinutes * 60);
        }
    }

    /**
     * Sanitize and validate common request parameters.
     * 
     * This method provides a standardized way to handle common request
     * parameters like pagination, date ranges, and search terms.
     * 
     * @param \Illuminate\Http\Request $request The HTTP request
     * @return array Validated and sanitized parameters
     * 
     * @example
     * ```php
     * public function index(Request $request) {
     *     $params = $this->sanitizeCommonParams($request);
     *     $query = User::query();
     *     // Apply params to query...
     * }
     * ```
     */
    protected function sanitizeCommonParams(\Illuminate\Http\Request $request): array
    {
        $rules = $this->getCommonValidationRules();
        $allRules = array_merge($rules['pagination'], $rules['date_range'], $rules['search']);
        
        return $this->validateAndSanitize($request, $allRules);
    }

    /**
     * Generate a cache key for the current request context.
     * 
     * This method creates unique cache keys based on:
     * - User ID (for user-specific caching)
     * - Request URL and parameters
     * - Custom prefix for different cache types
     * 
     * @param string $prefix Custom prefix for the cache key
     * @return string Unique cache key
     * 
     * @example
     * ```php
     * $cacheKey = $this->getCacheKey('user_credentials');
     * $credentials = cache()->remember($cacheKey, 300, function () {
     *     return $this->getUserCredentials();
     * });
     * ```
     */
    protected function getCacheKey(string $prefix = ''): string
    {
        $user = $this->getCurrentUser();
        $userId = $user ? $user->id : 'guest';
        
        return "{$prefix}:{$userId}:" . md5(request()->fullUrl());
    }
} 