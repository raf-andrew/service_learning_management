<?php

namespace App\Console\Commands\Auth;

class ManageAuthTokensCommand extends BaseAuthCommand
{
    protected $signature = 'auth:tokens
        {action : The action to perform (list|create|revoke|clear)}
        {--user= : User email}
        {--token= : Token ID}
        {--name= : Token name}
        {--expires= : Expiration date (Y-m-d)}
        {--all : Apply to all tokens}';

    protected $description = 'Manage authentication tokens';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listTokens();
            case 'create':
                return $this->createToken();
            case 'revoke':
                return $this->revokeToken();
            case 'clear':
                return $this->clearTokens();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listTokens()
    {
        $user = $this->option('user');

        try {
            $tokens = $user 
                ? $this->authService->getUserTokens($user)
                : $this->authService->getAllTokens();

            $this->table(
                ['ID', 'User', 'Name', 'Created', 'Expires', 'Last Used'],
                $tokens->map(fn($token) => [
                    $token->id,
                    $token->user->email,
                    $token->name,
                    $token->created_at->format('Y-m-d H:i:s'),
                    $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : 'Never',
                    $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i:s') : 'Never'
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list tokens: {$e->getMessage()}");
            return 1;
        }
    }

    protected function createToken()
    {
        $user = $this->option('user');
        $name = $this->option('name');
        $expires = $this->option('expires');

        if (!$user || !$name) {
            $this->error('User email and token name are required');
            return 1;
        }

        try {
            $token = $this->authService->createToken($user, [
                'name' => $name,
                'expires_at' => $expires ? new \DateTime($expires) : null
            ]);

            $this->info("Token created successfully for user {$user}");
            $this->info("Token: {$token->plainTextToken}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create token: {$e->getMessage()}");
            return 1;
        }
    }

    protected function revokeToken()
    {
        $token = $this->option('token');
        $user = $this->option('user');

        if (!$token && !$user) {
            $this->error('Either token ID or user email is required');
            return 1;
        }

        try {
            if ($token) {
                $this->authService->revokeToken($token);
                $this->info("Token {$token} revoked successfully");
            } else {
                $this->authService->revokeUserTokens($user);
                $this->info("All tokens for user {$user} revoked successfully");
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to revoke token: {$e->getMessage()}");
            return 1;
        }
    }

    protected function clearTokens()
    {
        $user = $this->option('user');
        $all = $this->option('all');

        if (!$user && !$all) {
            $this->error('Either user email or --all option is required');
            return 1;
        }

        if ($this->confirm('Are you sure you want to clear these tokens?')) {
            try {
                if ($all) {
                    $this->authService->clearAllTokens();
                    $this->info('All tokens cleared successfully');
                } else {
                    $this->authService->clearUserTokens($user);
                    $this->info("All tokens for user {$user} cleared successfully");
                }
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to clear tokens: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 