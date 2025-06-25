<?php

namespace App\Console\Commands\.web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class UpdateWeb3Checklist extends Command
{
    protected $signature = 'web3:update-checklist';
    protected $description = 'Update the Web3 testing checklist based on test results';

    protected $checklistFile;
    protected $reportsDir;

    public function __construct()
    {
        parent::__construct();
        $this->checklistFile = base_path('.checklists/testing.md');
        $this->reportsDir = base_path('.web3/reports');
    }

    public function handle()
    {
        $this->info('Updating Web3 testing checklist...');

        try {
            // Get latest test results
            $testResults = $this->getLatestTestResults();
            if (!$testResults) {
                throw new \Exception('No test results found. Please run tests first.');
            }

            // Read current checklist
            if (!File::exists($this->checklistFile)) {
                throw new \Exception('Checklist file not found: ' . $this->checklistFile);
            }

            $checklistContent = File::get($this->checklistFile);
            $updatedContent = $this->updateChecklistContent($checklistContent, $testResults);

            // Save updated checklist
            File::put($this->checklistFile, $updatedContent);
            $this->info('Checklist updated successfully!');
        } catch (\Exception $error) {
            $this->error("Failed to update checklist: " . $error->getMessage());
            return 1;
        }
    }

    protected function getLatestTestResults()
    {
        if (!File::exists($this->reportsDir)) {
            return null;
        }

        $files = collect(File::files($this->reportsDir))
            ->filter(function ($file) {
                return str_ends_with($file->getFilename(), '.json');
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            });

        if ($files->isEmpty()) {
            return null;
        }

        $latestFile = $files->first();
        return json_decode(File::get($latestFile), true);
    }

    protected function updateChecklistContent($content, $testResults)
    {
        $lines = explode("\n", $content);
        $updatedLines = [];
        $inTestSection = false;

        foreach ($lines as $line) {
            if (str_contains($line, '## Smart Contract Testing')) {
                $inTestSection = true;
                $updatedLines[] = $line;
                continue;
            }

            if ($inTestSection && str_starts_with($line, '## ')) {
                $inTestSection = false;
            }

            if ($inTestSection && str_starts_with($line, '- [ ]')) {
                $testName = trim(substr($line, 4));
                $passed = $this->isTestPassed($testName, $testResults);
                $updatedLines[] = $passed ? "- [x] {$testName}" : $line;
            } else {
                $updatedLines[] = $line;
            }
        }

        return implode("\n", $updatedLines);
    }

    protected function isTestPassed($testName, $testResults)
    {
        foreach ($testResults['passed'] as $passedTest) {
            if (str_contains($passedTest['name'], $testName)) {
                return true;
            }
        }
        return false;
    }
} 