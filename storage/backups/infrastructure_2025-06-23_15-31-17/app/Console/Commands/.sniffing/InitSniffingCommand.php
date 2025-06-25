<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InitSniffingCommand extends Command
{
    protected $signature = 'sniffing:init
                            {--force : Force initialization even if directory exists}';

    protected $description = 'Initialize the sniffing system with default configuration and directories';

    public function handle()
    {
        if (!$this->option('force') && File::exists(base_path('.sniffing'))) {
            $this->error('.sniffing directory already exists. Use --force to overwrite.');
            return 1;
        }

        $this->createDirectoryStructure();
        $this->createDefaultConfigs();
        $this->createDefaultRules();
        $this->createReportTemplates();

        $this->info('Sniffing system initialized successfully.');
        return 0;
    }

    private function createDirectoryStructure()
    {
        $directories = [
            '.sniffing/config/standards',
            '.sniffing/reports/templates',
            '.sniffing/reports/history',
            '.sniffing/rules/security',
            '.sniffing/rules/performance',
            '.sniffing/rules/quality',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory(base_path($directory), 0755, true);
        }
    }

    private function createDefaultConfigs()
    {
        // Create Laravel standards config
        $laravelStandards = <<<XML
<?xml version="1.0"?>
<ruleset name="Laravel Standards">
    <description>Laravel coding standards for PHP_CodeSniffer</description>
    <rule ref="PSR12"/>
    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="lineLimit" value="120"/>
            <property name="absoluteLineLimit" value="150"/>
        </properties>
    </rule>
</ruleset>
XML;
        File::put(base_path('.sniffing/config/standards/laravel.xml'), $laravelStandards);

        // Create security standards config
        $securityStandards = <<<XML
<?xml version="1.0"?>
<ruleset name="Security Standards">
    <description>Security-focused coding standards</description>
    <rule ref="Generic.PHP.DeprecatedFunctions"/>
    <rule ref="Generic.PHP.ForbiddenFunctions"/>
    <rule ref="Squiz.PHP.NonExecutableCode"/>
    <rule ref="Generic.CodeAnalysis.UnusedFunctionParameter"/>
</ruleset>
XML;
        File::put(base_path('.sniffing/config/standards/security.xml'), $securityStandards);

        // Create sniffing config
        $config = <<<PHP
<?php

return [
    'standards' => [
        'laravel' => '.sniffing/config/standards/laravel.xml',
        'security' => '.sniffing/config/standards/security.xml',
    ],
    'report_formats' => ['xml', 'html', 'markdown', 'json'],
    'default_report_format' => 'html',
    'report_history_limit' => 100,
    'auto_fix' => false,
    'exclude_paths' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
    ],
];
PHP;
        File::put(base_path('.sniffing/config/sniffing.php'), $config);
    }

    private function createDefaultRules()
    {
        // Create security rules
        $securityRules = <<<PHP
<?php

namespace App\Rules\Security;

class SecurityRule
{
    public function check($file)
    {
        // Implement security checks
        return [];
    }
}
PHP;
        File::put(base_path('.sniffing/rules/security/SecurityRule.php'), $securityRules);

        // Create performance rules
        $performanceRules = <<<PHP
<?php

namespace App\Rules\Performance;

class PerformanceRule
{
    public function check($file)
    {
        // Implement performance checks
        return [];
    }
}
PHP;
        File::put(base_path('.sniffing/rules/performance/PerformanceRule.php'), $performanceRules);

        // Create quality rules
        $qualityRules = <<<PHP
<?php

namespace App\Rules\Quality;

class QualityRule
{
    public function check($file)
    {
        // Implement quality checks
        return [];
    }
}
PHP;
        File::put(base_path('.sniffing/rules/quality/QualityRule.php'), $qualityRules);
    }

    private function createReportTemplates()
    {
        // Create HTML template
        $htmlTemplate = <<<PHP
<?php

return function($results) {
    return view('sniffing.reports.html', ['results' => $results])->render();
};
PHP;
        File::put(base_path('.sniffing/reports/templates/html.php'), $htmlTemplate);

        // Create Markdown template
        $markdownTemplate = <<<PHP
<?php

return function($results) {
    $output = "# Sniffing Report\n\n";
    foreach ($results as $result) {
        $output .= "## {$result->file_path}\n\n";
        $output .= "- Errors: {$result->error_count}\n";
        $output .= "- Warnings: {$result->warning_count}\n\n";
    }
    return $output;
};
PHP;
        File::put(base_path('.sniffing/reports/templates/markdown.php'), $markdownTemplate);

        // Create JSON template
        $jsonTemplate = <<<PHP
<?php

return function($results) {
    return json_encode($results, JSON_PRETTY_PRINT);
};
PHP;
        File::put(base_path('.sniffing/reports/templates/json.php'), $jsonTemplate);
    }
} 