<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Search\SearchQuery;
use App\Search\SearchEngine;
use App\Repositories\SniffResultRepository;
use App\Search\Filters\ExampleFilter; // Replace with actual filter classes
use App\Search\Sorters\ExampleSorter; // Replace with actual sorter classes
use App\Models\User;
use App\Models\SniffResult;

class SearchQueryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_instantiates_with_valid_parameters()
    {
        $query = new SearchQuery(['user_id' => 1, 'filters' => [], 'sorters' => []]);
        $this->assertInstanceOf(SearchQuery::class, $query);
    }

    /** @test */
    public function it_handles_invalid_or_missing_parameters_gracefully()
    {
        $query = new SearchQuery([]);
        $this->assertInstanceOf(SearchQuery::class, $query);
        $this->assertNull($query->get('user_id', null));
    }

    /** @test */
    public function it_applies_filters_correctly()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(2)->create(['user_id' => $user->id, 'score' => 10]);
        SniffResult::factory()->count(3)->create(['user_id' => $user->id, 'score' => 20]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'filters' => [new ExampleFilter('score', 20)]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_applies_sorters_correctly()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(2)->create(['user_id' => $user->id, 'score' => 10]);
        SniffResult::factory()->count(3)->create(['user_id' => $user->id, 'score' => 20]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'sorters' => [new ExampleSorter('score', 'desc')]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertGreaterThanOrEqual($results[0]->score, $results[1]->score);
    }

    /** @test */
    public function it_serializes_and_deserializes_state()
    {
        $params = ['user_id' => 1, 'filters' => [], 'sorters' => []];
        $query = new SearchQuery($params);
        $serialized = serialize($query);
        $unserialized = unserialize($serialized);
        $this->assertEquals($query, $unserialized);
    }

    /** @test */
    public function it_works_with_searchengine_and_sniffresultrepository()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(2)->create(['user_id' => $user->id]);
        $query = new SearchQuery(['user_id' => $user->id]);
        $engine = app(SearchEngine::class);
        $repo = app(SniffResultRepository::class);
        $resultsViaEngine = $engine->search($query);
        $resultsViaRepo = $repo->getAll($query);
        $this->assertEquals(count($resultsViaEngine), count($resultsViaRepo));
    }

    /** @test */
    public function it_handles_empty_datasets()
    {
        $query = new SearchQuery(['user_id' => 9999]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_handles_large_datasets()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(100)->create(['user_id' => $user->id]);
        $query = new SearchQuery(['user_id' => $user->id]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(100, $results);
    }

    /** @test */
    public function it_handles_conflicting_filters_and_sorters()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(2)->create(['user_id' => $user->id, 'score' => 10]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'filters' => [new ExampleFilter('score', 999)], // No results should match
            'sorters' => [new ExampleSorter('score', 'asc')],
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(0, $results);
    }
}
