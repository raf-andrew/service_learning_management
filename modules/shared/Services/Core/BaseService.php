<?php

namespace App\Modules\Shared\Services\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Traits\ErrorHandlingTrait;
use Modules\Shared\Traits\ConfigAccessTrait;
use Modules\Shared\Contracts\ServiceInterface;

abstract class BaseService implements ServiceInterface
{
    use ErrorHandlingTrait, ConfigAccessTrait;

    /**
     * Service name for logging and identification
     */
    protected string $serviceName;

    /**
     * Cache TTL in seconds
     */
    protected int $cacheTtl = 3600;

    /**
     * Whether caching is enabled for this service
     */
    protected bool $cachingEnabled = true;

    /**
     * Whether audit logging is enabled for this service
     */
    protected bool $auditEnabled = true;

    public function __construct()
    {
        $this->serviceName = $this->getServiceName();
        $this->initializeService();
    }

    /**
     * Get the service name
     */
    abstract protected function getServiceName(): string;

    /**
     * Initialize service-specific configurations
     */
    protected function initializeService(): void
    {
        $this->cacheTtl = $this->getConfig('cache.ttl', 3600);
        $this->cachingEnabled = $this->getConfig('cache.enabled', true);
        $this->auditEnabled = $this->getConfig('audit.enabled', true);
    }

    /**
     * Execute a method with caching
     */
    protected function withCache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }

        $cacheKey = $this->buildCacheKey($key);
        $ttl = $ttl ?? $this->cacheTtl;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Execute a method with audit logging
     */
    protected function withAudit(string $action, array $context, callable $callback): mixed
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            
            if ($this->auditEnabled) {
                $this->logAudit($action, array_merge($context, [
                    'success' => true,
                    'duration' => microtime(true) - $startTime
                ]));
            }
            
            return $result;
        } catch (\Exception $e) {
            if ($this->auditEnabled) {
                $this->logAudit($action, array_merge($context, [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'duration' => microtime(true) - $startTime
                ]));
            }
            throw $e;
        }
    }

    /**
     * Validate input data
     */
    protected function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Build cache key with service prefix
     */
    protected function buildCacheKey(string $key): string
    {
        return "service.{$this->serviceName}.{$key}";
    }

    /**
     * Log audit event
     */
    protected function logAudit(string $action, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }

        Log::info("SERVICE_AUDIT: [{$this->serviceName}] {$action}", $context);
    }

    /**
     * Clear service cache
     */
    public function clearCache(string $pattern = '*'): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $cacheKey = $this->buildCacheKey($pattern);
        Cache::forget($cacheKey);
    }

    /**
     * Get service statistics
     */
    public function getStatistics(): array
    {
        return [
            'service_name' => $this->serviceName,
            'caching_enabled' => $this->cachingEnabled,
            'audit_enabled' => $this->auditEnabled,
            'cache_ttl' => $this->cacheTtl,
        ];
    }

    /**
     * Health check for the service
     */
    public function healthCheck(): array
    {
        return [
            'service' => $this->serviceName,
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ];
    }
} 