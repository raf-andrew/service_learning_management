<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use PHP_CodeSniffer\CLI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CodeSnifferCommand extends Command
{
    protected $signature = 'sniff:run {--report=xml : Report format (xml, full, summary)}
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

        // Add file to sniff if specified
        if ($file) {
            $cli->setCommandLineValues([
                '--files' => $file,
            ]);
        }

        // Add fix option if specified
        if ($fix) {
            $cli->setCommandLineValues([
                '--fix' => true,
            ]);
        }

        // Run the sniffer
        $result = $cli->process();

        // Store results in database
        $this->storeSniffResults($result, $reportFormat);

        return $result === 0 ? 0 : 1;
    }

    private function storeSniffResults($result, $reportFormat)
    {
        // Create database connection if not exists
        if (!File::exists(storage_path('database/sniffing.sqlite'))) {
            File::put(storage_path('database/sniffing.sqlite'), '');
        }

        // Connect to SQLite database
        DB::connection('sqlite')->statement('CREATE TABLE IF NOT EXISTS sniff_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            result TEXT,
            report_format VARCHAR(20),
            file_path TEXT,
            fix_applied BOOLEAN DEFAULT FALSE
        )');

        // Store the results
        DB::connection('sqlite')->table('sniff_results')->insert([
            'result' => json_encode($result),
            'report_format' => $reportFormat,
            'file_path' => $this->option('file') ?? 'all files',
            'fix_applied' => $this->option('fix') ?? false,
        ]);
    }
}
