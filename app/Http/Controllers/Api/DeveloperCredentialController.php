<?php

namespace App\Http\Controllers;

use App\Models\DeveloperCredential;
use App\Services\DeveloperCredentialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeveloperCredentialController extends BaseApiController
{
    private $credentialService;

    public function __construct(DeveloperCredentialService $credentialService)
    {
        $this->credentialService = $credentialService;
    }

    public function index(): JsonResponse
    {
        return $this->executeDbOperation(function () {
            $this->applyRateLimit('credentials:index');
            
            $credentials = DeveloperCredential::where('user_id', $this->getCurrentUser()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($credentials, 'Credentials retrieved successfully');
        }, 'DeveloperCredentialController::index');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('credentials:store');
            
            $rules = [
                'github_token' => 'required|string',
                'github_username' => 'required|string|max:255',
                'permissions' => 'required|array',
                'permissions.codespaces' => 'required|boolean',
                'permissions.repositories' => 'required|boolean',
                'permissions.workflows' => 'required|boolean'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $credential = DeveloperCredential::create([
                'user_id' => $this->getCurrentUser()->id,
                'github_token' => $validatedData['github_token'],
                'github_username' => $validatedData['github_username'],
                'permissions' => $validatedData['permissions'],
                'is_active' => true,
                'expires_at' => now()->addYear()
            ]);

            return $this->successResponse($credential, 'Credential created successfully', 201);
        }, 'DeveloperCredentialController::store');
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request, $id) {
            $this->applyRateLimit('credentials:update');
            
            $credential = DeveloperCredential::where('user_id', $this->getCurrentUser()->id)
                ->findOrFail($id);

            $rules = [
                'github_username' => 'sometimes|required|string|max:255',
                'permissions' => 'sometimes|required|array',
                'permissions.codespaces' => 'required_with:permissions|boolean',
                'permissions.repositories' => 'required_with:permissions|boolean',
                'permissions.workflows' => 'required_with:permissions|boolean'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $credential->update($validatedData);

            return $this->successResponse($credential, 'Credential updated successfully');
        }, 'DeveloperCredentialController::update');
    }

    public function destroy($id): JsonResponse
    {
        return $this->executeDbOperation(function () use ($id) {
            $this->applyRateLimit('credentials:destroy');
            
            $credential = DeveloperCredential::where('user_id', $this->getCurrentUser()->id)
                ->findOrFail($id);

            $credential->delete();

            return $this->successResponse(null, 'Developer credential deleted successfully');
        }, 'DeveloperCredentialController::destroy');
    }

    public function activate($id): JsonResponse
    {
        return $this->executeDbOperation(function () use ($id) {
            $this->applyRateLimit('credentials:activate');
            
            $credential = DeveloperCredential::where('user_id', $this->getCurrentUser()->id)
                ->findOrFail($id);

            $credential->update([
                'is_active' => true,
                'expires_at' => now()->addYear()
            ]);

            return $this->successResponse($credential, 'Credential activated successfully');
        }, 'DeveloperCredentialController::activate');
    }

    public function deactivate($id): JsonResponse
    {
        return $this->executeDbOperation(function () use ($id) {
            $this->applyRateLimit('credentials:deactivate');
            
            $credential = DeveloperCredential::where('user_id', $this->getCurrentUser()->id)
                ->findOrFail($id);

            $credential->update(['is_active' => false]);

            return $this->successResponse($credential, 'Credential deactivated successfully');
        }, 'DeveloperCredentialController::deactivate');
    }

    public function create(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('credentials:create');
            
            $rules = [
                'github_token' => 'required|string',
                'github_username' => 'required|string'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $credential = $this->credentialService->createCredential($this->getCurrentUser(), $validatedData);
            
            return $this->successResponse($credential, 'Credential created successfully', 201);
        }, 'DeveloperCredentialController::create');
    }

    public function getActive(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('credentials:getActive');
            
            $credential = $this->credentialService->getActiveCredential($this->getCurrentUser());
            
            return $this->successResponse($credential, 'Active credential retrieved successfully');
        }, 'DeveloperCredentialController::getActive');
    }
} 