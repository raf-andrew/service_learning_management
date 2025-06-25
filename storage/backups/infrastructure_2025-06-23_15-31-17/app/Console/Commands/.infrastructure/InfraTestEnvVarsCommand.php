<?php

namespace App\Console\Commands\.infrastructure;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InfraTestEnvVarsCommand extends Command
{
    protected $signature = 'infra:test-env-vars';
    protected $description = 'Validate required environment variables across environments and output a Markdown report.';

    // Update this list with all required environment variables
    protected $requiredVars = [
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        // Add more as needed
    ];

    protected $envFiles = ['.env', '.env.staging', '.env.production'];

    public function handle()
    {
        $results = [];
        $basePath = base_path();
        foreach ($this->envFiles as $file) {
            $path = $basePath . DIRECTORY_SEPARATOR . $file;
            if (!File::exists($path)) {
                $results[$file] = 'File not found';
                continue;
            }
            $content = File::get($path);
            $vars = [];
            foreach (explode("\n", $content) as $line) {
                if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                    [$key, $value] = explode('=', $line, 2);
                    $vars[trim($key)] = trim($value);
                }
            }
            $missing = [];
            foreach ($this->requiredVars as $req) {
                if (!isset($vars[$req]) || $vars[$req] === '') {
                    $missing[] = $req;
                }
            }
            $results[$file] = count($missing) === 0 ? 'Passed' : ('Missing: ' . implode(', ', $missing));
        }
        $reportPath = base_path('.web3/reports/infrastructure_env_vars.md');
        $report = "# Environment Variables Test Report\n\n_Last Run: " . now()->format('Y-m-d H:i:s') . "_\n\n## Results\n";
        foreach ($results as $file => $result) {
            $report .= "- $file: $result\n";
        }
        File::put($reportPath, $report);
        $this->info('Environment variable validation complete. Report written to ' . $reportPath);
        return 0;
    }
}
