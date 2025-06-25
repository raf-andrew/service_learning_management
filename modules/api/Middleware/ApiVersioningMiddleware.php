<?php

namespace Modules\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ApiVersioningMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if versioning is enabled
            if (!config('modules.api.versioning.enabled', true)) {
                return $next($request);
            }

            // Determine API version from request
            $version = $this->getApiVersion($request);
            
            // Validate version
            if (!$this->isValidVersion($version)) {
                $supportedVersions = config('modules.api.versioning.supported_versions', []);
                $currentVersion = config('modules.api.versioning.current', 'v1');
                
                Log::warning('Invalid API version requested', [
                    'requested_version' => $version,
                    'supported_versions' => array_keys($supportedVersions),
                    'current_version' => $currentVersion,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->fullUrl(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API version',
                    'code' => 400,
                    'supported_versions' => array_keys($supportedVersions),
                    'current_version' => $currentVersion,
                    'timestamp' => now()->toISOString(),
                ], 400);
            }

            // Check if version is deprecated
            if ($this->isVersionDeprecated($version)) {
                $this->logDeprecatedVersionUsage($request, $version);
                
                // Add deprecation warning header
                $response = $next($request);
                $response->headers->set('X-API-Version-Deprecated', 'true');
                $response->headers->set('X-API-Version-Sunset', $this->getVersionSunsetDate($version));
                
                return $response;
            }

            // Set the API version in the request
            $request->attributes->set('api_version', $version);
            
            // Set the API version in config for this request
            Config::set('modules.api.versioning.current', $version);
            
            // Add version header to response
            $response = $next($request);
            $response->headers->set('X-API-Version', $version);
            
            return $response;

        } catch (\Exception $e) {
            Log::error('API Versioning error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Continue without versioning on error
            return $next($request);
        }
    }

    /**
     * Get API version from request
     */
    protected function getApiVersion(Request $request): string
    {
        $strategy = config('modules.api.versioning.strategy', 'header');
        
        switch ($strategy) {
            case 'header':
                return $this->getVersionFromHeader($request);
            case 'url':
                return $this->getVersionFromUrl($request);
            case 'accept':
                return $this->getVersionFromAcceptHeader($request);
            default:
                return config('modules.api.versioning.default_version', 'v1');
        }
    }

    /**
     * Get version from custom header
     */
    protected function getVersionFromHeader(Request $request): string
    {
        $headerName = config('modules.api.versioning.header_name', 'X-API-Version');
        $version = $request->header($headerName);
        
        if ($version) {
            return $version;
        }
        
        return config('modules.api.versioning.default_version', 'v1');
    }

    /**
     * Get version from URL path
     */
    protected function getVersionFromUrl(Request $request): string
    {
        $path = $request->path();
        $prefix = config('modules.api.versioning.url_prefix', 'v');
        
        // Extract version from URL like /api/v1/users
        if (preg_match("/^api\/{$prefix}(\d+)/", $path, $matches)) {
            return $prefix . $matches[1];
        }
        
        return config('modules.api.versioning.default_version', 'v1');
    }

    /**
     * Get version from Accept header
     */
    protected function getVersionFromAcceptHeader(Request $request): string
    {
        $acceptHeader = $request->header('Accept');
        
        if ($acceptHeader && preg_match('/application\/vnd\.api\.v(\d+)\+json/', $acceptHeader, $matches)) {
            return 'v' . $matches[1];
        }
        
        return config('modules.api.versioning.default_version', 'v1');
    }

    /**
     * Check if version is valid
     */
    protected function isValidVersion(string $version): bool
    {
        $supportedVersions = config('modules.api.versioning.supported_versions', []);
        
        return array_key_exists($version, $supportedVersions);
    }

    /**
     * Check if version is deprecated
     */
    protected function isVersionDeprecated(string $version): bool
    {
        $supportedVersions = config('modules.api.versioning.supported_versions', []);
        
        if (!isset($supportedVersions[$version])) {
            return false;
        }
        
        return $supportedVersions[$version]['deprecated'] ?? false;
    }

    /**
     * Get version sunset date
     */
    protected function getVersionSunsetDate(string $version): ?string
    {
        $supportedVersions = config('modules.api.versioning.supported_versions', []);
        
        if (!isset($supportedVersions[$version])) {
            return null;
        }
        
        return $supportedVersions[$version]['sunset_date'] ?? null;
    }

    /**
     * Log deprecated version usage
     */
    protected function logDeprecatedVersionUsage(Request $request, string $version): void
    {
        Log::warning('Deprecated API version used', [
            'version' => $version,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->fullUrl(),
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Get current API version
     */
    public static function getCurrentVersion(): string
    {
        return config('modules.api.versioning.current', 'v1');
    }

    /**
     * Get supported versions
     */
    public static function getSupportedVersions(): array
    {
        return config('modules.api.versioning.supported_versions', []);
    }

    /**
     * Check if versioning is enabled
     */
    public static function isEnabled(): bool
    {
        return config('modules.api.versioning.enabled', true);
    }
} 