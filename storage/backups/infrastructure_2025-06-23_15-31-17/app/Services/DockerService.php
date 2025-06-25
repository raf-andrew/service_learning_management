<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DockerService
{
    protected string $host;
    protected string $apiVersion;
    protected int $timeout;

    public function __construct()
    {
        $this->host = config('docker.host', 'unix:///var/run/docker.sock');
        $this->apiVersion = config('docker.api_version', '1.41');
        $this->timeout = config('docker.timeout', 30);
    }

    public function listContainers(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->host . '/v' . $this->apiVersion . '/containers/json');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to list containers', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception listing containers', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function createContainer(array $config): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->host . '/v' . $this->apiVersion . '/containers/create', $config);

            if ($response->successful()) {
                $data = $response->json();
                return $data['Id'] ?? null;
            }

            Log::error('Failed to create container', [
                'config' => $config,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception creating container', [
                'config' => $config,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function startContainer(string $containerId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->host . '/v' . $this->apiVersion . '/containers/' . $containerId . '/start');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception starting container', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function stopContainer(string $containerId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->host . '/v' . $this->apiVersion . '/containers/' . $containerId . '/stop');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception stopping container', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function deleteContainer(string $containerId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete($this->host . '/v' . $this->apiVersion . '/containers/' . $containerId);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception deleting container', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getContainerStatus(string $containerId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->host . '/v' . $this->apiVersion . '/containers/' . $containerId . '/json');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting container status', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
} 