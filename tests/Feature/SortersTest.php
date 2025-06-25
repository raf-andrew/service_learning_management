<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Search\Sorters\ExampleSorter; // Replace with actual sorter classes
use App\Search\SearchQuery;
use App\Search\SearchEngine;
use App\Models\User;
use App\Models\SniffResult;

class SortersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_applies_sorters_correctly()
    {
        $user = User::factory()->create();
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 10]);
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 20]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'sorters' => [new ExampleSorter('score', 'desc')]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertGreaterThanOrEqual($results[0]->score, $results[1]->score);
    }

    /** @test */
    public function it_handles_invalid_sorters_gracefully()
    {
        $user = User::factory()->create();
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 10]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'sorters' => [new ExampleSorter('invalid_field', 'asc')]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_handles_multiple_sorters()
    {
        $user = User::factory()->create();
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 10, 'created_at' => now()->subDays(1)]);
        SniffResult::factory()->create(['user_id' => $user->id, 'score' => 10, 'created_at' => now()]);
        $query = new SearchQuery([
            'user_id' => $user->id,
            'sorters' => [new ExampleSorter('score', 'asc'), new ExampleSorter('created_at', 'desc')]
        ]);
        $engine = app(SearchEngine::class);
        $results = $engine->search($query);
        $this->assertGreaterThanOrEqual($results[0]->created_at, $results[1]->created_at);
    }
}
