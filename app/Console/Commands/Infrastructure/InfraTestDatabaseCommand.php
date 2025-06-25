<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InfraTestDatabaseCommand extends Command
{
    protected $signature = 'infra:test-database';
    protected $description = 'Test database connection and output a Markdown report.';

    public function handle()
    {
        $reportPath = base_path('.web3/reports/infrastructure_database.md');
        $status = 'Failed';
        $message = '';
        try {
            DB::connection()->getPdo();
            $status = 'Passed';
            $message = 'Connection successful.';
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        $report = "# Database Setup Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n- Connection: $status\n- Message: $message\n";
        File::put($reportPath, $report);
        $this->info('Database connection test complete. Report written to ' . $reportPath);
        return 0;
    }
}
