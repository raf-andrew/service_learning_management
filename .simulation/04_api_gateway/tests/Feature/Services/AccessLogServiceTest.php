<?php

namespace Tests\Feature\Services;

use App\Models\AccessLog;
use App\Models\ApiKey;
use App\Models\Route;
use App\Services\AccessLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Request;

class AccessLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccessLogService $accessLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessLogService = new AccessLogService();
    }

    public function test_can_log_api_access()
    {
        $logData = [
            'api_key_id' => 1,
            'route_id' => 1,
            'method' => 'GET',
            'path' => '/api/test',
            'status_code' => 200,
            'response_time' => 100,
        ];

        $accessLog = $this->accessLogService->logAccess($logData);

        $this->assertDatabaseHas('access_logs', $logData);
        $this->assertEquals($logData['method'], $accessLog->method);
        $this->assertEquals($logData['path'], $accessLog->path);
    }

    public function test_log_access_uses_request_data_when_not_provided()
    {
        $logData = [
            'method' => 'GET',
            'path' => '/api/test',
            'status_code' => 200,
        ];

        $accessLog = $this->accessLogService->logAccess($logData);

        $this->assertEquals(Request::ip(), $accessLog->ip_address);
        $this->assertEquals(Request::userAgent(), $accessLog->user_agent);
    }

    public function test_can_get_access_logs_with_filters()
    {
        $apiKey = ApiKey::factory()->create();
        $route = Route::factory()->create();

        AccessLog::factory()->count(3)->create([
            'api_key_id' => $apiKey->id,
            'method' => 'GET',
            'status_code' => 200,
        ]);

        AccessLog::factory()->count(2)->create([
            'api_key_id' => $apiKey->id,
            'method' => 'POST',
            'status_code' => 201,
        ]);

        $filters = [
            'api_key_id' => $apiKey->id,
            'method' => 'GET',
            'status_code' => 200,
        ];

        $logs = $this->accessLogService->getAccessLogs($filters);

        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(fn ($log) => 
            $log->api_key_id === $apiKey->id &&
            $log->method === 'GET' &&
            $log->status_code === 200
        ));
    }

    public function test_can_get_access_statistics()
    {
        AccessLog::factory()->count(5)->create([
            'status_code' => 200,
            'method' => 'GET',
            'response_time' => 100,
        ]);

        AccessLog::factory()->count(3)->create([
            'status_code' => 404,
            'method' => 'GET',
            'response_time' => 50,
        ]);

        AccessLog::factory()->count(2)->create([
            'status_code' => 500,
            'method' => 'POST',
            'response_time' => 200,
        ]);

        $stats = $this->accessLogService->getAccessStatistics();

        $this->assertEquals(10, $stats['total_requests']);
        $this->assertEquals(100, $stats['average_response_time']);
        $this->assertEquals(5, $stats['status_codes'][200]);
        $this->assertEquals(3, $stats['status_codes'][404]);
        $this->assertEquals(2, $stats['status_codes'][500]);
        $this->assertEquals(8, $stats['methods']['GET']);
        $this->assertEquals(2, $stats['methods']['POST']);
    }

    public function test_can_cleanup_old_logs()
    {
        AccessLog::factory()->count(5)->create([
            'created_at' => now()->subDays(31),
        ]);

        AccessLog::factory()->count(3)->create([
            'created_at' => now()->subDays(15),
        ]);

        $deleted = $this->accessLogService->cleanupOldLogs(30);

        $this->assertEquals(5, $deleted);
        $this->assertDatabaseCount('access_logs', 3);
    }

    public function test_can_get_access_log_by_id()
    {
        $accessLog = AccessLog::factory()->create();

        $foundLog = $this->accessLogService->getAccessLog($accessLog->id);

        $this->assertNotNull($foundLog);
        $this->assertEquals($accessLog->id, $foundLog->id);
    }

    public function test_can_delete_access_log()
    {
        $accessLog = AccessLog::factory()->create();

        $deleted = $this->accessLogService->deleteAccessLog($accessLog);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('access_logs', ['id' => $accessLog->id]);
    }

    public function test_get_access_logs_paginates_results()
    {
        AccessLog::factory()->count(20)->create();

        $logs = $this->accessLogService->getAccessLogs(['per_page' => 10]);

        $this->assertCount(10, $logs);
        $this->assertEquals(20, $logs->total());
        $this->assertEquals(2, $logs->lastPage());
    }

    public function test_get_access_statistics_with_date_filters()
    {
        AccessLog::factory()->count(5)->create([
            'created_at' => now()->subDays(5),
            'status_code' => 200,
        ]);

        AccessLog::factory()->count(3)->create([
            'created_at' => now()->subDays(2),
            'status_code' => 404,
        ]);

        $stats = $this->accessLogService->getAccessStatistics([
            'start_date' => now()->subDays(3),
            'end_date' => now(),
        ]);

        $this->assertEquals(3, $stats['total_requests']);
        $this->assertEquals(3, $stats['status_codes'][404]);
        $this->assertArrayNotHasKey(200, $stats['status_codes']);
    }
} 