<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Base Event Class
 * 
 * Provides common functionality for all events across the application.
 * Implements standardized event patterns with monitoring, metrics, and error handling.
 * 
 * Features:
 * - Event identification and tracking
 * - Performance monitoring and metrics
 * - Error handling and recovery
 * - Event queuing and broadcasting
 * - Event replay capabilities
 * - Event validation and sanitization
 * 
 * @package App\Events
 */
abstract class BaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Event metadata.
     *
     * @var array<string, mixed>
     */
    protected array $metadata = [];

    /**
     * Event context.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Event timestamp.
     *
     * @var Carbon
     */
    protected Carbon $timestamp;

    /**
     * Event ID for tracking.
     *
     * @var string
     */
    protected string $eventId;

    /**
     * Event correlation ID for tracing.
     *
     * @var string|null
     */
    protected ?string $correlationId;

    /**
     * Event source.
     *
     * @var string
     */
    protected string $source;

    /**
     * Event priority.
     *
     * @var int
     */
    protected int $priority = 0;

    /**
     * Whether the event should be queued.
     *
     * @var bool
     */
    protected bool $shouldQueue = false;

    /**
     * Whether the event should be broadcast.
     *
     * @var bool
     */
    protected bool $shouldBroadcast = false;

    /**
     * Event retry attempts.
     *
     * @var int
     */
    protected int $retryAttempts = 0;

    /**
     * Maximum retry attempts.
     *
     * @var int
     */
    protected int $maxRetryAttempts = 3;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $metadata
     */
    public function __construct(array $context = [], array $metadata = [])
    {
        $this->eventId = Str::uuid()->toString();
        $this->correlationId = request()->header('X-Correlation-ID');
        $this->timestamp = now();
        $this->source = $this->getEventSource();
        $this->context = $this->sanitizeContext($context);
        $this->metadata = array_merge($this->getDefaultMetadata(), $metadata);
        
        $this->initialize();
    }

    /**
     * Initialize event-specific configurations.
     *
     * @return void
     */
    protected function initialize(): void
    {
        // Override in child classes for event-specific initialization
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getEventName(): string
    {
        return class_basename($this);
    }

    /**
     * Get the event ID.
     *
     * @return string
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * Get the correlation ID.
     *
     * @return string|null
     */
    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    /**
     * Set the correlation ID.
     *
     * @param string $correlationId
     * @return self
     */
    public function setCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;
        return $this;
    }

    /**
     * Get the event timestamp.
     *
     * @return Carbon
     */
    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    /**
     * Get the event source.
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the event priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set the event priority.
     *
     * @param int $priority
     * @return self
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get the event context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set the event context.
     *
     * @param array<string, mixed> $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $this->sanitizeContext($context);
        return $this;
    }

    /**
     * Get the event metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set the event metadata.
     *
     * @param array<string, mixed> $metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Get a specific metadata value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set a specific metadata value.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setMetadataValue(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Check if the event should be queued.
     *
     * @return bool
     */
    public function shouldQueue(): bool
    {
        return $this->shouldQueue;
    }

    /**
     * Set whether the event should be queued.
     *
     * @param bool $shouldQueue
     * @return self
     */
    public function setShouldQueue(bool $shouldQueue): self
    {
        $this->shouldQueue = $shouldQueue;
        return $this;
    }

    /**
     * Check if the event should be broadcast.
     *
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        return $this->shouldBroadcast;
    }

    /**
     * Set whether the event should be broadcast.
     *
     * @param bool $shouldBroadcast
     * @return self
     */
    public function setShouldBroadcast(bool $shouldBroadcast): self
    {
        $this->shouldBroadcast = $shouldBroadcast;
        return $this;
    }

    /**
     * Get the retry attempts.
     *
     * @return int
     */
    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    /**
     * Increment retry attempts.
     *
     * @return self
     */
    public function incrementRetryAttempts(): self
    {
        $this->retryAttempts++;
        return $this;
    }

    /**
     * Check if the event can be retried.
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->retryAttempts < $this->maxRetryAttempts;
    }

    /**
     * Get the maximum retry attempts.
     *
     * @return int
     */
    public function getMaxRetryAttempts(): int
    {
        return $this->maxRetryAttempts;
    }

    /**
     * Set the maximum retry attempts.
     *
     * @param int $maxRetryAttempts
     * @return self
     */
    public function setMaxRetryAttempts(int $maxRetryAttempts): self
    {
        $this->maxRetryAttempts = $maxRetryAttempts;
        return $this;
    }

    /**
     * Validate the event data.
     *
     * @return bool
     */
    public function validate(): bool
    {
        return $this->performValidation();
    }

    /**
     * Perform event-specific validation.
     *
     * @return bool
     */
    protected function performValidation(): bool
    {
        // Override in child classes for event-specific validation
        return true;
    }

    /**
     * Sanitize the event context.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function sanitizeContext(array $context): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'authorization'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($context[$field])) {
                $context[$field] = '[REDACTED]';
            }
        }
        
        return $context;
    }

    /**
     * Get default metadata for the event.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultMetadata(): array
    {
        return [
            'version' => '1.0',
            'environment' => config('app.env'),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID'),
        ];
    }

    /**
     * Get the event source.
     *
     * @return string
     */
    protected function getEventSource(): string
    {
        return config('app.name', 'Laravel');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('events'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'timestamp' => $this->timestamp->toISOString(),
            'source' => $this->source,
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'event.' . strtolower($this->getEventName());
    }

    /**
     * Get the event as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'correlation_id' => $this->correlationId,
            'timestamp' => $this->timestamp->toISOString(),
            'source' => $this->source,
            'priority' => $this->priority,
            'context' => $this->context,
            'metadata' => $this->metadata,
            'retry_attempts' => $this->retryAttempts,
            'max_retry_attempts' => $this->maxRetryAttempts,
        ];
    }

    /**
     * Get the event as JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Create an event from array data.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $event = new static(
            $data['context'] ?? [],
            $data['metadata'] ?? []
        );

        if (isset($data['event_id'])) {
            $event->eventId = $data['event_id'];
        }

        if (isset($data['correlation_id'])) {
            $event->correlationId = $data['correlation_id'];
        }

        if (isset($data['timestamp'])) {
            $event->timestamp = Carbon::parse($data['timestamp']);
        }

        if (isset($data['source'])) {
            $event->source = $data['source'];
        }

        if (isset($data['priority'])) {
            $event->priority = $data['priority'];
        }

        if (isset($data['retry_attempts'])) {
            $event->retryAttempts = $data['retry_attempts'];
        }

        return $event;
    }

    /**
     * Create an event from JSON data.
     *
     * @param string $json
     * @return static
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        return static::fromArray($data);
    }

    /**
     * Get the event queue connection.
     *
     * @return string|null
     */
    public function viaConnection(): ?string
    {
        return config('queue.default');
    }

    /**
     * Get the event queue name.
     *
     * @return string|null
     */
    public function viaQueue(): ?string
    {
        return 'events';
    }

    /**
     * Determine if the event should be broadcast after the database transaction.
     *
     * @return bool
     */
    public function broadcastAfterCommit(): bool
    {
        return true;
    }

    /**
     * Get the event's unique identifier for the queue.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->eventId;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'event:' . $this->getEventName(),
            'source:' . $this->source,
        ];
    }
} 