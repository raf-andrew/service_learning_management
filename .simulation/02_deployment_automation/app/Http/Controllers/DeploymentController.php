<?php

namespace App\Http\Controllers;

use App\Services\DeploymentService;
use App\Models\Deployment;
use App\Models\Build;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeploymentController extends Controller
{
    protected $deploymentService;

    public function __construct(DeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;
    }

    public function deploy(Request $request): JsonResponse
    {
        $request->validate([
            'environment' => 'required|string',
            'build_id' => 'required|integer|exists:builds,id'
        ]);

        try {
            $build = Build::findOrFail($request->build_id);
            $deployment = $this->deploymentService->deploy($request->environment, $build);

            return response()->json([
                'message' => 'Deployment started successfully',
                'deployment' => $deployment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Deployment failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function status(int $deploymentId): JsonResponse
    {
        try {
            $status = $this->deploymentService->getDeploymentStatus($deploymentId);

            return response()->json([
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get deployment status',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function rollback(int $deploymentId): JsonResponse
    {
        try {
            $rollbackDeployment = $this->deploymentService->rollback($deploymentId);

            return response()->json([
                'message' => 'Rollback started successfully',
                'deployment' => $rollbackDeployment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Rollback failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function list(Request $request): JsonResponse
    {
        $deployments = Deployment::with(['environment', 'build'])
            ->when($request->environment, function ($query, $environment) {
                return $query->whereHas('environment', function ($q) use ($environment) {
                    $q->where('name', $environment);
                });
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($deployments);
    }
} 