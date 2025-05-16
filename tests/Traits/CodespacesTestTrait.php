<?php

namespace Tests\Traits;

use App\Services\CodespacesTestReporter;
use App\Services\CodespacesHealthService;
use Illuminate\Support\Facades\Config;

trait CodespacesTestTrait
{
    protected $testReporter;
    protected $healthService;
    protected $currentTestId;

    protected function setUpCodespacesTest(): void
    {
        $this->testReporter = app(CodespacesTestReporter::class);
        $this->healthService = app(CodespacesHealthService::class);

        // Start test reporting
        $this->testReporter->startTest($this->getName());
        $this->currentTestId = $this->getName();

        // Check service health
        $this->testReporter->addStep('health_check', 'running');
        $healthResults = $this->healthService->checkAllServices();
        
        $allHealthy = true;
        foreach ($healthResults as $service => $result) {
            if (!$result['healthy']) {
                $allHealthy = false;
                $this->testReporter->addStep(
                    "health_check_{$service}",
                    'failed',
                    $result['message']
                );
            } else {
                $this->testReporter->addStep(
                    "health_check_{$service}",
                    'completed'
                );
            }
        }

        if (!$allHealthy) {
            $this->testReporter->completeTest(false, 'Service health check failed');
            $this->markTestSkipped('Required services are not healthy');
        }

        $this->testReporter->addStep('health_check', 'completed');
    }

    protected function tearDownCodespacesTest(): void
    {
        if ($this->hasFailed()) {
            $this->testReporter->completeTest(false, $this->getStatusMessage());
        } else {
            $this->testReporter->completeTest(true);
        }
    }

    protected function linkTestToChecklist(string $checklistItem): void
    {
        if ($this->currentTestId) {
            $this->testReporter->linkToChecklist($this->currentTestId, $checklistItem);
        }
    }

    protected function addTestStep(string $step, string $status, ?string $message = null): void
    {
        $this->testReporter->addStep($step, $status, $message);
    }

    protected function getStatusMessage(): string
    {
        $status = $this->getStatus();
        $message = '';

        switch ($status) {
            case \PHPUnit\Runner\BaseTestRunner::STATUS_ERROR:
                $message = 'Test error: ' . $this->getStatusMessage();
                break;
            case \PHPUnit\Runner\BaseTestRunner::STATUS_FAILURE:
                $message = 'Test failure: ' . $this->getStatusMessage();
                break;
            case \PHPUnit\Runner\BaseTestRunner::STATUS_SKIPPED:
                $message = 'Test skipped: ' . $this->getStatusMessage();
                break;
            case \PHPUnit\Runner\BaseTestRunner::STATUS_INCOMPLETE:
                $message = 'Test incomplete: ' . $this->getStatusMessage();
                break;
            case \PHPUnit\Runner\BaseTestRunner::STATUS_RISKY:
                $message = 'Test risky: ' . $this->getStatusMessage();
                break;
            case \PHPUnit\Runner\BaseTestRunner::STATUS_WARNING:
                $message = 'Test warning: ' . $this->getStatusMessage();
                break;
        }

        return $message;
    }
} 