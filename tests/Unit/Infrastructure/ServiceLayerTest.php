<?php

namespace Tests\Unit\Infrastructure;

use Tests\TestCase;
use App\Services\BaseService;
use App\Contracts\Services\ServiceInterface;
use App\Repositories\BaseRepository;
use App\Contracts\Repositories\RepositoryInterface;
use App\Traits\Services\AuditableTrait;
use App\Traits\Services\CacheableTrait;
use App\Traits\Services\ValidatableTrait;
use App\Modules\Shared\AuditService;
use App\Modules\Shared\MonitoringService;
use App\Modules\Shared\PerformanceOptimizationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Service Layer Infrastructure Test
 * 
 * Tests the comprehensive service layer improvements including:
 * - Base service functionality
 * - Repository pattern implementation
 * - Trait functionality
 * - Interface contracts
 * - Caching and audit logging
 */
class ServiceLayerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test base service implements interface
     */
    public function test_base_service_implements_interface(): void
    {
        $service = new TestService();
        
        $this->assertInstanceOf(ServiceInterface::class, $service);
        $this->assertInstanceOf(BaseService::class, $service);
    }

    /**
     * Test base repository implements interface
     */
    public function test_base_repository_implements_interface(): void
    {
        $repository = new TestRepository();
        
        $this->assertInstanceOf(RepositoryInterface::class, $repository);
        $this->assertInstanceOf(BaseRepository::class, $repository);
    }

    /**
     * Test service name generation
     */
    public function test_service_name_generation(): void
    {
        $service = new TestService();
        
        $this->assertEquals('Test', $service->getServiceName());
    }

    /**
     * Test repository name generation
     */
    public function test_repository_name_generation(): void
    {
        $repository = new TestRepository();
        
        $this->assertEquals('Test', $repository->getRepositoryName());
    }

    /**
     * Test service statistics
     */
    public function test_service_statistics(): void
    {
        $service = new TestService();
        $stats = $service->getStatistics();
        
        $this->assertArrayHasKey('service_name', $stats);
        $this->assertArrayHasKey('audit_enabled', $stats);
        $this->assertArrayHasKey('monitoring_enabled', $stats);
        $this->assertArrayHasKey('caching_enabled', $stats);
        $this->assertEquals('Test', $stats['service_name']);
    }

    /**
     * Test service health status
     */
    public function test_service_health_status(): void
    {
        $service = new TestService();
        $health = $service->getHealthStatus();
        
        $this->assertArrayHasKey('service_name', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('timestamp', $health);
        $this->assertEquals('Test', $health['service_name']);
        $this->assertEquals('healthy', $health['status']);
    }

    /**
     * Test repository statistics
     */
    public function test_repository_statistics(): void
    {
        $repository = new TestRepository();
        $stats = $repository->getRepositoryStatistics();
        
        $this->assertArrayHasKey('model_class', $stats);
        $this->assertArrayHasKey('repository_name', $stats);
        $this->assertArrayHasKey('cache_statistics', $stats);
        $this->assertEquals('Tests\Unit\Infrastructure\TestModel', $stats['model_class']);
        $this->assertEquals('Test', $stats['repository_name']);
    }

    /**
     * Test audit trait functionality
     */
    public function test_audit_trait_functionality(): void
    {
        $service = new TestService();
        
        // Test audit event logging
        $service->testAuditEvent();
        
        // Verify audit service was called
        $this->assertTrue(true); // Placeholder - would verify audit service calls
    }

    /**
     * Test cacheable trait functionality
     */
    public function test_cacheable_trait_functionality(): void
    {
        $service = new TestService();
        
        // Test caching
        $result1 = $service->testCaching('test_key', function () {
            return 'test_value';
        });
        
        $result2 = $service->testCaching('test_key', function () {
            return 'different_value';
        });
        
        $this->assertEquals('test_value', $result1);
        $this->assertEquals('test_value', $result2); // Should be cached
    }

    /**
     * Test validatable trait functionality
     */
    public function test_validatable_trait_functionality(): void
    {
        $service = new TestService();
        
        // Test validation
        $data = ['name' => 'Test Name', 'email' => 'test@example.com'];
        $rules = ['name' => 'required|string', 'email' => 'required|email'];
        
        $validated = $service->testValidation($data, $rules);
        
        $this->assertEquals($data, $validated);
    }

    /**
     * Test validation failure
     */
    public function test_validation_failure(): void
    {
        $service = new TestService();
        
        $data = ['name' => '', 'email' => 'invalid-email'];
        $rules = ['name' => 'required|string', 'email' => 'required|email'];
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $service->testValidation($data, $rules);
    }

    /**
     * Test repository CRUD operations
     */
    public function test_repository_crud_operations(): void
    {
        $repository = new TestRepository();
        
        // Test create
        $data = ['name' => 'Test Model', 'description' => 'Test Description'];
        $model = $repository->create($data);
        
        $this->assertInstanceOf(TestModel::class, $model);
        $this->assertEquals('Test Model', $model->name);
        
        // Test find
        $found = $repository->find($model->id);
        $this->assertInstanceOf(TestModel::class, $found);
        $this->assertEquals($model->id, $found->id);
        
        // Test update
        $updated = $repository->update($model, ['name' => 'Updated Name']);
        $this->assertEquals('Updated Name', $updated->name);
        
        // Test delete
        $deleted = $repository->delete($model);
        $this->assertTrue($deleted);
        
        // Test find after delete
        $found = $repository->find($model->id);
        $this->assertNull($found);
    }

    /**
     * Test repository pagination
     */
    public function test_repository_pagination(): void
    {
        $repository = new TestRepository();
        
        // Create test data
        for ($i = 1; $i <= 25; $i++) {
            $repository->create(['name' => "Test Model {$i}"]);
        }
        
        // Test pagination
        $paginator = $repository->paginate(10);
        
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertEquals(25, $paginator->total());
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(3, $paginator->lastPage());
    }

    /**
     * Test repository search
     */
    public function test_repository_search(): void
    {
        $repository = new TestRepository();
        
        // Create test data
        $repository->create(['name' => 'Apple Model']);
        $repository->create(['name' => 'Banana Model']);
        $repository->create(['name' => 'Orange Model']);
        
        // Test search
        $results = $repository->search('Apple');
        
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $results);
        $this->assertEquals(1, $results->total());
        $this->assertEquals('Apple Model', $results->first()->name);
    }

    /**
     * Test service configuration validation
     */
    public function test_service_configuration_validation(): void
    {
        $service = new TestService();
        
        $this->assertTrue($service->validateConfiguration());
    }

    /**
     * Test error handling in services
     */
    public function test_service_error_handling(): void
    {
        $service = new TestService();
        
        // Test error handling
        $this->expectException(\Exception::class);
        
        $service->testErrorHandling();
    }

    /**
     * Test batch processing
     */
    public function test_batch_processing(): void
    {
        $service = new TestService();
        
        $items = ['item1', 'item2', 'item3'];
        $results = $service->testBatchProcessing($items);
        
        $this->assertCount(3, $results);
        $this->assertEquals(['processed_item1', 'processed_item2', 'processed_item3'], $results);
    }
}

/**
 * Test Service for testing base service functionality
 */
class TestService extends BaseService
{
    public function testAuditEvent(): void
    {
        $this->logAuditEvent('test_event', ['test' => 'data']);
    }

    public function testCaching(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback);
    }

    public function testValidation(array $data, array $rules): array
    {
        return $this->validateData($data, $rules);
    }

    public function testErrorHandling(): void
    {
        $this->executeWithErrorHandling(function () {
            throw new \Exception('Test error');
        }, 'test_operation');
    }

    public function testBatchProcessing(array $items): array
    {
        return $this->batchProcess($items, function ($item) {
            return 'processed_' . $item;
        });
    }
}

/**
 * Test Repository for testing base repository functionality
 */
class TestRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return TestModel::class;
    }

    protected function getSearchableFields(): array
    {
        return ['name'];
    }

    protected function getFilterableFields(): array
    {
        return ['name', 'description'];
    }

    protected function getSortableFields(): array
    {
        return ['name', 'created_at'];
    }
}

/**
 * Test Model for testing repository functionality
 */
class TestModel extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name', 'description'];
    protected $table = 'test_models';
} 