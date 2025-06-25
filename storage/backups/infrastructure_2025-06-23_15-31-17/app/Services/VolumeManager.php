<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class VolumeManager
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function createVolume($name, array $options = [])
    {
        if ($this->volumeExists($name)) {
            return true;
        }

        $driver = $options['driver'] ?? 'local';
        $driverOpts = $options['driver_opts'] ?? [];
        $labels = $options['labels'] ?? [];

        $command = "docker volume create --driver {$driver}";
        
        foreach ($driverOpts as $key => $value) {
            $command .= " --opt {$key}={$value}";
        }
        
        foreach ($labels as $key => $value) {
            $command .= " --label {$key}={$value}";
        }

        $command .= " {$name}";

        $process = Process::run($command);
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to create volume {$name}: " . $process->errorOutput());
        }

        return true;
    }

    public function removeVolume($name)
    {
        if (!$this->volumeExists($name)) {
            return true;
        }

        $process = Process::run("docker volume rm {$name}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to remove volume {$name}: " . $process->errorOutput());
        }

        return true;
    }

    public function volumeExists($name)
    {
        $process = Process::run("docker volume ls --filter name={$name} --format '{{.Name}}'");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to check volume {$name}: " . $process->errorOutput());
        }

        return trim($process->output()) === $name;
    }

    public function getVolumeInfo($name)
    {
        $process = Process::run("docker volume inspect {$name}");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to get volume info for {$name}: " . $process->errorOutput());
        }

        return json_decode($process->output(), true)[0];
    }

    public function listVolumes()
    {
        $process = Process::run('docker volume ls --format json');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to list volumes: ' . $process->errorOutput());
        }

        return json_decode($process->output(), true);
    }

    public function pruneVolumes()
    {
        $process = Process::run('docker volume prune -f');
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to prune volumes: ' . $process->errorOutput());
        }

        return true;
    }

    public function backupVolume($name, $backupPath)
    {
        if (!$this->volumeExists($name)) {
            throw new \RuntimeException("Volume {$name} does not exist");
        }

        $process = Process::run("docker run --rm -v {$name}:/source -v {$backupPath}:/backup alpine tar -czf /backup/{$name}.tar.gz -C /source .");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to backup volume {$name}: " . $process->errorOutput());
        }

        return true;
    }

    public function restoreVolume($name, $backupPath)
    {
        if (!$file_exists("{$backupPath}/{$name}.tar.gz")) {
            throw new \RuntimeException("Backup file for volume {$name} does not exist");
        }

        if ($this->volumeExists($name)) {
            $this->removeVolume($name);
        }

        $this->createVolume($name);

        $process = Process::run("docker run --rm -v {$name}:/target -v {$backupPath}:/backup alpine sh -c 'cd /target && tar -xzf /backup/{$name}.tar.gz'");
        
        if (!$process->successful()) {
            throw new \RuntimeException("Failed to restore volume {$name}: " . $process->errorOutput());
        }

        return true;
    }

    public function getVolumeUsage($name)
    {
        $process = Process::run("docker system df -v --format json");
        
        if (!$process->successful()) {
            throw new \RuntimeException('Failed to get volume usage: ' . $process->errorOutput());
        }

        $data = json_decode($process->output(), true);
        $volumes = $data['Volumes'] ?? [];

        foreach ($volumes as $volume) {
            if ($volume['Name'] === $name) {
                return [
                    'size' => $volume['Size'],
                    'reclaimable' => $volume['Reclaimable'],
                ];
            }
        }

        return null;
    }
} 