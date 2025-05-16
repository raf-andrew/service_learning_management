<?php

namespace App\MCP\Core\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuditLogger
{
    protected array $logs = [];
    protected array $config = [
        'enabled' => true,
        'store_in_memory' => true,
        'log_to_file' => true,
        'max_memory_logs' => 1000,
    ];
    protected string $channel;
    protected array $context = [];

    public function __construct()
    {
        $this->channel = Config::get('mcp.logging.channel', 'audit');
    }

    public function log(string $category, string $message, array $context = []): void
    {
        $logData = array_merge($this->context, $context, [
            'category' => $category,
            'timestamp' => now()->toIso8601String(),
        ]);

        Log::channel($this->channel)->info($message, $logData);
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function clearContext(): void
    {
        $this->context = [];
    }

    public function getLogs(string $category = null, int $limit = null): array
    {
        if (!$category) {
            return array_slice($this->logs, -($limit ?? count($this->logs)));
        }

        $filtered = array_filter($this->logs, function($log) use ($category) {
            return $log['category'] === $category;
        });

        return array_slice($filtered, -($limit ?? count($filtered)));
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    public function getConfig(): array
    {
        return $this->config;
    }
} 