<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    protected $fillable = [
        'name',
        'branch',
        'url',
        'variables',
        'status',
        'last_deployment_id',
        'last_deployment_at'
    ];

    protected $casts = [
        'variables' => 'array',
        'last_deployment_at' => 'datetime'
    ];

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function builds(): HasMany
    {
        return $this->hasMany(Build::class);
    }

    public function getLastDeployment()
    {
        return $this->deployments()->latest()->first();
    }

    public function getLastSuccessfulDeployment()
    {
        return $this->deployments()
            ->where('status', 'success')
            ->latest()
            ->first();
    }

    public function isDeployable(): bool
    {
        return $this->status === 'ready';
    }

    public function markAsDeploying(): void
    {
        $this->update(['status' => 'deploying']);
    }

    public function markAsReady(): void
    {
        $this->update(['status' => 'ready']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function updateLastDeployment(Deployment $deployment): void
    {
        $this->update([
            'last_deployment_id' => $deployment->id,
            'last_deployment_at' => now()
        ]);
    }
} 