<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Logging Middleware
 * 
 * Logs all incoming requests with performance metrics and context.
 */
class RequestLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_', true);
        
        // Log request start
        $this->logRequest($request, $requestId, 'start');
        
        // Process request
        $response = $next($request);
        
        // Calculate duration
        $duration = microtime(true) - $startTime;
        
        // Log request completion
        $this->logRequest($request, $requestId, 'complete', $response, $duration);
        
        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);
        
        return $response;
    }

    /**
     * Log request information
     *
     * @param \Illuminate\Http\Request $request
     * @param string $requestId
     * @param string $stage
     * @param \Symfony\Component\HttpFoundation\Response|null $response
     * @param float|null $duration
     * @return void
     */
    private function logRequest(Request $request, string $requestId, string $stage, ?Response $response = null, ?float $duration = null): void
    {
        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'stage' => $stage,
            'timestamp' => now()->toISOString(),
        ];

        if ($response) {
            $context['status_code'] = $response->getStatusCode();
            $context['response_size'] = strlen($response->getContent());
        }

        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
            
            // Log as warning if request takes too long
            if ($duration > 1.0) {
                Log::warning("Slow request detected", $context);
                return;
            }
        }

        // Log sensitive operations
        if ($this->isSensitiveOperation($request)) {
            $context['sensitive_operation'] = true;
            Log::info("Sensitive operation request", $context);
            return;
        }

        // Log API requests
        if ($request->is('api/*')) {
            Log::info("API request", $context);
            return;
        }

        // Log other requests at debug level
        Log::debug("HTTP request", $context);
    }

    /**
     * Check if request is a sensitive operation
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    private function isSensitiveOperation(Request $request): bool
    {
        $sensitivePaths = [
            'auth/*',
            'password/*',
            'tokens/*',
            'credentials/*',
        ];

        foreach ($sensitivePaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
} 