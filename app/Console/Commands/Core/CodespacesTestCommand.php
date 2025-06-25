<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;

class CodespacesTestCommand extends Command
{
    protected $signature = 'codespaces:test {--suite=}';
    protected $description = 'Run codespaces health and test suites';

    protected $healthService;
    protected $reporter;

    public function __construct(CodespacesHealthService $healthService, CodespacesTestReporter $reporter)
    {
        parent::__construct();
        $this->healthService = $healthService;
        $this->reporter = $reporter;
    }

    public function handle()
    {
        if (!config('codespaces.enabled')) {
            $this->error('Codespaces are not enabled.');
            return 1;
        }

        $health = $this->healthService->checkAllServices();
        $unhealthy = collect($health)->filter(fn($s) => !$s['healthy']);
        if ($unhealthy->isNotEmpty()) {
            $this->error('Some services are unhealthy.');
            return 1;
        }

        $suite = $this->option('suite');
        $testCommand = 'test';
        if ($suite) {
            $testCommand .= ' --testsuite=' . $suite;
        }
        \Artisan::call($testCommand);
        $this->info('Tests executed.');
        return 0;
    }
} 