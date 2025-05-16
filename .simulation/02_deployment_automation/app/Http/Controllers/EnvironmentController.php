<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentService;
use App\Models\Environment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnvironmentController extends Controller
{
    protected $environmentService;

    public function __construct(EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:environments,name',
            'branch' => 'required|string',
            'url' => 'required|url',
            'variables' => 'required|array'
        ]);

        try {
            $environment = $this->environmentService->createEnvironment(
                $request->name,
                $request->only(['branch', 'url', 'variables'])
            );

            return response()->json([
                'message' => 'Environment created successfully',
                'environment' => $environment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create environment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, string $name): JsonResponse
    {
        $request->validate([
            'branch' => 'sometimes|string',
            'url' => 'sometimes|url',
            'variables' => 'sometimes|array'
        ]);

        try {
            $environment = $this->environmentService->updateEnvironment(
                $name,
                $request->only(['branch', 'url', 'variables'])
            );

            return response()->json([
                'message' => 'Environment updated successfully',
                'environment' => $environment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update environment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function delete(string $name): JsonResponse
    {
        try {
            $this->environmentService->deleteEnvironment($name);

            return response()->json([
                'message' => 'Environment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete environment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function validate(string $name): JsonResponse
    {
        try {
            $result = $this->environmentService->validateEnvironment($name);

            return response()->json([
                'message' => 'Environment validation successful',
                'valid' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Environment validation failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function list(): JsonResponse
    {
        $environments = Environment::with(['deployments', 'builds'])
            ->orderBy('name')
            ->get();

        return response()->json($environments);
    }
} 