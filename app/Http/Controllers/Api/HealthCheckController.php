<?php

namespace App\Http\Controllers;

use App\Models\HealthCheck;
use App\Services\HealthCheckService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class HealthCheckController extends Controller
{
    protected $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    public function index(): JsonResponse
    {
        $healthChecks = HealthCheck::all();
        return response()->json($healthChecks);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:health_checks',
            'type' => 'required|string|in:http,database,cache,queue,custom',
            'target' => 'required|string',
            'config' => 'nullable|array',
            'timeout' => 'nullable|integer|min:1',
            'retry_attempts' => 'nullable|integer|min:1',
            'retry_delay' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $healthCheck = HealthCheck::create($request->all());
        return response()->json($healthCheck, 201);
    }

    public function show(HealthCheck $healthCheck): JsonResponse
    {
        return response()->json($healthCheck);
    }

    public function update(Request $request, HealthCheck $healthCheck): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:health_checks,name,' . $healthCheck->id,
            'type' => 'string|in:http,database,cache,queue,custom',
            'target' => 'string',
            'config' => 'nullable|array',
            'timeout' => 'nullable|integer|min:1',
            'retry_attempts' => 'nullable|integer|min:1',
            'retry_delay' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $healthCheck->update($request->all());
        return response()->json($healthCheck);
    }

    public function destroy(HealthCheck $healthCheck): JsonResponse
    {
        $healthCheck->delete();
        return response()->json(null, 204);
    }

    public function runCheck(HealthCheck $healthCheck): JsonResponse
    {
        $result = $this->healthCheckService->checkService(
            $healthCheck->name,
            $healthCheck->config ?? []
        );

        return response()->json($result);
    }

    public function toggleActive(HealthCheck $healthCheck): JsonResponse
    {
        $healthCheck->is_active = !$healthCheck->is_active;
        $healthCheck->save();

        return response()->json([
            'id' => $healthCheck->id,
            'is_active' => $healthCheck->is_active
        ]);
    }

    public function getResults(HealthCheck $healthCheck): JsonResponse
    {
        $results = $healthCheck->results()
            ->latest()
            ->paginate(10);

        return response()->json($results);
    }

    public function getLatestResult(HealthCheck $healthCheck): JsonResponse
    {
        $result = $healthCheck->getLatestResult();
        return response()->json($result);
    }
} 