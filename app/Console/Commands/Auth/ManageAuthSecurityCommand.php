<?php

namespace App\Console\Commands\Auth;

class ManageAuthSecurityCommand extends BaseAuthCommand
{
    protected $signature = 'auth:security
        {action : The action to perform (show|set|reset)}
        {--setting= : Security setting name}
        {--value= : Security setting value}
        {--all : Apply to all settings}';

    protected $description = 'Manage authentication security settings';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'show':
                return $this->showSettings();
            case 'set':
                return $this->setSetting();
            case 'reset':
                return $this->resetSettings();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function showSettings()
    {
        $setting = $this->option('setting');

        try {
            if ($setting) {
                $value = $this->authService->getSecuritySetting($setting);
                $this->info("{$setting}: {$value}");
            } else {
                $settings = $this->authService->getAllSecuritySettings();
                $this->table(
                    ['Setting', 'Value', 'Description'],
                    collect($settings)->map(fn($value, $key) => [
                        $key,
                        $value['value'],
                        $value['description']
                    ])
                );
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to show settings: {$e->getMessage()}");
            return 1;
        }
    }

    protected function setSetting()
    {
        $setting = $this->option('setting');
        $value = $this->option('value');

        if (!$setting || !$value) {
            $this->error('Both setting name and value are required');
            return 1;
        }

        try {
            $this->authService->setSecuritySetting($setting, $value);
            $this->info("Security setting updated successfully: {$setting} = {$value}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to set security setting: {$e->getMessage()}");
            return 1;
        }
    }

    protected function resetSettings()
    {
        $setting = $this->option('setting');
        $all = $this->option('all');

        if (!$setting && !$all) {
            $this->error('Either setting name or --all option is required');
            return 1;
        }

        if ($this->confirm('Are you sure you want to reset these security settings?')) {
            try {
                if ($all) {
                    $this->authService->resetAllSecuritySettings();
                    $this->info('All security settings reset successfully');
                } else {
                    $this->authService->resetSecuritySetting($setting);
                    $this->info("Security setting reset successfully: {$setting}");
                }
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to reset security settings: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 