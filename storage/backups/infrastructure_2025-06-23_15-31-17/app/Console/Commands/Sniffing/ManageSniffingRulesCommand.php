<?php

namespace App\Console\Commands\Sniffing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageSniffingRulesCommand extends Command
{
    protected $signature = 'sniffing:rules
                            {action : Action to perform (list, add, remove, update)}
                            {--type= : Rule type (security, performance, quality, architecture, documentation, testing)}
                            {--name= : Rule name}
                            {--description= : Rule description}
                            {--code= : Rule code}
                            {--severity= : Rule severity (error, warning, info)}';

    protected $description = 'Manage sniffing rules';

    public function handle()
    {
        $action = $this->argument('action');

        return match($action) {
            'list' => $this->listRules(),
            'add' => $this->addRule(),
            'remove' => $this->removeRule(),
            'update' => $this->updateRule(),
            default => $this->error('Invalid action specified'),
        };
    }

    private function listRules()
    {
        $rulesPath = base_path('.sniffing/rules/ServiceLearning');
        $files = File::files($rulesPath);

        if (empty($files)) {
            $this->info('No rules found.');
            return 0;
        }

        $this->info('Available Rules:');
        $this->info('----------------');

        foreach ($files as $file) {
            $content = File::get($file);
            preg_match('/class\s+(\w+)/', $content, $matches);
            $className = $matches[1] ?? 'Unknown';
            
            $this->line("- {$className}");
            $this->line("  File: " . basename($file));
            $this->line("  Type: " . $this->getRuleType($content));
            $this->line("  Severity: " . $this->getRuleSeverity($content));
            $this->line('');
        }

        return 0;
    }

    private function addRule()
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $description = $this->option('description');
        $code = $this->option('code');
        $severity = $this->option('severity');

        if (!$type || !$name || !$description || !$code) {
            $this->error('Missing required options. Please provide --type, --name, --description, and --code.');
            return 1;
        }

        if (!$this->validateSeverity($severity)) {
            $this->error('Invalid severity level. Must be one of: error, warning, info');
            return 1;
        }

        $rulePath = base_path(".sniffing/rules/ServiceLearning/{$name}Sniff.php");
        
        if (File::exists($rulePath)) {
            $this->error("Rule {$name} already exists.");
            return 1;
        }

        $content = $this->generateRuleContent($name, $description, $code, $severity);
        File::put($rulePath, $content);

        $this->info("Rule {$name} created successfully.");
        return 0;
    }

    private function removeRule()
    {
        $name = $this->option('name');

        if (!$name) {
            $this->error('Please provide a rule name using --name option.');
            return 1;
        }

        $rulePath = base_path(".sniffing/rules/ServiceLearning/{$name}Sniff.php");
        
        if (!File::exists($rulePath)) {
            $this->error("Rule {$name} does not exist.");
            return 1;
        }

        File::delete($rulePath);
        $this->info("Rule {$name} removed successfully.");
        return 0;
    }

    private function updateRule()
    {
        $name = $this->option('name');
        $description = $this->option('description');
        $code = $this->option('code');
        $severity = $this->option('severity');

        if (!$name) {
            $this->error('Please provide a rule name using --name option.');
            return 1;
        }

        $rulePath = base_path(".sniffing/rules/ServiceLearning/{$name}Sniff.php");
        
        if (!File::exists($rulePath)) {
            $this->error("Rule {$name} does not exist.");
            return 1;
        }

        if ($severity && !$this->validateSeverity($severity)) {
            $this->error('Invalid severity level. Must be one of: error, warning, info');
            return 1;
        }

        $content = File::get($rulePath);
        
        if ($description) {
            $content = preg_replace('/\/\*\*\s*\n\s*\*\s*.*?\n\s*\*\//s', "/**\n * {$description}\n */", $content);
        }
        
        if ($code) {
            $content = preg_replace('/protected\s+\$code\s*=\s*\'[^\']*\'/', "protected \$code = '{$code}'", $content);
        }
        
        if ($severity) {
            $content = preg_replace('/protected\s+\$severity\s*=\s*\'[^\']*\'/', "protected \$severity = '{$severity}'", $content);
        }

        File::put($rulePath, $content);
        $this->info("Rule {$name} updated successfully.");
        return 0;
    }

    private function validateSeverity(?string $severity): bool
    {
        return !$severity || in_array($severity, ['error', 'warning', 'info']);
    }

    private function getRuleType(string $content): string
    {
        if (preg_match('/protected\s+\$type\s*=\s*\'([^\']*)\'/', $content, $matches)) {
            return $matches[1];
        }
        return 'Unknown';
    }

    private function getRuleSeverity(string $content): string
    {
        if (preg_match('/protected\s+\$severity\s*=\s*\'([^\']*)\'/', $content, $matches)) {
            return $matches[1];
        }
        return 'Unknown';
    }

    private function generateRuleContent(string $name, string $description, string $code, string $severity): string
    {
        return <<<PHP
<?php

namespace ServiceLearning\Sniffs;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * {$description}
 */
class {$name}Sniff implements Sniff
{
    protected \$code = '{$code}';
    protected \$severity = '{$severity}';

    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_FUNCTION,
            T_VARIABLE,
        ];
    }

    public function process(File \$phpcsFile, \$stackPtr)
    {
        // Implement rule logic here
    }
}
PHP;
    }
} 