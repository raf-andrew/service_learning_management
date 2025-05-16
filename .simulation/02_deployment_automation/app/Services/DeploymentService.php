<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Build;
use Illuminate\Support\Facades\Log;

class DeploymentService
{
    public function deploy(string $environmentName, Build $build): Deployment
    {
        $environment = Environment::where('name', $environmentName)->firstOrFail();

        if (!$environment->isDeployable()) {
            throw new \Exception("Environment {$environmentName} is not deployable");
        }

        $environment->markAsDeploying();

        try {
            $deployment = Deployment::create([
                'environment_id' => $environment->id,
                'build_id' => $build->id,
                'status' => 'in_progress',
                'deployed_by' => auth()->user()->name ?? 'system',
                'deployment_number' => $this->getNextDeploymentNumber($environment),
                'started_at' => now()
            ]);

            $deployment->markAsInProgress();

            // Execute deployment steps
            $this->executeDeploymentSteps($deployment);

            $deployment->markAsSuccessful();
            $environment->markAsReady();

            Log::info('Deployment completed successfully', [
                'environment' => $environmentName,
                'deployment' => $deployment->id,
                'build' => $build->id
            ]);

            return $deployment;
        } catch (\Exception $e) {
            $deployment->markAsFailed($e->getMessage());
            $environment->markAsFailed();

            Log::error('Deployment failed', [
                'environment' => $environmentName,
                'deployment' => $deployment->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function getDeploymentStatus(int $deploymentId): string
    {
        $deployment = Deployment::findOrFail($deploymentId);
        return $deployment->status;
    }

    public function rollback(int $deploymentId): Deployment
    {
        $failedDeployment = Deployment::findOrFail($deploymentId);
        $environment = $failedDeployment->environment;
        $lastSuccessfulDeployment = $environment->getLastSuccessfulDeployment();

        if (!$lastSuccessfulDeployment) {
            throw new \Exception('No successful deployment found to rollback to');
        }

        $environment->markAsDeploying();

        try {
            $rollbackDeployment = Deployment::create([
                'environment_id' => $environment->id,
                'build_id' => $lastSuccessfulDeployment->build_id,
                'status' => 'in_progress',
                'deployed_by' => auth()->user()->name ?? 'system',
                'deployment_number' => $this->getNextDeploymentNumber($environment),
                'rollback_to' => $lastSuccessfulDeployment->id,
                'started_at' => now()
            ]);

            $rollbackDeployment->markAsInProgress();

            // Execute rollback steps
            $this->executeRollbackSteps($rollbackDeployment, $lastSuccessfulDeployment);

            $rollbackDeployment->markAsSuccessful();
            $environment->markAsReady();

            Log::info('Rollback completed successfully', [
                'environment' => $environment->name,
                'deployment' => $rollbackDeployment->id,
                'rollback_to' => $lastSuccessfulDeployment->id
            ]);

            return $rollbackDeployment;
        } catch (\Exception $e) {
            $rollbackDeployment->markAsFailed($e->getMessage());
            $environment->markAsFailed();

            Log::error('Rollback failed', [
                'environment' => $environment->name,
                'deployment' => $rollbackDeployment->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function getNextDeploymentNumber(Environment $environment): int
    {
        $lastDeployment = $environment->deployments()
            ->orderBy('deployment_number', 'desc')
            ->first();

        return $lastDeployment ? $lastDeployment->deployment_number + 1 : 1;
    }

    protected function executeDeploymentSteps(Deployment $deployment): void
    {
        // Implementation of deployment steps
        // This would include:
        // 1. Code checkout
        // 2. Dependency installation
        // 3. Asset compilation
        // 4. Database migrations
        // 5. Cache clearing
        // 6. Service restart
    }

    protected function executeRollbackSteps(Deployment $deployment, Deployment $targetDeployment): void
    {
        // Implementation of rollback steps
        // This would include:
        // 1. Code checkout to target version
        // 2. Database rollback
        // 3. Cache clearing
        // 4. Service restart
    }
} 