<?php

namespace App\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SetupProjectCommand extends Command
{
    protected $signature = 'project:setup';
    protected $description = 'Setup Laravel project with required dependencies and directory structure';

    public function handle()
    {
        $this->info('Setting up Laravel project...');

        // Set environment variable for Docker Compose
        putenv('PWD=' . base_path());

        // Create Laravel project and install dependencies
        $process = new Process([
            'docker-compose',
            '-f',
            'docker-compose.test.yml',
            'run',
            '--rm',
            'test',
            'sh',
            '-c',
            'composer create-project laravel/laravel . && ' .
            'composer require --dev phpunit/phpunit phpmd/phpmd squizlabs/php_codesniffer && ' .
            'mkdir -p src/MCP/Core && ' .
            'chown -R www-data:www-data /var/www/html'
        ]);

        $process->setTty(true);
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('Project setup completed');
            return 0;
        }

        $this->error('Project setup failed');
        $this->error($process->getErrorOutput());
        return 1;
    }
} 