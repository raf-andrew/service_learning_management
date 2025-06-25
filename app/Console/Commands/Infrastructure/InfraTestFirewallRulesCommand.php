<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InfraTestFirewallRulesCommand extends Command
{
    protected $signature = 'infra:test-firewall-rules';
    protected $description = 'Check for firewall rules (manual review required for most PHP environments) and output a Markdown report.';

    public function handle()
    {
        $reportPath = base_path('.web3/reports/infrastructure_firewall_rules.md');
        
        // Ensure the reports directory exists
        File::makeDirectory(dirname($reportPath), 0755, true, true);
        
        $report = "# Firewall Rules Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n- Rules found: Manual review required\n- Open ports: Manual review required\n";
        File::put($reportPath, $report);
        $this->info('Firewall rules test (manual review) complete. Report written to ' . $reportPath);
        return 0;
    }
}
