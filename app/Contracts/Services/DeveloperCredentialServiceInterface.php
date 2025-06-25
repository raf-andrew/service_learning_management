<?php

namespace App\Contracts\Services;

use App\Models\DeveloperCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Developer Credential Service Interface
 * 
 * Defines the contract for developer credential management operations.
 * This interface ensures loose coupling and enables easy testing and mocking.
 */
interface DeveloperCredentialServiceInterface
{
    /**
     * Create a new developer credential
     *
     * @param \App\Models\User $user
     * @param array<string, mixed> $data
     * @return \App\Models\DeveloperCredential
     * @throws \App\Exceptions\CredentialException
     */
    public function createCredential(User $user, array $data): DeveloperCredential;

    /**
     * Get active credential for user
     *
     * @param \App\Models\User $user
     * @return \App\Models\DeveloperCredential|null
     */
    public function getActiveCredential(User $user): ?DeveloperCredential;

    /**
     * Get all credentials for user
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCredentials(User $user): Collection;

    /**
     * Update a credential
     *
     * @param \App\Models\DeveloperCredential $credential
     * @param array<string, mixed> $data
     * @return \App\Models\DeveloperCredential
     * @throws \App\Exceptions\CredentialException
     */
    public function updateCredential(DeveloperCredential $credential, array $data): DeveloperCredential;

    /**
     * Delete a credential
     *
     * @param \App\Models\DeveloperCredential $credential
     * @return bool
     * @throws \App\Exceptions\CredentialException
     */
    public function deleteCredential(DeveloperCredential $credential): bool;

    /**
     * Deactivate expired credentials
     *
     * @return int Number of deactivated credentials
     */
    public function deactivateExpiredCredentials(): int;

    /**
     * Get credentials statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    /**
     * Validate GitHub token
     *
     * @param string $token
     * @return bool
     */
    public function validateGitHubToken(string $token): bool;

    /**
     * Get default permissions for new credentials
     *
     * @return array<string, bool>
     */
    public function getDefaultPermissions(): array;
} 