<?php

namespace App\Traits\Services;

use App\Modules\Shared\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Auditable Trait
 * 
 * Provides audit logging functionality for services.
 * Implements comprehensive audit trail capabilities.
 */
trait AuditableTrait
{
    /**
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * @var bool
     */
    protected bool $auditEnabled = true;

    /**
     * Initialize audit service
     */
    protected function initializeAudit(): void
    {
        $this->auditService = app(AuditService::class);
        $this->auditEnabled = config('audit.enabled', true);
    }

    /**
     * Log audit event
     */
    protected function logAuditEvent(string $action, array $context = [], string $level = 'info'): void
    {
        if (!$this->auditEnabled) {
            return;
        }

        $auditContext = array_merge($context, [
            'service' => $this->getServiceName(),
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id() ?? 'system',
            'ip_address' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown'
        ]);

        // Log to Laravel log
        Log::log($level, "Audit: {$action}", $auditContext);

        // Log to audit service
        $this->auditService->log($this->getServiceName(), $action, $auditContext);
    }

    /**
     * Log data access
     */
    protected function logDataAccess(string $resource, $resourceId, string $action, array $context = []): void
    {
        $this->logAuditEvent('data_access', array_merge($context, [
            'resource' => $resource,
            'resource_id' => $resourceId,
            'action' => $action
        ]));
    }

    /**
     * Log data modification
     */
    protected function logDataModification(string $resource, $resourceId, string $action, array $oldData = [], array $newData = []): void
    {
        $this->logAuditEvent('data_modification', [
            'resource' => $resource,
            'resource_id' => $resourceId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'changes' => $this->calculateChanges($oldData, $newData)
        ]);
    }

    /**
     * Log security event
     */
    protected function logSecurityEvent(string $event, array $context = []): void
    {
        $this->logAuditEvent('security_event', array_merge($context, [
            'event' => $event
        ]), 'warning');
    }

    /**
     * Log performance event
     */
    protected function logPerformanceEvent(string $operation, float $duration, array $context = []): void
    {
        $this->logAuditEvent('performance_event', array_merge($context, [
            'operation' => $operation,
            'duration' => $duration,
            'duration_ms' => round($duration * 1000, 2)
        ]));
    }

    /**
     * Log error event
     */
    protected function logErrorEvent(string $operation, string $error, array $context = []): void
    {
        $this->logAuditEvent('error_event', array_merge($context, [
            'operation' => $operation,
            'error' => $error
        ]), 'error');
    }

    /**
     * Calculate changes between old and new data
     */
    protected function calculateChanges(array $oldData, array $newData): array
    {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            if (!array_key_exists($key, $oldData) || $oldData[$key] !== $newValue) {
                $changes[$key] = [
                    'old' => $oldData[$key] ?? null,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    /**
     * Get audit trail for a resource
     */
    public function getAuditTrail(string $resource, $resourceId, int $limit = 100): array
    {
        if (!$this->auditEnabled) {
            return [];
        }

        return $this->auditService->getAuditTrail($this->getServiceName(), $resource, $resourceId, $limit);
    }

    /**
     * Get service name for audit logging
     */
    abstract protected function getServiceName(): string;
} 