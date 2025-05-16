<?php

namespace Tests\MCP\Security;

use Tests\MCP\BaseTestCase;
use App\MCP\Security\RBAC;
use App\MCP\Core\Services\AuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

class RBACTest extends BaseTestCase
{
    protected RBAC $rbac;
    protected AuditLogger $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        $this->auditLogger->shouldReceive('log')->andReturn(true);
        $this->rbac = new RBAC($this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_create_role(): void
    {
        $this->assertTrue($this->rbac->createRole('admin', 'Administrator role'));
        $this->assertFalse($this->rbac->createRole('admin', 'Duplicate role'));

        $roles = $this->rbac->getRoles();
        $this->assertTrue($roles->has('admin'));
        $this->assertEquals('Administrator role', $roles->get('admin')['description']);
    }

    public function test_can_create_permission(): void
    {
        $this->assertTrue($this->rbac->createPermission('create_user', 'Can create users'));
        $this->assertFalse($this->rbac->createPermission('create_user', 'Duplicate permission'));

        $permissions = $this->rbac->getPermissions();
        $this->assertTrue($permissions->has('create_user'));
        $this->assertEquals('Can create users', $permissions->get('create_user')['description']);
    }

    public function test_can_assign_permission_to_role(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');

        $this->assertTrue($this->rbac->assignPermissionToRole('admin', 'create_user'));
        $this->assertFalse($this->rbac->assignPermissionToRole('nonexistent', 'create_user'));
        $this->assertFalse($this->rbac->assignPermissionToRole('admin', 'nonexistent'));

        $rolePermissions = $this->rbac->getRolePermissions('admin');
        $this->assertTrue($rolePermissions->contains('create_user'));
    }

    public function test_can_assign_role_to_user(): void
    {
        $this->rbac->createRole('admin');

        $this->assertTrue($this->rbac->assignRoleToUser('user1', 'admin'));
        $this->assertFalse($this->rbac->assignRoleToUser('user1', 'nonexistent'));

        $userRoles = $this->rbac->getUserRoles('user1');
        $this->assertTrue($userRoles->contains('admin'));
    }

    public function test_can_check_user_permission(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');
        $this->rbac->assignRoleToUser('user1', 'admin');

        $this->assertTrue($this->rbac->hasPermission('user1', 'create_user'));
        $this->assertFalse($this->rbac->hasPermission('user1', 'nonexistent'));
        $this->assertFalse($this->rbac->hasPermission('nonexistent', 'create_user'));
    }

    public function test_can_check_user_role(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->assignRoleToUser('user1', 'admin');

        $this->assertTrue($this->rbac->hasRole('user1', 'admin'));
        $this->assertFalse($this->rbac->hasRole('user1', 'nonexistent'));
        $this->assertFalse($this->rbac->hasRole('nonexistent', 'admin'));
    }

    public function test_can_remove_role(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');
        $this->rbac->assignRoleToUser('user1', 'admin');

        $this->assertTrue($this->rbac->removeRole('admin'));
        $this->assertFalse($this->rbac->removeRole('nonexistent'));

        $this->assertFalse($this->rbac->hasRole('user1', 'admin'));
        $this->assertTrue($this->rbac->getRolePermissions('admin')->isEmpty());
    }

    public function test_can_remove_permission(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');

        $this->assertTrue($this->rbac->removePermission('create_user'));
        $this->assertFalse($this->rbac->removePermission('nonexistent'));

        $this->assertFalse($this->rbac->getRolePermissions('admin')->contains('create_user'));
    }

    public function test_can_remove_role_from_user(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->assignRoleToUser('user1', 'admin');

        $this->assertTrue($this->rbac->removeRoleFromUser('user1', 'admin'));
        $this->assertFalse($this->rbac->removeRoleFromUser('user1', 'nonexistent'));
        $this->assertFalse($this->rbac->removeRoleFromUser('nonexistent', 'admin'));

        $this->assertFalse($this->rbac->hasRole('user1', 'admin'));
    }

    public function test_can_remove_permission_from_role(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');

        $this->assertTrue($this->rbac->removePermissionFromRole('admin', 'create_user'));
        $this->assertFalse($this->rbac->removePermissionFromRole('admin', 'nonexistent'));
        $this->assertFalse($this->rbac->removePermissionFromRole('nonexistent', 'create_user'));

        $this->assertFalse($this->rbac->getRolePermissions('admin')->contains('create_user'));
    }

    public function test_user_permissions_are_cached(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');
        $this->rbac->assignRoleToUser('user1', 'admin');

        // First call should cache permissions
        $this->assertTrue($this->rbac->hasPermission('user1', 'create_user'));

        // Remove permission but keep cache
        $this->rbac->removePermissionFromRole('admin', 'create_user');

        // Should still return true due to cache
        $this->assertTrue($this->rbac->hasPermission('user1', 'create_user'));

        // Clear cache
        Cache::forget('user_permissions_user1');

        // Should now return false
        $this->assertFalse($this->rbac->hasPermission('user1', 'create_user'));
    }

    public function test_user_permissions_cache_is_cleared_on_role_assignment(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');

        // Initial state - no permission
        $this->assertFalse($this->rbac->hasPermission('user1', 'create_user'));

        // Assign role - should clear cache
        $this->rbac->assignRoleToUser('user1', 'admin');

        // Should have permission now
        $this->assertTrue($this->rbac->hasPermission('user1', 'create_user'));
    }

    public function test_user_permissions_cache_is_cleared_on_role_removal(): void
    {
        $this->rbac->createRole('admin');
        $this->rbac->createPermission('create_user');
        $this->rbac->assignPermissionToRole('admin', 'create_user');
        $this->rbac->assignRoleToUser('user1', 'admin');

        // Initial state - has permission
        $this->assertTrue($this->rbac->hasPermission('user1', 'create_user'));

        // Remove role - should clear cache
        $this->rbac->removeRoleFromUser('user1', 'admin');

        // Should not have permission now
        $this->assertFalse($this->rbac->hasPermission('user1', 'create_user'));
    }

    public function test_logs_warnings_for_invalid_operations(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Role admin already exists');

        $this->rbac->createRole('admin');
        $this->rbac->createRole('admin');

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission create_user already exists');

        $this->rbac->createPermission('create_user');
        $this->rbac->createPermission('create_user');

        Log::shouldReceive('warning')
            ->once()
            ->with('Role nonexistent does not exist');

        $this->rbac->assignPermissionToRole('nonexistent', 'create_user');

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission nonexistent does not exist');

        $this->rbac->assignPermissionToRole('admin', 'nonexistent');
    }

    public function test_audit_logs_are_created(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('security', 'Role admin created', ['role' => 'admin', 'description' => '']);

        $this->rbac->createRole('admin');

        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('security', 'Permission create_user created', ['permission' => 'create_user', 'description' => '']);

        $this->rbac->createPermission('create_user');

        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('security', 'Permission create_user assigned to role admin', [
                'role' => 'admin',
                'permission' => 'create_user',
            ]);

        $this->rbac->assignPermissionToRole('admin', 'create_user');

        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('security', 'Role admin assigned to user user1', [
                'user_id' => 'user1',
                'role' => 'admin',
            ]);

        $this->rbac->assignRoleToUser('user1', 'admin');
    }
} 