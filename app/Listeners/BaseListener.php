<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\BaseEvent;
use App\Services\EnhancedAuditService;
use App\Services\CacheService;

/**
 * Base Listener Class
 * 
 * Provides common functionality for all event listeners across the application.
 * Implements standardized listener patterns with error handling, monitoring, and metrics.
 * 
 * Features:
 * - Error handling and recovery
 * - Performance monitoring and metrics
 * - Event replay capabilities
 * - Listener queuing and processing
 * - Event monitoring and metrics
 * - Graceful degradation
 * 
 * @package App\Listeners
 */
abstract class BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Whether the listener should be queued.
     *
     * @var bool
     */
    protected bool $shouldQueue = true;

    /**
     * Whether the listener should be processed immediately.
     *
     * @var bool
     */
    protected bool $shouldProcessImmediately = false;

    /**
     * Listener configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Audit service instance.
     *
     * @var EnhancedAuditService
     */
    protected EnhancedAuditService $auditService;

    /**
     * Cache service instance.
     *
     * @var CacheService
     */
    protected CacheService $cacheService;

    /**
     * Listener statistics.
     *
     * @var array<string, mixed>
     */
    protected array $statistics = [
        'processed' => 0,
        'failed' => 0,
        'retried' => 0,
        'processing_time' => 0,
    ];

    /**
     * Create a new listener instance.
     */
    public function __construct()
    {
        $this->auditService = app(EnhancedAuditService::class);
        $this->cacheService = app(CacheService::class);
        $this->loadConfiguration();
        $this->initialize();
    }

    /**
     * Initialize listener-specific configurations.
     *
     * @return void
     */
    protected function initialize(): void
    {
        // Override in child classes for listener-specific initialization
    }

    /**
     * Load listener configuration.
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $listenerName = $this->getListenerName();
        $configKey = "listeners.{$listenerName}";
        $this->config = config($configKey, []);
        
        $this->shouldQueue = $this->config['should_queue'] ?? true;
        $this->shouldProcessImmediately = $this->config['should_process_immediately'] ?? false;
        $this->tries = $this->config['tries'] ?? 3;
        $this->backoff = $this->config['backoff'] ?? 60;
        $this->timeout = $this->config['timeout'] ?? 120;
    }

    /**
     * Handle the event.
     *
     * @param BaseEvent $event
     * @return void
     */
    public function handle(BaseEvent $event): void
    {
        $startTime = microtime(true);
        
        try {
            // Validate event
            if (!$this->validateEvent($event)) {
                $this->logListenerError('Event validation failed', $event);
                return;
            }

            // Pre-process event
            $this->preProcess($event);

            // Process event
            $this->process($event);

            // Post-process event
            $this->postProcess($event);

            // Update statistics
            $this->updateStatistics($startTime, true);

            // Log success
            $this->logListenerSuccess($event);

        } catch (\Exception $e) {
            $this->handleException($e, $event, $startTime);
        }
    }

    /**
     * Validate the event before processing.
     *
     * @param BaseEvent $event
     * @return bool
     */
    protected function validateEvent(BaseEvent $event): bool
    {
        return $event->validate();
    }

    /**
     * Pre-process the event.
     *
     * @param BaseEvent $event
     * @return void
     */
    protected function preProcess(BaseEvent $event): void
    {
        // Override in child classes for pre-processing logic
    }

    /**
     * Process the event.
     *
     * @param BaseEvent $event
     * @return void
     */
    abstract protected function process(BaseEvent $event): void;

    /**
     * Post-process the event.
     *
     * @param BaseEvent $event
     * @return void
     */
    protected function postProcess(BaseEvent $event): void
    {
        // Override in child classes for post-processing logic
    }

    /**
     * Handle exceptions during event processing.
     *
     * @param \Exception $exception
     * @param BaseEvent $event
     * @param float $startTime
     * @return void
     */
    protected function handleException(\Exception $exception, BaseEvent $event, float $startTime): void
    {
        $this->updateStatistics($startTime, false);
        $this->statistics['failed']++;

        // Log the error
        $this->logListenerError($exception->getMessage(), $event, $exception);

        // Check if we should retry
        if ($this->shouldRetry($event, $exception)) {
            $this->retryEvent($event, $exception);
        } else {
            $this->handleFinalFailure($event, $exception);
        }
    }

    /**
     * Determine if the event should be retried.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @return bool
     */
    protected function shouldRetry(BaseEvent $event, \Exception $exception): bool
    {
        // Don't retry if event has exceeded max retry attempts
        if (!$event->canRetry()) {
            return false;
        }

        // Don't retry for certain types of exceptions
        $nonRetryableExceptions = [
            \InvalidArgumentException::class,
            \TypeError::class,
            \ParseError::class,
        ];

        foreach ($nonRetryableExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retry the event processing.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @return void
     */
    protected function retryEvent(BaseEvent $event, \Exception $exception): void
    {
        $event->incrementRetryAttempts();
        $this->statistics['retried']++;

        // Calculate delay based on retry attempts
        $delay = $this->calculateRetryDelay($event->getRetryAttempts());

        // Re-queue the event
        if ($this->shouldQueue) {
            $this->release($delay);
        }

        $this->logListenerRetry($event, $exception, $delay);
    }

    /**
     * Handle final failure of event processing.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @return void
     */
    protected function handleFinalFailure(BaseEvent $event, \Exception $exception): void
    {
        // Log final failure
        $this->logListenerFinalFailure($event, $exception);

        // Store failed event for later analysis
        $this->storeFailedEvent($event, $exception);

        // Fire failure event
        $this->fireFailureEvent($event, $exception);
    }

    /**
     * Calculate retry delay based on attempt number.
     *
     * @param int $attempt
     * @return int
     */
    protected function calculateRetryDelay(int $attempt): int
    {
        // Exponential backoff with jitter
        $baseDelay = $this->backoff;
        $delay = $baseDelay * pow(2, $attempt - 1);
        $jitter = rand(0, 1000) / 1000; // Random jitter between 0-1 seconds
        
        return (int) ($delay + $jitter);
    }

    /**
     * Update listener statistics.
     *
     * @param float $startTime
     * @param bool $success
     * @return void
     */
    protected function updateStatistics(float $startTime, bool $success): void
    {
        $processingTime = microtime(true) - $startTime;
        $this->statistics['processing_time'] += $processingTime;

        if ($success) {
            $this->statistics['processed']++;
        } else {
            $this->statistics['failed']++;
        }
    }

    /**
     * Log listener success.
     *
     * @param BaseEvent $event
     * @return void
     */
    protected function logListenerSuccess(BaseEvent $event): void
    {
        $this->auditService->log('listener', 'success', [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'processing_time' => $this->statistics['processing_time'],
        ]);

        Log::info('Listener processed event successfully', [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
        ]);
    }

    /**
     * Log listener error.
     *
     * @param string $message
     * @param BaseEvent $event
     * @param \Exception|null $exception
     * @return void
     */
    protected function logListenerError(string $message, BaseEvent $event, ?\Exception $exception = null): void
    {
        $context = [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'error' => $message,
        ];

        if ($exception) {
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $this->auditService->log('listener', 'error', $context);

        Log::error('Listener processing failed', $context);
    }

    /**
     * Log listener retry.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @param int $delay
     * @return void
     */
    protected function logListenerRetry(BaseEvent $event, \Exception $exception, int $delay): void
    {
        $this->auditService->log('listener', 'retry', [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'retry_attempt' => $event->getRetryAttempts(),
            'delay' => $delay,
            'error' => $exception->getMessage(),
        ]);

        Log::warning('Listener retrying event', [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'retry_attempt' => $event->getRetryAttempts(),
            'delay' => $delay,
        ]);
    }

    /**
     * Log listener final failure.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @return void
     */
    protected function logListenerFinalFailure(BaseEvent $event, \Exception $exception): void
    {
        $this->auditService->log('listener', 'final_failure', [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'total_retries' => $event->getRetryAttempts(),
            'error' => $exception->getMessage(),
        ]);

        Log::error('Listener final failure', [
            'listener' => $this->getListenerName(),
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'total_retries' => $event->getRetryAttempts(),
        ]);
    }

    /**
     * Store failed event for later analysis.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @return void
     */
    protected function storeFailedEvent(BaseEvent $event, \Exception $exception): void
    {
        $failedEvent = [
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'listener' => $this->getListenerName(),
            'error' => $exception->getMessage(),
            'timestamp' => now()->toISOString(),
            'event_data' => $event->toArray(),
        ];

        // Store in cache for later analysis
        $cacheKey = "failed_events:{$event->getEventId()}";
        $this->cacheService->put($cacheKey, $failedEvent, 86400); // 24 hours
    }

    /**
     * Fire failure event.
     *
     * @param BaseEvent $event
     * @param \Exception $exception
     * @return void
     */
    protected function fireFailureEvent(BaseEvent $event, \Exception $exception): void
    {
        // This would typically fire a custom failure event
        // For now, we'll just log it
        Log::critical('Event processing failed permanently', [
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
            'listener' => $this->getListenerName(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the listener name.
     *
     * @return string
     */
    public function getListenerName(): string
    {
        return class_basename($this);
    }

    /**
     * Get listener statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return array_merge($this->statistics, [
            'listener_name' => $this->getListenerName(),
            'should_queue' => $this->shouldQueue,
            'should_process_immediately' => $this->shouldProcessImmediately,
            'tries' => $this->tries,
            'backoff' => $this->backoff,
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'listener:' . $this->getListenerName(),
        ];
    }

    /**
     * Get the connection the job should be sent to.
     *
     * @return string|null
     */
    public function viaConnection(): ?string
    {
        return config('queue.default');
    }

    /**
     * Get the queue the job should be sent to.
     *
     * @return string|null
     */
    public function viaQueue(): ?string
    {
        return 'listeners';
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime|null
     */
    public function retryAfter(): ?\DateTime
    {
        return now()->addSeconds($this->backoff);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Listener job failed permanently', [
            'listener' => $this->getListenerName(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->auditService->log('listener', 'job_failed', [
            'listener' => $this->getListenerName(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Determine if the listener should be processed immediately.
     *
     * @return bool
     */
    public function shouldProcessImmediately(): bool
    {
        return $this->shouldProcessImmediately;
    }

    /**
     * Set whether the listener should be processed immediately.
     *
     * @param bool $shouldProcessImmediately
     * @return self
     */
    public function setShouldProcessImmediately(bool $shouldProcessImmediately): self
    {
        $this->shouldProcessImmediately = $shouldProcessImmediately;
        return $this;
    }
} 