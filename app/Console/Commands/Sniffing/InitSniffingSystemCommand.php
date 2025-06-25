<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InitSniffingSystemCommand extends Command
{
    protected $signature = 'sniffing:init {--force : Force initialization even if directory exists}';
    protected $description = 'Initialize the sniffing system with all necessary components';

    public function handle()
    {
        $this->info('Initializing sniffing system...');

        // Create directory structure
        $this->createDirectoryStructure();

        // Create configuration files
        $this->createConfigurationFiles();

        // Create custom rules
        $this->createCustomRules();

        // Create report templates
        $this->createReportTemplates();

        // Run database migrations
        $this->runMigrations();

        $this->info('Sniffing system initialized successfully!');
        return 0;
    }

    private function createDirectoryStructure()
    {
        $directories = [
            '.sniffing',
            '.sniffing/rules',
            '.sniffing/rules/ServiceLearning',
            '.sniffing/reports',
            '.sniffing/reports/templates',
            '.sniffing/reports/templates/html',
            '.sniffing/reports/templates/markdown',
            '.sniffing/reports/templates/json',
            '.sniffing/reports/templates/text',
            '.sniffing/cache',
            '.sniffing/logs',
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("Created directory: {$directory}");
            } else {
                $this->warn("Directory already exists: {$directory}");
            }
        }
    }

    private function createConfigurationFiles()
    {
        // Create phpcs.xml
        $phpcsXml = base_path('.sniffing/phpcs.xml');
        if (!File::exists($phpcsXml) || $this->option('force')) {
            File::copy(__DIR__ . '/../../../.sniffing/phpcs.xml', $phpcsXml);
            $this->info('Created phpcs.xml configuration');
        }

        // Create .gitignore
        $gitignore = base_path('.sniffing/.gitignore');
        if (!File::exists($gitignore) || $this->option('force')) {
            File::put($gitignore, "cache/*\nlogs/*\nreports/*\n!reports/.gitkeep\n");
            $this->info('Created .gitignore file');
        }

        // Create .gitkeep files
        $gitkeepDirs = [
            '.sniffing/cache',
            '.sniffing/logs',
            '.sniffing/reports',
        ];

        foreach ($gitkeepDirs as $dir) {
            $gitkeep = base_path("{$dir}/.gitkeep");
            if (!File::exists($gitkeep)) {
                File::put($gitkeep, '');
                $this->info("Created .gitkeep in {$dir}");
            }
        }
    }

    private function createCustomRules()
    {
        $rules = [
            'ServiceLearningStandard.php' => base_path('.sniffing/rules/ServiceLearning/ServiceLearningStandard.php'),
        ];

        foreach ($rules as $rule => $path) {
            if (!File::exists($path) || $this->option('force')) {
                File::copy(__DIR__ . "/../../../.sniffing/rules/ServiceLearning/{$rule}", $path);
                $this->info("Created custom rule: {$rule}");
            }
        }
    }

    private function createReportTemplates()
    {
        $templates = [
            'html.blade.php' => base_path('.sniffing/reports/templates/html/report.blade.php'),
            'markdown.blade.php' => base_path('.sniffing/reports/templates/markdown/report.blade.php'),
        ];

        foreach ($templates as $template => $path) {
            if (!File::exists($path) || $this->option('force')) {
                File::copy(__DIR__ . "/../../../.sniffing/reports/templates/{$template}", $path);
                $this->info("Created report template: {$template}");
            }
        }
    }

    private function runMigrations()
    {
        if ($this->option('force')) {
            $this->info('Running migrations...');
            Artisan::call('migrate:fresh', [
                '--path' => 'database/migrations/sniffing',
                '--force' => true,
            ]);
            $this->info('Migrations completed successfully');
        }
    }
} 