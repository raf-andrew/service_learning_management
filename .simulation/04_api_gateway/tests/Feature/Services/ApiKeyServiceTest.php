<?php

namespace Tests\Feature\Services;

use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ApiKeyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApiKeyService $apiKeyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKeyService = new ApiKeyService();
    }

    public function test_generate_key_returns_random_string()
    {
        $key1 = $this->apiKeyService->generateKey();
        $key2 = $this->apiKeyService->generateKey();

        $this->assertIsString($key1);
        $this->assertEquals(32, strlen($key1));
        $this->assertNotEquals($key1, $key2);
    }

    public function test_can_create_api_key()
    {
        $apiKeyData = [
            'name' => 'Test API Key',
            'user_id' => 1,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ];

        $apiKey = $this->apiKeyService->createApiKey($apiKeyData);

        $this->assertDatabaseHas('api_keys', [
            'name' => $apiKeyData['name'],
            'user_id' => $apiKeyData['user_id'],
            'is_active' => $apiKeyData['is_active'],
        ]);
        $this->assertTrue(Hash::check($apiKey->key, $apiKey->key));
    }

    public function test_can_validate_api_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $isValid = $this->apiKeyService->validateKey($apiKey->key);

        $this->assertNotNull($isValid);
        $this->assertEquals($apiKey->id, $isValid->id);
    }

    public function test_validate_key_returns_null_for_inactive_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => false,
        ]);

        $isValid = $this->apiKeyService->validateKey($apiKey->key);

        $this->assertNull($isValid);
    }

    public function test_validate_key_returns_null_for_expired_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $isValid = $this->apiKeyService->validateKey($apiKey->key);

        $this->assertNull($isValid);
    }

    public function test_can_update_api_key()
    {
        $apiKey = ApiKey::factory()->create();
        $updateData = [
            'name' => 'Updated API Key',
            'expires_at' => now()->addDays(60),
        ];

        $updatedApiKey = $this->apiKeyService->updateApiKey($apiKey, $updateData);

        $this->assertEquals($updateData['name'], $updatedApiKey->name);
        $this->assertEquals($updateData['expires_at']->timestamp, $updatedApiKey->expires_at->timestamp);
    }

    public function test_can_delete_api_key()
    {
        $apiKey = ApiKey::factory()->create();

        $deleted = $this->apiKeyService->deleteApiKey($apiKey);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    public function test_can_get_user_api_keys()
    {
        $userId = 1;
        ApiKey::factory()->count(3)->create([
            'user_id' => $userId,
            'is_active' => true,
        ]);
        ApiKey::factory()->count(2)->create([
            'user_id' => $userId,
            'is_active' => false,
        ]);

        $apiKeys = $this->apiKeyService->getUserApiKeys($userId);

        $this->assertCount(3, $apiKeys);
        $this->assertTrue($apiKeys->every(fn ($key) => $key->is_active));
    }

    public function test_can_deactivate_api_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
        ]);

        $deactivatedKey = $this->apiKeyService->deactivateApiKey($apiKey);

        $this->assertFalse($deactivatedKey->is_active);
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'is_active' => false,
        ]);
    }

    public function test_can_reactivate_api_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => false,
        ]);

        $reactivatedKey = $this->apiKeyService->reactivateApiKey($apiKey);

        $this->assertTrue($reactivatedKey->is_active);
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'is_active' => true,
        ]);
    }

    public function test_validate_key_returns_null_for_nonexistent_key()
    {
        $isValid = $this->apiKeyService->validateKey('nonexistent-key');

        $this->assertNull($isValid);
    }

    public function test_create_api_key_sets_default_values()
    {
        $apiKeyData = [
            'name' => 'Test API Key',
            'user_id' => 1,
        ];

        $apiKey = $this->apiKeyService->createApiKey($apiKeyData);

        $this->assertTrue($apiKey->is_active);
        $this->assertNull($apiKey->expires_at);
    }
} 