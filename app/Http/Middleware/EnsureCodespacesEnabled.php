<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCodespacesEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('codespaces.enabled', false)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Codespaces are not enabled'], 403);
            }
            
            return redirect()->route('home')->with('error', 'Codespaces are not enabled');
        }

        return $next($request);
    }
} 