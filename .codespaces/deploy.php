<?php

require_once __DIR__ . '/vendor/autoload.php';

use Codespaces\Deployments\DeploymentManager;

// Parse command line options
$options = getopt('', ['environment:', 'config:', 'action:', 'service:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php deploy.php [options]\n";
    echo "Options:\n";
    echo "  --environment=<env>    Environment to deploy to (local or codespaces)\n";
    echo "  --config=<path>        Path to configuration file\n";
    echo "  --action=<action>      Action to perform (deploy, validate, rollback)\n";
    echo "  --service=<name>       Specific service to deploy (optional)\n";
    echo "  --help                 Show this help message\n";
    exit(0);
}

// Set default values
$environment = $options['environment'] ?? 'codespaces';
$configPath = $options['config'] ?? __DIR__ . '/config/services.json';
$action = $options['action'] ?? 'deploy';
$service = $options['service'] ?? null;

// Validate environment
if (!in_array($environment, ['local', 'codespaces'])) {
    echo "Error: Invalid environment. Must be 'local' or 'codespaces'\n";
    exit(1);
}

// Validate config file
if (!file_exists($configPath)) {
    echo "Error: Configuration file not found: {$configPath}\n";
    exit(1);
}

try {
    // Create deployment manager
    $manager = new DeploymentManager($configPath, $environment);

    // Execute requested action
    switch ($action) {
        case 'deploy':
            echo "Starting deployment in {$environment} environment...\n";
            if ($service) {
                echo "Deploying service: {$service}\n";
                $result = $manager->deployService($service);
            } else {
                echo "Deploying all services...\n";
                $result = $manager->deployServices();
            }
            break;

        case 'validate':
            echo "Validating deployment in {$environment} environment...\n";
            if ($service) {
                echo "Validating service: {$service}\n";
                $result = $manager->validateService($service);
            } else {
                echo "Validating all services...\n";
                $manager->validateDeployment();
                $result = true;
            }
            break;

        case 'rollback':
            if (!$service) {
                echo "Error: Service name required for rollback\n";
                exit(1);
            }
            echo "Rolling back service {$service} in {$environment} environment...\n";
            $manager->rollbackDeployment($service);
            $result = true;
            break;

        default:
            echo "Error: Invalid action '{$action}'\n";
            exit(1);
    }

    // Print deployment state
    $state = $manager->getDeploymentState();
    echo "\nDeployment State:\n";
    echo "================\n";
    foreach ($state as $serviceName => $serviceState) {
        echo "{$serviceName}:\n";
        echo "  Status: {$serviceState['status']}\n";
        echo "  Health: {$serviceState['health']}\n";
        echo "  Last Update: {$serviceState['timestamp']}\n";
    }

    // Exit with appropriate status code
    exit($result ? 0 : 1);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 