<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ResponseTimeTrackingMiddleware extends BaseMiddleware
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
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $startTime;
        
        // Add response time header
        $response->headers->set('X-Response-Time', sprintf('%.2f ms', $duration * 1000));
        
        // Log response time if it exceeds threshold
        if ($duration > 1.0) { // Log if response takes more than 1 second
            Log::warning('Slow response detected', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration' => $duration,
                'status' => $response->getStatusCode()
            ]);
        }
        
        return $response;
    }
} 