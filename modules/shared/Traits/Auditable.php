<?php

namespace Modules\Shared\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait Auditable
{
    /**
     * Log an audit event
     */
    public function logAudit(string $action, array $context = []): void
    {
        $auditData = [
            'model' => get_class($this),
            'action' => $action,
            'user_id' => $this->getCurrentUserId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        // Log to Laravel log
        $this->logToLaravel($auditData);

        // Store in database if configured
        $this->storeInDatabase($auditData);
    }

    /**
     * Get current user ID safely
     */
    protected function getCurrentUserId(): ?int
    {
        try {
            return Auth::id();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Log to Laravel log system
     */
    protected function logToLaravel(array $auditData): void
    {
        $message = "AUDIT: [{$auditData['model']}] {$auditData['action']}";
        Log::info($message, $auditData);
    }

    /**
     * Store audit data in database
     */
    protected function storeInDatabase(array $auditData): void
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('audit_logs')) {
                return;
            }

            DB::table('audit_logs')->insert([
                'model' => $auditData['model'],
                'action' => $auditData['action'],
                'user_id' => $auditData['user_id'],
                'ip_address' => $auditData['ip_address'],
                'user_agent' => $auditData['user_agent'],
                'context' => json_encode($auditData['context']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store audit log in database', [
                'error' => $e->getMessage(),
                'audit_data' => $auditData
            ]);
        }
    }

    /**
     * Log creation event
     */
    public function logCreated(array $context = []): void
    {
        $this->logAudit('created', $context);
    }

    /**
     * Log update event
     */
    public function logUpdated(array $context = []): void
    {
        $this->logAudit('updated', $context);
    }

    /**
     * Log deletion event
     */
    public function logDeleted(array $context = []): void
    {
        $this->logAudit('deleted', $context);
    }

    /**
     * Log access event
     */
    public function logAccessed(array $context = []): void
    {
        $this->logAudit('accessed', $context);
    }

    /**
     * Log security event
     */
    public function logSecurity(string $action, array $context = []): void
    {
        $this->logAudit("security.{$action}", $context);
    }
} 