<?php

namespace App\Modules\Soc2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'soc2_audit_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'model_type',
        'model_id',
        'action', // create, update, delete, view, export, validate, assess
        'description',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
        'status', // success, failure, warning
        'error_message',
        'execution_time', // milliseconds
        'resource_consumed', // memory, cpu, etc.
        'compliance_relevant', // whether this action is relevant for compliance
        'data_classification', // public, internal, confidential, restricted
        'retention_period', // days to retain this log
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'compliance_relevant' => 'boolean',
        'execution_time' => 'integer',
        'retention_period' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'request_data',
        'response_data',
        'metadata',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the related model instance.
     */
    public function auditable()
    {
        return $this->morphTo('model');
    }

    /**
     * Scope a query to only include compliance-relevant logs.
     */
    public function scopeComplianceRelevant($query)
    {
        return $query->where('compliance_relevant', true);
    }

    /**
     * Scope a query to only include logs by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include logs by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs by model type.
     */
    public function scopeByModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include logs by data classification.
     */
    public function scopeByDataClassification($query, string $classification)
    {
        return $query->where('data_classification', $classification);
    }

    /**
     * Check if the log entry is successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the log entry is a failure.
     */
    public function isFailure(): bool
    {
        return $this->status === 'failure';
    }

    /**
     * Check if the log entry is a warning.
     */
    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    /**
     * Check if the log entry is compliance-relevant.
     */
    public function isComplianceRelevant(): bool
    {
        return $this->compliance_relevant;
    }

    /**
     * Check if the log entry should be retained.
     */
    public function shouldBeRetained(): bool
    {
        if (!$this->retention_period) {
            return true; // Keep indefinitely if no retention period specified
        }

        return $this->created_at->addDays($this->retention_period)->isFuture();
    }

    /**
     * Get the action display name.
     */
    public function getActionDisplay(): string
    {
        return ucfirst($this->action);
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplay(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get the data classification display name.
     */
    public function getDataClassificationDisplay(): string
    {
        return ucfirst($this->data_classification);
    }

    /**
     * Get the execution time in a human-readable format.
     */
    public function getExecutionTimeDisplay(): string
    {
        if ($this->execution_time < 1000) {
            return "{$this->execution_time}ms";
        } else {
            return round($this->execution_time / 1000, 2) . 's';
        }
    }

    /**
     * Get the log age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get the days until retention expires.
     */
    public function getDaysUntilRetentionExpires(): ?int
    {
        if (!$this->retention_period) {
            return null;
        }

        $expiryDate = $this->created_at->addDays($this->retention_period);
        return max(0, $expiryDate->diffInDays(now()));
    }

    /**
     * Check if the log entry is high-priority for compliance.
     */
    public function isHighPriorityCompliance(): bool
    {
        return $this->compliance_relevant && 
               ($this->isFailure() || 
                $this->data_classification === 'restricted' ||
                in_array($this->action, ['delete', 'export', 'validate']));
    }

    /**
     * Get a summary of the log entry for display.
     */
    public function getSummary(): string
    {
        $summary = "User {$this->user_id} performed {$this->action}";
        
        if ($this->model_type && $this->model_id) {
            $summary .= " on {$this->model_type} #{$this->model_id}";
        }
        
        if ($this->status !== 'success') {
            $summary .= " (Status: {$this->status})";
        }
        
        return $summary;
    }

    /**
     * Get the request data in a safe format.
     */
    public function getSafeRequestData(): array
    {
        $data = $this->request_data ?? [];
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'credential'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }
        
        return $data;
    }

    /**
     * Check if the log entry contains sensitive data.
     */
    public function containsSensitiveData(): bool
    {
        return in_array($this->data_classification, ['confidential', 'restricted']) ||
               $this->action === 'export' ||
               $this->action === 'view';
    }

    /**
     * Get the compliance category for this log entry.
     */
    public function getComplianceCategory(): string
    {
        if ($this->action === 'validate') {
            return 'validation';
        } elseif ($this->action === 'assess') {
            return 'assessment';
        } elseif ($this->action === 'export') {
            return 'data_export';
        } elseif ($this->action === 'delete') {
            return 'data_deletion';
        } else {
            return 'general';
        }
    }
} 