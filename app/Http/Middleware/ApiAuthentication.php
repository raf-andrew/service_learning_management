<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ApiKey;

class ApiAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            Log::warning('API request without API key', [
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            return response()->json(['error' => 'API key is required'], 401);
        }

        $validKey = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$validKey) {
            Log::warning('Invalid API key used', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'key' => $apiKey
            ]);
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Add API key info to request for later use
        $request->attributes->set('api_key', $validKey);

        // Log successful authentication
        Log::info('API request authenticated', [
            'ip' => $request->ip(),
            'path' => $request->path(),
            'key_id' => $validKey->id
        ]);

        return $next($request);
    }
} 