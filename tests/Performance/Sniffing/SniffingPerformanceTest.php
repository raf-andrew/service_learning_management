<?php

namespace Tests\Performance\Sniffing;

use Tests\TestCase;
use App\Models\User;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class SniffingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $largeFile;
    protected $startTime;
    protected $memoryStart;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and generate token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create large test file
        $this->createLargeTestFile();
    }

    /**
     * Test performance with large file
     */
    public function test_performance_with_large_file(): void
    {
        $this->startPerformanceMeasurement();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/run', [
            'files' => [$this->largeFile],
            'report_format' => 'json',
            'severity' => 'error',
        ]);

        $this->endPerformanceMeasurement();

        $response->assertStatus(200);

        // Assert performance metrics
        $this->assertLessThan(5, $this->getExecutionTime(), 'Execution time should be less than 5 seconds');
        $this->assertLessThan(100 * 1024 * 1024, $this->getMemoryUsage(), 'Memory usage should be less than 100MB');
    }

    /**
     * Test performance with multiple files
     */
    public function test_performance_with_multiple_files(): void
    {
        $this->startPerformanceMeasurement();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/run', [
            'files' => $this->getMultipleTestFiles(),
            'report_format' => 'json',
            'severity' => 'error',
        ]);

        $this->endPerformanceMeasurement();

        $response->assertStatus(200);

        // Assert performance metrics
        $this->assertLessThan(10, $this->getExecutionTime(), 'Execution time should be less than 10 seconds');
        $this->assertLessThan(200 * 1024 * 1024, $this->getMemoryUsage(), 'Memory usage should be less than 200MB');
    }

    /**
     * Test performance with concurrent requests
     */
    public function test_performance_with_concurrent_requests(): void
    {
        $this->startPerformanceMeasurement();

        $promises = [];
        for ($i = 0; $i < 5; $i++) {
            $promises[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJsonAsync('/api/sniffing/run', [
                'files' => [$this->largeFile],
                'report_format' => 'json',
                'severity' => 'error',
            ]);
        }

        $responses = \GuzzleHttp\Promise\Utils::unwrap($promises);

        $this->endPerformanceMeasurement();

        foreach ($responses as $response) {
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Assert performance metrics
        $this->assertLessThan(15, $this->getExecutionTime(), 'Execution time should be less than 15 seconds');
        $this->assertLessThan(500 * 1024 * 1024, $this->getMemoryUsage(), 'Memory usage should be less than 500MB');
    }

    /**
     * Test performance with different report formats
     */
    public function test_performance_with_different_formats(): void
    {
        $formats = ['html', 'markdown', 'json'];
        $results = [];

        foreach ($formats as $format) {
            $this->startPerformanceMeasurement();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson('/api/sniffing/run', [
                'files' => [$this->largeFile],
                'report_format' => $format,
                'severity' => 'error',
            ]);

            $this->endPerformanceMeasurement();

            $results[$format] = [
                'time' => $this->getExecutionTime(),
                'memory' => $this->getMemoryUsage(),
            ];

            $response->assertStatus(200);
        }

        // Assert performance metrics
        foreach ($results as $format => $metrics) {
            $this->assertLessThan(5, $metrics['time'], "Execution time for $format should be less than 5 seconds");
            $this->assertLessThan(100 * 1024 * 1024, $metrics['memory'], "Memory usage for $format should be less than 100MB");
        }
    }

    /**
     * Create large test file
     */
    protected function createLargeTestFile(): void
    {
        $content = '';
        for ($i = 0; $i < 1000; $i++) {
            $content .= "<?php\n";
            $content .= "class TestClass$i {\n";
            $content .= "    public function testMethod$i() {\n";
            $content .= "        echo 'test';\n";
            $content .= "    }\n";
            $content .= "}\n\n";
        }

        $this->largeFile = 'large_test.php';
        File::create([
            'path' => $this->largeFile,
            'content' => $content,
        ]);
    }

    /**
     * Get multiple test files
     */
    protected function getMultipleTestFiles(): array
    {
        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $path = "test$i.php";
            File::create([
                'path' => $path,
                'content' => "<?php echo 'test$i';",
            ]);
            $files[] = $path;
        }
        return $files;
    }

    /**
     * Start performance measurement
     */
    protected function startPerformanceMeasurement(): void
    {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage();
    }

    /**
     * End performance measurement
     */
    protected function endPerformanceMeasurement(): void
    {
        $this->endTime = microtime(true);
        $this->memoryEnd = memory_get_usage();
    }

    /**
     * Get execution time
     */
    protected function getExecutionTime(): float
    {
        return $this->endTime - $this->startTime;
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage(): int
    {
        return $this->memoryEnd - $this->memoryStart;
    }
} 