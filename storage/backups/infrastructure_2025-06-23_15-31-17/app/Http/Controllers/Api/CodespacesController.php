<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class CodespacesController extends Controller
{
    protected CodespacesHealthService $healthService;
    protected CodespacesTestReporter $testReporter;

    public function __construct(
        CodespacesHealthService $healthService,
        CodespacesTestReporter $testReporter
    ) {
        $this->healthService = $healthService;
        $this->testReporter = $testReporter;
    }

    public function health(): JsonResponse
    {
        $healthStatus = $this->healthService->checkAllServices();
        return response()->json($healthStatus);
    }

    public function runTests(Request $request): JsonResponse
    {
        // Check if Codespaces is enabled
        if (!Config::get('codespaces.enabled', false)) {
            return response()->json([
                'error' => 'Codespaces is not enabled'
            ], 403);
        }

        // Check service health before running tests
        $healthStatus = $this->healthService->checkAllServices();
        $unhealthyServices = array_filter($healthStatus, fn($status) => !$status['healthy']);

        if (!empty($unhealthyServices)) {
            return response()->json([
                'error' => 'Some services are unhealthy. Please fix them before running tests.',
                'unhealthy_services' => $unhealthyServices
            ], 500);
        }

        // Build test command
        $command = 'test';
        if ($request->has('suite')) {
            $command .= ' --testsuite=' . $request->input('suite');
        }
        if ($request->has('filter')) {
            $command .= ' --filter=' . $request->input('filter');
        }

        // Run tests
        $exitCode = Artisan::call($command);

        // Get test results
        $results = $this->testReporter->getLastResults();
        $results['suite'] = $request->input('suite', 'all');
        $results['filter'] = $request->input('filter');

        return response()->json([
            'success' => $exitCode === 0,
            'results' => $results
        ]);
    }

    public function generateReport(Request $request): JsonResponse
    {
        $report = $this->testReporter->generateReport($request->all());
        return response()->json(['report' => $report]);
    }

    public function saveReport(Request $request): JsonResponse
    {
        $filename = $this->testReporter->saveReport($request->all());
        return response()->json(['filename' => $filename]);
    }
} 