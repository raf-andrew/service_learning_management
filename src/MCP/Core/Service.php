<?php

namespace App\MCP\Core;

use Illuminate\Support\Facades\Log;

abstract class Service
{
    protected string $id;
    protected string $name;
    protected array $config = [];
    protected array $metrics = [];
    protected bool $isHealthy = true;

    public function __construct()
    {
        $this->id = uniqid($this->getName() . '_');
        $this->initialize();
    }

    abstract protected function initialize(): void;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    public function setHealthy(bool $isHealthy): void
    {
        $this->isHealthy = $isHealthy;
    }

    public function getStatus(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_healthy' => $this->isHealthy,
            'metrics' => $this->metrics
        ];
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function recordMetric(string $name, $value): void
    {
        $this->metrics[$name] = [
            'value' => $value,
            'timestamp' => now(),
        ];
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->name}] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->name}] {$message}", $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning("[{$this->name}] {$message}", $context);
    }
} 