<?php

namespace App\Console\Commands\Auth;

class ManageAuthDatabaseCommand extends BaseAuthCommand
{
    protected $signature = 'auth:database
        {action : The action to perform (migrate|seed|reset|backup|restore)}
        {--table= : Table name}
        {--seed= : Seeder class}
        {--backup= : Backup file}
        {--force : Force operation}';

    protected $description = 'Manage authentication database';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'migrate':
                return $this->migrate();
            case 'seed':
                return $this->seed();
            case 'reset':
                return $this->reset();
            case 'backup':
                return $this->backup();
            case 'restore':
                return $this->restore();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function migrate()
    {
        $table = $this->option('table');
        $force = $this->option('force');

        try {
            $migrations = $this->authService->migrateDatabase([
                'table' => $table,
                'force' => $force
            ]);

            $this->info("Database migrated successfully:");
            $this->table(
                ['Migration', 'Status'],
                $migrations->map(fn($migration) => [
                    $migration->name,
                    $migration->status
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to migrate database: {$e->getMessage()}");
            return 1;
        }
    }

    protected function seed()
    {
        $seed = $this->option('seed');
        $force = $this->option('force');

        try {
            $seeds = $this->authService->seedDatabase([
                'seed' => $seed,
                'force' => $force
            ]);

            $this->info("Database seeded successfully:");
            $this->table(
                ['Seeder', 'Status'],
                $seeds->map(fn($seed) => [
                    $seed->name,
                    $seed->status
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to seed database: {$e->getMessage()}");
            return 1;
        }
    }

    protected function reset()
    {
        $table = $this->option('table');
        $force = $this->option('force');

        if (!$force && !$this->confirm('Are you sure you want to reset the database?')) {
            return 0;
        }

        try {
            $this->authService->resetDatabase([
                'table' => $table,
                'force' => $force
            ]);

            $this->info("Database reset successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to reset database: {$e->getMessage()}");
            return 1;
        }
    }

    protected function backup()
    {
        $backup = $this->option('backup');
        $force = $this->option('force');

        try {
            $result = $this->authService->backupDatabase([
                'backup' => $backup,
                'force' => $force
            ]);

            $this->info("Database backed up successfully: {$result->path}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to backup database: {$e->getMessage()}");
            return 1;
        }
    }

    protected function restore()
    {
        $backup = $this->option('backup');
        $force = $this->option('force');

        if (!$backup) {
            $this->error('Backup file is required');
            return 1;
        }

        if (!$force && !$this->confirm('Are you sure you want to restore the database?')) {
            return 0;
        }

        try {
            $this->authService->restoreDatabase([
                'backup' => $backup,
                'force' => $force
            ]);

            $this->info("Database restored successfully from: {$backup}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to restore database: {$e->getMessage()}");
            return 1;
        }
    }
} 