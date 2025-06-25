<?php

namespace App\Console\Commands\Auth;

class ManageAuthLogsCommand extends BaseAuthCommand
{
    protected $signature = 'auth:logs
        {action : The action to perform (show|clear|export)}
        {--user= : User email}
        {--type= : Log type (login|logout|failed|all)}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--file= : Export file path}';

    protected $description = 'Manage authentication logs';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'show':
                return $this->showLogs();
            case 'clear':
                return $this->clearLogs();
            case 'export':
                return $this->exportLogs();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function showLogs()
    {
        $user = $this->option('user');
        $type = $this->option('type') ?? 'all';
        $from = $this->option('from');
        $to = $this->option('to');

        try {
            $logs = $this->authService->getLogs([
                'user' => $user,
                'type' => $type,
                'from' => $from,
                'to' => $to
            ]);

            $this->table(
                ['Date', 'User', 'Type', 'IP Address', 'User Agent'],
                $logs->map(fn($log) => [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->email,
                    $log->type,
                    $log->ip_address,
                    $log->user_agent
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to show logs: {$e->getMessage()}");
            return 1;
        }
    }

    protected function clearLogs()
    {
        $user = $this->option('user');
        $type = $this->option('type') ?? 'all';
        $from = $this->option('from');
        $to = $this->option('to');

        if ($this->confirm('Are you sure you want to clear these logs?')) {
            try {
                $this->authService->clearLogs([
                    'user' => $user,
                    'type' => $type,
                    'from' => $from,
                    'to' => $to
                ]);

                $this->info('Logs cleared successfully');
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to clear logs: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }

    protected function exportLogs()
    {
        $user = $this->option('user');
        $type = $this->option('type') ?? 'all';
        $from = $this->option('from');
        $to = $this->option('to');
        $file = $this->option('file');

        if (!$file) {
            $this->error('Export file path is required');
            return 1;
        }

        try {
            $this->authService->exportLogs([
                'user' => $user,
                'type' => $type,
                'from' => $from,
                'to' => $to
            ], $file);

            $this->info("Logs exported successfully to: {$file}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to export logs: {$e->getMessage()}");
            return 1;
        }
    }
} 