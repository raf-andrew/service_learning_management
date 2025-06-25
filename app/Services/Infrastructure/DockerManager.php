<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class DockerManager
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function startServices()
    {
        $process = Process::run('docker-compose up -d');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to start services: ' . $process->errorOutput());
        }

        return true;
    }

    public function stopServices()
    {
        $process = Process::run('docker-compose down');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to stop services: ' . $process->errorOutput());
        }

        return true;
    }

    public function waitForService($service, $timeout = 60)
    {
        $startTime = time();
        
        while (time() - $startTime < $timeout) {
            if ($this->isServiceReady($service)) {
                return true;
            }
            
            sleep(1);
        }

        throw new \RuntimeException("Service {$service} failed to start within {$timeout} seconds");
    }

    protected function isServiceReady($service)
    {
        $process = Process::run("docker-compose ps {$service}");
        
        if (!$process->successful()) {
            return false;
        }

        return Str::contains($process->output(), 'Up');
    }

    public function getServiceStatus()
    {
        $process = Process::run('docker-compose ps --format json');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to get service status: ' . $process->errorOutput());
        }

        $services = json_decode($process->output(), true);
        $status = [];

        foreach ($services as $service) {
            $status[$service['Service']] = $service['State'];
        }

        return $status;
    }

    public function removeVolumes()
    {
        $process = Process::run('docker-compose down -v');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to remove volumes: ' . $process->errorOutput());
        }

        return true;
    }

    public function rebuildService($service)
    {
        $process = Process::run("docker-compose build {$service}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to rebuild service {$service}: " . $process->errorOutput());
        }

        return true;
    }

    public function getServiceLogs($service, $lines = 100)
    {
        $process = Process::run("docker-compose logs --tail={$lines} {$service}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to get logs for service {$service}: " . $process->errorOutput());
        }

        return $process->output();
    }

    public function executeCommand($service, $command)
    {
        $process = Process::run("docker-compose exec {$service} {$command}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to execute command on service {$service}: " . $process->errorOutput());
        }

        return $process->output();
    }

    public function getContainerInfo($service)
    {
        $process = Process::run("docker-compose ps -q {$service}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to get container ID for service {$service}: " . $process->errorOutput());
        }

        $containerId = trim($process->output());
        
        $process = Process::run("docker inspect {$containerId}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to get container info for service {$service}: " . $process->errorOutput());
        }

        return json_decode($process->output(), true)[0];
    }

    public function getResourceUsage()
    {
        $process = Process::run('docker stats --no-stream --format json');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to get resource usage: ' . $process->errorOutput());
        }

        return json_decode($process->output(), true);
    }
} 