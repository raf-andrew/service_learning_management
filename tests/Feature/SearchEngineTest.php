<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Search\SearchEngine;
use App\Search\SearchQuery;
use App\Search\Filters\ExampleFilter; // Replace with actual filter classes
use App\Search\Sorters\ExampleSorter; // Replace with actual sorter classes
use App\Repositories\SniffResultRepository;
use App\Models\User; // Adjust as needed
use App\Models\SniffResult; // Adjust as needed

class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    protected $searchEngine;
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(SniffResultRepository::class);
        $this->searchEngine = $this->app->make(SearchEngine::class);
    }

    /** @test */
    public function it_executes_queries_and_returns_expected_results()
    {
        // Arrange: Seed database with test data
        $user = User::factory()->create();
        SniffResult::factory()->count(5)->create(['user_id' => $user->id]);
        $query = new SearchQuery(['user_id' => $user->id]);

        // Act
        $results = $this->searchEngine->search($query);

        // Assert
        $this->assertCount(5, $results);
    }

    /** @test */
    public function it_applies_filters_and_sorters_via_searchquery()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(2)->create(['user_id' => $user->id, 'score' => 10]);
        SniffResult::factory()->count(3)->create(['user_id' => $user->id, 'score' => 20]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'filters' => [new \App\Search\Filters\ExampleFilter('score', 20)],
            'sorters' => [new \App\Search\Sorters\ExampleSorter('score', 'desc')],
        ]);
        $results = $this->searchEngine->search($query);
        $this->assertCount(3, $results);
        $this->assertGreaterThanOrEqual($results[0]->score, $results[1]->score);
    }

    /** @test */
    public function it_handles_large_result_sets()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(1000)->create(['user_id' => $user->id]);
        $query = new SearchQuery(['user_id' => $user->id]);
        $results = $this->searchEngine->search($query);
        $this->assertCount(1000, $results);
    }

    /** @test */
    public function it_integrates_with_sniffresultrepository()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(4)->create(['user_id' => $user->id]);
        $repo = $this->app->make(\App\Repositories\SniffResultRepository::class);
        $query = new SearchQuery(['user_id' => $user->id]);
        $results = $repo->getAll($query);
        $this->assertCount(4, $results);
    }

    /** @test */
    public function it_applies_filters_and_sorters()
    {
        // Arrange: Seed database with test data
        $user = User::factory()->create();
        SniffResult::factory()->count(3)->create(['user_id' => $user->id, 'score' => 10]);
        SniffResult::factory()->count(2)->create(['user_id' => $user->id, 'score' => 20]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'filters' => [new ExampleFilter('score', 20)],
            'sorters' => [new ExampleSorter('score', 'desc')],
        ]);

        // Act
        $results = $this->searchEngine->search($query);

        // Assert
        $this->assertCount(2, $results);
        $this->assertGreaterThanOrEqual($results[1]->score, $results[0]->score);
    }

    /** @test */
    public function it_handles_pagination_and_limits()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(30)->create(['user_id' => $user->id]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'limit' => 10,
            'page' => 2,
        ]);
        $results = $this->searchEngine->search($query);
        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_integrates_with_searchquery_and_sniffresultrepository()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(4)->create(['user_id' => $user->id]);
        $query = new SearchQuery(['user_id' => $user->id]);
        $resultsViaEngine = $this->searchEngine->search($query);
        $resultsViaRepo = $this->repository->getAll($query);
        $this->assertEquals(count($resultsViaEngine), count($resultsViaRepo));
    }

    /** @test */
    public function it_handles_empty_queries()
    {
        $query = new SearchQuery([]);
        $results = $this->searchEngine->search($query);
        $this->assertIsIterable($results);
    }
}
