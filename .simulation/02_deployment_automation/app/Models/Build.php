<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Build extends Model
{
    protected $fillable = [
        'environment_id',
        'branch',
        'commit_hash',
        'commit_message',
        'status',
        'build_number',
        'artifacts',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'artifacts' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
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

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }

    public function markAsSuccessful(array $artifacts = []): void
    {
        $this->update([
            'status' => 'success',
            'artifacts' => $artifacts,
            'completed_at' => now()
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
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
} 