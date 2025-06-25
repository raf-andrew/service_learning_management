<?php

namespace App\Console\Commands\Auth;

class ManageAuthTestsCommand extends BaseAuthCommand
{
    protected $signature = 'auth:tests
        {action : The action to perform (list|run|generate)}
        {--suite= : Test suite}
        {--filter= : Test filter}
        {--name= : Test name}
        {--type= : Test type (unit|feature|integration)}';

    protected $description = 'Manage authentication tests';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listTests();
            case 'run':
                return $this->runTests();
            case 'generate':
                return $this->generateTest();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listTests()
    {
        $suite = $this->option('suite');
        $type = $this->option('type');

        try {
            $tests = $this->authService->getAllTests([
                'suite' => $suite,
                'type' => $type
            ]);

            $this->table(
                ['Name', 'Type', 'Suite', 'Status'],
                $tests->map(fn($test) => [
                    $test->name,
                    $test->type,
                    $test->suite,
                    $test->status
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list tests: {$e->getMessage()}");
            return 1;
        }
    }

    protected function runTests()
    {
        $suite = $this->option('suite');
        $filter = $this->option('filter');

        try {
            $results = $this->authService->runTests([
                'suite' => $suite,
                'filter' => $filter
            ]);

            $this->info("Test Results:");
            $this->table(
                ['Test', 'Status', 'Time', 'Message'],
                $results->map(fn($result) => [
                    $result->name,
                    $result->status,
                    $result->time,
                    $result->message
                ])
            );

            return $results->contains('status', 'failed') ? 1 : 0;
        } catch (\Exception $e) {
            $this->error("Failed to run tests: {$e->getMessage()}");
            return 1;
        }
    }

    protected function generateTest()
    {
        $name = $this->option('name');
        $type = $this->option('type');
        $suite = $this->option('suite');

        if (!$name || !$type) {
            $this->error('Test name and type are required');
            return 1;
        }

        try {
            $test = $this->authService->generateTest([
                'name' => $name,
                'type' => $type,
                'suite' => $suite
            ]);

            $this->info("Test generated successfully: {$test->name}");
            $this->info("Location: {$test->path}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate test: {$e->getMessage()}");
            return 1;
        }
    }
} 