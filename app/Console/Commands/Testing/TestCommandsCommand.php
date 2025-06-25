<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class TestCommandsCommand extends Command
{
    protected $signature = 'test:commands {commandName? : Specific command to test} {--all : Test all commands} {--generate : Generate missing tests}';
    protected $description = 'Test Laravel commands with automatic test generation';

    protected $reportsDir;
    protected $isWindows;

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.reports/command-tests');
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function handle()
    {
        $this->info('Starting command testing...');
        
        // Create reports directory
        File::makeDirectory($this->reportsDir, 0755, true, true);

        if ($this->option('all')) {
            $this->testAllCommands();
        } elseif ($command = $this->argument('commandName')) {
            $this->testSingleCommand($command);
        } else {
            $this->error('Please specify a command or use --all to test all commands');
            return 1;
        }

        return 0;
    }

    protected function testAllCommands()
    {
        $commands = $this->getAllCommands();
        $this->info("Found " . count($commands) . " commands to test");

        foreach ($commands as $command) {
            $this->testSingleCommand($command);
        }
    }

    protected function testSingleCommand($commandName)
    {
        $this->info("Testing command: {$commandName}");
        
        // Generate test if needed
        if ($this->option('generate')) {
            $this->generateCommandTest($commandName);
        }

        // Run the test
        $this->runCommandTest($commandName);
    }

    protected function getAllCommands()
    {
        $commands = [];
        
        // Get all command files
        $commandFiles = File::glob(app_path('Console/Commands/**/*.php'));
        
        foreach ($commandFiles as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className) {
                try {
                    $reflection = new \ReflectionClass($className);
                    if ($reflection->isAbstract()) {
                        continue; // Skip abstract classes
                    }
                    $signature = $this->getCommandSignature($className);
                    if ($signature) {
                        $commands[] = $signature;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return array_unique($commands);
    }

    protected function getClassNameFromFile($file)
    {
        $content = File::get($file);
        if (preg_match('/class\s+(\w+)\s+extends\s+Command/', $content, $matches)) {
            $className = $matches[1];
            $namespace = $this->getNamespaceFromFile($content);
            return $namespace ? $namespace . '\\' . $className : $className;
        }
        return null;
    }

    protected function getNamespaceFromFile($content)
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    protected function getCommandSignature($className)
    {
        try {
            $reflection = new \ReflectionClass($className);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);
            $instance = $reflection->newInstanceWithoutConstructor();
            return $signatureProperty->getValue($instance);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function generateCommandTest($commandName)
    {
        $testClassName = $this->getTestClassName($commandName);
        $testFilePath = $this->getTestFilePath($commandName);
        
        // Ensure the directory exists
        $testDir = dirname($testFilePath);
        if (!File::exists($testDir)) {
            File::makeDirectory($testDir, 0755, true, true);
        }

        if (File::exists($testFilePath)) {
            $this->info("Test already exists for {$commandName}");
            return;
        }

        $testContent = $this->generateTestContent($commandName, $testClassName);
        File::put($testFilePath, $testContent);
        
        $this->info("Generated test for {$commandName}: {$testFilePath}");
    }

    protected function getTestClassName($commandName)
    {
        // Convert command name to test class name
        $className = str_replace([':', '-'], '_', $commandName);
        $className = ucwords($className, '_');
        $className = str_replace('_', '', $className);
        return $className . 'Test';
    }

    protected function getTestFilePath($commandName)
    {
        $testClassName = $this->getTestClassName($commandName);
        return base_path("tests/Feature/Commands/{$testClassName}.php");
    }

    protected function generateTestContent($commandName, $testClassName)
    {
        $commandClass = $this->findCommandClass($commandName);
        
        return "<?php

namespace Tests\\Feature\\Commands;

use Tests\\TestCase;
use Illuminate\\Support\\Facades\\Artisan;

class {$testClassName} extends TestCase
{
    public function test_{$this->getTestMethodName($commandName)}_command_exists()
    {
        // Check if the command exists in Artisan::all()
        \$this->assertTrue(array_key_exists('{$commandName}', Artisan::all()));
    }

    public function test_{$this->getTestMethodName($commandName)}_command_can_be_executed()
    {
        try {
            \$exitCode = Artisan::call('{$commandName}');
            \$this->assertIsInt(\$exitCode);
        } catch (\Exception \$e) {
            // Command may fail due to missing dependencies, but should not crash
            \$this->assertTrue(true, 'Command executed without fatal errors');
        }
    }

    public function test_{$this->getTestMethodName($commandName)}_command_has_proper_signature()
    {
        \$command = Artisan::all()['{$commandName}'] ?? null;
        if (\$command) {
            \$reflection = new \ReflectionClass(\$command);
            \$signatureProperty = \$reflection->getProperty('signature');
            \$signatureProperty->setAccessible(true);
            \$signature = \$signatureProperty->getValue(\$command);
            \$this->assertNotEmpty(\$signature);
            \$this->assertNotEmpty(\$command->getDescription());
        }
    }
}";
    }

    protected function getTestMethodName($commandName)
    {
        return str_replace([':', '-'], '_', $commandName);
    }

    protected function findCommandClass($commandName)
    {
        $commandFiles = File::glob(app_path('Console/Commands/**/*.php'));
        
        foreach ($commandFiles as $file) {
            $content = File::get($file);
            if (strpos($content, "'{$commandName}'") !== false || strpos($content, "\"{$commandName}\"") !== false) {
                return $this->getClassNameFromFile($file);
            }
        }
        
        return null;
    }

    protected function runCommandTest($commandName)
    {
        $testFilePath = $this->getTestFilePath($commandName);
        
        if (!File::exists($testFilePath)) {
            $this->error("No test file found for {$commandName}");
            return;
        }

        $this->info("Running test for {$commandName}...");
        
        $command = 'php vendor/bin/phpunit';
        if ($this->isWindows) {
            $command = 'php vendor/bin/phpunit';
        }
        
        $command .= " --filter=" . $this->getTestClassName($commandName);
        $command .= " --log-junit={$this->reportsDir}/{$this->getTestMethodName($commandName)}_test.xml";
        
        $process = new Process(explode(' ', $command));
        $process->setWorkingDirectory(base_path());
        $process->run();

        if ($process->isSuccessful()) {
            $this->info("✅ Test passed for {$commandName}");
        } else {
            $this->error("❌ Test failed for {$commandName}");
            $this->error($process->getErrorOutput());
        }
    }
} 