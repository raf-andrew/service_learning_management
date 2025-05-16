<?php

namespace App\MCP\Core\Services;

class AgentRegistry
{
    protected array $registry = [];

    public function register(string $category, string $name, object $agent): void
    {
        if (!isset($this->registry[$category])) {
            $this->registry[$category] = [];
        }
        $this->registry[$category][$name] = $agent;
    }

    public function get(string $category, string $name): ?object
    {
        return $this->registry[$category][$name] ?? null;
    }

    public function getAll(string $category = null): array
    {
        if ($category) {
            return $this->registry[$category] ?? [];
        }
        return $this->registry;
    }

    public function deregister(string $category, string $name): void
    {
        if (isset($this->registry[$category][$name])) {
            unset($this->registry[$category][$name]);
        }
    }
} 