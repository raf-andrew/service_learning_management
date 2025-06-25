<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InfraTestKeyManagementCommand extends Command
{
    protected $signature = 'infra:test-key-management';
    protected $description = 'Scan for secrets in code and check key management best practices, outputting a Markdown report.';

    protected $secretPatterns = [
        'PRIVATE_KEY', 'SECRET', 'MNEMONIC', 'API_KEY', 'PASSWORD', 'ENCRYPTION_KEY'
    ];

    public function handle()
    {
        $repoRoot = base_path();
        $reportPath = base_path('.web3/reports/infrastructure_key_management.md');
        $foundSecrets = [];
        foreach (File::allFiles($repoRoot) as $file) {
            $ext = $file->getExtension();
            if (!in_array($ext, ['js','php','env','json','ts','py'])) continue;
            $content = File::get($file->getRealPath());
            foreach ($this->secretPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $foundSecrets[] = $file->getRelativePathname() . ": $pattern";
                }
            }
        }
        $envFiles = File::glob($repoRoot . '/.env*');
        $vaultUsage = count($envFiles) > 0;
        $report = "# Key Management Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n";
        if (count($foundSecrets) === 0 && $vaultUsage) {
            $report .= "- No secrets in code: Passed\n- Secure storage: Passed\n";
        } else {
            if (count($foundSecrets) > 0) {
                $report .= "- No secrets in code: Failed (found: " . implode(', ', $foundSecrets) . ")\n";
            } else {
                $report .= "- No secrets in code: Passed\n";
            }
            $report .= $vaultUsage ? "- Secure storage: Passed\n" : "- Secure storage: Failed (no .env or vault found)\n";
        }
        File::put($reportPath, $report);
        $this->info('Key management test complete. Report written to ' . $reportPath);
        return 0;
    }
}
