<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use App\Services\SniffingService;
use Illuminate\Support\Facades\File;

class TestSniffingCommand extends Command
{
    protected $signature = 'sniffing:test {--file= : Specific file to test} {--format=json : Output format (json/html)}';
    protected $description = 'Test the sniffing system with sample code';

    public function handle()
    {
        $this->info('Starting sniffing system test...');

        // Get test file
        $testFile = $this->option('file') ?? 'app/Services/TestService.php';
        if (!File::exists($testFile)) {
            $this->error("Test file not found: {$testFile}");
            return 1;
        }

        // Run sniffing analysis
        $sniffingService = app(SniffingService::class);
        $results = $sniffingService->analyzeFile($testFile);

        // Display results
        $this->info("\nSniffing Results:");
        $this->info("Total violations: " . count($results['violations']));
        
        foreach ($results['violations'] as $violation) {
            $this->warn("\nViolation found:");
            $this->line("Line: {$violation['line']}");
            $this->line("Type: {$violation['type']}");
            $this->line("Message: {$violation['message']}");
        }

        // Generate report
        $format = $this->option('format');
        $reportPath = $sniffingService->generateReport($results, $format);
        
        $this->info("\nReport generated at: {$reportPath}");

        return 0;
    }
} 