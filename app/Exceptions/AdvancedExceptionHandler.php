<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Advanced Exception Handler
 * 
 * Provides comprehensive error handling with recovery mechanisms.
 */
class AdvancedExceptionHandler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logException($e);
            $this->trackException($e);
            $this->attemptRecovery($e);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->renderException($e, $request);
        });
    }

    /**
     * Log exception with structured data
     *
     * @param \Throwable $e
     * @return void
     */
    protected function logException(Throwable $e): void
    {
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        // Determine log level based on exception type
        $level = $this->getLogLevel($e);
        
        Log::log($level, 'Exception occurred', $context);
    }

    /**
     * Track exception for monitoring
     *
     * @param \Throwable $e
     * @return void
     */
    protected function trackException(Throwable $e): void
    {
        $key = 'exceptions:' . date('Y-m-d');
        $exceptions = Cache::get($key, []);
        
        $exceptionData = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'count' => 1,
            'first_occurrence' => now()->toISOString(),
            'last_occurrence' => now()->toISOString(),
        ];
        
        $exceptionType = get_class($e);
        if (isset($exceptions[$exceptionType])) {
            $exceptions[$exceptionType]['count']++;
            $exceptions[$exceptionType]['last_occurrence'] = now()->toISOString();
        } else {
            $exceptions[$exceptionType] = $exceptionData;
        }
        
        Cache::put($key, $exceptions, 86400); // 24 hours
    }

    /**
     * Attempt recovery from exception
     *
     * @param \Throwable $e
     * @return void
     */
    protected function attemptRecovery(Throwable $e): void
    {
        // Database connection recovery
        if ($this->isDatabaseException($e)) {
            $this->attemptDatabaseRecovery();
        }
        
        // Cache recovery
        if ($this->isCacheException($e)) {
            $this->attemptCacheRecovery();
        }
        
        // Session recovery
        if ($this->isSessionException($e)) {
            $this->attemptSessionRecovery();
        }
    }

    /**
     * Render exception with appropriate response
     *
     * @param \Throwable $e
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function renderException(Throwable $e, Request $request)
    {
        // API requests
        if ($request->expectsJson()) {
            return $this->renderApiException($e, $request);
        }
        
        // Web requests
        return $this->renderWebException($e, $request);
    }

    /**
     * Render API exception
     *
     * @param \Throwable $e
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function renderApiException(Throwable $e, Request $request)
    {
        $statusCode = $this->getStatusCode($e);
        $errorCode = $this->getErrorCode($e);
        
        $response = [
            'error' => [
                'code' => $errorCode,
                'message' => $this->getErrorMessage($e),
                'details' => $this->getErrorDetails($e),
                'request_id' => request()->id(),
                'timestamp' => now()->toISOString(),
            ],
        ];
        
        // Add debug information in development
        if (config('app.debug')) {
            $response['error']['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Render web exception
     *
     * @param \Throwable $e
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function renderWebException(Throwable $e, Request $request)
    {
        $statusCode = $this->getStatusCode($e);
        
        // Check if we have a custom error view
        $view = $this->getErrorView($statusCode);
        
        if (view()->exists($view)) {
            return response()->view($view, [
                'exception' => $e,
                'statusCode' => $statusCode,
                'requestId' => request()->id(),
            ], $statusCode);
        }
        
        // Fallback to default error response
        return response()->view('errors.generic', [
            'exception' => $e,
            'statusCode' => $statusCode,
            'requestId' => request()->id(),
        ], $statusCode);
    }

    /**
     * Get log level for exception
     *
     * @param \Throwable $e
     * @return string
     */
    protected function getLogLevel(Throwable $e): string
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            
            if ($statusCode >= 500) {
                return 'error';
            } elseif ($statusCode >= 400) {
                return 'warning';
            }
        }
        
        // Critical exceptions
        $criticalExceptions = [
            \PDOException::class,
            \RedisException::class,
            \MemcachedException::class,
        ];
        
        foreach ($criticalExceptions as $criticalException) {
            if ($e instanceof $criticalException) {
                return 'critical';
            }
        }
        
        return 'error';
    }

    /**
     * Get HTTP status code for exception
     *
     * @param \Throwable $e
     * @return int
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        
        // Map exception types to status codes
        $statusCodeMap = [
            \Illuminate\Auth\AuthenticationException::class => 401,
            \Illuminate\Auth\Access\AuthorizationException::class => 403,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class => 404,
            \Illuminate\Validation\ValidationException::class => 422,
            \PDOException::class => 500,
            \RedisException::class => 500,
            \MemcachedException::class => 500,
        ];
        
        foreach ($statusCodeMap as $exceptionClass => $statusCode) {
            if ($e instanceof $exceptionClass) {
                return $statusCode;
            }
        }
        
        return 500;
    }

    /**
     * Get error code for exception
     *
     * @param \Throwable $e
     * @return string
     */
    protected function getErrorCode(Throwable $e): string
    {
        $statusCode = $this->getStatusCode($e);
        
        $errorCodeMap = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_SERVER_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
        ];
        
        return $errorCodeMap[$statusCode] ?? 'UNKNOWN_ERROR';
    }

    /**
     * Get user-friendly error message
     *
     * @param \Throwable $e
     * @return string
     */
    protected function getErrorMessage(Throwable $e): string
    {
        $statusCode = $this->getStatusCode($e);
        
        $messageMap = [
            400 => 'The request could not be processed.',
            401 => 'Authentication is required.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The requested resource was not found.',
            422 => 'The provided data is invalid.',
            429 => 'Too many requests. Please try again later.',
            500 => 'An internal server error occurred.',
            502 => 'The server is temporarily unavailable.',
            503 => 'The service is temporarily unavailable.',
        ];
        
        return $messageMap[$statusCode] ?? 'An unexpected error occurred.';
    }

    /**
     * Get error details
     *
     * @param \Throwable $e
     * @return array<string, mixed>
     */
    protected function getErrorDetails(Throwable $e): array
    {
        $details = [];
        
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $details['validation_errors'] = $e->errors();
        }
        
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $details['model'] = $e->getModel();
            $details['ids'] = $e->getIds();
        }
        
        return $details;
    }

    /**
     * Get error view for status code
     *
     * @param int $statusCode
     * @return string
     */
    protected function getErrorView(int $statusCode): string
    {
        $viewMap = [
            401 => 'errors.401',
            403 => 'errors.403',
            404 => 'errors.404',
            419 => 'errors.419',
            429 => 'errors.429',
            500 => 'errors.500',
            503 => 'errors.503',
        ];
        
        return $viewMap[$statusCode] ?? 'errors.generic';
    }

    /**
     * Check if exception is database-related
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function isDatabaseException(Throwable $e): bool
    {
        return $e instanceof \PDOException ||
               $e instanceof \Illuminate\Database\QueryException ||
               str_contains($e->getMessage(), 'SQLSTATE');
    }

    /**
     * Check if exception is cache-related
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function isCacheException(Throwable $e): bool
    {
        return $e instanceof \RedisException ||
               $e instanceof \MemcachedException ||
               str_contains($e->getMessage(), 'cache');
    }

    /**
     * Check if exception is session-related
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function isSessionException(Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'session') ||
               str_contains($e->getMessage(), 'Session');
    }

    /**
     * Attempt database recovery
     *
     * @return void
     */
    protected function attemptDatabaseRecovery(): void
    {
        try {
            // Try to reconnect to database
            DB::reconnect();
            Log::info('Database connection recovered');
        } catch (\Exception $e) {
            Log::error('Failed to recover database connection', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Attempt cache recovery
     *
     * @return void
     */
    protected function attemptCacheRecovery(): void
    {
        try {
            // Try to reconnect to cache
            if (config('cache.default') === 'redis') {
                Redis::connect();
                Log::info('Cache connection recovered');
            }
        } catch (\Exception $e) {
            Log::error('Failed to recover cache connection', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Attempt session recovery
     *
     * @return void
     */
    protected function attemptSessionRecovery(): void
    {
        try {
            // Clear session and regenerate
            session()->flush();
            session()->regenerate();
            Log::info('Session recovered');
        } catch (\Exception $e) {
            Log::error('Failed to recover session', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get exception statistics
     *
     * @return array<string, mixed>
     */
    public function getExceptionStatistics(): array
    {
        $key = 'exceptions:' . date('Y-m-d');
        $exceptions = Cache::get($key, []);
        
        $totalExceptions = array_sum(array_column($exceptions, 'count'));
        $uniqueExceptions = count($exceptions);
        
        return [
            'total_exceptions' => $totalExceptions,
            'unique_exceptions' => $uniqueExceptions,
            'exceptions' => $exceptions,
            'date' => date('Y-m-d'),
        ];
    }

    /**
     * Clear exception statistics
     *
     * @return void
     */
    public function clearExceptionStatistics(): void
    {
        $key = 'exceptions:' . date('Y-m-d');
        Cache::forget($key);
    }
} 