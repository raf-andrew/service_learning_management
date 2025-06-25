<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable events and queues by default
        Event::fake();
        Queue::fake();
        
        // Clear storage
        Storage::fake('local');
        
        // Begin database transaction
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback database transaction
        DB::rollBack();
        
        parent::tearDown();
    }

    /**
     * Assert that the response has the correct status code and JSON structure
     */
    protected function assertJsonResponse($response, $status = 200, $structure = null)
    {
        $response->assertStatus($status);
        
        if ($structure) {
            $response->assertJsonStructure($structure);
        }
    }

    /**
     * Assert that the database has the expected records
     */
    protected function assertDatabaseHasRecords($table, $data)
    {
        foreach ($data as $record) {
            $this->assertDatabaseHas($table, $record);
        }
    }

    /**
     * Assert that the given event was dispatched
     */
    protected function assertEventDispatched($eventClass)
    {
        Event::assertDispatched($eventClass);
    }

    /**
     * Assert that the given job was pushed to the queue
     */
    protected function assertJobPushed($jobClass)
    {
        Queue::assertPushed($jobClass);
    }

    /**
     * Create test data with the given factory
     */
    protected function createTestData($factory, $count = 1, $attributes = [])
    {
        return $factory::factory()->count($count)->create($attributes);
    }
} 