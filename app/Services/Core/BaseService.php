<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\Shared\AuditService;
use App\Modules\Shared\MonitoringService;
use App\Modules\Shared\PerformanceOptimizationService;
use App\Contracts\Services\ServiceInterface;

/**
 * Base Service Class
 * 
 * Provides common functionality for all services across the application.
 * Implements DRY principles and standardizes service patterns.
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * @var MonitoringService
     */
    protected MonitoringService $monitoringService;

    /**
     * @var PerformanceOptimizationService
     */
    protected PerformanceOptimizationService $performanceService;

    /**
     * @var string
     */
    protected string $serviceName;

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var bool
     */
    protected bool $auditEnabled = true;

    /**
     * @var bool
     */
    protected bool $monitoringEnabled = true;

    /**
     * @var bool
     */
    protected bool $cachingEnabled = true;

    /**
     * @var int
     */
    protected int $defaultCacheTtl = 3600;

    /**
     * Constructor
     */
    public function __construct(
        AuditService $auditService = null,
        MonitoringService $monitoringService = null,
        PerformanceOptimizationService $performanceService = null
    ) {
        $this->auditService = $auditService ?? app(AuditService::class);
        $this->monitoringService = $monitoringService ?? app(MonitoringService::class);
        $this->performanceService = $performanceService ?? app(PerformanceOptimizationService::class);
        $this->serviceName = $this->getServiceName();
        $this->loadConfiguration();
        $this->initialize();
    }

    /**
     * Initialize service-specific configurations
     */
    protected function initialize(): void
    {
        // Override in child classes for service-specific initialization
    }

    /**
     * Get service name for logging and monitoring
     */
    public function getServiceName(): string
    {
        $className = class_basename($this);
        return str_replace('Service', '', $className);
    }

    /**
     * Load service configuration
     */
    protected function loadConfiguration(): void
    {
        $configKey = "services.{$this->serviceName}";
        $this->config = config($configKey, []);
        $this->auditEnabled = $this->config['audit_enabled'] ?? true;
        $this->monitoringEnabled = $this->config['monitoring_enabled'] ?? true;
        $this->cachingEnabled = $this->config['caching_enabled'] ?? true;
        $this->defaultCacheTtl = $this->config['cache_ttl'] ?? 3600;
    }

    /**
     * Execute a database transaction with error handling
     */
    protected function executeTransaction(callable $operation, string $context = ''): mixed
    {
        $startTime = microtime(true);
        
        try {
            $this->logOperation('transaction_start', [
                'context' => $context,
                'service' => $this->serviceName
            ]);

            $result = DB::transaction($operation);

            $duration = microtime(true) - $startTime;
            $this->logOperation('transaction_success', [
                'context' => $context,
                'duration' => $duration,
                'service' => $this->serviceName
            ]);

            $this->recordMetric('transaction_duration', $duration);
            $this->recordMetric('transaction_success', 1);

            return $result;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->logError('transaction_failed', $e->getMessage(), [
                'context' => $context,
                'duration' => $duration,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $this->recordMetric('transaction_duration', $duration);
            $this->recordMetric('transaction_failure', 1);

            throw $e;
        }
    }

    /**
     * Find a model by ID with error handling and caching
     */
    protected function findModel(string $modelClass, $id, array $relations = [], bool $useCache = true): Model
    {
        $cacheKey = $this->generateCacheKey('model', $modelClass, $id, $relations);
        
        if ($useCache && $this->cachingEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->recordMetric('cache_hit', 1);
                return $cached;
            }
        }

        try {
            $query = $modelClass::query();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            $model = $query->findOrFail($id);

            if ($useCache && $this->cachingEnabled) {
                Cache::put($cacheKey, $model, $this->defaultCacheTtl);
                $this->recordMetric('cache_miss', 1);
            }

            $this->recordMetric('model_found', 1);
            return $model;
        } catch (ModelNotFoundException $e) {
            $this->logWarning('model_not_found', "Model not found: {$modelClass} with ID {$id}");
            $this->recordMetric('model_not_found', 1);
            throw $e;
        }
    }

    /**
     * Create a model with validation and error handling
     */
    protected function createModel(string $modelClass, array $data, array $rules = [], array $options = []): Model
    {
        $this->validateData($data, $rules);

        return $this->executeTransaction(function () use ($modelClass, $data, $options) {
            $model = $modelClass::create($data);
            
            $this->logOperation('model_created', [
                'model_class' => $modelClass,
                'model_id' => $model->id,
                'data_keys' => array_keys($data)
            ]);

            $this->recordMetric('model_created', 1);
            
            // Fire model created event
            Event::dispatch("model.created", $model);
            
            return $model;
        }, "createModel:{$modelClass}");
    }

    /**
     * Update a model with validation and error handling
     */
    protected function updateModel(Model $model, array $data, array $rules = []): Model
    {
        $this->validateData($data, $rules);

        return $this->executeTransaction(function () use ($model, $data) {
            $model->update($data);
            
            $this->logOperation('model_updated', [
                'model_class' => get_class($model),
                'model_id' => $model->id,
                'data_keys' => array_keys($data)
            ]);

            $this->recordMetric('model_updated', 1);
            
            // Clear cache for this model
            $this->clearModelCache($model);
            
            // Fire model updated event
            Event::dispatch("model.updated", $model);
            
            return $model;
        }, "updateModel:" . get_class($model));
    }

    /**
     * Delete a model with error handling
     */
    protected function deleteModel(Model $model): bool
    {
        return $this->executeTransaction(function () use ($model) {
            $modelClass = get_class($model);
            $modelId = $model->id;
            
            $deleted = $model->delete();
            
            $this->logOperation('model_deleted', [
                'model_class' => $modelClass,
                'model_id' => $modelId
            ]);

            $this->recordMetric('model_deleted', 1);
            
            // Clear cache for this model
            $this->clearModelCache($model);
            
            // Fire model deleted event
            Event::dispatch("model.deleted", $model);
            
            return $deleted;
        }, "deleteModel:" . get_class($model));
    }

    /**
     * Validate data against rules
     */
    protected function validateData(array $data, array $rules): void
    {
        if (empty($rules)) {
            return;
        }

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            $this->logError('validation_failed', 'Data validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data_keys' => array_keys($data)
            ]);

            $this->recordMetric('validation_failure', 1);
            throw new ValidationException($validator);
        }

        $this->recordMetric('validation_success', 1);
    }

    /**
     * Execute operation with error handling and monitoring
     */
    protected function executeWithErrorHandling(callable $operation, string $operationName, array $context = []): mixed
    {
        $startTime = microtime(true);
        
        try {
            $this->logOperation("{$operationName}_start", array_merge($context, [
                'service' => $this->serviceName
            ]));
            
            $result = $operation();
            
            $duration = microtime(true) - $startTime;
            $this->logOperation("{$operationName}_success", array_merge($context, [
                'duration' => $duration,
                'result_type' => gettype($result),
                'service' => $this->serviceName
            ]));
            
            $this->recordMetric("{$operationName}_duration", $duration);
            $this->recordMetric("{$operationName}_success", 1);
            
            return $result;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->logError($operationName, $e->getMessage(), array_merge($context, [
                'duration' => $duration,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]));

            $this->recordMetric("{$operationName}_duration", $duration);
            $this->recordMetric("{$operationName}_failure", 1);
            
            throw $e;
        }
    }

    /**
     * Log operation for audit purposes
     */
    protected function logOperation(string $action, array $context = []): void
    {
        $logContext = array_merge($context, [
            'service' => $this->serviceName,
            'timestamp' => now()->toISOString()
        ]);

        Log::info("Service {$this->serviceName}: {$action}", $logContext);

        if ($this->auditEnabled) {
            $this->auditService->log($this->serviceName, $action, $logContext);
        }
    }

    /**
     * Log error for debugging
     */
    protected function logError(string $operation, string $error, array $context = []): void
    {
        $logContext = array_merge($context, [
            'service' => $this->serviceName,
            'operation' => $operation,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);

        Log::error("Service {$this->serviceName} {$operation} error", $logContext);

        if ($this->auditEnabled) {
            $this->auditService->log("{$this->serviceName}_error", $operation, $logContext);
        }
    }

    /**
     * Log warning
     */
    protected function logWarning(string $operation, string $message, array $context = []): void
    {
        $logContext = array_merge($context, [
            'service' => $this->serviceName,
            'operation' => $operation,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ]);

        Log::warning("Service {$this->serviceName} {$operation} warning", $logContext);

        if ($this->auditEnabled) {
            $this->auditService->log("{$this->serviceName}_warning", $operation, $logContext);
        }
    }

    /**
     * Record metric for monitoring
     */
    protected function recordMetric(string $name, $value): void
    {
        if ($this->monitoringEnabled) {
            $this->monitoringService->recordMetric($this->serviceName, $name, $value);
        }
    }

    /**
     * Generate cache key
     */
    protected function generateCacheKey(string $type, ...$parts): string
    {
        $key = "service:{$this->serviceName}:{$type}:" . implode(':', array_filter($parts));
        return md5($key);
    }

    /**
     * Clear cache for a model
     */
    protected function clearModelCache(Model $model): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $cacheKey = $this->generateCacheKey('model', get_class($model), $model->id);
        Cache::forget($cacheKey);
        
        $this->logOperation('cache_cleared', [
            'cache_key' => $cacheKey,
            'model_class' => get_class($model),
            'model_id' => $model->id
        ]);
    }

    /**
     * Get cached value or execute callback
     */
    protected function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }

        $ttl = $ttl ?? $this->defaultCacheTtl;
        $cacheKey = $this->generateCacheKey('remember', $key);

        return Cache::remember($cacheKey, $ttl, function () use ($callback, $key) {
            $this->recordMetric('cache_miss', 1);
            return $callback();
        });
    }

    /**
     * Batch process items with progress tracking
     */
    protected function batchProcess(array $items, callable $processor, int $batchSize = 100): array
    {
        $results = [];
        $total = count($items);
        $batches = array_chunk($items, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            $this->logOperation('batch_process_start', [
                'batch_index' => $batchIndex,
                'batch_size' => count($batch),
                'total_items' => $total,
                'progress' => round(($batchIndex * $batchSize / $total) * 100, 2)
            ]);

            foreach ($batch as $item) {
                try {
                    $result = $processor($item);
                    $results[] = $result;
                    $this->recordMetric('batch_item_success', 1);
                } catch (\Exception $e) {
                    $this->logError('batch_item_failed', $e->getMessage(), [
                        'item' => $item
                    ]);
                    $this->recordMetric('batch_item_failure', 1);
                }
            }

            $this->logOperation('batch_process_complete', [
                'batch_index' => $batchIndex,
                'batch_size' => count($batch),
                'results_count' => count($results)
            ]);
        }

        return $results;
    }

    /**
     * Get service statistics
     */
    public function getStatistics(): array
    {
        return [
            'service_name' => $this->serviceName,
            'audit_enabled' => $this->auditEnabled,
            'monitoring_enabled' => $this->monitoringEnabled,
            'caching_enabled' => $this->cachingEnabled,
            'default_cache_ttl' => $this->defaultCacheTtl,
            'config' => $this->config
        ];
    }

    /**
     * Get service health status
     */
    public function getHealthStatus(): array
    {
        return [
            'service_name' => $this->serviceName,
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'metrics' => $this->monitoringService->getServiceMetrics($this->serviceName) ?? []
        ];
    }

    /**
     * Validate service configuration
     */
    public function validateConfiguration(): bool
    {
        // Override in child classes for service-specific validation
        return true;
    }
} 