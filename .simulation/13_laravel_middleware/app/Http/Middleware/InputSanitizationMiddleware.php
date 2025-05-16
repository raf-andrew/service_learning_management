<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InputSanitizationMiddleware extends BaseMiddleware
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
        // Sanitize GET parameters
        $this->sanitizeInput($request->query());
        
        // Sanitize POST parameters
        $this->sanitizeInput($request->post());
        
        // Sanitize JSON input
        if ($request->isJson()) {
            $this->sanitizeInput($request->json()->all());
        }
        
        return $next($request);
    }
    
    /**
     * Sanitize input data.
     *
     * @param  array  $data
     * @return void
     */
    private function sanitizeInput(array &$data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->sanitizeInput($value);
            } else {
                // Remove HTML tags and encode special characters
                $data[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
        }
    }
} 