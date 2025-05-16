<?php

namespace App\Http\Controllers;

use App\Services\BuildService;
use App\Models\Build;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BuildController extends Controller
{
    protected $buildService;

    public function __construct(BuildService $buildService)
    {
        $this->buildService = $buildService;
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'branch' => 'required|string'
        ]);

        try {
            $build = $this->buildService->createBuild($request->branch);

            return response()->json([
                'message' => 'Build started successfully',
                'build' => $build
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Build failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function status(int $buildId): JsonResponse
    {
        try {
            $status = $this->buildService->getBuildStatus($buildId);

            return response()->json([
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get build status',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function validate(int $buildId): JsonResponse
    {
        try {
            $result = $this->buildService->validateBuild($buildId);

            return response()->json([
                'message' => 'Build validation successful',
                'valid' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Build validation failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function list(Request $request): JsonResponse
    {
        $builds = Build::with(['environment'])
            ->when($request->branch, function ($query, $branch) {
                return $query->where('branch', $branch);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($builds);
    }
} 