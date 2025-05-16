<?php

namespace App\Http\Controllers;

use App\Models\DeveloperCredential;
use App\Services\DeveloperCredentialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DeveloperCredentialController extends Controller
{
    private $credentialService;

    public function __construct(DeveloperCredentialService $credentialService)
    {
        $this->credentialService = $credentialService;
    }

    public function index()
    {
        $credentials = DeveloperCredential::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $credentials
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'github_token' => 'required|string',
            'github_username' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.codespaces' => 'required|boolean',
            'permissions.repositories' => 'required|boolean',
            'permissions.workflows' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credential = DeveloperCredential::create([
            'user_id' => auth()->id(),
            'github_token' => $request->github_token,
            'github_username' => $request->github_username,
            'permissions' => $request->permissions,
            'is_active' => true,
            'expires_at' => now()->addYear()
        ]);

        return response()->json([
            'success' => true,
            'data' => $credential
        ]);
    }

    public function update(Request $request, $id)
    {
        $credential = DeveloperCredential::where('user_id', auth()->id())
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'github_username' => 'sometimes|required|string|max:255',
            'permissions' => 'sometimes|required|array',
            'permissions.codespaces' => 'required_with:permissions|boolean',
            'permissions.repositories' => 'required_with:permissions|boolean',
            'permissions.workflows' => 'required_with:permissions|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credential->update($request->only(['github_username', 'permissions']));

        return response()->json([
            'success' => true,
            'data' => $credential
        ]);
    }

    public function destroy($id)
    {
        $credential = DeveloperCredential::where('user_id', auth()->id())
            ->findOrFail($id);

        $credential->delete();

        return response()->json([
            'success' => true,
            'message' => 'Developer credential deleted successfully'
        ]);
    }

    public function activate($id)
    {
        $credential = DeveloperCredential::where('user_id', auth()->id())
            ->findOrFail($id);

        $credential->update([
            'is_active' => true,
            'expires_at' => now()->addYear()
        ]);

        return response()->json([
            'success' => true,
            'data' => $credential
        ]);
    }

    public function deactivate($id)
    {
        $credential = DeveloperCredential::where('user_id', auth()->id())
            ->findOrFail($id);

        $credential->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'data' => $credential
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'github_token' => 'required|string',
            'github_username' => 'required|string'
        ]);

        try {
            $credential = $this->credentialService->createCredential($request->user(), $validated);
            return response()->json([
                'success' => true,
                'data' => $credential
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActive(Request $request): JsonResponse
    {
        try {
            $credential = $this->credentialService->getActiveCredential($request->user());
            return response()->json([
                'success' => true,
                'data' => $credential
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 