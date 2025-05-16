<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ApiKeyService
{
    /**
     * Generate a new API key
     *
     * @return string
     */
    public function generateKey(): string
    {
        return Str::random(32);
    }

    /**
     * Create a new API key
     *
     * @param array $data
     * @return ApiKey
     */
    public function createApiKey(array $data): ApiKey
    {
        $key = $this->generateKey();
        
        return ApiKey::create([
            'name' => $data['name'],
            'key' => Hash::make($key),
            'user_id' => $data['user_id'],
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Validate an API key
     *
     * @param string $key
     * @return ApiKey|null
     */
    public function validateKey(string $key): ?ApiKey
    {
        $apiKey = ApiKey::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get()
            ->first(function ($apiKey) use ($key) {
                return Hash::check($key, $apiKey->key);
            });

        return $apiKey;
    }

    /**
     * Update an API key
     *
     * @param ApiKey $apiKey
     * @param array $data
     * @return ApiKey
     */
    public function updateApiKey(ApiKey $apiKey, array $data): ApiKey
    {
        $apiKey->update($data);
        return $apiKey;
    }

    /**
     * Delete an API key
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function deleteApiKey(ApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }

    /**
     * Get API keys for a user
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserApiKeys(int $userId)
    {
        return ApiKey::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Deactivate an API key
     *
     * @param ApiKey $apiKey
     * @return ApiKey
     */
    public function deactivateApiKey(ApiKey $apiKey): ApiKey
    {
        $apiKey->update(['is_active' => false]);
        return $apiKey;
    }

    /**
     * Reactivate an API key
     *
     * @param ApiKey $apiKey
     * @return ApiKey
     */
    public function reactivateApiKey(ApiKey $apiKey): ApiKey
    {
        $apiKey->update(['is_active' => true]);
        return $apiKey;
    }
} 