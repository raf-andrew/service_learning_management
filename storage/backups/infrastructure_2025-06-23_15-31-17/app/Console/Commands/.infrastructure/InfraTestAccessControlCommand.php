<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InfraTestAccessControlCommand extends Command
{
    protected $signature = 'infra:test-access-control';
    protected $description = 'Check for access control policy files and output a Markdown report.';

    public function handle()
    {
        $repoRoot = base_path();
        $reportPath = base_path('.web3/reports/infrastructure_access_control.md');
        
        // Ensure the reports directory exists
        File::makeDirectory(dirname($reportPath), 0755, true, true);
        
        $roleFiles = collect(File::allFiles($repoRoot))
            ->filter(function ($file) {
                return in_array($file->getFilename(), ['roles.json','permissions.json','policy.php','Gate.php']);
            });
        $report = "# Access Control Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n";
        if ($roleFiles->count() > 0) {
            $report .= "- Policy files: Found (" . $roleFiles->implode('getRelativePathname', ', ') . ")\n- Permissions: Review required\n";
        } else {
            $report .= "- Policy files: Not found\n- Permissions: Manual review required\n";
        }
        File::put($reportPath, $report);
        $this->info('Access control test complete. Report written to ' . $reportPath);
        return 0;
    }
}
