<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvironmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_environment()
    {
        $response = $this->postJson('/api/environments', [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'environment' => [
                    'id',
                    'name',
                    'branch',
                    'url',
                    'variables',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('environments', [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com'
        ]);
    }

    public function test_create_environment_fails_with_duplicate_name()
    {
        Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);

        $response = $this->postJson('/api/environments', [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'message',
                'error'
            ]);
    }

    public function test_update_environment()
    {
        $environment = Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);

        $response = $this->putJson("/api/environments/{$environment->name}", [
            'branch' => 'main',
            'variables' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'environment' => [
                    'id',
                    'name',
                    'branch',
                    'url',
                    'variables',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('environments', [
            'name' => 'test-environment',
            'branch' => 'main',
            'url' => 'http://test.example.com'
        ]);
    }

    public function test_delete_environment()
    {
        $environment = Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);

        $response = $this->deleteJson("/api/environments/{$environment->name}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Environment deleted successfully'
            ]);

        $this->assertDatabaseMissing('environments', [
            'name' => 'test-environment'
        ]);
    }

    public function test_validate_environment()
    {
        $environment = Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);

        $response = $this->postJson("/api/environments/{$environment->name}/validate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'valid'
            ]);
    }

    public function test_validate_environment_fails_with_missing_variables()
    {
        $environment = Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing'
                // Missing APP_DEBUG
            ],
            'status' => 'ready'
        ]);

        $response = $this->postJson("/api/environments/{$environment->name}/validate");

        $response->assertStatus(400)
            ->assertJsonStructure([
                'message',
                'error'
            ]);
    }

    public function test_list_environments()
    {
        // Create multiple environments
        Environment::create([
            'name' => 'test-environment-1',
            'branch' => 'develop',
            'url' => 'http://test1.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);

        Environment::create([
            'name' => 'test-environment-2',
            'branch' => 'main',
            'url' => 'http://test2.example.com',
            'variables' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false'
            ],
            'status' => 'ready'
        ]);

        $response = $this->getJson('/api/environments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'branch',
                    'url',
                    'variables',
                    'status',
                    'deployments',
                    'builds'
                ]
            ]);
    }
} 