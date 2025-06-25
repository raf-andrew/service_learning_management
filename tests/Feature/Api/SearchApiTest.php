<?php

/**
 * @fileoverview Feature tests for Search API endpoint
 * @tags feature, api, search, laravel, vitest, web3, modular
 * @description Tests for /api/search endpoint (Web3 Modular Search)
 * @coverage api/search
 * @since 1.0.0
 * @author System
 */

namespace Tests\Feature\Api;

use Tests\Feature\TestCase;
use App\Models\User;
use App\Services\Search\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->searchService = Mockery::mock(SearchService::class);
        $this->app->instance(SearchService::class, $this->searchService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * @group search-basic
     */
    public function test_search_endpoint_returns_results()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'test search',
            'filters' => ['type' => 'document'],
            'limit' => 10
        ];

        $expectedResults = [
            'results' => [
                [
                    'id' => 1,
                    'title' => 'Test Document',
                    'content' => 'This is a test document',
                    'type' => 'document',
                    'score' => 0.95
                ]
            ],
            'total' => 1,
            'query' => 'test search'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($searchData['query'], $searchData['filters'], $searchData['limit'])
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', $searchData);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-basic
     */
    public function test_search_endpoint_with_minimal_query()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'simple search'
        ];

        $expectedResults = [
            'results' => [],
            'total' => 0,
            'query' => 'simple search'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($searchData['query'], [], 20)
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', $searchData);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-filters
     */
    public function test_search_endpoint_with_complex_filters()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'advanced search',
            'filters' => [
                'type' => ['document', 'code'],
                'date_range' => ['start' => '2023-01-01', 'end' => '2023-12-31'],
                'tags' => ['important', 'urgent'],
                'author' => 'john.doe'
            ],
            'limit' => 50
        ];

        $expectedResults = [
            'results' => [
                [
                    'id' => 1,
                    'title' => 'Advanced Document',
                    'content' => 'This is an advanced search result',
                    'type' => 'document',
                    'tags' => ['important'],
                    'author' => 'john.doe',
                    'score' => 0.98
                ]
            ],
            'total' => 1,
            'query' => 'advanced search'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($searchData['query'], $searchData['filters'], $searchData['limit'])
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', $searchData);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-pagination
     */
    public function test_search_endpoint_with_pagination()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'pagination test',
            'page' => 2,
            'per_page' => 5
        ];

        $expectedResults = [
            'results' => [
                [
                    'id' => 6,
                    'title' => 'Page 2 Result',
                    'content' => 'This is a result from page 2',
                    'type' => 'document',
                    'score' => 0.85
                ]
            ],
            'total' => 15,
            'current_page' => 2,
            'per_page' => 5,
            'last_page' => 3,
            'query' => 'pagination test'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($searchData['query'], [], 20, $searchData['page'], $searchData['per_page'])
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', $searchData);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-sorting
     */
    public function test_search_endpoint_with_sorting()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'sorting test',
            'sort_by' => 'date',
            'sort_order' => 'desc'
        ];

        $expectedResults = [
            'results' => [
                [
                    'id' => 1,
                    'title' => 'Latest Result',
                    'content' => 'This is the latest result',
                    'type' => 'document',
                    'created_at' => '2023-12-31T23:59:59Z',
                    'score' => 0.92
                ]
            ],
            'total' => 1,
            'query' => 'sorting test',
            'sort_by' => 'date',
            'sort_order' => 'desc'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($searchData['query'], [], 20, 1, 20, $searchData['sort_by'], $searchData['sort_order'])
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', $searchData);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-validation
     */
    public function test_search_endpoint_validates_required_query()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/search', [
            'filters' => ['type' => 'document']
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['query']);
    }

    /**
     * @test
     * @group search-validation
     */
    public function test_search_endpoint_validates_query_length()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/search', [
            'query' => str_repeat('a', 1001) // Too long query
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['query']);
    }

    /**
     * @test
     * @group search-validation
     */
    public function test_search_endpoint_validates_limit_range()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/search', [
            'query' => 'test',
            'limit' => 1001 // Too high limit
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['limit']);
    }

    /**
     * @test
     * @group search-validation
     */
    public function test_search_endpoint_validates_sort_order()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/search', [
            'query' => 'test',
            'sort_order' => 'invalid' // Invalid sort order
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['sort_order']);
    }

    /**
     * @test
     * @group search-error-handling
     */
    public function test_search_endpoint_handles_service_errors()
    {
        Sanctum::actingAs($this->user);

        $this->searchService->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception('Search service unavailable'));

        $response = $this->postJson('/api/search', [
            'query' => 'test search'
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'message' => 'Search service unavailable'
                ]);
    }

    /**
     * @test
     * @group search-error-handling
     */
    public function test_search_endpoint_handles_empty_results()
    {
        Sanctum::actingAs($this->user);

        $expectedResults = [
            'results' => [],
            'total' => 0,
            'query' => 'no results'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', [
            'query' => 'no results'
        ]);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-performance
     */
    public function test_search_endpoint_performance_with_large_dataset()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'performance test',
            'limit' => 100
        ];

        $expectedResults = [
            'results' => array_fill(0, 100, [
                'id' => 1,
                'title' => 'Performance Test Result',
                'content' => 'This is a performance test result',
                'type' => 'document',
                'score' => 0.90
            ]),
            'total' => 100,
            'query' => 'performance test'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->andReturn($expectedResults);

        $startTime = microtime(true);
        
        $response = $this->postJson('/api/search', $searchData);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200)
                ->assertJson($expectedResults);

        // Assert that the search completes within a reasonable time (2 seconds)
        $this->assertLessThan(2.0, $executionTime, 'Search took too long to execute');
    }

    /**
     * @test
     * @group search-security
     */
    public function test_search_endpoint_sanitizes_input()
    {
        Sanctum::actingAs($this->user);

        $maliciousQuery = '<script>alert("xss")</script> test';
        $sanitizedQuery = 'test'; // Expected sanitized version

        $expectedResults = [
            'results' => [],
            'total' => 0,
            'query' => $sanitizedQuery
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($sanitizedQuery, [], 20)
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', [
            'query' => $maliciousQuery
        ]);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }

    /**
     * @test
     * @group search-rate-limiting
     */
    public function test_search_endpoint_respects_rate_limiting()
    {
        Sanctum::actingAs($this->user);

        // Make multiple requests in quick succession
        for ($i = 0; $i < 60; $i++) {
            $this->searchService->shouldReceive('search')
                ->once()
                ->andReturn(['results' => [], 'total' => 0, 'query' => "search $i"]);
        }

        // Make requests up to the rate limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/api/search', [
                'query' => "search $i"
            ]);
            $response->assertStatus(200);
        }

        // The 61st request should be rate limited
        $response = $this->postJson('/api/search', [
            'query' => 'rate limited search'
        ]);
        $response->assertStatus(429);
    }

    /**
     * @test
     * @group search-web3-integration
     */
    public function test_search_endpoint_with_web3_modular_filters()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'query' => 'web3 blockchain',
            'filters' => [
                'blockchain' => 'ethereum',
                'contract_type' => 'erc20',
                'network' => 'mainnet',
                'web3_provider' => 'metamask'
            ]
        ];

        $expectedResults = [
            'results' => [
                [
                    'id' => 1,
                    'title' => 'ERC20 Token Contract',
                    'content' => 'Ethereum ERC20 token contract implementation',
                    'type' => 'smart_contract',
                    'blockchain' => 'ethereum',
                    'contract_type' => 'erc20',
                    'score' => 0.95
                ]
            ],
            'total' => 1,
            'query' => 'web3 blockchain'
        ];

        $this->searchService->shouldReceive('search')
            ->once()
            ->with($searchData['query'], $searchData['filters'], 20)
            ->andReturn($expectedResults);

        $response = $this->postJson('/api/search', $searchData);

        $response->assertStatus(200)
                ->assertJson($expectedResults);
    }
} 