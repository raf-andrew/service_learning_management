<?php

namespace Tests\Feature\Controllers;

use App\Models\Service;
use App\Models\ServiceInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceInstanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);
    }

    public function test_can_register_new_instance(): void
    {
        $response = $this->postJson("/api/services/{$this->service->name}/instances", [
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'active',
            'metadata' => ['environment' => 'test'],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'host' => 'localhost',
                'port' => 8080,
                'status' => 'active',
                'metadata' => ['environment' => 'test'],
            ]);

        $this->assertDatabaseHas('service_instances', [
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);
    }

    public function test_cannot_register_duplicate_instance(): void
    {
        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $response = $this->postJson("/api/services/{$this->service->name}/instances", [
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $response->assertStatus(409);
    }

    public function test_can_list_service_instances(): void
    {
        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8081,
        ]);

        $response = $this->getJson("/api/services/{$this->service->name}/instances");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_update_instance(): void
    {
        $instance = ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $response = $this->putJson("/api/services/{$this->service->name}/instances/{$instance->id}", [
            'status' => 'inactive',
            'metadata' => ['environment' => 'production'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'inactive',
                'metadata' => ['environment' => 'production'],
            ]);
    }

    public function test_can_delete_instance(): void
    {
        $instance = ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $response = $this->deleteJson("/api/services/{$this->service->name}/instances/{$instance->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('service_instances', [
            'id' => $instance->id,
        ]);
    }

    public function test_can_update_instance_heartbeat(): void
    {
        $instance = ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $response = $this->postJson("/api/services/{$this->service->name}/instances/{$instance->id}/heartbeat");

        $response->assertStatus(200);

        $this->assertNotNull($instance->fresh()->last_heartbeat);
    }

    public function test_returns_404_for_nonexistent_service(): void
    {
        $response = $this->getJson('/api/services/nonexistent/instances');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_instance(): void
    {
        $response = $this->getJson("/api/services/{$this->service->name}/instances/999");

        $response->assertStatus(404);
    }
} 