<?php

namespace App\Services;

use App\Contracts\Services\DeveloperCredentialServiceInterface;
use App\Contracts\Repositories\DeveloperCredentialRepositoryInterface;
use App\Models\DeveloperCredential;
use App\Models\User;
use App\Events\DeveloperCredentialCreated;
use App\Repositories\DeveloperCredentialRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Collection;

class DeveloperCredentialService extends BaseService
{
    protected DeveloperCredentialRepository $repository;

    public function __construct(DeveloperCredentialRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new developer credential
     */
    public function createCredential(User $user, array $data): DeveloperCredential
    {
        $this->validateInput($data, [
            'github_token' => 'required|string|min:10',
            'github_username' => 'required|string|min:1|max:255',
        ]);

        $sanitizedData = $this->sanitizeInput($data);

        $credential = $this->repository->create([
            'user_id' => $user->id,
            'github_token' => $sanitizedData['github_token'],
            'github_username' => $sanitizedData['github_username'],
            'permissions' => $this->getDefaultPermissions(),
            'is_active' => true,
            'expires_at' => now()->addYear()
        ]);

        // Fire event for credential creation
        Event::dispatch(new DeveloperCredentialCreated($credential));

        return $credential;
    }

    /**
     * Get active credential for user
     */
    public function getActiveCredential(User $user): ?DeveloperCredential
    {
        return $this->repository->findActiveCredential($user);
    }

    /**
     * Get all credentials for user
     */
    public function getUserCredentials(User $user): Collection
    {
        return $this->repository->getUserCredentials($user);
    }

    /**
     * Update credential
     */
    public function updateCredential(DeveloperCredential $credential, array $data): DeveloperCredential
    {
        $this->validateInput($data, [
            'github_username' => 'sometimes|string|min:1|max:255',
            'permissions' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $sanitizedData = $this->sanitizeInput($data);

        return $this->repository->update($credential->id, $sanitizedData);
    }

    /**
     * Delete credential
     */
    public function deleteCredential(DeveloperCredential $credential): bool
    {
        return $this->repository->delete($credential->id);
    }

    /**
     * Activate credential
     */
    public function activateCredential(DeveloperCredential $credential): DeveloperCredential
    {
        $this->logOperation('activateCredential', ['credential_id' => $credential->id]);

        try {
            // Deactivate all other credentials for this user
            DeveloperCredential::where('user_id', $credential->user_id)
                ->where('id', '!=', $credential->id)
                ->update(['is_active' => false]);

            $updatedCredential = $this->updateModel($credential, [
                'is_active' => true,
                'expires_at' => now()->addYear()
            ]);

            $this->clearUserCredentialsCache($credential->user_id);

            return $updatedCredential;
        } catch (\Exception $e) {
            $this->logError('activateCredential', $e, ['credential_id' => $credential->id]);
            throw $e;
        }
    }

    /**
     * Deactivate credential
     */
    public function deactivateCredential(DeveloperCredential $credential): DeveloperCredential
    {
        $this->logOperation('deactivateCredential', ['credential_id' => $credential->id]);

        try {
            $updatedCredential = $this->updateModel($credential, ['is_active' => false]);

            $this->clearUserCredentialsCache($credential->user_id);

            return $updatedCredential;
        } catch (\Exception $e) {
            $this->logError('deactivateCredential', $e, ['credential_id' => $credential->id]);
            throw $e;
        }
    }

    /**
     * Validate GitHub token
     */
    public function validateGitHubToken(string $token): bool
    {
        try {
            // This would typically make an API call to GitHub to validate the token
            // For now, we'll do basic validation
            return !empty($token) && strlen($token) >= 40;
        } catch (\Exception $e) {
            $this->logError('validateGitHubToken', $e);
            return false;
        }
    }

    /**
     * Get expired credentials
     */
    public function getExpiredCredentials(): \Illuminate\Database\Eloquent\Collection
    {
        return DeveloperCredential::where('expires_at', '<', now())
            ->where('is_active', true)
            ->get();
    }

    /**
     * Clean up expired credentials
     */
    public function cleanupExpiredCredentials(): int
    {
        $this->logOperation('cleanupExpiredCredentials');

        try {
            $expiredCredentials = $this->getExpiredCredentials();
            $count = $expiredCredentials->count();

            foreach ($expiredCredentials as $credential) {
                $this->deactivateCredential($credential);
            }

            $this->logOperation('cleanupExpiredCredentials', ['cleaned_count' => $count]);

            return $count;
        } catch (\Exception $e) {
            $this->logError('cleanupExpiredCredentials', $e);
            throw $e;
        }
    }

    /**
     * Get default permissions
     */
    private function getDefaultPermissions(): array
    {
        return [
            'codespaces' => true,
            'repositories' => true,
            'workflows' => true
        ];
    }

    /**
     * Clear user credentials cache
     */
    private function clearUserCredentialsCache(int $userId): void
    {
        $patterns = [
            "active_credential:user_id:{$userId}",
            "user_credentials:user_id:{$userId}"
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    public function deactivateExpiredCredentials(): int
    {
        return $this->repository->deactivateExpiredCredentials();
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
} 