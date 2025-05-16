<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggingMiddleware extends BaseMiddleware
{
    /**
     * Perform any actions before the request is processed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function before(Request $request): void
    {
        if (!$this->shouldProcess($request)) {
            return;
        }

        $startTime = microtime(true);

        // Store start time in request for use in after()
        $request->attributes->set('request_start_time', $startTime);

        Log::info('Incoming Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'input' => $this->sanitizeInput($request->all())
        ]);
    }

    /**
     * Perform any actions after the request is processed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function after(Request $request, Response $response): void
    {
        if (!$this->shouldProcess($request)) {
            return;
        }

        $startTime = $request->attributes->get('request_start_time');
        $duration = microtime(true) - $startTime;

        Log::info('Request Completed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration' => round($duration * 1000, 2) . 'ms'
        ]);
    }

    /**
     * Check if the request should be processed by this middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldProcess(Request $request): bool
    {
        // Skip logging for certain paths or methods
        $excludedPaths = $this->config('logging.excluded_paths', []);
        $excludedMethods = $this->config('logging.excluded_methods', []);

        return !in_array($request->path(), $excludedPaths) &&
               !in_array($request->method(), $excludedMethods);
    }

    /**
     * Sanitize headers to remove sensitive information.
     *
     * @param  array  $headers
     * @return array
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token'
        ];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = '[REDACTED]';
            }
        }

        return $headers;
    }

    /**
     * Sanitize input data to remove sensitive information.
     *
     * @param  array  $input
     * @return array
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitiveFields = [
            'password',
            'token',
            'secret',
            'api_key',
            'credit_card'
        ];

        foreach ($input as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $input[$key] = '[REDACTED]';
            }
        }

        return $input;
    }
} 