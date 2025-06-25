<?php

namespace App\Console\Commands\Deployment;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    protected $signature = 'deploy {environment : The environment to deploy to (development|staging|production)}';
    protected $description = 'Deploy the application to the specified environment';

    public function handle()
    {
        $environment = $this->argument('environment');

        if (!in_array($environment, ['development', 'staging', 'production'])) {
            $this->error('Invalid environment. Must be one of: development, staging, production');
            return 1;
        }

        try {
            // Initialize environment
            $this->initializeEnvironment($environment);

            // Deploy to Codespaces
            $this->deployToCodespaces();

            // Deploy to Vapor
            $this->deployToVapor();

            // Generate deployment report
            $this->generateDeploymentReport($environment);

            $this->info('Deployment completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during deployment: ' . $e->getMessage());
            return 1;
        }
    }

    protected function initializeEnvironment(string $environment)
    {
        $this->info("Initializing environment: {$environment}");

        // Set environment variables
        putenv("APP_ENV={$environment}");

        // Initialize Terraform
        $this->info('Initializing Terraform...');
        $this->runProcess(['terraform', 'init'], base_path('infrastructure/terraform'));

        // Plan infrastructure
        $this->info('Planning infrastructure...');
        $this->runProcess(['terraform', 'plan', '-var', "environment={$environment}"], base_path('infrastructure/terraform'));

        // Apply infrastructure
        $this->info('Applying infrastructure...');
        $this->runProcess(['terraform', 'apply', '-auto-approve', '-var', "environment={$environment}"], base_path('infrastructure/terraform'));
    }

    protected function deployToCodespaces()
    {
        $this->info('Deploying to Codespaces...');

        // Build and push Docker image
        $this->info('Building Docker image...');
        $this->runProcess(['docker', 'build', '-t', "service-learning-management:" . getenv('APP_ENV'), '.']);
        $this->runProcess(['docker', 'push', "service-learning-management:" . getenv('APP_ENV')]);

        // Deploy to Codespaces
        $this->info('Deploying to Codespaces...');
        $this->runProcess(['gh', 'codespace', 'deploy', "service-learning-management:" . getenv('APP_ENV')]);
    }

    protected function deployToVapor()
    {
        $this->info('Deploying to Vapor...');

        // Run tests
        $this->info('Running tests...');
        $this->runProcess(['php', 'artisan', 'test']);

        // Deploy to Vapor
        $this->info('Deploying to Vapor...');
        $this->runProcess(['vapor', 'deploy', getenv('APP_ENV')]);
    }

    protected function generateDeploymentReport(string $environment)
    {
        $this->info('Generating deployment report...');

        $reportContent = <<<EOT
# Deployment Report: {$environment}

## Deployment Date
{$this->getCurrentDate()}

## Environment Details
- Environment: {$environment}
- Region: {$this->getAwsRegion()}
- Database: {$environment}-db
- Cache: {$environment}-cache
- Queue: {$environment}-queue

## Test Results
{$this->getTestResults()}

## Infrastructure Status
{$this->getTerraformOutput()}
EOT;

        $reportPath = base_path("tests/reports/deployment-{$environment}-report.md");
        file_put_contents($reportPath, $reportContent);

        $this->info("Deployment report generated at: {$reportPath}");
    }

    protected function runProcess(array $command, ?string $cwd = null)
    {
        $process = new Process($command, $cwd);
        $process->setTty(true);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Command failed: " . $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    protected function getCurrentDate(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function getAwsRegion(): string
    {
        return getenv('AWS_REGION') ?: 'us-east-1';
    }

    protected function getTestResults(): string
    {
        $reports = glob(base_path('tests/reports/*.md'));
        $results = '';

        foreach ($reports as $report) {
            $results .= file_get_contents($report) . "\n";
        }

        return $results;
    }

    protected function getTerraformOutput(): string
    {
        return $this->runProcess(['terraform', 'output'], base_path('infrastructure/terraform'));
    }
} 