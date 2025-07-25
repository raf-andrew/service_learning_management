<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TestHelper
{
    /**
     * Create a temporary file
     *
     * @param string $content
     * @param string $extension
     * @return string
     */
    public static function createTempFile(string $content = '', string $extension = 'txt'): string
    {
        $path = storage_path('app/temp/' . Str::random(40) . '.' . $extension);
        File::put($path, $content);
        return $path;
    }

    /**
     * Clean up temporary files
     *
     * @return void
     */
    public static function cleanupTempFiles(): void
    {
        $path = storage_path('app/temp');
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }

    /**
     * Create a test database
     *
     * @param string $name
     * @return void
     */
    public static function createTestDatabase(string $name): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        
        config(["database.connections.{$connection}.database" => $name]);
        
        \DB::statement("CREATE DATABASE IF NOT EXISTS {$name}");
        \DB::statement("USE {$name}");
        
        config(["database.connections.{$connection}.database" => $database]);
    }

    /**
     * Drop a test database
     *
     * @param string $name
     * @return void
     */
    public static function dropTestDatabase(string $name): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        
        config(["database.connections.{$connection}.database" => $name]);
        
        \DB::statement("DROP DATABASE IF EXISTS {$name}");
        
        config(["database.connections.{$connection}.database" => $database]);
    }

    /**
     * Create a test storage disk
     *
     * @param string $name
     * @return void
     */
    public static function createTestStorageDisk(string $name): void
    {
        $path = storage_path("app/{$name}");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        
        config(["filesystems.disks.{$name}" => [
            'driver' => 'local',
            'root' => $path,
        ]]);
    }

    /**
     * Clean up a test storage disk
     *
     * @param string $name
     * @return void
     */
    public static function cleanupTestStorageDisk(string $name): void
    {
        $path = storage_path("app/{$name}");
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }

    /**
     * Create a test cache store
     *
     * @param string $name
     * @return void
     */
    public static function createTestCacheStore(string $name): void
    {
        config(["cache.stores.{$name}" => [
            'driver' => 'array',
            'serialize' => false,
        ]]);
    }

    /**
     * Create a test queue connection
     *
     * @param string $name
     * @return void
     */
    public static function createTestQueueConnection(string $name): void
    {
        config(["queue.connections.{$name}" => [
            'driver' => 'sync',
        ]]);
    }

    /**
     * Create a test mailer
     *
     * @param string $name
     * @return void
     */
    public static function createTestMailer(string $name): void
    {
        config(["mail.mailers.{$name}" => [
            'transport' => 'array',
        ]]);
    }

    /**
     * Create a test session driver
     *
     * @param string $name
     * @return void
     */
    public static function createTestSessionDriver(string $name): void
    {
        config(["session.driver" => $name]);
    }

    /**
     * Create a test auth guard
     *
     * @param string $name
     * @return void
     */
    public static function createTestAuthGuard(string $name): void
    {
        config(["auth.guards.{$name}" => [
            'driver' => 'session',
            'provider' => 'users',
        ]]);
    }

    /**
     * Create a test auth provider
     *
     * @param string $name
     * @return void
     */
    public static function createTestAuthProvider(string $name): void
    {
        config(["auth.providers.{$name}" => [
            'driver' => 'eloquent',
            'model' => \App\Models\User::class,
        ]]);
    }

    /**
     * Create a test event listener
     *
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public static function createTestEventListener(string $event, callable $listener): void
    {
        Event::listen($event, $listener);
    }

    /**
     * Create a test job
     *
     * @param string $job
     * @param array $data
     * @return void
     */
    public static function createTestJob(string $job, array $data = []): void
    {
        dispatch(new $job($data));
    }

    /**
     * Create a test notification
     *
     * @param string $notification
     * @param array $data
     * @return void
     */
    public static function createTestNotification(string $notification, array $data = []): void
    {
        Notification::send(
            \App\Models\User::factory()->create(),
            new $notification($data)
        );
    }

    /**
     * Generate a random string
     */
    public static function randomString(int $length = 10): string
    {
        return Str::random($length);
    }

    /**
     * Generate a random email
     */
    public static function randomEmail(): string
    {
        return Str::random(10) . '@example.com';
    }

    /**
     * Generate a random password
     */
    public static function randomPassword(): string
    {
        return Hash::make(Str::random(10));
    }

    /**
     * Create a test file in storage
     */
    public static function createTestFile(string $path, string $content = ''): string
    {
        Storage::put($path, $content ?: Str::random(100));
        return $path;
    }

    /**
     * Generate test data for a model
     */
    public static function generateTestData(array $attributes = []): array
    {
        return array_merge([
            'name' => self::randomString(),
            'email' => self::randomEmail(),
            'password' => self::randomPassword(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
    }

    /**
     * Assert that an array has all required keys
     */
    public static function assertArrayHasKeys(array $array, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create a test database record
     */
    public static function createTestRecord(string $table, array $data): int
    {
        return \DB::table($table)->insertGetId($data);
    }

    /**
     * Clean up test data
     */
    public static function cleanupTestData(): void
    {
        Storage::deleteDirectory('test');
        \DB::table('test_records')->truncate();
    }
} 