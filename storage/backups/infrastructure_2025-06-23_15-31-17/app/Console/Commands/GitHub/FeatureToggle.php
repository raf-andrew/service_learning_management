<?php

namespace App\Console\Commands\GitHub;

use Illuminate\Console\Command;
use App\Models\GitHub\Feature;

class FeatureToggle extends Command
{
    protected $signature = 'github:feature
                          {action : The action to perform (list|enable|disable|add|remove)}
                          {name? : The feature name}
                          {--description= : Feature description}
                          {--conditions=* : Feature conditions}';

    protected $description = 'Manage GitHub feature flags';

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');

        switch ($action) {
            case 'list':
                return $this->listFeatures();
            case 'enable':
                return $this->toggleFeature($name, true);
            case 'disable':
                return $this->toggleFeature($name, false);
            case 'add':
                return $this->addFeature($name);
            case 'remove':
                return $this->removeFeature($name);
            default:
                $this->error('Invalid action');
                return 1;
        }
    }

    private function listFeatures()
    {
        $features = Feature::all();
        
        if ($features->isEmpty()) {
            $this->info('No features configured');
            return 0;
        }

        $headers = ['Name', 'Status', 'Conditions', 'Description'];
        $rows = $features->map(function ($feature) {
            return [
                $feature->name,
                $feature->enabled ? 'Enabled' : 'Disabled',
                json_encode($feature->conditions),
                $feature->description
            ];
        });

        $this->table($headers, $rows);
        return 0;
    }

    private function toggleFeature($name, $enabled)
    {
        if (!$name) {
            $this->error('Feature name is required');
            return 1;
        }

        $feature = Feature::where('name', $name)->first();
        if (!$feature) {
            $this->error("Feature '{$name}' not found");
            return 1;
        }

        $feature->update(['enabled' => $enabled]);
        $this->info("Feature '{$name}' " . ($enabled ? 'enabled' : 'disabled'));
        return 0;
    }

    private function addFeature($name)
    {
        if (!$name) {
            $this->error('Feature name is required');
            return 1;
        }

        if (Feature::where('name', $name)->exists()) {
            $this->error("Feature '{$name}' already exists");
            return 1;
        }

        Feature::create([
            'name' => $name,
            'enabled' => false,
            'description' => $this->option('description'),
            'conditions' => $this->option('conditions')
        ]);

        $this->info("Feature '{$name}' added");
        return 0;
    }

    private function removeFeature($name)
    {
        if (!$name) {
            $this->error('Feature name is required');
            return 1;
        }

        $feature = Feature::where('name', $name)->first();
        if (!$feature) {
            $this->error("Feature '{$name}' not found");
            return 1;
        }

        $feature->delete();
        $this->info("Feature '{$name}' removed");
        return 0;
    }
} 