<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['error' => 'API key is required'], 401);
        }

        try {
            $key = ApiKey::where('key', $apiKey)->first();

            if (!$key) {
                return response()->json(['error' => 'Invalid API key'], 401);
            }

            if (!$key->is_active) {
                return response()->json(['error' => 'API key is inactive'], 401);
            }

            if ($key->is_expired) {
                return response()->json(['error' => 'API key has expired'], 401);
            }

            // Add the API key to the request for use in controllers
            $request->attributes->set('api_key', $key);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }
    }
} 