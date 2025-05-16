<?php

namespace Codespaces\Deployments;

class DeploymentTracker {
    private string $deploymentsPath;
    private array $currentState;
    private array $config;

    public function __construct(string $configPath) {
        $this->deploymentsPath = __DIR__ . '/state';
        $this->config = json_decode(file_get_contents($configPath), true);
        $this->loadCurrentState();
    }

    private function loadCurrentState(): void {
        $stateFile = $this->deploymentsPath . '/current.json';
        if (file_exists($stateFile)) {
            $this->currentState = json_decode(file_get_contents($stateFile), true);
        } else {
            $this->currentState = [
                'deployments' => [],
                'last_update' => date('c'),
                'version' => '1.0.0'
            ];
            $this->saveCurrentState();
        }
    }

    private function saveCurrentState(): void {
        if (!is_dir($this->deploymentsPath)) {
            mkdir($this->deploymentsPath, 0755, true);
        }
        
        $stateFile = $this->deploymentsPath . '/current.json';
        $this->currentState['last_update'] = date('c');
        file_put_contents($stateFile, json_encode($this->currentState, JSON_PRETTY_PRINT));
        
        // Create a historical record
        $historyFile = $this->deploymentsPath . '/history/' . date('Y-m-d_H-i-s') . '.json';
        if (!is_dir(dirname($historyFile))) {
            mkdir(dirname($historyFile), 0755, true);
        }
        file_put_contents($historyFile, json_encode($this->currentState, JSON_PRETTY_PRINT));
    }

    public function trackDeployment(string $serviceName, array $deploymentData): void {
        $this->currentState['deployments'][$serviceName] = [
            'status' => $deploymentData['status'],
            'timestamp' => date('c'),
            'environment' => $deploymentData['environment'],
            'version' => $deploymentData['version'],
            'health' => $deploymentData['health'] ?? 'unknown',
            'metrics' => $deploymentData['metrics'] ?? [],
            'configuration' => $deploymentData['configuration'] ?? []
        ];
        
        $this->saveCurrentState();
    }

    public function updateServiceHealth(string $serviceName, string $status, array $metrics = []): void {
        if (isset($this->currentState['deployments'][$serviceName])) {
            $this->currentState['deployments'][$serviceName]['health'] = $status;
            $this->currentState['deployments'][$serviceName]['metrics'] = $metrics;
            $this->currentState['deployments'][$serviceName]['last_health_check'] = date('c');
            $this->saveCurrentState();
        }
    }

    public function getDeploymentStatus(string $serviceName): ?array {
        return $this->currentState['deployments'][$serviceName] ?? null;
    }

    public function getAllDeployments(): array {
        return $this->currentState['deployments'];
    }

    public function getDeploymentHistory(string $serviceName, int $limit = 10): array {
        $history = [];
        $historyDir = $this->deploymentsPath . '/history';
        
        if (!is_dir($historyDir)) {
            return $history;
        }

        $files = glob($historyDir . '/*.json');
        rsort($files);

        foreach ($files as $file) {
            $state = json_decode(file_get_contents($file), true);
            if (isset($state['deployments'][$serviceName])) {
                $history[] = $state['deployments'][$serviceName];
                if (count($history) >= $limit) {
                    break;
                }
            }
        }

        return $history;
    }

    public function cleanupOldHistory(int $daysToKeep = 30): void {
        $historyDir = $this->deploymentsPath . '/history';
        if (!is_dir($historyDir)) {
            return;
        }

        $cutoff = strtotime("-{$daysToKeep} days");
        $files = glob($historyDir . '/*.json');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }

    public function validateDeployment(string $serviceName): bool {
        if (!isset($this->currentState['deployments'][$serviceName])) {
            return false;
        }

        $deployment = $this->currentState['deployments'][$serviceName];
        $serviceConfig = $this->config['services'][$serviceName] ?? null;

        if (!$serviceConfig) {
            return false;
        }

        // Check required fields
        $requiredFields = ['status', 'timestamp', 'environment', 'version', 'health'];
        foreach ($requiredFields as $field) {
            if (!isset($deployment[$field])) {
                return false;
            }
        }

        // Validate environment matches configuration
        if ($deployment['environment'] !== $serviceConfig['environment']['MCP_ENV']) {
            return false;
        }

        return true;
    }
} 