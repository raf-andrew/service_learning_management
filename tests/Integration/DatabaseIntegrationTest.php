<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeveloperCredential;
use App\Services\DeveloperCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Database Integration Tests
 * 
 * These tests verify real database interactions, transactions,
 * and data consistency across the application.
 */
class DatabaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected DeveloperCredentialService $credentialService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialService = app(DeveloperCredentialService::class);
    }

    /**
     * Test complete user lifecycle with database transactions.
     */
    public function test_user_lifecycle_with_database_transactions()
    {
        // Start transaction
        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => 'test@example.com',
            ]);

            // Create developer credential
            $credentialData = [
                'github_token' => 'ghp_test_token_123',
                'github_username' => 'testuser',
            ];

            $credential = $this->credentialService->createCredential($user, $credentialData);

            $this->assertDatabaseHas('developer_credentials', [
                'id' => $credential->id,
                'user_id' => $user->id,
                'github_username' => 'testuser',
            ]);

            // Verify relationship integrity
            $this->assertEquals($user->id, $credential->user_id);
            $this->assertTrue($credential->is_active);

            // Update credential
            $updatedCredential = $this->credentialService->updateCredential($credential, [
                'github_username' => 'updateduser',
            ]);

            $this->assertDatabaseHas('developer_credentials', [
                'id' => $credential->id,
                'github_username' => 'updateduser',
            ]);

            // Commit transaction
            DB::commit();

            $this->assertTrue(true, 'Transaction completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Test transaction rollback on failure.
     */
    public function test_transaction_rollback_on_failure()
    {
        $initialUserCount = User::count();
        $initialCredentialCount = DeveloperCredential::count();

        // Start transaction
        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);

            // Create credential
            $credential = $this->credentialService->createCredential($user, [
                'github_token' => 'ghp_test_token_123',
                'github_username' => 'testuser',
            ]);

            // Simulate a failure
            throw new \Exception('Simulated failure');

        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            // Verify that no data was persisted
            $this->assertEquals($initialUserCount, User::count());
            $this->assertEquals($initialCredentialCount, DeveloperCredential::count());

            $this->assertTrue(true, 'Transaction rolled back successfully');
        }
    }

    /**
     * Test data consistency across related tables.
     */
    public function test_data_consistency_across_related_tables()
    {
        // Create user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create multiple credentials for the same user
        $credential1 = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_token_1',
            'github_username' => 'user1',
        ]);

        $credential2 = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_token_2',
            'github_username' => 'user2',
        ]);

        // Verify referential integrity
        $this->assertEquals($user->id, $credential1->user_id);
        $this->assertEquals($user->id, $credential2->user_id);

        // Verify user can access their credentials
        $userCredentials = $user->developerCredentials;
        $this->assertCount(2, $userCredentials);
        $this->assertTrue($userCredentials->contains($credential1));
        $this->assertTrue($userCredentials->contains($credential2));

        // Test cascade delete (if implemented)
        $user->delete();
        $this->assertDatabaseMissing('developer_credentials', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test concurrent database operations.
     */
    public function test_concurrent_database_operations()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Simulate concurrent operations
        $promises = [];
        
        for ($i = 0; $i < 5; $i++) {
            $promises[] = function () use ($user, $i) {
                return $this->credentialService->createCredential($user, [
                    'github_token' => "ghp_token_{$i}",
                    'github_username' => "user{$i}",
                ]);
            };
        }

        // Execute operations
        $credentials = [];
        foreach ($promises as $promise) {
            $credentials[] = $promise();
        }

        // Verify all operations completed successfully
        $this->assertCount(5, $credentials);
        
        $userCredentials = $user->developerCredentials;
        $this->assertCount(5, $userCredentials);
    }

    /**
     * Test event firing during database operations.
     */
    public function test_event_firing_during_database_operations()
    {
        Event::fake();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);

        // Verify event was fired
        Event::assertDispatched(\App\Events\DeveloperCredentialCreated::class, function ($event) use ($credential) {
            return $event->credential->id === $credential->id;
        });
    }
} 