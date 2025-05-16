<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Pre-processing
            $this->before($request);

            // Process the request
            $response = $next($request);

            // Post-processing
            $this->after($request, $response);

            return $response;
        } catch (\Exception $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Handle any exceptions that occur during middleware processing.
     *
     * @param  \Exception  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleException(\Exception $e, Request $request): Response
    {
        Log::error('Middleware Error: ' . $e->getMessage(), [
            'exception' => $e,
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]
        ]);

        return response()->json([
            'error' => 'An error occurred while processing your request',
            'message' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
        ], 500);
    }

    /**
     * Perform any actions before the request is processed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function before(Request $request): void
    {
        // Override in child classes
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
        // Override in child classes
    }

    /**
     * Get the middleware's configuration.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        return config("middleware.{$key}", $default);
    }

    /**
     * Check if the request should be processed by this middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldProcess(Request $request): bool
    {
        return true;
    }
} 