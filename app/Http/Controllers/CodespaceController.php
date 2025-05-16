<?php

namespace App\Http\Controllers;

use App\Services\CodespaceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CodespaceController extends Controller
{
    protected $codespaceService;

    public function __construct(CodespaceService $codespaceService)
    {
        $this->codespaceService = $codespaceService;
    }

    public function index()
    {
        try {
            $codespaces = $this->codespaceService->list();
            $regions = $this->codespaceService->getAvailableRegions();
            $machines = $this->codespaceService->getAvailableMachines();

            return response()->json([
                'success' => true,
                'data' => [
                    'codespaces' => $codespaces,
                    'regions' => $regions,
                    'machines' => $machines
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'branch' => 'required|string',
            'region' => 'nullable|string',
            'machine' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $codespace = $this->codespaceService->create(
                $request->name,
                $request->branch,
                $request->region,
                $request->machine
            );

            return response()->json([
                'success' => true,
                'data' => $codespace
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($name)
    {
        try {
            $this->codespaceService->delete($name);
            return response()->json([
                'success' => true,
                'message' => "Codespace {$name} deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rebuild($name)
    {
        try {
            $result = $this->codespaceService->rebuild($name);
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function status($name)
    {
        try {
            $status = $this->codespaceService->getStatus($name);
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function connect($name)
    {
        try {
            $connection = $this->codespaceService->connect($name);
            return response()->json([
                'success' => true,
                'data' => $connection
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 