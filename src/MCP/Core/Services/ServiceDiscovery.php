<?php

namespace App\MCP\Core\Services;

class ServiceDiscovery
{
    protected array $services = [];
    protected array $endpoints = [];

    public function register(string $name, object $service, array $metadata = []): void
    {
        $this->services[$name] = [
            'service' => $service,
            'metadata' => $metadata,
            'status' => 'active',
            'registered_at' => now(),
        ];
    }

    public function registerEndpoint(string $serviceName, string $endpoint, string $method = 'GET'): void
    {
        if (!isset($this->endpoints[$serviceName])) {
            $this->endpoints[$serviceName] = [];
        }
        $this->endpoints[$serviceName][] = [
            'endpoint' => $endpoint,
            'method' => $method,
            'registered_at' => now(),
        ];
    }

    public function get(string $name): ?array
    {
        return $this->services[$name] ?? null;
    }

    public function getEndpoints(string $serviceName): array
    {
        return $this->endpoints[$serviceName] ?? [];
    }

    public function getAllServices(): array
    {
        return $this->services;
    }

    public function deregister(string $name): void
    {
        unset($this->services[$name]);
        unset($this->endpoints[$name]);
    }

    public function updateStatus(string $name, string $status): void
    {
        if (isset($this->services[$name])) {
            $this->services[$name]['status'] = $status;
            $this->services[$name]['updated_at'] = now();
        }
    }
} 