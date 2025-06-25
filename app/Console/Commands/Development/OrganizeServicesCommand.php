<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OrganizeServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'services:organize 
                            {--dry-run : Show what would be done without making changes}
                            {--backup : Create backups before making changes}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Organize services into proper directories and refactor namespaces';

    /**
     * Service organization mapping
     */
    protected array $serviceMapping = [
        // Core services
        'BaseService.php' => 'Core',
        'BaseRepository.php' => 'Core',
        'AuditService.php' => 'Core',
        
        // Caching services
        'CacheService.php' => 'Caching',
        
        // Monitoring services
        'MonitoringService.php' => 'Monitoring',
        'PerformanceOptimizationService.php' => 'Monitoring',
        
        // Configuration services
        'ConfigurationService.php' => 'Configuration',
        'ModuleDiscoveryService.php' => 'Configuration',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Service Organization and Namespace Refactoring ===');
        
        $basePath = 'modules/shared';
        $servicesPath = $basePath . '/Services';
        $dryRun = $this->option('dry-run');
        $backup = $this->option('backup');
        $verbose = $this->option('verbose');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
        }

        // Create backup directory if needed
        $backupDir = null;
        if ($backup && !$dryRun) {
            $backupDir = 'backups/services-' . date('Y-m-d_H-i-s');
            File::makeDirectory($backupDir, 0755, true);
            $this->info("Created backup directory: {$backupDir}");
        }

        $processedCount = 0;
        $errors = [];

        foreach ($this->serviceMapping as $serviceFile => $targetDir) {
            $this->processService($serviceFile, $targetDir, $basePath, $servicesPath, $dryRun, $backup, $backupDir, $verbose, $processedCount, $errors);
        }

        // Update service provider registrations
        $this->updateServiceProviders($dryRun, $verbose);

        // Generate summary
        $this->displaySummary($processedCount, $errors, $dryRun, $backupDir);

        return 0;
    }

    /**
     * Process a single service file
     */
    protected function processService(
        string $serviceFile,
        string $targetDir,
        string $basePath,
        string $servicesPath,
        bool $dryRun,
        bool $backup,
        ?string $backupDir,
        bool $verbose,
        int &$processedCount,
        array &$errors
    ): void {
        $sourcePath = $basePath . '/' . $serviceFile;
        $targetPath = $servicesPath . '/' . $targetDir . '/' . $serviceFile;

        if ($verbose) {
            $this->line("Processing: {$serviceFile} -> {$targetDir}");
        }

        if (!File::exists($sourcePath)) {
            $errors[] = "Service file not found: {$sourcePath}";
            $this->error("Service file not found: {$sourcePath}");
            return;
        }

        try {
            // Create target directory
            $targetDirPath = $servicesPath . '/' . $targetDir;
            if (!File::exists($targetDirPath)) {
                if (!$dryRun) {
                    File::makeDirectory($targetDirPath, 0755, true);
                    if ($verbose) {
                        $this->info("Created directory: {$targetDirPath}");
                    }
                } else {
                    $this->line("Would create directory: {$targetDirPath}");
                }
            }

            // Backup file if needed
            if ($backup && !$dryRun) {
                $backupPath = $backupDir . '/' . $serviceFile . '.backup';
                File::copy($sourcePath, $backupPath);
                if ($verbose) {
                    $this->info("Backed up: {$sourcePath} -> {$backupPath}");
                }
            }

            // Move file
            if (!$dryRun) {
                File::move($sourcePath, $targetPath);
                if ($verbose) {
                    $this->info("Moved: {$sourcePath} -> {$targetPath}");
                }
            } else {
                $this->line("Would move: {$sourcePath} -> {$targetPath}");
            }

            // Update namespace
            $this->updateNamespace($targetPath, $targetDir, $dryRun, $verbose);

            // Update references in other files
            $serviceName = pathinfo($serviceFile, PATHINFO_FILENAME);
            $this->updateReferences($serviceName, $targetDir, $dryRun, $verbose);

            $processedCount++;

        } catch (\Exception $e) {
            $errors[] = "Error processing {$serviceFile}: " . $e->getMessage();
            $this->error("Error processing {$serviceFile}: " . $e->getMessage());
        }
    }

    /**
     * Update namespace in a file
     */
    protected function updateNamespace(string $filePath, string $newNamespace, bool $dryRun, bool $verbose): void
    {
        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return;
        }

        $content = File::get($filePath);
        $originalContent = $content;

        // Update namespace declaration
        $content = preg_replace(
            '/namespace App\\\\Modules\\\\Shared;/',
            "namespace App\\Modules\\Shared\\Services\\{$newNamespace};",
            $content
        );

        // Update use statements for moved services
        $content = preg_replace(
            '/use App\\\\Modules\\\\Shared\\\\([^;]+);/',
            "use App\\Modules\\Shared\\Services\\{$newNamespace}\$1;",
            $content
        );

        if ($content !== $originalContent) {
            if (!$dryRun) {
                File::put($filePath, $content);
                if ($verbose) {
                    $this->info("Updated namespace in: {$filePath}");
                }
            } else {
                $this->line("Would update namespace in: {$filePath}");
            }
        }
    }

    /**
     * Update references in other files
     */
    protected function updateReferences(string $serviceName, string $newPath, bool $dryRun, bool $verbose): void
    {
        $files = File::allFiles('modules');
        $updatedCount = 0;

        foreach ($files as $file) {
            if (str_contains($file->getPathname(), $serviceName)) {
                continue; // Skip the service file itself
            }

            $content = File::get($file->getPathname());
            $originalContent = $content;

            // Update use statements
            $oldNamespace = "App\\Modules\\Shared\\{$serviceName}";
            $newNamespace = "App\\Modules\\Shared\\Services\\{$newPath}\\{$serviceName}";

            $content = str_replace($oldNamespace, $newNamespace, $content);

            if ($content !== $originalContent) {
                if (!$dryRun) {
                    File::put($file->getPathname(), $content);
                    if ($verbose) {
                        $this->info("Updated references in: {$file->getPathname()}");
                    }
                } else {
                    $this->line("Would update references in: {$file->getPathname()}");
                }
                $updatedCount++;
            }
        }

        if ($verbose && $updatedCount > 0) {
            $this->info("Updated {$updatedCount} files with references to {$serviceName}");
        }
    }

    /**
     * Update service provider registrations
     */
    protected function updateServiceProviders(bool $dryRun, bool $verbose): void
    {
        $serviceProviderFiles = [
            'modules/shared/SharedServiceProvider.php',
            'modules/e2ee/Providers/E2eeServiceProvider.php',
            'modules/soc2/providers/Soc2ServiceProvider.php',
            'modules/web3/Web3ServiceProvider.php',
            'modules/mcp/MCPServiceProvider.php'
        ];

        $this->info('Updating service provider registrations...');

        foreach ($serviceProviderFiles as $providerFile) {
            if (!File::exists($providerFile)) {
                continue;
            }

            $content = File::get($providerFile);
            $originalContent = $content;
            $updated = false;

            // Update service bindings to new namespaces
            foreach ($this->serviceMapping as $serviceFile => $targetDir) {
                $serviceName = pathinfo($serviceFile, PATHINFO_FILENAME);
                $oldNamespace = "App\\Modules\\Shared\\{$serviceName}";
                $newNamespace = "App\\Modules\\Shared\\Services\\{$targetDir}\\{$serviceName}";

                $content = str_replace($oldNamespace, $newNamespace, $content);
            }

            if ($content !== $originalContent) {
                if (!$dryRun) {
                    File::put($providerFile, $content);
                    if ($verbose) {
                        $this->info("Updated service provider: {$providerFile}");
                    }
                } else {
                    $this->line("Would update service provider: {$providerFile}");
                }
            }
        }
    }

    /**
     * Display summary of operations
     */
    protected function displaySummary(int $processedCount, array $errors, bool $dryRun, ?string $backupDir): void
    {
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Services processed: {$processedCount}");
        $this->line("Target directories: " . count(array_unique($this->serviceMapping)));

        if (!empty($errors)) {
            $this->error("Errors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if (!$dryRun) {
            if ($backupDir) {
                $this->warn("Backup location: {$backupDir}");
            }
            $this->info('All services have been organized and namespaces updated!');
        } else {
            $this->warn('Dry run completed. Review the changes above.');
        }

        $this->newLine();
        $this->info('=== Next Steps ===');
        $this->line('1. Run tests to ensure everything works correctly');
        $this->line('2. Update any remaining hardcoded references');
        $this->line('3. Update documentation to reflect new structure');
        $this->line('4. Commit changes to version control');
    }
} 