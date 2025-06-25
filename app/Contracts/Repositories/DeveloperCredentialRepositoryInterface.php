<?php

namespace App\Contracts\Repositories;

use App\Models\DeveloperCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Developer Credential Repository Interface
 * 
 * Defines the contract for developer credential data access operations.
 * This interface ensures loose coupling and enables easy testing and mocking.
 */
interface DeveloperCredentialRepositoryInterface
{
    /**
     * Find a model by its primary key
     *
     * @param int $id
     * @param array<string> $with Relationships to eager load
     * @return \App\Models\DeveloperCredential|null
     */
    public function find(int $id, array $with = []): ?DeveloperCredential;

    /**
     * Find a model by its primary key or throw an exception
     *
     * @param int $id
     * @param array<string> $with Relationships to eager load
     * @return \App\Models\DeveloperCredential
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $with = []): DeveloperCredential;

    /**
     * Get all models
     *
     * @param array<string> $with Relationships to eager load
     * @param array<string, mixed> $filters Filters to apply
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAll(array $with = [], array $filters = []): Collection;

    /**
     * Create a new model
     *
     * @param array<string, mixed> $data
     * @return \App\Models\DeveloperCredential
     */
    public function create(array $data): DeveloperCredential;

    /**
     * Update a model
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return \App\Models\DeveloperCredential
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): DeveloperCredential;

    /**
     * Delete a model
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool;

    /**
     * Find active credential for user
     *
     * @param \App\Models\User $user
     * @return \App\Models\DeveloperCredential|null
     */
    public function findActiveCredential(User $user): ?DeveloperCredential;

    /**
     * Get all credentials for user
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCredentials(User $user): Collection;

    /**
     * Find credentials by GitHub username
     *
     * @param string $githubUsername
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByGithubUsername(string $githubUsername): Collection;

    /**
     * Find expired credentials
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findExpiredCredentials(): Collection;

    /**
     * Deactivate expired credentials
     *
     * @return int Number of deactivated credentials
     */
    public function deactivateExpiredCredentials(): int;

    /**
     * Find credentials by permission
     *
     * @param string $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByPermission(string $permission): Collection;

    /**
     * Get credentials statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    /**
     * Clear user-specific cache
     *
     * @param int $userId
     * @return void
     */
    public function clearUserCache(int $userId): void;

    /**
     * Clear cache for this repository
     *
     * @return void
     */
    public function clearCache(): void;
} 