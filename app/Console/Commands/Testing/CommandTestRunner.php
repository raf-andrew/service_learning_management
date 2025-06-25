<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

class CommandTestRunner extends Command
{
    protected $signature = 'test:command 
        {command-name : The command to test}
        {--type=feature : Test type (unit, feature)}
        {--coverage : Generate coverage report}
        {--fix : Auto-fix common issues}';

    protected $description = 'Test a specific Laravel command and generate detailed reports';

    protected $reportsDir;
    protected $isWindows;

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.reports/commands');
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function handle()
    {
        $commandName = $this->argument('command-name');
        $testType = $this->option('type');
        $coverage = $this->option('coverage');
        $fix = $this->option('fix');

        $this->info("🔍 Testing command: {$commandName}");
        $this->info("Test type: {$testType}");
        
        $this->createDirectories();

        // Step 1: Check if command exists
        if (!$this->commandExists($commandName)) {
            $this->error("Command '{$commandName}' not found!");
            return 1;
        }

        // Step 2: Check if test exists
        $testFile = $this->findTestFile($commandName, $testType);
        if (!$testFile) {
            $this->warn("No test file found for {$commandName}");
            if ($fix) {
                $testFile = $this->createTestFile($commandName, $testType);
            } else {
                $this->error("Test file not found. Use --fix to create one.");
                return 1;
            }
        }
        $this->info('Using test file: ' . $testFile);

        // Step 3: Run the test
        $result = $this->runTest($testFile, $commandName, $coverage);

        // Step 4: Generate report
        $this->generateReport($commandName, $result, $testType);

        return $result['exit_code'];
    }

    protected function commandExists($commandName)
    {
        try {
            $commands = Artisan::all();
            return array_key_exists($commandName, $commands);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function findTestFile($commandName, $testType)
    {
        $safeName = preg_replace('/[^A-Za-z0-9_]/', '_', $commandName);
        $testPath = "tests/{$testType}/Commands/{$safeName}Test.php";
        return File::exists($testPath) ? $testPath : null;
    }

    protected function createTestFile($commandName, $testType)
    {
        $this->info("Creating test file for {$commandName}...");
        $safeName = preg_replace('/[^A-Za-z0-9_]/', '_', $commandName);
        $testPath = "tests/{$testType}/Commands/{$safeName}Test.php";
        $commandClass = $this->findCommandClass($commandName);
        
        if (!$commandClass) {
            $this->error("Could not find command class for {$commandName}");
            return null;
        }

        $testContent = $this->generateTestContent($commandName, $commandClass, $testType, $safeName);
        File::put($testPath, $testContent);
        
        $this->info("Created test file: {$testPath}");
        return $testPath;
    }

    protected function findCommandClass($commandName)
    {
        $commandsPath = app_path('Console/Commands');
        $files = File::allFiles($commandsPath);
        
        foreach ($files as $file) {
            $className = 'App\\Console\\Commands\\' . str_replace('/', '\\', $file->getRelativePathname());
            $className = str_replace('.php', '', $className);
            
            if (class_exists($className)) {
                try {
                    $reflection = new \ReflectionClass($className);
                    if ($reflection->isAbstract()) {
                        continue;
                    }
                    $constructor = $reflection->getConstructor();
                    if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                        continue;
                    }
                    if ($reflection->isSubclassOf(\Illuminate\Console\Command::class)) {
                        $instance = new $className();
                        if ($instance->getName() === $commandName) {
                            return $className;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        
        return null;
    }

    protected function generateTestContent($commandName, $commandClass, $testType, $safeName = null)
    {
        $className = class_basename($commandClass);
        $testClassName = ($safeName ? ucfirst($safeName) : $className) . "Test";
        $namespace = "Tests\\" . ucfirst($testType) . "\\Commands";
        
        return "<?php

namespace {$namespace};

use Tests\\" . ucfirst($testType) . "\\TestCase;
use {$commandClass};

class {$testClassName} extends TestCase
{
    public function test_command_can_be_executed()
    {
        \$this->artisan('{$commandName}')
            ->assertExitCode(0);
    }

    public function test_command_has_help_option()
    {
        \$this->artisan('{$commandName}', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_command_handles_invalid_options()
    {
        \$this->artisan('{$commandName}', ['--invalid-option' => true])
            ->assertExitCode(1);
    }

    public function test_command_produces_expected_output()
    {
        \$this->artisan('{$commandName}')
            ->expectsOutput('')
            ->assertExitCode(0);
    }
}";
    }

    protected function runTest($testFile, $commandName, $coverage)
    {
        $this->info("Running test: {$testFile}");
        
        $command = "php vendor/bin/phpunit {$testFile}";
        if ($coverage) {
            $command .= " --coverage-html=.reports/coverage/{$commandName}";
        }
        $command .= " --log-junit=.reports/commands/{$commandName}_test.xml";
        
        $process = new Process(explode(' ', $command));
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(120);
        
        $process->run();
        
        $result = [
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
            'success' => $process->isSuccessful()
        ];
        
        if ($result['success']) {
            $this->info("✅ Test passed for {$commandName}");
        } else {
            $this->error("❌ Test failed for {$commandName}");
            $this->error($result['error_output']);
        }
        
        return $result;
    }

    protected function generateReport($commandName, $result, $testType)
    {
        $report = [
            'command' => $commandName,
            'test_type' => $testType,
            'timestamp' => now()->toISOString(),
            'success' => $result['success'],
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
            'errors' => $result['error_output'],
            'recommendations' => $this->generateRecommendations($result)
        ];
        
        $reportPath = $this->reportsDir . "/{$commandName}_report.json";
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("Report saved to: {$reportPath}");
    }

    protected function generateRecommendations($result)
    {
        $recommendations = [];
        
        if (!$result['success']) {
            $errorOutput = $result['error_output'];
            
            if (strpos($errorOutput, 'database') !== false) {
                $recommendations[] = "Fix database connection issues";
            }
            
            if (strpos($errorOutput, 'class not found') !== false) {
                $recommendations[] = "Check class imports and namespaces";
            }
            
            if (strpos($errorOutput, 'method not found') !== false) {
                $recommendations[] = "Verify method exists in the class";
            }
            
            if (strpos($errorOutput, 'syntax error') !== false) {
                $recommendations[] = "Fix PHP syntax errors";
            }
        } else {
            $recommendations[] = "Test passed successfully";
        }
        
        return $recommendations;
    }

    protected function createDirectories()
    {
        File::makeDirectory($this->reportsDir, 0755, true, true);
        File::makeDirectory(base_path('.reports/coverage'), 0755, true, true);
    }
} 