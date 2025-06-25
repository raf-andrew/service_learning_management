<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use PHP_CodeSniffer\CLI;
use App\Models\SniffingResult;

class SniffCommand extends Command
{
    protected $signature = 'sniff:run
                                {--report=xml : Report format (xml, full, summary)}
                                {--file= : Specific file to sniff}
                                {--fix : Automatically fix sniff violations}';

    protected $description = 'Run PHP CodeSniffer with Laravel standards';

    public function handle()
    {
        $reportFormat = $this->option('report');
        $file = $this->option('file');
        $fix = $this->option('fix');

        // Initialize CLI
        $cli = new CLI();
        $cli->setCommandLineValues([
            '--standard' => base_path('phpcs.xml'),
            '--report' => $reportFormat,
            '--encoding' => 'utf-8',
        ]);

        // Add additional options
        $options = [];
        if ($file) {
            $options['--files'] = $file;
        }
        if ($fix) {
            $options['--fix'] = true;
        }
        
        // Merge options
        $cli->setCommandLineValues(array_merge([
            '--standard' => base_path('phpcs.xml'),
            '--report' => $reportFormat,
            '--encoding' => 'utf-8',
        ], $options));

        // Run the sniffer
        $result = $cli->process();

        // Store results
        $this->storeResults($result, $reportFormat, $file, $fix);

        return $result === 0 ? 0 : 1;
    }

    private function storeResults($result, $reportFormat, $file, $fix)
    {
        // Convert PHP_CodeSniffer result to JSON
        $resultData = json_encode($result);
        
        // Extract error and warning counts
        $errorCount = 0;
        $warningCount = 0;
        
        if (isset($result['totals'])) {
            $errorCount = $result['totals']['errors'];
            $warningCount = $result['totals']['warnings'];
        }

        SniffingResult::create([
            'result_data' => $resultData,
            'report_format' => $reportFormat,
            'file_path' => $file ?? 'all files',
            'fix_applied' => $fix,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
        ]);
    }
}
