<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateCommandNamespacesCommand extends Command
{
    protected $signature = 'commands:update-namespaces';
    protected $description = 'Update namespaces of moved commands';

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
        
        foreach ($this->domains as $domain => $files) {
            $this->info("Processing {$domain} domain...");
            
            foreach ($files as $file) {
                $filePath = $basePath . '/.' . $domain . '/' . $file;
                
                if (!File::exists($filePath)) {
                    $this->warn("File not found: {$file}");
                    continue;
                }

                $content = File::get($filePath);
                $className = pathinfo($file, PATHINFO_FILENAME);
                
                // Update namespace
                $newNamespace = "App\\Console\\Commands\\.{$domain}";
                $content = preg_replace(
                    '/namespace\s+App\\\Console\\\Commands;/',
                    "namespace {$newNamespace};",
                    $content
                );
                
                // Update any references to the class
                $content = preg_replace(
                    '/use\s+App\\\\Console\\\\Commands\\\\' . preg_quote($className, '/') . ';/',
                    "use {$newNamespace}\\{$className};",
                    $content
                );
                
                File::put($filePath, $content);
                $this->info("Updated namespace for {$file}");
            }
        }

        $this->info('Namespace updates completed!');
    }
} 