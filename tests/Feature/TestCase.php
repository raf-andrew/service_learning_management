<?php

namespace Tests\Feature;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestReporter;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithoutMiddleware, DatabaseTransactions;

    protected $reporter;
    protected $user;
    protected $headers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new TestReporter();
        $this->withoutExceptionHandling();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->reporter->addCodeQualityMetric('memory_usage', memory_get_peak_usage(true));
    }

    /**
     * Create and authenticate a user
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createAndAuthenticateUser(array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create($attributes);
        $this->actingAs($user);
        return $user;
    }

    /**
     * Set API request headers
     *
     * @param array $headers
     * @return self
     */
    protected function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Make an API request
     *
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return \Illuminate\Testing\TestResponse
     */
    protected function apiRequest(string $method, string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        $headers = array_merge($this->headers, $headers);
        return $this->json($method, $uri, $data, $headers);
    }

    /**
     * Assert that a response has the expected structure
     *
     * @param array $structure
     * @param \Illuminate\Testing\TestResponse $response
     * @return void
     */
    protected function assertResponseStructure(array $structure, \Illuminate\Testing\TestResponse $response): void
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Assert that a response has the expected status code
     *
     * @param int $status
     * @param \Illuminate\Testing\TestResponse $response
     * @return void
     */
    protected function assertResponseStatus(int $status, \Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus($status);
    }

    /**
     * Assert that a response has the expected data
     *
     * @param array $data
     * @param \Illuminate\Testing\TestResponse $response
     * @return void
     */
    protected function assertResponseData(array $data, \Illuminate\Testing\TestResponse $response): void
    {
        $response->assertJson($data);
    }

    /**
     * Assert that a response has the expected validation errors
     *
     * @param array $errors
     * @param \Illuminate\Testing\TestResponse $response
     * @return void
     */
    protected function assertValidationErrors(array $errors, \Illuminate\Testing\TestResponse $response): void
    {
        $response->assertJsonValidationErrors($errors);
    }
} 