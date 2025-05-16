<?php

declare(strict_types=1);

namespace MCP\Tests\Unit\Models;

use MCP\Core\Database\ConnectionManager;
use MCP\Models\Model;
use MCP\Tests\Helpers\DatabaseTestCase;
use PDO;

class TestModel extends Model
{
    protected string $table = 'test_models';
    protected array $fillable = ['name', 'description', 'status'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'status' => 'int',
        'is_active' => 'bool',
        'settings' => 'json'
    ];
}

class ModelTest extends DatabaseTestCase
{
    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestModel($this->getConnectionManager());
    }

    public function testModelCanBeCreated(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    public function testModelCanCreateRecord(): void
    {
        $data = [
            'name' => 'Test Model',
            'description' => 'Test Description',
            'status' => 1,
            'is_active' => true,
            'settings' => ['key' => 'value'],
            'password' => 'secret'
        ];

        $id = $this->model->create($data);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $record = $this->model->find($id);
        $this->assertIsArray($record);
        $this->assertEquals('Test Model', $record['name']);
        $this->assertEquals('Test Description', $record['description']);
        $this->assertEquals(1, $record['status']);
        $this->assertTrue($record['is_active']);
        $this->assertEquals(['key' => 'value'], $record['settings']);
        $this->assertArrayNotHasKey('password', $record);
    }

    public function testModelCanFindRecord(): void
    {
        $id = $this->insertTestData();
        $record = $this->model->find($id);

        $this->assertIsArray($record);
        $this->assertEquals($id, $record['id']);
        $this->assertEquals('Test Model', $record['name']);
    }

    public function testModelCanUpdateRecord(): void
    {
        $id = $this->insertTestData();
        $data = [
            'name' => 'Updated Model',
            'description' => 'Updated Description'
        ];

        $result = $this->model->update($id, $data);
        $this->assertTrue($result);

        $record = $this->model->find($id);
        $this->assertEquals('Updated Model', $record['name']);
        $this->assertEquals('Updated Description', $record['description']);
    }

    public function testModelCanDeleteRecord(): void
    {
        $id = $this->insertTestData();
        $result = $this->model->delete($id);
        $this->assertTrue($result);

        $record = $this->model->find($id);
        $this->assertNull($record);
    }

    public function testModelCanQueryWithWhere(): void
    {
        $id1 = $this->insertTestData(['name' => 'Model 1']);
        $id2 = $this->insertTestData(['name' => 'Model 2']);

        $results = $this->model->where('name', '=', 'Model 1')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Model 1', $results[0]['name']);
    }

    public function testModelCanCountRecords(): void
    {
        $this->insertTestData(['name' => 'Model 1']);
        $this->insertTestData(['name' => 'Model 2']);

        $count = $this->model->count();
        $this->assertEquals(2, $count);
    }

    public function testModelFiltersFillableFields(): void
    {
        $data = [
            'name' => 'Test Model',
            'description' => 'Test Description',
            'status' => 1,
            'invalid_field' => 'value'
        ];

        $id = $this->model->create($data);
        $record = $this->model->find($id);

        $this->assertArrayHasKey('name', $record);
        $this->assertArrayHasKey('description', $record);
        $this->assertArrayHasKey('status', $record);
        $this->assertArrayNotHasKey('invalid_field', $record);
    }

    public function testModelCastsAttributes(): void
    {
        $data = [
            'name' => 'Test Model',
            'status' => '1',
            'is_active' => '1',
            'settings' => '{"key":"value"}'
        ];

        $id = $this->model->create($data);
        $record = $this->model->find($id);

        $this->assertIsInt($record['status']);
        $this->assertIsBool($record['is_active']);
        $this->assertIsArray($record['settings']);
        $this->assertEquals(['key' => 'value'], $record['settings']);
    }

    private function insertTestData(array $data = []): int
    {
        $defaultData = [
            'name' => 'Test Model',
            'description' => 'Test Description',
            'status' => 1,
            'is_active' => true,
            'settings' => json_encode(['key' => 'value']),
            'password' => 'secret'
        ];

        $data = array_merge($defaultData, $data);
        return $this->model->create($data);
    }
} 