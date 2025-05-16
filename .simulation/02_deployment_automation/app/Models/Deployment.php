<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $fillable = [
        'environment_id',
        'build_id',
        'status',
        'deployed_by',
        'deployment_number',
        'rollback_to',
        'started_at',
        'completed_at',
        'error_message'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rollback_to' => 'integer'
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function build(): BelongsTo
    {
        return $this->belongsTo(Build::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isRollback(): bool
    {
        return !is_null($this->rollback_to);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }

    public function markAsSuccessful(): void
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now()
        ]);

        $this->environment->updateLastDeployment($this);
    }

    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    public function getDuration(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    public function rollbackTo(Deployment $deployment): void
    {
        $this->update([
            'rollback_to' => $deployment->id,
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }
} 