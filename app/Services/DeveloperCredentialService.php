<?php

namespace App\Services;

use App\Models\DeveloperCredential;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeveloperCredentialService
{
    public function createCredential(User $user, array $data): DeveloperCredential
    {
        return DeveloperCredential::create([
            'user_id' => $user->id,
            'github_token' => $this->encryptToken($data['github_token']),
            'github_username' => $data['github_username'],
            'permissions' => $this->determinePermissions($data['github_token']),
            'is_active' => true
        ]);
    }

    public function updateCredential(DeveloperCredential $credential, array $data): bool
    {
        if (isset($data['github_token'])) {
            $data['github_token'] = $this->encryptToken($data['github_token']);
            $data['permissions'] = $this->determinePermissions($data['github_token']);
        }

        return $credential->update($data);
    }

    public function deactivateCredential(DeveloperCredential $credential): bool
    {
        return $credential->update(['is_active' => false]);
    }

    public function getActiveCredential(User $user): ?DeveloperCredential
    {
        return $user->developerCredentials()
            ->active()
            ->latest()
            ->first();
    }

    public function validateToken(string $token): bool
    {
        try {
            $process = Process::fromShellCommandline("gh auth status --token {$token}");
            $process->run();
            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function encryptToken(string $token): string
    {
        return encrypt($token);
    }

    private function determinePermissions(string $token): array
    {
        $permissions = ['codespace:read'];

        try {
            $process = Process::fromShellCommandline("gh auth status --token {$token}");
            $process->run();
            
            if ($process->isSuccessful()) {
                $permissions[] = 'codespace:write';
                
                // Check for admin permissions
                $process = Process::fromShellCommandline("gh api user");
                $process->run();
                $userData = json_decode($process->getOutput(), true);
                
                if (isset($userData['site_admin']) && $userData['site_admin']) {
                    $permissions[] = 'codespace:admin';
                }
            }
        } catch (\Exception $e) {
            // Log error but don't throw
            \Log::error('Error determining permissions', ['error' => $e->getMessage()]);
        }

        return $permissions;
    }
} 