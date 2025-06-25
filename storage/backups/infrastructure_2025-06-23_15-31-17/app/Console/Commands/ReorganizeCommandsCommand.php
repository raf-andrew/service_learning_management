<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ReorganizeCommandsCommand extends Command
{
    protected $signature = 'commands:reorganize';
    protected $description = 'Reorganize commands into domain-specific folders';

    protected $domains = [
        'sniffing' => [
            'TestSniffingCommand.php',
            'SniffingAnalyzeCommand.php',
            'AnalyzeSniffingResultsCommand.php',
            'ManageSniffingRulesCommand.php',
            'InitSniffingSystemCommand.php',
            'InitSniffingCommand.php',
            'SniffCommand.php',
            'ClearSniffingDataCommand.php',
            'GenerateReportCommand.php',
            'CodeSnifferCommand.php',
        ],
        'codespaces' => [
            'CodespacesTestCommand.php',
            'CodespacesHealthCheckCommand.php',
            'ManageCodespacesEnvironment.php',
            'ManageCodespacesServices.php',
            'RunCodespacesTests.php',
            'CodespaceCommand.php',
        ],
        'web3' => [
            'ManageWeb3Contracts.php',
            'ManageWeb3Environment.php',
            'UpdateWeb3Checklist.php',
            'RunWeb3Tests.php',
            'ManageWeb3Dashboard.php',
            'DeployWeb3Contracts.php',
        ],
        'infrastructure' => [
            'InfraTestMonitoringCommand.php',
            'InfraTestFirewallRulesCommand.php',
            'InfraTestAccessControlCommand.php',
            'InfraTestKeyManagementCommand.php',
            'InfraTestCacheCommand.php',
            'InfraTestDatabaseCommand.php',
            'InfraTestEnvVarsCommand.php',
        ],
        'environment' => [
            'EnvSync.php',
            'EnvRestore.php',
        ],
        'documentation' => [
            'GenerateDocsCommand.php',
        ],
        'docker' => [
            'DockerCommand.php',
        ],
    ];

    public function handle()
    {
        $basePath = app_path('Console/Commands');
        
        // Create domain directories
        foreach (array_keys($this->domains) as $domain) {
            $domainPath = $basePath . '/.' . $domain;
            if (!File::exists($domainPath)) {
                File::makeDirectory($domainPath, 0755, true);
                $this->info("Created directory: .{$domain}");
            }
        }

        // Move files to their respective domains
        foreach ($this->domains as $domain => $files) {
            foreach ($files as $file) {
                $sourcePath = $basePath . '/' . $file;
                $destinationPath = $basePath . '/.' . $domain . '/' . $file;

                if (File::exists($sourcePath)) {
                    File::move($sourcePath, $destinationPath);
                    $this->info("Moved {$file} to .{$domain}");
                } else {
                    $this->warn("File not found: {$file}");
                }
            }
        }

        $this->info('Command reorganization completed!');
    }
} 