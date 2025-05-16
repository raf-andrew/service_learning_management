<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestError;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestCase;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Framework\TestSuite;

// Create error and failure directories if they don't exist
$errorDir = __DIR__ . '/../../../.errors';
$failureDir = __DIR__ . '/../../../.failures';

if (!file_exists($errorDir)) {
    mkdir($errorDir, 0755, true);
}

if (!file_exists($failureDir)) {
    mkdir($failureDir, 0755, true);
}

// Custom test result printer
class StagingTestResultPrinter implements FailedSubscriber, ErroredSubscriber, FinishedSubscriber, SkippedSubscriber
{
    protected string $errorDir;
    protected string $failureDir;
    protected array $results = [
        'tests' => 0,
        'failures' => 0,
        'errors' => 0,
        'skipped' => 0,
        'time' => 0
    ];
    
    public function __construct(string $errorDir, string $failureDir)
    {
        $this->errorDir = $errorDir;
        $this->failureDir = $failureDir;
    }
    
    public function notify(Failed $event): void
    {
        $this->results['failures']++;
        $this->logFailure($event);
    }
    
    public function notify(Errored $event): void
    {
        $this->results['errors']++;
        $this->logError($event);
    }
    
    public function notify(Finished $event): void
    {
        $this->results['tests']++;
        $this->results['time'] += $event->telemetryInfo()->time();
    }
    
    public function notify(Skipped $event): void
    {
        $this->results['skipped']++;
    }
    
    protected function logError(Errored $event): void
    {
        $filename = sprintf(
            '%s/%s_%s.error',
            $this->errorDir,
            date('Y-m-d_H-i-s'),
            str_replace('\\', '_', $event->test()->className())
        );
        
        $content = sprintf(
            "Test: %s\nTime: %s\nError: %s\nStack Trace:\n%s\n",
            $event->test()->className(),
            date('Y-m-d H:i:s'),
            $event->throwable()->message(),
            $event->throwable()->stackTrace()
        );
        
        file_put_contents($filename, $content);
    }
    
    protected function logFailure(Failed $event): void
    {
        $filename = sprintf(
            '%s/%s_%s.failure',
            $this->failureDir,
            date('Y-m-d_H-i-s'),
            str_replace('\\', '_', $event->test()->className())
        );
        
        $content = sprintf(
            "Test: %s\nTime: %s\nFailure: %s\nStack Trace:\n%s\n",
            $event->test()->className(),
            date('Y-m-d H:i:s'),
            $event->throwable()->message(),
            $event->throwable()->stackTrace()
        );
        
        file_put_contents($filename, $content);
    }
    
    public function getResults(): array
    {
        return $this->results;
    }
}

// Create test suite
$suite = TestSuite::empty('Staging Environment Tests');
$suite->addTestFile(__DIR__ . '/StagingEnvironmentTest.php');

// Create test runner
$runner = new TestRunner();

// Create and register result printer
$printer = new StagingTestResultPrinter($errorDir, $failureDir);
\PHPUnit\Event\Facade::instance()->registerSubscriber($printer);

// Run tests
$result = $runner->run($suite);

// Output results
echo "\nTest Results:\n";
echo "Tests: " . $printer->getResults()['tests'] . "\n";
echo "Failures: " . $printer->getResults()['failures'] . "\n";
echo "Errors: " . $printer->getResults()['errors'] . "\n";
echo "Skipped: " . $printer->getResults()['skipped'] . "\n";
echo "Time: " . round($printer->getResults()['time'], 2) . " seconds\n";

// Exit with appropriate status code
exit($result->wasSuccessful() ? 0 : 1); 