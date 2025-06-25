<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class SetupEnvironmentCommand extends Command
{
    protected $signature = 'setup:environment';
    protected $description = 'Set up the development environment';

    public function handle()
    {
        $this->info('Starting development environment setup...');

        if (!$this->checkDocker()) {
            return 1;
        }

        $this->createDirectories();
        $this->setupEnvironmentFile();
        $this->startDockerContainers();
        $this->installDependencies();
        $this->setupLaravel();
        $this->setupNode();
        $this->setPermissions();

        $this->info('Setup completed successfully!');
        $this->info('Your application is now running at: http://localhost:8000');
        $this->info('MailHog interface is available at: http://localhost:8025');

        return 0;
    }

    protected function checkDocker(): bool
    {
        $this->info('Checking Docker installation...');

        $dockerProcess = new Process(['docker', '--version']);
        $dockerProcess->run();

        if (!$dockerProcess->isSuccessful()) {
            $this->error('Docker is not installed. Please install Docker first.');
            return false;
        }

        $dockerComposeProcess = new Process(['docker-compose', '--version']);
        $dockerComposeProcess->run();

        if (!$dockerComposeProcess->isSuccessful()) {
            $this->error('Docker Compose is not installed. Please install Docker Compose first.');
            return false;
        }

        return true;
    }

    protected function createDirectories()
    {
        $this->info('Creating necessary directories...');

        $directories = [
            'docker/mysql',
            'docker/nginx/conf.d',
            'docker/php'
        ];

        foreach ($directories as $dir) {
            File::makeDirectory(base_path($dir), 0755, true, true);
        }
    }

    protected function setupEnvironmentFile()
    {
        if (!File::exists(base_path('.env'))) {
            $this->info('Creating .env file...');
            File::copy(base_path('.env.example'), base_path('.env'));
        }
    }

    protected function startDockerContainers()
    {
        $this->info('Building and starting Docker containers...');

        $process = new Process(['docker-compose', 'up', '-d', '--build']);
        $process->setTty(true);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to start Docker containers.');
            exit(1);
        }
    }

    protected function installDependencies()
    {
        $this->info('Installing PHP dependencies...');

        $process = new Process(['docker-compose', 'exec', 'app', 'composer', 'install']);
        $process->setTty(true);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to install PHP dependencies.');
            exit(1);
        }
    }

    protected function setupLaravel()
    {
        $this->info('Generating application key...');
        $this->call('key:generate');

        $this->info('Running database migrations...');
        $this->call('migrate');
    }

    protected function setupNode()
    {
        $this->info('Installing Node.js dependencies...');

        $process = new Process(['docker-compose', 'exec', 'app', 'npm', 'install']);
        $process->setTty(true);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to install Node.js dependencies.');
            exit(1);
        }

        $this->info('Building assets...');

        $process = new Process(['docker-compose', 'exec', 'app', 'npm', 'run', 'build']);
        $process->setTty(true);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to build assets.');
            exit(1);
        }
    }

    protected function setPermissions()
    {
        $this->info('Setting permissions...');

        $process = new Process(['docker-compose', 'exec', 'app', 'chown', '-R', 'www-data:www-data', 'storage', 'bootstrap/cache']);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to set permissions.');
            exit(1);
        }
    }
} 