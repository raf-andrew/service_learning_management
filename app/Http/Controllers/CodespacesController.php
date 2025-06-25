<?php

namespace App\Http\Controllers;

use App\Services\CodespaceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CodespacesController extends Controller
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
    // ...copy all other methods from CodespaceController...
}
