<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DeveloperCredential;
use App\Services\CodespaceService;

class RequireDeveloperCredentials
{
    protected $codespaceService;

    public function __construct(CodespaceService $codespaceService)
    {
        $this->codespaceService = $codespaceService;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $credential = DeveloperCredential::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'message' => 'No active developer credentials found'
            ], 403);
        }

        // Validate GitHub access
        if (!$this->codespaceService->validateDeveloperAccess($credential)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid GitHub credentials'
            ], 403);
        }

        // Check permissions based on the route
        if (!$this->hasRequiredPermissions($credential, $request)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        // Update last used timestamp
        $credential->update(['last_used_at' => now()]);

        return $next($request);
    }

    protected function hasRequiredPermissions(DeveloperCredential $credential, Request $request): bool
    {
        $permissions = $credential->permissions;

        // Check if the route requires codespace permissions
        if (str_contains($request->path(), 'codespaces')) {
            return $permissions['codespaces'] ?? false;
        }

        // Check if the route requires repository permissions
        if (str_contains($request->path(), 'repositories')) {
            return $permissions['repositories'] ?? false;
        }

        // Check if the route requires workflow permissions
        if (str_contains($request->path(), 'workflows')) {
            return $permissions['workflows'] ?? false;
        }

        return true;
    }
} 