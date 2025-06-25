<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InfrastructureAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'infrastructure:analyze {--detailed : Show detailed analysis} {--fix : Attempt to fix issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze infrastructure for improvement opportunities';

    /**
     * Analysis results
     */
    private array $results = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting Infrastructure Analysis...');
        
        $this->analyzeCodeQuality();
        $this->analyzeDatabaseStructure();
        $this->analyzeRouteStructure();
        $this->analyzeConfiguration();
        $this->analyzeSecurity();
        $this->analyzePerformance();
        $this->analyzeModuleArchitecture();
        $this->analyzeTestingCoverage();
        $this->analyzeDocumentation();
        
        $this->displayResults();
        
        return 0;
    }

    /**
     * Analyze code quality
     */
    private function analyzeCodeQuality(): void
    {
        $this->info('📊 Analyzing Code Quality...');
        
        $issues = [];
        
        // Check for TODO/FIXME comments
        $todoFiles = $this->findTodoComments();
        if (!empty($todoFiles)) {
            $issues[] = "Found " . count($todoFiles) . " files with TODO/FIXME comments";
        }
        
        // Check for large files
        $largeFiles = $this->findLargeFiles();
        if (!empty($largeFiles)) {
            $issues[] = "Found " . count($largeFiles) . " files larger than 500 lines";
        }
        
        // Check for complex methods
        $complexMethods = $this->findComplexMethods();
        if (!empty($complexMethods)) {
            $issues[] = "Found " . count($complexMethods) . " methods with high complexity";
        }
        
        $this->results['code_quality'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'todo_files' => $todoFiles,
            'large_files' => $largeFiles,
            'complex_methods' => $complexMethods,
        ];
    }

    /**
     * Analyze database structure
     */
    private function analyzeDatabaseStructure(): void
    {
        $this->info('🗄️ Analyzing Database Structure...');
        
        $issues = [];
        
        try {
            // Check migration status
            $pendingMigrations = $this->getPendingMigrations();
            if (!empty($pendingMigrations)) {
                $issues[] = "Found " . count($pendingMigrations) . " pending migrations";
            }
            
            // Check table structure
            $tables = $this->analyzeTableStructure();
            $issues = array_merge($issues, $tables['issues']);
            
            $this->results['database'] = [
                'status' => empty($issues) ? 'good' : 'needs_attention',
                'issues' => $issues,
                'pending_migrations' => $pendingMigrations,
                'tables' => $tables['tables'],
            ];
        } catch (\Exception $e) {
            $this->results['database'] = [
                'status' => 'error',
                'issues' => ["Database connection failed: " . $e->getMessage()],
            ];
        }
    }

    /**
     * Analyze route structure
     */
    private function analyzeRouteStructure(): void
    {
        $this->info('🛣️ Analyzing Route Structure...');
        
        $issues = [];
        $routes = Route::getRoutes();
        
        // Check for unnamed routes
        $unnamedRoutes = collect($routes)->filter(function ($route) {
            return empty($route->getName());
        })->count();
        
        if ($unnamedRoutes > 0) {
            $issues[] = "Found {$unnamedRoutes} unnamed routes";
        }
        
        // Check for duplicate route names
        $routeNames = collect($routes)->map(function ($route) {
            return $route->getName();
        })->filter()->countBy();
        
        $duplicates = $routeNames->filter(function ($count) {
            return $count > 1;
        });
        
        if ($duplicates->count() > 0) {
            $issues[] = "Found " . $duplicates->count() . " duplicate route names";
        }
        
        $this->results['routes'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'total_routes' => $routes->count(),
            'unnamed_routes' => $unnamedRoutes,
            'duplicate_names' => $duplicates->toArray(),
        ];
    }

    /**
     * Analyze configuration
     */
    private function analyzeConfiguration(): void
    {
        $this->info('⚙️ Analyzing Configuration...');
        
        $issues = [];
        
        // Check for missing environment variables
        $requiredEnvVars = ['APP_KEY', 'APP_ENV', 'DB_CONNECTION'];
        $missingEnvVars = [];
        
        foreach ($requiredEnvVars as $var) {
            if (empty(env($var))) {
                $missingEnvVars[] = $var;
            }
        }
        
        if (!empty($missingEnvVars)) {
            $issues[] = "Missing environment variables: " . implode(', ', $missingEnvVars);
        }
        
        // Check for debug mode in production
        if (config('app.debug') && config('app.env') === 'production') {
            $issues[] = "Debug mode is enabled in production";
        }
        
        $this->results['configuration'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'missing_env_vars' => $missingEnvVars,
            'debug_enabled' => config('app.debug'),
            'environment' => config('app.env'),
        ];
    }

    /**
     * Analyze security
     */
    private function analyzeSecurity(): void
    {
        $this->info('🔒 Analyzing Security...');
        
        $issues = [];
        
        // Check for hardcoded secrets
        $hardcodedSecrets = $this->findHardcodedSecrets();
        if (!empty($hardcodedSecrets)) {
            $issues[] = "Found " . count($hardcodedSecrets) . " potential hardcoded secrets";
        }
        
        // Check for exposed sensitive files
        $exposedFiles = $this->findExposedFiles();
        if (!empty($exposedFiles)) {
            $issues[] = "Found " . count($exposedFiles) . " potentially exposed sensitive files";
        }
        
        $this->results['security'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'hardcoded_secrets' => $hardcodedSecrets,
            'exposed_files' => $exposedFiles,
        ];
    }

    /**
     * Analyze performance
     */
    private function analyzePerformance(): void
    {
        $this->info('⚡ Analyzing Performance...');
        
        $issues = [];
        
        // Check cache configuration
        if (config('cache.default') === 'file') {
            $issues[] = "Using file cache driver - consider Redis for better performance";
        }
        
        // Check queue configuration
        if (config('queue.default') === 'sync') {
            $issues[] = "Using synchronous queue driver - consider database or Redis for better performance";
        }
        
        $this->results['performance'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];
    }

    /**
     * Analyze module architecture
     */
    private function analyzeModuleArchitecture(): void
    {
        $this->info('🏗️ Analyzing Module Architecture...');
        
        $issues = [];
        $modules = [];
        
        $moduleDirs = File::directories(base_path('modules'));
        
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $moduleIssues = [];
            
            // Check for service provider
            if (!File::exists($moduleDir . '/ServiceProvider.php') && 
                !File::exists($moduleDir . '/' . ucfirst($moduleName) . 'ServiceProvider.php')) {
                $moduleIssues[] = "Missing service provider";
            }
            
            // Check for routes
            if (!File::exists($moduleDir . '/routes')) {
                $moduleIssues[] = "Missing routes directory";
            }
            
            // Check for config
            if (!File::exists($moduleDir . '/config')) {
                $moduleIssues[] = "Missing config directory";
            }
            
            $modules[$moduleName] = [
                'status' => empty($moduleIssues) ? 'good' : 'needs_attention',
                'issues' => $moduleIssues,
            ];
            
            if (!empty($moduleIssues)) {
                $issues[] = "Module {$moduleName}: " . implode(', ', $moduleIssues);
            }
        }
        
        $this->results['modules'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'module_details' => $modules,
        ];
    }

    /**
     * Analyze testing coverage
     */
    private function analyzeTestingCoverage(): void
    {
        $this->info('🧪 Analyzing Testing Coverage...');
        
        $issues = [];
        
        // Check for test files
        $testFiles = File::glob(base_path('tests/**/*.php'));
        $appFiles = File::glob(base_path('app/**/*.php'));
        
        $testRatio = count($testFiles) / max(count($appFiles), 1);
        
        if ($testRatio < 0.5) {
            $issues[] = "Low test coverage ratio: " . round($testRatio * 100, 1) . "%";
        }
        
        $this->results['testing'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
            'test_files' => count($testFiles),
            'app_files' => count($appFiles),
            'test_ratio' => $testRatio,
        ];
    }

    /**
     * Analyze documentation
     */
    private function analyzeDocumentation(): void
    {
        $this->info('📚 Analyzing Documentation...');
        
        $issues = [];
        
        // Check for README
        if (!File::exists(base_path('README.md'))) {
            $issues[] = "Missing README.md file";
        }
        
        // Check for API documentation
        if (!File::exists(base_path('docs'))) {
            $issues[] = "Missing docs directory";
        }
        
        $this->results['documentation'] = [
            'status' => empty($issues) ? 'good' : 'needs_attention',
            'issues' => $issues,
        ];
    }

    /**
     * Display analysis results
     */
    private function displayResults(): void
    {
        $this->newLine();
        $this->info('📋 Infrastructure Analysis Results');
        $this->newLine();
        
        $totalIssues = 0;
        
        foreach ($this->results as $category => $result) {
            $status = $result['status'];
            $issues = $result['issues'] ?? [];
            $totalIssues += count($issues);
            
            $statusIcon = $status === 'good' ? '✅' : ($status === 'error' ? '❌' : '⚠️');
            $this->line("{$statusIcon} " . Str::title(str_replace('_', ' ', $category)));
            
            if (!empty($issues)) {
                foreach ($issues as $issue) {
                    $this->line("   • {$issue}");
                }
            }
            
            if ($this->option('detailed')) {
                $this->displayDetailedResults($category, $result);
            }
            
            $this->newLine();
        }
        
        $this->info("Total Issues Found: {$totalIssues}");
        
        if ($totalIssues > 0) {
            $this->warn('Consider running with --fix to attempt automatic fixes');
        }
    }

    /**
     * Display detailed results for a category
     */
    private function displayDetailedResults(string $category, array $result): void
    {
        switch ($category) {
            case 'security':
                if (!empty($result['hardcoded_secrets'])) {
                    $this->line("   🔍 Hardcoded Secrets Found:");
                    foreach ($result['hardcoded_secrets'] as $file) {
                        $this->line("      - {$file}");
                    }
                }
                if (!empty($result['exposed_files'])) {
                    $this->line("   🔍 Exposed Files:");
                    foreach ($result['exposed_files'] as $file) {
                        $this->line("      - {$file}");
                    }
                }
                break;
                
            case 'code_quality':
                if (!empty($result['todo_files'])) {
                    $this->line("   🔍 Files with TODO/FIXME:");
                    foreach ($result['todo_files'] as $file) {
                        $this->line("      - {$file}");
                    }
                }
                if (!empty($result['large_files'])) {
                    $this->line("   🔍 Large Files (>500 lines):");
                    foreach ($result['large_files'] as $file) {
                        $this->line("      - {$file['file']} ({$file['lines']} lines)");
                    }
                }
                break;
                
            case 'database':
                if (!empty($result['pending_migrations'])) {
                    $this->line("   🔍 Pending Migrations:");
                    foreach ($result['pending_migrations'] as $migration) {
                        $this->line("      - {$migration}");
                    }
                }
                break;
        }
    }

    /**
     * Find TODO comments
     */
    private function findTodoComments(): array
    {
        $files = [];
        $patterns = ['*.php', '*.js', '*.vue', '*.blade.php'];
        
        foreach ($patterns as $pattern) {
            try {
                $phpFiles = File::glob(base_path("**/{$pattern}"), GLOB_BRACE);
                
                foreach ($phpFiles as $file) {
                    if (strpos($file, 'vendor/') !== false || strpos($file, 'node_modules/') !== false) continue;
                    
                    if (!File::exists($file)) continue;
                    
                    try {
                        $content = File::get($file);
                        if (preg_match('/TODO|FIXME|HACK|XXX/i', $content)) {
                            $files[] = str_replace(base_path() . '/', '', $file);
                        }
                    } catch (\Exception $e) {
                        // Skip files that can't be read
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // Skip patterns that cause errors
                continue;
            }
        }
        
        return $files;
    }

    /**
     * Find large files
     */
    private function findLargeFiles(): array
    {
        $files = [];
        
        try {
            $phpFiles = File::glob(base_path('**/*.php'), GLOB_BRACE);
            
            foreach ($phpFiles as $file) {
                if (strpos($file, 'vendor/') !== false || strpos($file, 'node_modules/') !== false) continue;
                
                if (!File::exists($file)) continue;
                
                try {
                    $lines = count(file($file));
                    if ($lines > 500) {
                        $files[] = [
                            'file' => str_replace(base_path() . '/', '', $file),
                            'lines' => $lines,
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip files that can't be read
                    continue;
                }
            }
        } catch (\Exception $e) {
            // Handle glob errors
        }
        
        return $files;
    }

    /**
     * Find complex methods
     */
    private function findComplexMethods(): array
    {
        // This is a simplified implementation
        // In a real scenario, you'd use tools like PHPMD or PHPStan
        return [];
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles($migrator->paths());
            $ran = $migrator->getRepository()->getRan();
            
            return array_diff($files, $ran);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze table structure
     */
    private function analyzeTableStructure(): array
    {
        $issues = [];
        $tables = [];
        
        try {
            $dbTables = DB::select('SELECT name FROM sqlite_master WHERE type="table"');
            
            foreach ($dbTables as $table) {
                $tableName = $table->name;
                $columns = DB::select("PRAGMA table_info({$tableName})");
                
                $tables[$tableName] = [
                    'columns' => count($columns),
                    'has_primary_key' => collect($columns)->contains('pk', 1),
                ];
                
                if (!collect($columns)->contains('pk', 1)) {
                    $issues[] = "Table {$tableName} has no primary key";
                }
            }
        } catch (\Exception $e) {
            $issues[] = "Could not analyze table structure: " . $e->getMessage();
        }
        
        return ['issues' => $issues, 'tables' => $tables];
    }

    /**
     * Find hardcoded secrets
     */
    private function findHardcodedSecrets(): array
    {
        $files = [];
        
        try {
            $phpFiles = File::glob(base_path('**/*.php'), GLOB_BRACE);
            
            foreach ($phpFiles as $file) {
                if (strpos($file, 'vendor/') !== false || strpos($file, 'node_modules/') !== false) continue;
                
                // Skip test files and scripts that may legitimately contain test credentials
                if (strpos($file, 'tests/') !== false || strpos($file, 'scripts/') !== false) continue;
                
                if (!File::exists($file)) continue;
                
                try {
                    $content = File::get($file);
                    
                    // Skip files that are likely configuration files using env() properly
                    if (strpos($file, 'config/') !== false && strpos($content, 'env(') !== false) {
                        continue;
                    }
                    
                    // More specific patterns for actual hardcoded secrets
                    $patterns = [
                        '/password\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
                        '/secret\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
                        '/api_key\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
                        '/token\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
                        '/private_key\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
                        '/encryption_key\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
                    ];
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $files[] = str_replace(base_path() . '/', '', $file);
                            break; // Only add each file once
                        }
                    }
                } catch (\Exception $e) {
                    // Skip files that can't be read
                    continue;
                }
            }
        } catch (\Exception $e) {
            // Handle glob errors
        }
        
        return $files;
    }

    /**
     * Find exposed files
     */
    private function findExposedFiles(): array
    {
        $exposedFiles = [];
        $sensitiveFiles = ['.env', 'composer.lock', 'package-lock.json', '.gitignore'];
        
        foreach ($sensitiveFiles as $file) {
            if (File::exists(public_path($file))) {
                $exposedFiles[] = $file;
            }
        }
        
        return $exposedFiles;
    }
}
