<?php

declare(strict_types=1);

namespace MCP\Tests\Helpers;

use PHPUnit\Framework\MockObject\MockObject;
use MCP\Core\Logger\Logger;
use MCP\Core\Database\ConnectionManager;
use MCP\Core\Config\Config;
use MCP\Core\Http\Request;
use MCP\Core\Http\Response;
use MCP\Core\Validation\Validator;
use PDO;
use PDOStatement;

class MockFactory
{
    public static function createMockLogger(): Logger|MockObject
    {
        return self::getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockConnectionManager(): ConnectionManager|MockObject
    {
        return self::getMockBuilder(ConnectionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockConfig(): Config|MockObject
    {
        return self::getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockRequest(): Request|MockObject
    {
        return self::getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockResponse(): Response|MockObject
    {
        return self::getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockValidator(): Validator|MockObject
    {
        return self::getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockPDO(): PDO|MockObject
    {
        return self::getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function createMockPDOStatement(): PDOStatement|MockObject
    {
        return self::getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public static function generateTestModelData(): array
    {
        return [
            'name' => 'Test Model ' . uniqid(),
            'description' => 'Test Description ' . uniqid()
        ];
    }

    public static function generateTestRelationData(int $modelId): array
    {
        return [
            'test_model_id' => $modelId,
            'name' => 'Test Relation ' . uniqid()
        ];
    }

    private static function getMockBuilder(string $className): \PHPUnit\Framework\MockObject\MockBuilder
    {
        return (new \PHPUnit\Framework\TestCase())->getMockBuilder($className);
    }
} 