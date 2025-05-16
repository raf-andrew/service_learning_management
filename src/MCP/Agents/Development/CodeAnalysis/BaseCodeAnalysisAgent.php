<?php

namespace App\MCP\Agents\Development\CodeAnalysis;

use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

/**
 * Base class for code analysis agents
 * 
 * Provides common functionality for code analysis agents:
 * - Health monitoring
 * - Lifecycle management
 * - Logging
 * - Metrics tracking
 * - Analysis reporting
 */
abstract class BaseCodeAnalysisAgent
{
    protected HealthMonitor $healthMonitor;
    protected AgentLifecycleManager $lifecycleManager;
    protected LoggerInterface $logger;

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        $this->healthMonitor = $healthMonitor;
        $this->lifecycleManager = $lifecycleManager;
        $this->logger = $logger;
    }

    /**
     * Initialize the agent
     */
    public function initialize(): void
    {
        $this->logger->info('Initializing code analysis agent: ' . static::class);
        $this->healthMonitor->registerAgent($this);
        $this->lifecycleManager->registerAgent($this);
    }

    /**
     * Shutdown the agent
     */
    public function shutdown(): void
    {
        $this->logger->info('Shutting down code analysis agent: ' . static::class);
        $this->healthMonitor->unregisterAgent($this);
        $this->lifecycleManager->unregisterAgent($this);
    }

    /**
     * Get the agent's health status
     */
    public function getHealthStatus(): array
    {
        return [
            'status' => 'healthy',
            'last_check' => date('Y-m-d H:i:s'),
            'metrics' => $this->getMetrics()
        ];
    }

    /**
     * Get the agent's metrics
     */
    abstract public function getMetrics(): array;

    /**
     * Analyze code and return results
     */
    abstract public function analyze(array $files): array;

    /**
     * Get analysis recommendations
     */
    abstract public function getRecommendations(): array;

    /**
     * Get analysis report
     */
    abstract public function getReport(): array;
} 