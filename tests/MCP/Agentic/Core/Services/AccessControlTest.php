<?php

namespace Tests\MCP\Agentic\Core\Services;

use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\AuditLogger;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\MCP\Agentic\BaseAgenticTestCase;

class AccessControlTest extends BaseAgenticTestCase
{
    protected AccessControl $accessControl;
    protected AuditLogger $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        $this->accessControl = new AccessControl($this->auditLogger);
        
        Config::set('mcp.agentic.tenants', [
            'tenant1' => [
                'name' => 'Test Tenant 1',
                'status' => 'active',
            ],
            'tenant2' => [
                'name' => 'Test Tenant 2',
                'status' => 'active',
            ],
        ]);
    }

    public function test_can_register_and_check_policy(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Policy registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Access granted', Mockery::any())
            ->once();

        $this->accessControl->registerPolicy('read', 'document', function ($user, $tenant) {
            return $user === 'user1' && $tenant === 'tenant1';
        });

        $this->accessControl->setCurrentUser('user1');
        $this->accessControl->validateTenantAccess('tenant1');

        $this->assertTrue($this->accessControl->check('read', 'document'));
    }

    public function test_can_register_and_check_human_review_rule(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Human review rule registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Human review required', Mockery::any())
            ->once();

        $this->accessControl->registerHumanReviewRule('deploy', function ($context) {
            return $context['environment'] === 'production';
        });

        $this->assertTrue($this->accessControl->requiresHumanReview('deploy', [
            'environment' => 'production',
        ]));
    }

    public function test_can_register_and_manage_capabilities(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Capability registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Capability updated', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Capability removed', Mockery::any())
            ->once();

        $this->accessControl->registerCapability('admin', ['read', 'write', 'delete']);
        
        $capability = $this->accessControl->getCapability('admin');
        $this->assertNotNull($capability);
        $this->assertEquals(['read', 'write', 'delete'], $capability['permissions']);

        $this->accessControl->updateCapability('admin', ['read', 'write']);
        
        $capability = $this->accessControl->getCapability('admin');
        $this->assertEquals(['read', 'write'], $capability['permissions']);

        $this->accessControl->removeCapability('admin');
        $this->assertNull($this->accessControl->getCapability('admin'));
    }

    public function test_denies_access_when_no_policy_exists(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Access denied: No policy found', Mockery::any())
            ->once();

        $this->accessControl->setCurrentUser('user1');
        $this->accessControl->validateTenantAccess('tenant1');

        $this->assertFalse($this->accessControl->check('read', 'document'));
    }

    public function test_denies_access_when_policy_returns_false(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Policy registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Access denied', Mockery::any())
            ->once();

        $this->accessControl->registerPolicy('read', 'document', function ($user, $tenant) {
            return false;
        });

        $this->accessControl->setCurrentUser('user1');
        $this->accessControl->validateTenantAccess('tenant1');

        $this->assertFalse($this->accessControl->check('read', 'document'));
    }

    public function test_requires_human_review_based_on_context(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Human review rule registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Human review required', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'No human review required', Mockery::any())
            ->once();

        $this->accessControl->registerHumanReviewRule('deploy', function ($context) {
            return $context['environment'] === 'production';
        });

        $this->assertTrue($this->accessControl->requiresHumanReview('deploy', [
            'environment' => 'production',
        ]));

        $this->assertFalse($this->accessControl->requiresHumanReview('deploy', [
            'environment' => 'staging',
        ]));
    }

    public function test_validates_tenant_access(): void
    {
        $this->accessControl->validateTenantAccess('tenant1');
        $this->assertEquals('tenant1', $this->accessControl->getCurrentTenant());

        $this->expectException(\RuntimeException::class);
        $this->accessControl->validateTenantAccess('invalid_tenant');
    }

    public function test_manages_current_user(): void
    {
        $this->accessControl->setCurrentUser('user1');
        $this->assertEquals('user1', $this->accessControl->getCurrentUser());
    }

    public function test_can_remove_policy(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Policy registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Policy removed', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Access denied: No policy found', Mockery::any())
            ->once();

        $this->accessControl->registerPolicy('read', 'document', function ($user, $tenant) {
            return true;
        });

        $this->accessControl->removePolicy('read', 'document');

        $this->accessControl->setCurrentUser('user1');
        $this->accessControl->validateTenantAccess('tenant1');

        $this->assertFalse($this->accessControl->check('read', 'document'));
    }

    public function test_can_remove_human_review_rule(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Human review rule registered', Mockery::any())
            ->once();
        
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Human review rule removed', Mockery::any())
            ->once();

        $this->accessControl->registerHumanReviewRule('deploy', function ($context) {
            return true;
        });

        $this->accessControl->removeHumanReviewRule('deploy');

        $this->assertFalse($this->accessControl->requiresHumanReview('deploy', []));
    }

    public function test_cannot_update_nonexistent_capability(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->accessControl->updateCapability('nonexistent', []);
    }

    public function test_can_get_all_capabilities(): void
    {
        $this->auditLogger->shouldReceive('log')
            ->with('security', 'Capability registered', Mockery::any())
            ->times(2);

        $this->accessControl->registerCapability('admin', ['read', 'write']);
        $this->accessControl->registerCapability('user', ['read']);

        $capabilities = $this->accessControl->getAllCapabilities();
        
        $this->assertCount(2, $capabilities);
        $this->assertTrue($capabilities->has('admin'));
        $this->assertTrue($capabilities->has('user'));
    }
} 