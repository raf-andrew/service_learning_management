<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Application Exception Handler
 * 
 * Provides comprehensive exception handling with:
 * - Structured error logging
 * - API response formatting
 * - Security-conscious error messages
 * - Performance monitoring
 * - Audit trail for errors
 */
class Handler extends ExceptionHandler
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
        'github_token',
        'api_token',
        'secret',
        'key',
    ];

    /**
     * The list of exception types that should not be reported.
     *
     * @var array<class-string<\Throwable>>
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logException($e);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->renderApiException($e, $request);
            }
        });
    }

    /**
     * Log exception with structured data.
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
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        if ($e instanceof ValidationException) {
            Log::warning('Validation exception', $context);
        } elseif ($e instanceof AuthenticationException) {
            Log::info('Authentication exception', $context);
        } elseif ($e instanceof AuthorizationException) {
            Log::warning('Authorization exception', $context);
        } elseif ($e instanceof ModelNotFoundException) {
            Log::info('Model not found exception', $context);
        } elseif ($e instanceof QueryException) {
            Log::error('Database query exception', $context);
        } elseif ($e instanceof ThrottleRequestsException) {
            Log::warning('Rate limit exceeded', $context);
        } else {
            Log::error('Unhandled exception', $context);
        }
    }

    /**
     * Render API exception response.
     *
     * @param \Throwable $e
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function renderApiException(Throwable $e, Request $request): JsonResponse
    {
        $statusCode = $this->getStatusCode($e);
        $message = $this->getErrorMessage($e);
        $data = $this->getErrorData($e);

        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $this->getErrorCode($e),
            'timestamp' => now()->toISOString(),
        ];

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get HTTP status code for exception.
     *
     * @param \Throwable $e
     * @return int
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 404;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 405;
        }

        if ($e instanceof ThrottleRequestsException) {
            return 429;
        }

        if ($e instanceof QueryException) {
            return 500;
        }

        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        return 500;
    }

    /**
     * Get user-friendly error message.
     *
     * @param \Throwable $e
     * @return string
     */
    protected function getErrorMessage(Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return 'Validation failed';
        }

        if ($e instanceof AuthenticationException) {
            return 'Authentication required';
        }

        if ($e instanceof AuthorizationException) {
            return 'Access denied';
        }

        if ($e instanceof ModelNotFoundException) {
            return 'Resource not found';
        }

        if ($e instanceof NotFoundHttpException) {
            return 'Endpoint not found';
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 'Method not allowed';
        }

        if ($e instanceof ThrottleRequestsException) {
            return 'Too many requests';
        }

        if ($e instanceof QueryException) {
            return config('app.debug') ? $e->getMessage() : 'Database error occurred';
        }

        if ($e instanceof HttpException) {
            return $e->getMessage();
        }

        return config('app.debug') ? $e->getMessage() : 'An unexpected error occurred';
    }

    /**
     * Get error code for exception.
     *
     * @param \Throwable $e
     * @return string
     */
    protected function getErrorCode(Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return 'VALIDATION_ERROR';
        }

        if ($e instanceof AuthenticationException) {
            return 'AUTHENTICATION_REQUIRED';
        }

        if ($e instanceof AuthorizationException) {
            return 'ACCESS_DENIED';
        }

        if ($e instanceof ModelNotFoundException) {
            return 'RESOURCE_NOT_FOUND';
        }

        if ($e instanceof NotFoundHttpException) {
            return 'ENDPOINT_NOT_FOUND';
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 'METHOD_NOT_ALLOWED';
        }

        if ($e instanceof ThrottleRequestsException) {
            return 'RATE_LIMIT_EXCEEDED';
        }

        if ($e instanceof QueryException) {
            return 'DATABASE_ERROR';
        }

        if ($e instanceof HttpException) {
            return 'HTTP_ERROR';
        }

        return 'INTERNAL_ERROR';
    }

    /**
     * Get additional error data.
     *
     * @param \Throwable $e
     * @return array
     */
    protected function getErrorData(Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            return [
                'errors' => $e->errors(),
                'failed_rules' => $e->validator->failed(),
            ];
        }

        if ($e instanceof ThrottleRequestsException) {
            return [
                'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            ];
        }

        return [];
    }
} 