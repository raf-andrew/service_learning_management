<?php

namespace App\Modules\Shared\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Modules\Shared\Exceptions\SharedException;
use Throwable;

trait ErrorHandlingTrait
{
    /**
     * Handle exceptions with consistent logging and response
     */
    protected function handleException(Throwable $exception, string $context = '', array $contextData = []): JsonResponse
    {
        $errorCode = $this->getErrorCode($exception);
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getErrorMessage($exception);
        
        // Log the exception with context
        $this->logException($exception, $context, $contextData);
        
        // Build error response
        $response = [
            'error' => true,
            'message' => $message,
            'code' => $errorCode,
            'timestamp' => now()->toISOString(),
        ];
        
        // Add debug information in non-production environments
        if (App::environment(['local', 'staging', 'testing'])) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'context' => $context,
                'context_data' => $contextData,
            ];
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Handle validation errors
     */
    protected function handleValidationError(array $errors, string $context = ''): JsonResponse
    {
        $this->logValidationError($errors, $context);
        
        return response()->json([
            'error' => true,
            'message' => 'Validation failed',
            'code' => 'VALIDATION_ERROR',
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], 422);
    }

    /**
     * Handle authentication errors
     */
    protected function handleAuthenticationError(string $message = 'Authentication failed', string $context = ''): JsonResponse
    {
        $this->logAuthenticationError($message, $context);
        
        return response()->json([
            'error' => true,
            'message' => $message,
            'code' => 'AUTHENTICATION_ERROR',
            'timestamp' => now()->toISOString(),
        ], 401);
    }

    /**
     * Handle authorization errors
     */
    protected function handleAuthorizationError(string $message = 'Access denied', string $context = ''): JsonResponse
    {
        $this->logAuthorizationError($message, $context);
        
        return response()->json([
            'error' => true,
            'message' => $message,
            'code' => 'AUTHORIZATION_ERROR',
            'timestamp' => now()->toISOString(),
        ], 403);
    }

    /**
     * Handle not found errors
     */
    protected function handleNotFoundError(string $message = 'Resource not found', string $context = ''): JsonResponse
    {
        $this->logNotFoundError($message, $context);
        
        return response()->json([
            'error' => true,
            'message' => $message,
            'code' => 'NOT_FOUND_ERROR',
            'timestamp' => now()->toISOString(),
        ], 404);
    }

    /**
     * Handle rate limiting errors
     */
    protected function handleRateLimitError(string $message = 'Rate limit exceeded', string $context = ''): JsonResponse
    {
        $this->logRateLimitError($message, $context);
        
        return response()->json([
            'error' => true,
            'message' => $message,
            'code' => 'RATE_LIMIT_ERROR',
            'timestamp' => now()->toISOString(),
        ], 429);
    }

    /**
     * Handle server errors
     */
    protected function handleServerError(string $message = 'Internal server error', string $context = ''): JsonResponse
    {
        $this->logServerError($message, $context);
        
        return response()->json([
            'error' => true,
            'message' => $message,
            'code' => 'SERVER_ERROR',
            'timestamp' => now()->toISOString(),
        ], 500);
    }

    /**
     * Get error code from exception
     */
    protected function getErrorCode(Throwable $exception): string
    {
        if ($exception instanceof SharedException) {
            return $exception->getErrorCode();
        }
        
        // Map common exceptions to error codes
        $exceptionClass = get_class($exception);
        
        return match ($exceptionClass) {
            \Illuminate\Validation\ValidationException::class => 'VALIDATION_ERROR',
            \Illuminate\Auth\AuthenticationException::class => 'AUTHENTICATION_ERROR',
            \Illuminate\Auth\Access\AuthorizationException::class => 'AUTHORIZATION_ERROR',
            \Illuminate\Database\Eloquent\ModelNotFoundException::class => 'NOT_FOUND_ERROR',
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class => 'NOT_FOUND_ERROR',
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class => 'RATE_LIMIT_ERROR',
            \Illuminate\Database\QueryException::class => 'DATABASE_ERROR',
            \PDOException::class => 'DATABASE_ERROR',
            default => 'UNKNOWN_ERROR',
        };
    }

    /**
     * Get HTTP status code from exception
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof SharedException) {
            return $exception->getStatusCode();
        }
        
        // Map common exceptions to status codes
        $exceptionClass = get_class($exception);
        
        return match ($exceptionClass) {
            \Illuminate\Validation\ValidationException::class => 422,
            \Illuminate\Auth\AuthenticationException::class => 401,
            \Illuminate\Auth\Access\AuthorizationException::class => 403,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class => 404,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class => 404,
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class => 429,
            \Illuminate\Database\QueryException::class => 500,
            \PDOException::class => 500,
            default => 500,
        };
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof SharedException) {
            return $exception->getMessage();
        }
        
        // Return user-friendly messages for common exceptions
        $exceptionClass = get_class($exception);
        
        return match ($exceptionClass) {
            \Illuminate\Validation\ValidationException::class => 'The provided data is invalid.',
            \Illuminate\Auth\AuthenticationException::class => 'Authentication is required.',
            \Illuminate\Auth\Access\AuthorizationException::class => 'You do not have permission to perform this action.',
            \Illuminate\Database\Eloquent\ModelNotFoundException::class => 'The requested resource was not found.',
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class => 'The requested page was not found.',
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class => 'Too many requests. Please try again later.',
            \Illuminate\Database\QueryException::class => 'A database error occurred.',
            \PDOException::class => 'A database error occurred.',
            default => 'An unexpected error occurred.',
        };
    }

    /**
     * Log exception with context
     */
    protected function logException(Throwable $exception, string $context = '', array $contextData = []): void
    {
        $logData = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'context' => $context,
            'context_data' => $contextData,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ];
        
        Log::error("Exception in {$context}", $logData);
    }

    /**
     * Log validation error
     */
    protected function logValidationError(array $errors, string $context = ''): void
    {
        Log::warning("Validation error in {$context}", [
            'errors' => $errors,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ]);
    }

    /**
     * Log authentication error
     */
    protected function logAuthenticationError(string $message, string $context = ''): void
    {
        Log::warning("Authentication error in {$context}", [
            'message' => $message,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ]);
    }

    /**
     * Log authorization error
     */
    protected function logAuthorizationError(string $message, string $context = ''): void
    {
        Log::warning("Authorization error in {$context}", [
            'message' => $message,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ]);
    }

    /**
     * Log not found error
     */
    protected function logNotFoundError(string $message, string $context = ''): void
    {
        Log::info("Not found error in {$context}", [
            'message' => $message,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ]);
    }

    /**
     * Log rate limit error
     */
    protected function logRateLimitError(string $message, string $context = ''): void
    {
        Log::warning("Rate limit error in {$context}", [
            'message' => $message,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ]);
    }

    /**
     * Log server error
     */
    protected function logServerError(string $message, string $context = ''): void
    {
        Log::error("Server error in {$context}", [
            'message' => $message,
            'user_id' => auth()->id(),
            'request_id' => request()->id(),
        ]);
    }

    /**
     * Safely execute a callback with error handling
     */
    protected function safeExecute(callable $callback, string $context = '', array $contextData = [])
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            $this->logException($exception, $context, $contextData);
            throw $exception;
        }
    }

    /**
     * Execute callback with timeout
     */
    protected function executeWithTimeout(callable $callback, int $timeout = 30, string $context = ''): mixed
    {
        $startTime = microtime(true);
        
        try {
            return $callback();
        } catch (Throwable $exception) {
            $executionTime = microtime(true) - $startTime;
            
            if ($executionTime >= $timeout) {
                Log::error("Operation timed out in {$context}", [
                    'execution_time' => $executionTime,
                    'timeout' => $timeout,
                    'exception' => $exception->getMessage(),
                ]);
            }
            
            throw $exception;
        }
    }

    /**
     * Retry operation with exponential backoff
     */
    protected function retryOperation(callable $callback, int $maxAttempts = 3, int $baseDelay = 1000, string $context = ''): mixed
    {
        $attempt = 1;
        $lastException = null;
        
        while ($attempt <= $maxAttempts) {
            try {
                return $callback();
            } catch (Throwable $exception) {
                $lastException = $exception;
                
                if ($attempt === $maxAttempts) {
                    break;
                }
                
                $delay = $baseDelay * (2 ** ($attempt - 1));
                Log::warning("Retry attempt {$attempt} failed in {$context}", [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay' => $delay,
                    'exception' => $exception->getMessage(),
                ]);
                
                usleep($delay * 1000);
                $attempt++;
            }
        }
        
        Log::error("All retry attempts failed in {$context}", [
            'max_attempts' => $maxAttempts,
            'final_exception' => $lastException->getMessage(),
        ]);
        
        throw $lastException;
    }
} 