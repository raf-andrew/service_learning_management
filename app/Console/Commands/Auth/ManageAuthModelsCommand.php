<?php

namespace App\Console\Commands\Auth;

class ManageAuthModelsCommand extends BaseAuthCommand
{
    protected $signature = 'auth:models
        {action : The action to perform (list|register|unregister)}
        {--name= : Model name}
        {--class= : Model class}
        {--table= : Database table}
        {--fillable= : Fillable attributes}
        {--hidden= : Hidden attributes}';

    protected $description = 'Manage authentication models';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listModels();
            case 'register':
                return $this->registerModel();
            case 'unregister':
                return $this->unregisterModel();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listModels()
    {
        try {
            $models = $this->authService->getAllModels();

            $this->table(
                ['Name', 'Class', 'Table', 'Fillable', 'Hidden'],
                $models->map(fn($model) => [
                    $model->name,
                    $model->class,
                    $model->table,
                    implode(', ', $model->fillable),
                    implode(', ', $model->hidden)
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list models: {$e->getMessage()}");
            return 1;
        }
    }

    protected function registerModel()
    {
        $name = $this->option('name');
        $class = $this->option('class');
        $table = $this->option('table');
        $fillable = $this->option('fillable');
        $hidden = $this->option('hidden');

        if (!$name || !$class || !$table) {
            $this->error('Model name, class, and table are required');
            return 1;
        }

        try {
            $this->authService->registerModel([
                'name' => $name,
                'class' => $class,
                'table' => $table,
                'fillable' => $fillable ? explode(',', $fillable) : [],
                'hidden' => $hidden ? explode(',', $hidden) : []
            ]);

            $this->info("Model registered successfully: {$name}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to register model: {$e->getMessage()}");
            return 1;
        }
    }

    protected function unregisterModel()
    {
        $name = $this->option('name');
        if (!$name) {
            $this->error('Model name is required');
            return 1;
        }

        if ($this->confirm("Are you sure you want to unregister model {$name}?")) {
            try {
                $this->authService->unregisterModel($name);
                $this->info("Model unregistered successfully: {$name}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to unregister model: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 