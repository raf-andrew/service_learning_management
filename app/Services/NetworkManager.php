<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class NetworkManager
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function createNetwork($name, array $options = [])
    {
        if ($this->networkExists($name)) {
            return true;
        }

        $driver = $options['driver'] ?? 'bridge';
        $subnet = $options['subnet'] ?? null;
        $gateway = $options['gateway'] ?? null;

        $command = "docker network create --driver {$driver}";
        
        if ($subnet) {
            $command .= " --subnet {$subnet}";
        }
        
        if ($gateway) {
            $command .= " --gateway {$gateway}";
        }

        $command .= " {$name}";

        $process = Process::run($command);
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to create network {$name}: " . $process->errorOutput());
        }

        return true;
    }

    public function removeNetwork($name)
    {
        if (!$this->networkExists($name)) {
            return true;
        }

        $process = Process::run("docker network rm {$name}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to remove network {$name}: " . $process->errorOutput());
        }

        return true;
    }

    public function networkExists($name)
    {
        $process = Process::run("docker network ls --filter name={$name} --format '{{.Name}}'");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to check network {$name}: " . $process->errorOutput());
        }

        return trim($process->output()) === $name;
    }

    public function getNetworkStatus($name)
    {
        if (!$this->networkExists($name)) {
            return 'not_found';
        }

        $process = Process::run("docker network inspect {$name} --format '{{.State}}'");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to get network status for {$name}: " . $process->errorOutput());
        }

        return trim($process->output());
    }

    public function connectContainer($network, $container)
    {
        $process = Process::run("docker network connect {$network} {$container}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to connect container {$container} to network {$network}: " . $process->errorOutput());
        }

        return true;
    }

    public function disconnectContainer($network, $container)
    {
        $process = Process::run("docker network disconnect {$network} {$container}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to disconnect container {$container} from network {$network}: " . $process->errorOutput());
        }

        return true;
    }

    public function getNetworkInfo($name)
    {
        $process = Process::run("docker network inspect {$name}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to get network info for {$name}: " . $process->errorOutput());
        }

        return json_decode($process->output(), true)[0];
    }

    public function listNetworks()
    {
        $process = Process::run('docker network ls --format json');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to list networks: ' . $process->errorOutput());
        }

        return json_decode($process->output(), true);
    }

    public function getConnectedContainers($network)
    {
        $info = $this->getNetworkInfo($network);
        return $info['Containers'] ?? [];
    }

    public function pruneNetworks()
    {
        $process = Process::run('docker network prune -f');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to prune networks: ' . $process->errorOutput());
        }

        return true;
    }
} 