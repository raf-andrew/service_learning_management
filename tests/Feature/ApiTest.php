<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Traits\CodespacesTestTrait;

class ApiTest extends TestCase
{
    use CodespacesTestTrait, RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCodespacesTest();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    protected function tearDown(): void
    {
        $this->tearDownCodespacesTest();
        parent::tearDown();
    }

    /**
     * Test that API endpoints are accessible
     *
     * @return void
     */
    public function test_api_endpoints_are_accessible()
    {
        $this->addTestStep('api_endpoints', 'running');
        $routes = Route::getRoutes();
        $apiRoutes = collect($routes)->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/');
        });

        foreach ($apiRoutes as $route) {
            $method = strtolower($route->methods()[0]);
            $uri = $route->uri();
            
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                            ->$method($uri);

            $this->assertNotEquals(404, $response->status(), "API endpoint {$method} {$uri} is not accessible");
        }
        $this->addTestStep('api_endpoints', 'completed');
        $this->linkTestToChecklist('api-endpoints');
    }

    /**
     * Test that API responses are in the correct format
     *
     * @return void
     */
    public function test_api_responses_format()
    {
        $this->addTestStep('api_format', 'running');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/user');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]);
        $this->addTestStep('api_format', 'completed');
        $this->linkTestToChecklist('api-format');
    }

    /**
     * Test that API validation works
     *
     * @return void
     */
    public function test_api_validation()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->post('/api/user', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    /**
     * Test that API authentication works
     *
     * @return void
     */
    public function test_api_authentication()
    {
        $response = $this->get('/api/user');
        $response->assertStatus(401);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/user');
        $response->assertStatus(200);
    }

    /**
     * Test that API rate limiting works
     *
     * @return void
     */
    public function test_api_rate_limiting()
    {
        $maxAttempts = 60;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                            ->get('/api/test');
            $this->assertEquals(200, $response->status());
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/test');
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that API error handling works
     *
     * @return void
     */
    public function test_api_error_handling()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/non-existent-endpoint');

        $response->assertStatus(404)
                ->assertJsonStructure([
                    'message',
                    'error'
                ]);
    }

    /**
     * Test that API versioning works
     *
     * @return void
     */
    public function test_api_versioning()
    {
        $this->addTestStep('api_versioning', 'running');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->withHeader('Accept', 'application/vnd.api.v1+json')
                        ->get('/api/user');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/vnd.api.v1+json');
        $this->addTestStep('api_versioning', 'completed');
        $this->linkTestToChecklist('api-versioning');
    }

    /**
     * Test that API pagination works
     *
     * @return void
     */
    public function test_api_pagination()
    {
        $this->addTestStep('api_pagination', 'running');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/users');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next'
                    ],
                    'meta' => [
                        'current_page',
                        'from',
                        'last_page',
                        'path',
                        'per_page',
                        'to',
                        'total'
                    ]
                ]);
        $this->addTestStep('api_pagination', 'completed');
        $this->linkTestToChecklist('api-pagination');
    }

    /**
     * Test that API filtering works
     *
     * @return void
     */
    public function test_api_filtering()
    {
        $this->addTestStep('api_filtering', 'running');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/users?filter[name]=test');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data'
                ]);
        $this->addTestStep('api_filtering', 'completed');
        $this->linkTestToChecklist('api-filtering');
    }

    /**
     * Test that API sorting works
     *
     * @return void
     */
    public function test_api_sorting()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/users?sort=name');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data'
                ]);
    }

    /**
     * Test that API includes work
     *
     * @return void
     */
    public function test_api_includes()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                        ->get('/api/users?include=roles');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'included'
                ]);
    }
} 