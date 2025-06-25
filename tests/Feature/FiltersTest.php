<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Search\Filters\ExampleFilter; // Replace with actual filter classes
use App\Search\SearchQuery;
use App\Search\SearchEngine;
use App\Models\User;
use App\Models\SniffResult;

class FiltersTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_handles_invalid_filters_gracefully()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(2)->create(['user_id' => $user->id]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'filters' => [new ExampleFilter('invalid_field', 999)]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_handles_multiple_filters()
    {
        $user = User::factory()->create();
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 10, 'status' => 'pass']);
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 10, 'status' => 'fail']);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'filters' => [new ExampleFilter('score', 10), new ExampleFilter('status', 'pass')]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(1, $results);
    }
}
