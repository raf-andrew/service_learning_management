<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BuildService;
use App\Models\Build;
use App\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class BuildServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $buildService;
    protected $environment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildService = new BuildService();

        // Create test environment
        $this->environment = Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);
    }

    public function test_create_build_creates_build()
    {
        $build = $this->buildService->createBuild('develop');

        $this->assertInstanceOf(Build::class, $build);
        $this->assertEquals('develop', $build->branch);
        $this->assertEquals('success', $build->status);
        $this->assertNotNull($build->artifacts);
        $this->assertArrayHasKey('app', $build->artifacts);
        $this->assertArrayHasKey('vendor', $build->artifacts);
        $this->assertArrayHasKey('public', $build->artifacts);
    }

    public function test_get_build_status_returns_correct_status()
    {
        $build = $this->buildService->createBuild('develop');
        $status = $this->buildService->getBuildStatus($build->id);

        $this->assertEquals('success', $status);
    }

    public function test_validate_build_validates_build()
    {
        $build = $this->buildService->createBuild('develop');
        $result = $this->buildService->validateBuild($build->id);

        $this->assertTrue($result);
    }

    public function test_validate_build_fails_when_missing_artifacts()
    {
        $build = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [], // Empty artifacts
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Build artifacts are missing');

        $this->buildService->validateBuild($build->id);
    }

    public function test_validate_build_fails_when_missing_required_artifacts()
    {
        $build = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [
                'app' => 'app.zip'
                // Missing vendor and public artifacts
            ],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required build artifacts: vendor, public');

        $this->buildService->validateBuild($build->id);
    }

    public function test_build_number_increments()
    {
        $build1 = $this->buildService->createBuild('develop');
        $build2 = $this->buildService->createBuild('develop');

        $this->assertEquals(1, $build1->build_number);
        $this->assertEquals(2, $build2->build_number);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 