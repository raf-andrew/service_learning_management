<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Repositories\SniffResultRepository;
use App\Search\SearchQuery;
use App\Models\User;
use App\Models\SniffResult;

class SniffResultRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_gets_all_results_for_a_user()
    {
        $user = User::factory()->create();
        SniffResult::factory()->count(5)->create(['user_id' => $user->id]);
        $repo = app(SniffResultRepository::class);
        $query = new SearchQuery(['user_id' => $user->id]);
        $results = $repo->getAll($query);
        $this->assertCount(5, $results);
    }

    /** @test */
    public function it_gets_results_by_file()
    {
        $user = User::factory()->create();
        $repo = app(SniffResultRepository::class);
        $sniff = SniffResult::factory()->create(['user_id' => $user->id, 'file' => 'foo.php']);
        $results = $repo->getByFile('foo.php');
        $this->assertCount(1, $results);
        $this->assertEquals('foo.php', $results[0]->file);
    }

    /** @test */
    public function it_gets_latest_results()
    {
        $user = User::factory()->create();
        $repo = app(SniffResultRepository::class);
        SniffResult::factory()->count(2)->create(['user_id' => $user->id]);
        $results = $repo->getLatestResults(1);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_handles_empty_results()
    {
        $repo = app(SniffResultRepository::class);
        $results = $repo->getAll(new SearchQuery(['user_id' => 9999]));
        $this->assertCount(0, $results);
    }
}
