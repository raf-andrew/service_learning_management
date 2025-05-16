<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LiveTestRunner
{
    private $results = [
        'total' => 0,
        'passed' => 0,
        'failed' => [],
        'skipped' => []
    ];

    private $config;

    public function __construct()
    {
        $this->config = json_decode(file_get_contents(__DIR__ . '/../.codespaces/testing/config/test-environment.json'), true);
    }

    public function run()
    {
        $this->setupEnvironment();
        $this->verifyEnvironment();
        $this->runTestSuites();
        $this->generateReport();
    }

    private function setupEnvironment()
    {
        // Set environment variables
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');
        putenv('DB_HOST=' . $this->config['database']['host']);
        putenv('DB_PORT=' . $this->config['database']['port']);
        putenv('DB_DATABASE=' . $this->config['database']['database']);
        putenv('DB_USERNAME=' . $this->config['database']['username']);
        putenv('DB_PASSWORD=' . $this->config['database']['password']);
        putenv('REDIS_HOST=' . $this->config['redis']['host']);
        putenv('REDIS_PORT=' . $this->config['redis']['port']);
        putenv('MAIL_HOST=' . $this->config['mail']['host']);
        putenv('MAIL_PORT=' . $this->config['mail']['port']);

        // Create required directories
        $directories = [
            'storage/logs',
            'storage/framework/testing',
            'storage/logs/coverage',
            'storage/logs/reports'
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    private function verifyEnvironment()
    {
        // Check database connection
        try {
            $pdo = new PDO(
                "mysql:host={$this->config['database']['host']};port={$this->config['database']['port']};dbname={$this->config['database']['database']}",
                $this->config['database']['username'],
                $this->config['database']['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->config['database']['database']}");
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }

        // Check Redis connection
        try {
            $redis = new \Redis();
            $redis->connect(
                $this->config['redis']['host'],
                $this->config['redis']['port']
            );
        } catch (Exception $e) {
            throw new RuntimeException("Redis connection failed: " . $e->getMessage());
        }
    }

    private function runTestSuites()
    {
        $testSuites = [
            'Feature' => 'tests/Feature',
            'Unit' => 'tests/Unit',
            'Integration' => 'tests/Integration'
        ];

        foreach ($testSuites as $suite => $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = glob("$directory/*.php");
            foreach ($files as $file) {
                $this->runTestFile($file);
            }
        }
    }

    private function runTestFile($file)
    {
        $this->results['total']++;

        try {
            $output = [];
            $returnVar = 0;
            $artisan = __DIR__ . '/../artisan';
            exec("php $artisan test $file", $output, $returnVar);

            if ($returnVar === 0) {
                $this->results['passed']++;
            } else {
                $this->results['failed'][] = [
                    'file' => $file,
                    'output' => implode("\n", $output)
                ];
            }
        } catch (Exception $e) {
            $this->results['failed'][] = [
                'file' => $file,
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateReport()
    {
        // Generate JSON report
        $jsonReport = json_encode($this->results, JSON_PRETTY_PRINT);
        file_put_contents('storage/logs/reports/test-results.json', $jsonReport);

        // Generate Markdown report
        $markdownReport = "# Test Results\n\n";
        $markdownReport .= "Total Tests: {$this->results['total']}\n";
        $markdownReport .= "Passed: {$this->results['passed']}\n";
        $markdownReport .= "Failed: " . count($this->results['failed']) . "\n";
        $markdownReport .= "Skipped: " . count($this->results['skipped']) . "\n\n";

        if (!empty($this->results['failed'])) {
            $markdownReport .= "## Failed Tests\n\n";
            foreach ($this->results['failed'] as $failure) {
                $markdownReport .= "### {$failure['file']}\n\n";
                if (isset($failure['error'])) {
                    $markdownReport .= "Error: {$failure['error']}\n\n";
                }
                if (isset($failure['output'])) {
                    $markdownReport .= "Output:\n```\n{$failure['output']}\n```\n\n";
                }
            }
        }

        file_put_contents('storage/logs/reports/test-results.md', $markdownReport);
    }
}

// Run the tests
$runner = new LiveTestRunner();
$runner->run(); 