<?php

namespace App\Console\Commands\Auth;

class ManageAuthRolesCommand extends BaseAuthCommand
{
    protected $signature = 'auth:roles
        {action : The action to perform (list|create|update|delete|assign|revoke)}
        {--role= : Role name}
        {--permission= : Permission name}
        {--user= : User email}
        {--description= : Role description}';

    protected $description = 'Manage authentication roles and permissions';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listRoles();
            case 'create':
                return $this->createRole();
            case 'update':
                return $this->updateRole();
            case 'delete':
                return $this->deleteRole();
            case 'assign':
                return $this->assignRole();
            case 'revoke':
                return $this->revokeRole();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listRoles()
    {
        $roles = $this->authService->getAllRoles();
        
        $this->table(
            ['Role', 'Description', 'Permissions', 'Users'],
            $roles->map(fn($role) => [
                $role->name,
                $role->description,
                $role->permissions->count(),
                $role->users->count()
            ])
        );

        return 0;
    }

    protected function createRole()
    {
        $role = $this->option('role');
        $description = $this->option('description');

        if (!$role) {
            $this->error('Role name is required');
            return 1;
        }

        try {
            $this->authService->createRole([
                'name' => $role,
                'description' => $description
            ]);

            $this->info("Role created successfully: {$role}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create role: {$e->getMessage()}");
            return 1;
        }
    }

    protected function updateRole()
    {
        $role = $this->option('role');
        if (!$role) {
            $this->error('Role name is required');
            return 1;
        }

        try {
            $this->authService->updateRole($role, [
                'description' => $this->option('description')
            ]);

            $this->info("Role updated successfully: {$role}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update role: {$e->getMessage()}");
            return 1;
        }
    }

    protected function deleteRole()
    {
        $role = $this->option('role');
        if (!$role) {
            $this->error('Role name is required');
            return 1;
        }

        if ($this->confirm("Are you sure you want to delete role {$role}?")) {
            try {
                $this->authService->deleteRole($role);
                $this->info("Role deleted successfully: {$role}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to delete role: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }

    protected function assignRole()
    {
        $role = $this->option('role');
        $user = $this->option('user');

        if (!$role || !$user) {
            $this->error('Role name and user email are required');
            return 1;
        }

        try {
            $this->authService->assignRole($user, $role);
            $this->info("Role {$role} assigned to user {$user} successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to assign role: {$e->getMessage()}");
            return 1;
        }
    }

    protected function revokeRole()
    {
        $role = $this->option('role');
        $user = $this->option('user');

        if (!$role || !$user) {
            $this->error('Role name and user email are required');
            return 1;
        }

        try {
            $this->authService->revokeRole($user, $role);
            $this->info("Role {$role} revoked from user {$user} successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to revoke role: {$e->getMessage()}");
            return 1;
        }
    }
} 