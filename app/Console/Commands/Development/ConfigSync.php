<?php

namespace App\Console\Commands\GitHub;

use Illuminate\Console\Command;
use App\Models\GitHub\Config;
use App\Models\GitHub\Repository;
use Illuminate\Support\Facades\File;

class ConfigSync extends Command
{
    protected $signature = 'github:sync-config {--force : Force sync even if config exists}';
    protected $description = 'Sync GitHub configurations from environment and repository';

    public function handle()
    {
        // Sync GitHub token
        $token = env('GITHUB_TOKEN');
        if (!$token) {
            $this->error('GITHUB_TOKEN not found in environment');
            return 1;
        }

        Config::updateOrCreate(
            ['key' => 'GITHUB_TOKEN'],
            [
                'value' => $token,
                'group' => 'github',
                'is_encrypted' => true,
                'description' => 'GitHub Personal Access Token'
            ]
        );

        // Sync repository information
        $repo = env('GITHUB_REPOSITORY');
        if (!$repo) {
            // Try to get from git config
            $gitConfig = base_path('.git/config');
            if (File::exists($gitConfig)) {
                $config = parse_ini_file($gitConfig, true);
                $url = $config['remote "origin"']['url'] ?? null;
                if ($url) {
                    $repo = str_replace(['git@github.com:', 'https://github.com/'], '', $url);
                    $repo = str_replace('.git', '', $repo);
                }
            }
        }

        if (!$repo) {
            $this->error('Could not determine GitHub repository');
            return 1;
        }

        Config::updateOrCreate(
            ['key' => 'GITHUB_REPOSITORY'],
            [
                'value' => $repo,
                'group' => 'github',
                'is_encrypted' => false,
                'description' => 'GitHub Repository Name'
            ]
        );

        // Sync repository settings
        try {
            $repository = Repository::firstOrCreate(['full_name' => $repo]);
            $repository->syncFromGitHub();
            $this->info('Repository settings synced successfully');
        } catch (\Exception $e) {
            $this->error('Failed to sync repository settings: ' . $e->getMessage());
            return 1;
        }

        $this->info('GitHub configuration synced successfully!');
        return 0;
    }
} 