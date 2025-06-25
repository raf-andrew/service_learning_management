<?php

namespace App\Repositories;

use App\Contracts\Repositories\DeveloperCredentialRepositoryInterface;
use App\Models\DeveloperCredential;
use App\Models\User;
use App\Traits\HasLogging;
use App\Traits\HasCaching;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Developer Credential Repository
 * 
 * Handles data access for developer credentials.
 * Implements interface-driven architecture with logging and caching.
 */
class DeveloperCredentialRepository implements DeveloperCredentialRepositoryInterface
{
    use HasLogging, HasCaching;

    /**
     * Find a model by its primary key
     *
     * @param int $id
     * @param array<string> $with Relationships to eager load
     * @return \App\Models\DeveloperCredential|null
     */
    public function find(int $id, array $with = []): ?DeveloperCredential
    {
        $cacheKey = "credential_{$id}";
        
        return $this->rememberInCache($cacheKey, function () use ($id, $with) {
            $query = DeveloperCredential::query();
            
            if (!empty($with)) {
                $query->with($with);
            }
            
            return $query->find($id);
        }, [], $this->getCacheTtlForType('user_data'));
    }

    /**
     * Find a model by its primary key or throw an exception
     *
     * @param int $id
     * @param array<string> $with Relationships to eager load
     * @return \App\Models\DeveloperCredential
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $with = []): DeveloperCredential
    {
        $credential = $this->find($id, $with);
        
        if (!$credential) {
            throw new ModelNotFoundException("DeveloperCredential with ID {$id} not found");
        }
        
        return $credential;
    }

    /**
     * Get all models
     *
     * @param array<string> $with Relationships to eager load
     * @param array<string, mixed> $filters Filters to apply
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAll(array $with = [], array $filters = []): Collection
    {
        $cacheKey = "credentials_all_" . md5(serialize($filters));
        
        return $this->rememberInCache($cacheKey, function () use ($with, $filters) {
            $query = DeveloperCredential::query();
            
            if (!empty($with)) {
                $query->with($with);
            }
            
            // Apply filters
            foreach ($filters as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
            
            return $query->get();
        }, [], $this->getCacheTtlForType('user_data'));
    }

    /**
     * Create a new model
     *
     * @param array<string, mixed> $data
     * @return \App\Models\DeveloperCredential
     */
    public function create(array $data): DeveloperCredential
    {
        $startTime = microtime(true);
        
        try {
            $this->logDatabaseOperation('create', 'developer_credentials');
            
            $credential = DeveloperCredential::create($data);
            
            $duration = microtime(true) - $startTime;
            $this->logPerformance('create_credential', $duration, [
                'credential_id' => $credential->id,
                'user_id' => $credential->user_id,
            ]);
            
            return $credential;
            
        } catch (\Exception $e) {
            $this->logErrorWithException('create_credential', $e, ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Update a model
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return \App\Models\DeveloperCredential
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): DeveloperCredential
    {
        $startTime = microtime(true);
        
        try {
            $this->logDatabaseOperation('update', 'developer_credentials');
            
            $credential = $this->findOrFail($id);
            $credential->update($data);
            $credential->refresh();
            
            $duration = microtime(true) - $startTime;
            $this->logPerformance('update_credential', $duration, [
                'credential_id' => $id,
            ]);
            
            return $credential;
            
        } catch (\Exception $e) {
            $this->logErrorWithException('update_credential', $e, [
                'id' => $id,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Delete a model
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $startTime = microtime(true);
        
        try {
            $this->logDatabaseOperation('delete', 'developer_credentials');
            
            $credential = $this->findOrFail($id);
            $deleted = $credential->delete();
            
            $duration = microtime(true) - $startTime;
            $this->logPerformance('delete_credential', $duration, [
                'credential_id' => $id,
            ]);
            
            return $deleted;
            
        } catch (\Exception $e) {
            $this->logErrorWithException('delete_credential', $e, ['id' => $id]);
            throw $e;
        }
    }

    /**
     * Find active credential for user
     *
     * @param \App\Models\User $user
     * @return \App\Models\DeveloperCredential|null
     */
    public function findActiveCredential(User $user): ?DeveloperCredential
    {
        $cacheKey = "active_credential_{$user->id}";
        
        return $this->rememberInCache($cacheKey, function () use ($user) {
            return DeveloperCredential::where('user_id', $user->id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();
        }, [], $this->getCacheTtlForType('user_data'));
    }

    /**
     * Get all credentials for user
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCredentials(User $user): Collection
    {
        $cacheKey = "user_credentials_{$user->id}";
        
        return $this->rememberInCache($cacheKey, function () use ($user) {
            return DeveloperCredential::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }, [], $this->getCacheTtlForType('user_data'));
    }

    /**
     * Find credentials by GitHub username
     *
     * @param string $githubUsername
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByGithubUsername(string $githubUsername): Collection
    {
        $cacheKey = "credentials_github_{$githubUsername}";
        
        return $this->rememberInCache($cacheKey, function () use ($githubUsername) {
            return DeveloperCredential::where('github_username', $githubUsername)
                ->where('is_active', true)
                ->get();
        }, [], $this->getCacheTtlForType('user_data'));
    }

    /**
     * Find expired credentials
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findExpiredCredentials(): Collection
    {
        return DeveloperCredential::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();
    }

    /**
     * Deactivate expired credentials
     *
     * @return int Number of deactivated credentials
     */
    public function deactivateExpiredCredentials(): int
    {
        $startTime = microtime(true);
        
        try {
            $this->logDatabaseOperation('update', 'developer_credentials');
            
            $count = DeveloperCredential::where('is_active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->update(['is_active' => false]);
            
            $duration = microtime(true) - $startTime;
            $this->logPerformance('deactivate_expired_credentials', $duration, [
                'deactivated_count' => $count,
            ]);
            
            return $count;
            
        } catch (\Exception $e) {
            $this->logErrorWithException('deactivate_expired_credentials', $e);
            throw $e;
        }
    }

    /**
     * Find credentials by permission
     *
     * @param string $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByPermission(string $permission): Collection
    {
        $cacheKey = "credentials_permission_{$permission}";
        
        return $this->rememberInCache($cacheKey, function () use ($permission) {
            return DeveloperCredential::where('is_active', true)
                ->whereJsonContains("permissions->{$permission}", true)
                ->get();
        }, [], $this->getCacheTtlForType('user_data'));
    }

    /**
     * Get credentials statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $cacheKey = 'credentials_statistics';
        
        return $this->rememberInCache($cacheKey, function () {
            $total = DeveloperCredential::count();
            $active = DeveloperCredential::where('is_active', true)->count();
            $expired = DeveloperCredential::where('is_active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count();
            
            return [
                'total' => $total,
                'active' => $active,
                'expired' => $expired,
                'expired_percentage' => $total > 0 ? round(($expired / $total) * 100, 2) : 0,
                'last_updated' => now()->toISOString(),
            ];
        }, [], $this->getCacheTtlForType('statistics'));
    }

    /**
     * Clear user-specific cache
     *
     * @param int $userId
     * @return void
     */
    public function clearUserCache(int $userId): void
    {
        $this->removeFromCache("active_credential_{$userId}");
        $this->removeFromCache("user_credentials_{$userId}");
        $this->removeFromCache("credential_{$userId}");
    }

    /**
     * Clear cache for this repository
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->clearAllCache();
    }
} 