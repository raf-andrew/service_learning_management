<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class InfraTestCacheCommand extends Command
{
    protected $signature = 'infra:test-cache';
    protected $description = 'Test cache (Redis) connection and output a Markdown report.';

    public function handle()
    {
        $reportPath = base_path('.web3/reports/infrastructure_cache.md');
        $status = 'Failed';
        $message = '';
        try {
            Cache::put('infra_test_key', 'ok', 10);
            $value = Cache::get('infra_test_key');
            if ($value === 'ok') {
                $status = 'Passed';
                $message = 'Cache connection and write successful.';
            } else {
                $message = 'Cache write/read failed.';
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        $report = "# Cache Setup Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n- Connection: $status\n- Message: $message\n";
        File::put($reportPath, $report);
        $this->info('Cache connection test complete. Report written to ' . $reportPath);
        return 0;
    }
}
