<?php

namespace App\Console\Commands\Auth;

class ManageAuthUsersCommand extends BaseAuthCommand
{
    protected $signature = 'auth:users 
        {action : The action to perform (list|create|update|delete)}
        {--email= : User email}
        {--name= : User name}
        {--role= : User role}
        {--status= : User status}';

    protected $description = 'Manage authentication users';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listUsers();
            case 'create':
                return $this->createUser();
            case 'update':
                return $this->updateUser();
            case 'delete':
                return $this->deleteUser();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listUsers()
    {
        $users = $this->authService->getAllUsers();
        
        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Status'],
            $users->map(fn($user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->status
            ])
        );

        return 0;
    }

    protected function createUser()
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $role = $this->option('role');
        $status = $this->option('status');

        if (!$email || !$name) {
            $this->error('Email and name are required');
            return 1;
        }

        try {
            $user = $this->authService->createUser([
                'email' => $email,
                'name' => $name,
                'role' => $role,
                'status' => $status
            ]);

            $this->info("User created successfully: {$user->email}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create user: {$e->getMessage()}");
            return 1;
        }
    }

    protected function updateUser()
    {
        $email = $this->option('email');
        if (!$email) {
            $this->error('Email is required for update');
            return 1;
        }

        try {
            $user = $this->authService->updateUser($email, [
                'name' => $this->option('name'),
                'role' => $this->option('role'),
                'status' => $this->option('status')
            ]);

            $this->info("User updated successfully: {$user->email}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update user: {$e->getMessage()}");
            return 1;
        }
    }

    protected function deleteUser()
    {
        $email = $this->option('email');
        if (!$email) {
            $this->error('Email is required for deletion');
            return 1;
        }

        if ($this->confirm("Are you sure you want to delete user {$email}?")) {
            try {
                $this->authService->deleteUser($email);
                $this->info("User deleted successfully: {$email}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to delete user: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 