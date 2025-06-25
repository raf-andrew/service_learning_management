<?php

namespace App\Console\Commands\GitHub;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateToken extends Command
{
    protected $signature = 'github:update-token';
    protected $description = 'Update GitHub token with interactive prompts';

    private const GITHUB_TOKEN_URL = 'https://github.com/settings/tokens/new';
    private const REQUIRED_SCOPES = [
        'repo' => 'Full control of private repositories',
        'workflow' => 'Update GitHub Action workflows',
        'admin:org' => 'Full control of organizations and teams',
        'admin:repo_hook' => 'Full control of repository hooks',
        'admin:org_hook' => 'Full control of organization hooks',
        'admin:gpg_key' => 'Full control of GPG keys',
        'admin:ssh_signing_key' => 'Full control of SSH signing keys'
    ];

    public function handle()
    {
        $this->info('GitHub Token Update Process');
        $this->info('==========================');
        $this->newLine();

        // Show required scopes
        $this->info('Required Token Scopes:');
        foreach (self::REQUIRED_SCOPES as $scope => $description) {
            $this->line("• {$scope}: {$description}");
        }
        $this->newLine();

        // Open browser if possible
        if ($this->confirm('Would you like to open the GitHub token creation page in your browser?', true)) {
            $this->openBrowser();
        } else {
            $this->info('Please visit: ' . self::GITHUB_TOKEN_URL);
        }

        // Get token
        $token = $this->secret('Please enter your new GitHub token');
        
        if (empty($token)) {
            $this->error('Token cannot be empty');
            return 1;
        }

        // Validate token format
        if (!preg_match('/^ghp_[a-zA-Z0-9]{36}$/', $token)) {
            $this->error('Invalid token format. GitHub tokens should start with "ghp_" followed by 36 characters.');
            return 1;
        }

        // Update .env file
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            $this->error('.env file not found');
            return 1;
        }

        $envContent = File::get($envPath);
        $newEnvContent = $this->updateEnvToken($envContent, $token);

        if ($newEnvContent === $envContent) {
            $this->warn('Token was not updated. Make sure GITHUB_TOKEN exists in your .env file.');
            return 1;
        }

        File::put($envPath, $newEnvContent);
        $this->info('GitHub token updated successfully!');

        // Sync config
        if ($this->confirm('Would you like to sync the new token with the database?', true)) {
            $this->call('github:sync-config');
        }

        return 0;
    }

    private function openBrowser()
    {
        $url = self::GITHUB_TOKEN_URL;
        
        if (PHP_OS_FAMILY === 'Windows') {
            exec("start {$url}");
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec("open {$url}");
        } else {
            exec("xdg-open {$url}");
        }
    }

    private function updateEnvToken($content, $token)
    {
        $pattern = '/^GITHUB_TOKEN=.*/m';
        $replacement = "GITHUB_TOKEN={$token}";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replacement, $content);
        }

        return $content . "\n{$replacement}\n";
    }
} 