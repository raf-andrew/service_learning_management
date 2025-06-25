<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InfraTestMonitoringCommand extends Command
{
    protected $signature = 'infra:test-monitoring';
    protected $description = 'Check for monitoring tool configs and output a Markdown report.';

    public function handle()
    {
        $repoRoot = base_path();
        $reportPath = base_path('.web3/reports/infrastructure_monitoring.md');
        
        // Ensure the reports directory exists
        File::makeDirectory(dirname($reportPath), 0755, true, true);
        
        $monitorFiles = collect(File::allFiles($repoRoot))
            ->filter(function ($file) {
                return in_array($file->getFilename(), ['prometheus.yml','grafana.ini','monitoring.js','monitoring.php','monitoring.json']);
            });
        $report = "# Monitoring Setup Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n";
        if ($monitorFiles->count() > 0) {
            $report .= "- Monitoring tools: Found (" . $monitorFiles->implode('getRelativePathname', ', ') . ")\n";
        } else {
            $report .= "- Monitoring tools: Not found\n";
        }
        File::put($reportPath, $report);
        $this->info('Monitoring setup test complete. Report written to ' . $reportPath);
        return 0;
    }
}
