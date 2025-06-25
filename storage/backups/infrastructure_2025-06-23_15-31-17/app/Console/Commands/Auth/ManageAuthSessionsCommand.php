<?php

namespace App\Console\Commands\Auth;

class ManageAuthSessionsCommand extends BaseAuthCommand
{
    protected $signature = 'auth:sessions
        {action : The action to perform (list|invalidate|clear)}
        {--user= : User email}
        {--session= : Session ID}
        {--all : Apply to all sessions}';

    protected $description = 'Manage authentication sessions';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listSessions();
            case 'invalidate':
                return $this->invalidateSession();
            case 'clear':
                return $this->clearSessions();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listSessions()
    {
        $user = $this->option('user');
        $sessions = $user 
            ? $this->authService->getUserSessions($user)
            : $this->authService->getAllSessions();

        $this->table(
            ['Session ID', 'User', 'IP Address', 'Last Activity', 'Device'],
            $sessions->map(fn($session) => [
                $session->id,
                $session->user->email,
                $session->ip_address,
                $session->last_activity->diffForHumans(),
                $session->user_agent
            ])
        );

        return 0;
    }

    protected function invalidateSession()
    {
        $session = $this->option('session');
        $user = $this->option('user');

        if (!$session && !$user) {
            $this->error('Either session ID or user email is required');
            return 1;
        }

        try {
            if ($session) {
                $this->authService->invalidateSession($session);
                $this->info("Session {$session} invalidated successfully");
            } else {
                $this->authService->invalidateUserSessions($user);
                $this->info("All sessions for user {$user} invalidated successfully");
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to invalidate session: {$e->getMessage()}");
            return 1;
        }
    }

    protected function clearSessions()
    {
        if (!$this->option('all') && !$this->option('user')) {
            $this->error('Either --all or --user option is required');
            return 1;
        }

        if ($this->confirm('Are you sure you want to clear these sessions?')) {
            try {
                if ($this->option('all')) {
                    $this->authService->clearAllSessions();
                    $this->info('All sessions cleared successfully');
                } else {
                    $this->authService->clearUserSessions($this->option('user'));
                    $this->info("All sessions for user {$this->option('user')} cleared successfully");
                }
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to clear sessions: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 