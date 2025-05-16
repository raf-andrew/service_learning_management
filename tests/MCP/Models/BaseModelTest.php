<?php

namespace MCP\Tests\Models;

use PHPUnit\Framework\TestCase;
use MCP\Models\BaseModel;
use PDO;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BaseModelTest extends TestCase
{
    private $db;
    private $logger;
    private $model;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        // Create a concrete class for testing
        $this->model = new class($this->db, $this->logger) extends BaseModel {
            protected $table = 'test_table';
            
            public function createTable()
            {
                $this->db->exec("CREATE TABLE test_table (
                    id INTEGER PRIMARY KEY,
                    name TEXT,
                    email TEXT
                )");
            }
        };

        $this->model->createTable();
    }

    public function testCreate()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $id = $this->model->create($data);
        $this->assertIsInt($id);
        $this->assertEquals(1, $id);
    }

    public function testFind()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $id = $this->model->create($data);
        $result = $this->model->find($id);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['email'], $result['email']);
    }

    public function testUpdate()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $id = $this->model->create($data);
        
        $updateData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com'
        ];

        $this->model->update($id, $updateData);
        $result = $this->model->find($id);

        $this->assertEquals($updateData['name'], $result['name']);
        $this->assertEquals($updateData['email'], $result['email']);
    }

    public function testDelete()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $id = $this->model->create($data);
        $this->model->delete($id);
        
        $result = $this->model->find($id);
        $this->assertFalse($result);
    }

    public function testAll()
    {
        $data1 = [
            'name' => 'User 1',
            'email' => 'user1@example.com'
        ];

        $data2 = [
            'name' => 'User 2',
            'email' => 'user2@example.com'
        ];

        $this->model->create($data1);
        $this->model->create($data2);

        $results = $this->model->all();
        
        $this->assertCount(2, $results);
        $this->assertEquals($data1['name'], $results[0]['name']);
        $this->assertEquals($data2['name'], $results[1]['name']);
    }
} 