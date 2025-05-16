<?php

namespace Tests\Feature\Controllers;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_new_service(): void
    {
        $response = $this->postJson('/api/services', [
            'name' => 'test-service',
            'version' => '1.0.0',
            'description' => 'Test service',
            'metadata' => ['environment' => 'test'],
            'tags' => ['api', 'test'],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'test-service',
                'version' => '1.0.0',
                'description' => 'Test service',
                'metadata' => ['environment' => 'test'],
                'tags' => ['api', 'test'],
            ]);

        $this->assertDatabaseHas('services', [
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);
    }

    public function test_cannot_register_duplicate_service(): void
    {
        Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $response = $this->postJson('/api/services', [
            'name' => 'test-service',
            'version' => '2.0.0',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_get_service_by_name(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $response = $this->getJson("/api/services/{$service->name}");

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'test-service',
                'version' => '1.0.0',
            ]);
    }

    public function test_returns_404_for_nonexistent_service(): void
    {
        $response = $this->getJson('/api/services/nonexistent');

        $response->assertStatus(404);
    }

    public function test_can_update_service(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $response = $this->putJson("/api/services/{$service->name}", [
            'version' => '2.0.0',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'test-service',
                'version' => '2.0.0',
                'description' => 'Updated description',
            ]);
    }

    public function test_can_delete_service(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $response = $this->deleteJson("/api/services/{$service->name}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('services', [
            'name' => 'test-service',
        ]);
    }

    public function test_can_list_services(): void
    {
        Service::create([
            'name' => 'service-1',
            'version' => '1.0.0',
            'tags' => ['api'],
        ]);

        Service::create([
            'name' => 'service-2',
            'version' => '1.0.0',
            'tags' => ['api', 'test'],
        ]);

        $response = $this->getJson('/api/services?tag=test');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'name' => 'service-2',
                    'tags' => ['api', 'test'],
                ],
            ]);
    }
} 