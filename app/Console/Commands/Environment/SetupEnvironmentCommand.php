<?php

namespace App\Console\Commands\Environment;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupEnvironmentCommand extends Command
{
    protected $signature = 'env:setup';
    protected $description = 'Setup environment files for local and codespaces development';

    public function handle()
    {
        $this->info('Setting up environment files...');

        // Create .env.example if it doesn't exist
        $this->createEnvExample();

        // Create .env from .env.example if it doesn't exist
        $this->createEnvFile();

        // Ensure GitHub token is set
        $this->ensureGitHubToken();

        $this->info('Environment setup complete');
        return 0;
    }

    protected function createEnvExample()
    {
        $envExample = <<<'EOT'
# Environment Configuration
# This file serves as a template for both local and codespaces environments
# Copy this file to .env and update the values as needed

# Application Environment
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# GitHub Configuration
GITHUB_TOKEN=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=

# Database Configuration
# Local Development
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=service_learning
DB_USERNAME=root
DB_PASSWORD=

# Codespaces Development
CODESPACES_DB_CONNECTION=mysql
CODESPACES_DB_HOST=mysql
CODESPACES_DB_PORT=3306
CODESPACES_DB_DATABASE=service_learning
CODESPACES_DB_USERNAME=root
CODESPACES_DB_PASSWORD=

# Redis Configuration
# Local Development
REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6379

# Codespaces Development
CODESPACES_REDIS_HOST=redis
CODESPACES_REDIS_PASSWORD=null
CODESPACES_REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

# Service Configuration
# Set to 'local' for local development or 'codespaces' for codespaces development
SERVICE_ENVIRONMENT=local

# Test Configuration
TEST_DB_CONNECTION=mysql
TEST_DB_HOST=localhost
TEST_DB_PORT=3306
TEST_DB_DATABASE=service_learning_test
TEST_DB_USERNAME=root
TEST_DB_PASSWORD=
EOT;

        if (!File::exists(base_path('.env.example'))) {
            File::put(base_path('.env.example'), $envExample);
            $this->info('Created .env.example file');
        } else {
            $this->info('.env.example file already exists');
        }
    }

    protected function createEnvFile()
    {
        if (!File::exists(base_path('.env'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->info('Created .env file from .env.example');
        } else {
            $this->info('.env file already exists');
        }
    }

    protected function ensureGitHubToken()
    {
        $envContent = File::get(base_path('.env'));
        if (!preg_match('/^GITHUB_TOKEN=/m', $envContent)) {
            File::append(base_path('.env'), "\nGITHUB_TOKEN=");
            $this->info('Added GITHUB_TOKEN to .env');
        }
    }
} 