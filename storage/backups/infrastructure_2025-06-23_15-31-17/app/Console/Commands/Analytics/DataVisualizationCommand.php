<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class DataVisualizationCommand extends Command
{
    protected $signature = 'analytics:visualize
                          {type : Type of visualization (dashboard|chart|report)}
                          {--data=all : Data type to visualize (all|contract|transaction|event|metric)}
                          {--period=7d : Time period (1d|7d|30d|90d|1y|all)}
                          {--format=html : Output format (html|pdf|json)}
                          {--theme=light : Theme (light|dark)}
                          {--interactive : Enable interactive mode}';

    protected $description = 'Generate visualizations from collected analytics data';

    protected $web3Path;
    protected $analyticsPath;
    protected $visualizationsPath;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
        $this->visualizationsPath = base_path('storage/analytics/visualizations');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create visualizations directory if it doesn't exist
        if (!File::exists($this->visualizationsPath)) {
            File::makeDirectory($this->visualizationsPath, 0755, true);
        }

        $type = $this->argument('type');
        $dataType = $this->option('data');
        $period = $this->option('period');
        $format = $this->option('format');
        $theme = $this->option('theme');
        $interactive = $this->option('interactive');

        $this->info('Generating analytics visualization...');
        $this->info("Type: {$type}");
        $this->info("Data: {$dataType}");
        $this->info("Period: {$period}");
        $this->info("Format: {$format}");
        $this->info("Theme: {$theme}");

        switch ($type) {
            case 'dashboard':
                return $this->generateDashboard($dataType, $period, $format, $theme, $interactive);
            case 'chart':
                return $this->generateChart($dataType, $period, $format, $theme, $interactive);
            case 'report':
                return $this->generateReport($dataType, $period, $format, $theme);
            default:
                $this->error('Invalid visualization type');
                return 1;
        }
    }

    protected function generateDashboard($dataType, $period, $format, $theme, $interactive)
    {
        $this->info('Generating analytics dashboard...');

        $outputPath = "{$this->visualizationsPath}/dashboard";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute dashboard generation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-dashboard.js " .
                  "--data {$dataType} " .
                  "--period {$period} " .
                  "--format {$format} " .
                  "--theme {$theme} " .
                  "--output {$outputPath}";

        if ($interactive) {
            $command .= ' --interactive';
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to generate dashboard');
            return 1;
        }

        $this->info('Dashboard generated successfully');
        return 0;
    }

    protected function generateChart($dataType, $period, $format, $theme, $interactive)
    {
        $this->info('Generating analytics chart...');

        $outputPath = "{$this->visualizationsPath}/charts";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute chart generation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-chart.js " .
                  "--data {$dataType} " .
                  "--period {$period} " .
                  "--format {$format} " .
                  "--theme {$theme} " .
                  "--output {$outputPath}";

        if ($interactive) {
            $command .= ' --interactive';
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to generate chart');
            return 1;
        }

        $this->info('Chart generated successfully');
        return 0;
    }

    protected function generateReport($dataType, $period, $format, $theme)
    {
        $this->info('Generating analytics report...');

        $outputPath = "{$this->visualizationsPath}/reports";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute report generation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-report.js " .
                  "--data {$dataType} " .
                  "--period {$period} " .
                  "--format {$format} " .
                  "--theme {$theme} " .
                  "--output {$outputPath}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to generate report');
            return 1;
        }

        $this->info('Report generated successfully');
        return 0;
    }
} 