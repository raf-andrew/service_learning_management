<?php

class TestPlanUpdater
{
    private $testPlanFile = __DIR__ . '/../.reports/test_plan.md';
    private $reportsDir = __DIR__ . '/../.reports';
    private $testResults = [];
    private $sniffResults = [];

    public function update()
    {
        $this->loadTestResults();
        $this->loadSniffResults();
        $this->updateTestPlan();
    }

    private function loadTestResults()
    {
        $serviceHealthXml = $this->reportsDir . '/temp/service_health_test.xml';
        $deploymentXml = $this->reportsDir . '/temp/deployment_test.xml';

        if (file_exists($serviceHealthXml)) {
            $this->testResults['service_health'] = $this->parseJUnitXml($serviceHealthXml);
        }

        if (file_exists($deploymentXml)) {
            $this->testResults['deployment'] = $this->parseJUnitXml($deploymentXml);
        }
    }

    private function loadSniffResults()
    {
        $psr12Xml = $this->reportsDir . '/temp/psr12_sniff.xml';
        $phpmdXml = $this->reportsDir . '/temp/phpmd.xml';

        if (file_exists($psr12Xml)) {
            $this->sniffResults['psr12'] = $this->parseJUnitXml($psr12Xml);
        }

        if (file_exists($phpmdXml)) {
            $this->sniffResults['phpmd'] = $this->parsePhpMdXml($phpmdXml);
        }
    }

    private function parseJUnitXml($file)
    {
        $xml = simplexml_load_file($file);
        return [
            'tests' => (int)$xml->testsuite['tests'],
            'failures' => (int)$xml->testsuite['failures'],
            'errors' => (int)$xml->testsuite['errors']
        ];
    }

    private function parsePhpMdXml($file)
    {
        $xml = simplexml_load_file($file);
        return [
            'violations' => count($xml->file)
        ];
    }

    private function updateTestPlan()
    {
        $testPlan = file_get_contents($this->testPlanFile);
        $lines = explode("\n", $testPlan);
        $updatedLines = [];
        $inResults = false;

        foreach ($lines as $line) {
            if (strpos($line, '## Test Results') !== false) {
                $inResults = true;
                $updatedLines[] = $line;
                $updatedLines[] = '';
                $updatedLines[] = '### Unit Tests';
                $updatedLines[] = '```json';
                $updatedLines[] = json_encode([
                    'total' => array_sum(array_column($this->testResults, 'tests')),
                    'passed' => array_sum(array_column($this->testResults, 'tests')) - 
                               array_sum(array_column($this->testResults, 'failures')) -
                               array_sum(array_column($this->testResults, 'errors')),
                    'failed' => array_sum(array_column($this->testResults, 'failures')) +
                               array_sum(array_column($this->testResults, 'errors')),
                    'failures' => array_filter($this->testResults, function($result) {
                        return $result['failures'] > 0 || $result['errors'] > 0;
                    }, ARRAY_FILTER_USE_BOTH)
                ], JSON_PRETTY_PRINT);
                $updatedLines[] = '```';
                $updatedLines[] = '';
                $updatedLines[] = '### Code Sniffs';
                $updatedLines[] = '```json';
                $updatedLines[] = json_encode([
                    'total' => array_sum(array_column($this->sniffResults, 'violations')),
                    'passed' => 0,
                    'failed' => array_sum(array_column($this->sniffResults, 'violations')),
                    'violations' => $this->sniffResults
                ], JSON_PRETTY_PRINT);
                $updatedLines[] = '```';
                continue;
            }

            if ($inResults && strpos($line, '## Progress Tracking') !== false) {
                $inResults = false;
            }

            if (!$inResults) {
                $updatedLines[] = $line;
            }
        }

        file_put_contents($this->testPlanFile, implode("\n", $updatedLines));
    }
}

$updater = new TestPlanUpdater();
$updater->update(); 