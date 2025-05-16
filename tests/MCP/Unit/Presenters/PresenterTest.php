<?php

declare(strict_types=1);

namespace MCP\Tests\Unit\Presenters;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Presenters\Presenter;
use MCP\Core\Http\Response;
use MCP\Core\Logger\Logger;
use MCP\Core\Config\Config;

class TestPresenter extends Presenter
{
    protected array $hidden = ['password'];
    protected array $casts = [
        'id' => 'int',
        'status' => 'int',
        'is_active' => 'bool',
        'settings' => 'json',
        'created_at' => 'date'
    ];

    public function testSetData(array $data): void
    {
        $this->setData($data);
    }

    public function testGetData(): array
    {
        return $this->getData();
    }

    public function testRender(string $view, array $data = []): Response
    {
        return $this->render($view, $data);
    }

    public function testJson(array $data, int $status = 200): Response
    {
        return $this->json($data, $status);
    }

    public function testError(string $message, int $status = 400): Response
    {
        return $this->error($message, $status);
    }

    public function testSuccess(array $data = [], int $status = 200): Response
    {
        return $this->success($data, $status);
    }

    public function testNotFound(string $message = 'Resource not found'): Response
    {
        return $this->notFound($message);
    }

    public function testUnauthorized(string $message = 'Unauthorized'): Response
    {
        return $this->unauthorized($message);
    }

    public function testForbidden(string $message = 'Forbidden'): Response
    {
        return $this->forbidden($message);
    }

    public function testServerError(string $message = 'Internal server error'): Response
    {
        return $this->serverError($message);
    }

    public function testGetViewPath(string $view): string
    {
        return $this->getViewPath($view);
    }

    public function testLog(string $level, string $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    public function testGetConfig(string $key, mixed $default = null): mixed
    {
        return $this->getConfig($key, $default);
    }
}

class PresenterTest extends TestCase
{
    private TestPresenter $presenter;
    private Response $response;
    private Logger $logger;
    private Config $config;
    private array $testData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->response = MockFactory::createMockResponse();
        $this->logger = MockFactory::createMockLogger();
        $this->config = MockFactory::createMockConfig();

        $this->testData = [
            'id' => '1',
            'name' => 'Test',
            'status' => '1',
            'is_active' => '1',
            'settings' => '{"key":"value"}',
            'created_at' => '2024-03-20 12:00:00',
            'password' => 'secret'
        ];

        $this->presenter = new TestPresenter(
            $this->response,
            $this->logger,
            $this->config,
            $this->testData
        );
    }

    public function testPresenterCanBeCreated(): void
    {
        $this->assertInstanceOf(Presenter::class, $this->presenter);
    }

    public function testPresenterCanSetAndGetData(): void
    {
        $data = ['test' => 'data'];
        $this->presenter->testSetData($data);
        $this->assertEquals($data, $this->presenter->testGetData());
    }

    public function testPresenterCanRenderView(): void
    {
        $view = 'test.view';
        $data = ['test' => 'data'];
        $viewPath = 'views/test/view.php';
        $content = '<html>Test content</html>';

        $this->config->expects($this->once())
            ->method('get')
            ->with('app.views_path', 'views')
            ->willReturn('views');

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'text/html');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with($content);

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with(200);

        // Create a temporary view file
        $tempDir = sys_get_temp_dir();
        $tempViewPath = $tempDir . '/' . $viewPath;
        @mkdir(dirname($tempViewPath), 0777, true);
        file_put_contents($tempViewPath, $content);

        $result = $this->presenter->testRender($view, $data);
        $this->assertSame($this->response, $result);

        // Clean up
        unlink($tempViewPath);
        rmdir(dirname($tempViewPath));
    }

    public function testPresenterCanReturnJsonResponse(): void
    {
        $data = ['test' => 'data'];
        $status = 201;

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode($data, JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with($status);

        $result = $this->presenter->testJson($data, $status);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanReturnErrorResponse(): void
    {
        $message = 'Test error';
        $status = 422;

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode([
                'error' => [
                    'message' => $message,
                    'status' => $status
                ]
            ], JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with($status);

        $result = $this->presenter->testError($message, $status);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanReturnSuccessResponse(): void
    {
        $data = ['test' => 'data'];
        $status = 201;

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode([
                'success' => true,
                'data' => $data
            ], JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with($status);

        $result = $this->presenter->testSuccess($data, $status);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanReturnNotFoundResponse(): void
    {
        $message = 'Custom not found message';

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode([
                'error' => [
                    'message' => $message,
                    'status' => 404
                ]
            ], JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with(404);

        $result = $this->presenter->testNotFound($message);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanReturnUnauthorizedResponse(): void
    {
        $message = 'Custom unauthorized message';

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode([
                'error' => [
                    'message' => $message,
                    'status' => 401
                ]
            ], JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with(401);

        $result = $this->presenter->testUnauthorized($message);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanReturnForbiddenResponse(): void
    {
        $message = 'Custom forbidden message';

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode([
                'error' => [
                    'message' => $message,
                    'status' => 403
                ]
            ], JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with(403);

        $result = $this->presenter->testForbidden($message);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanReturnServerErrorResponse(): void
    {
        $message = 'Custom server error message';

        $this->response->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'application/json');

        $this->response->expects($this->once())
            ->method('setBody')
            ->with(json_encode([
                'error' => [
                    'message' => $message,
                    'status' => 500
                ]
            ], JSON_PRETTY_PRINT));

        $this->response->expects($this->once())
            ->method('setStatusCode')
            ->with(500);

        $result = $this->presenter->testServerError($message);
        $this->assertSame($this->response, $result);
    }

    public function testPresenterCanGetViewPath(): void
    {
        $view = 'test.view';
        $expectedPath = 'views/test/view.php';

        $this->config->expects($this->once())
            ->method('get')
            ->with('app.views_path', 'views')
            ->willReturn('views');

        $result = $this->presenter->testGetViewPath($view);
        $this->assertEquals($expectedPath, $result);
    }

    public function testPresenterCanLogMessages(): void
    {
        $level = 'info';
        $message = 'Test log message';
        $context = ['test' => 'data'];

        $this->logger->expects($this->once())
            ->method('log')
            ->with($level, $message, $context);

        $this->presenter->testLog($level, $message, $context);
    }

    public function testPresenterCanGetConfigValues(): void
    {
        $key = 'test.key';
        $value = 'test value';
        $default = 'default value';

        $this->config->expects($this->once())
            ->method('get')
            ->with($key, $default)
            ->willReturn($value);

        $result = $this->presenter->testGetConfig($key, $default);
        $this->assertEquals($value, $result);
    }

    public function testPresenterCanConvertToArray(): void
    {
        $result = $this->presenter->toArray();

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertIsInt($result['id']);
        $this->assertIsInt($result['status']);
        $this->assertIsBool($result['is_active']);
        $this->assertIsArray($result['settings']);
        $this->assertInstanceOf(\DateTime::class, $result['created_at']);
    }

    public function testPresenterCanConvertToJson(): void
    {
        $result = $this->presenter->toJson();
        $decoded = json_decode($result, true);

        $this->assertIsString($result);
        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey('password', $decoded);
        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals(1, $decoded['status']);
        $this->assertTrue($decoded['is_active']);
        $this->assertEquals(['key' => 'value'], $decoded['settings']);
    }

    public function testPresenterCanGetValue(): void
    {
        $this->assertEquals('Test', $this->presenter->get('name'));
        $this->assertEquals('default', $this->presenter->get('non_existent', 'default'));
    }

    public function testPresenterCanSetValue(): void
    {
        $this->presenter->set('name', 'Updated');
        $this->assertEquals('Updated', $this->presenter->get('name'));
    }

    public function testPresenterCanCheckIfKeyExists(): void
    {
        $this->assertTrue($this->presenter->has('name'));
        $this->assertFalse($this->presenter->has('non_existent'));
    }

    public function testPresenterCanGetAllData(): void
    {
        $result = $this->presenter->all();
        $this->assertEquals($this->testData, $result);
    }

    public function testPresenterCanGetOnlySpecifiedKeys(): void
    {
        $result = $this->presenter->only(['name', 'status']);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function testPresenterCanGetAllExceptSpecifiedKeys(): void
    {
        $result = $this->presenter->except(['name', 'status']);
        $this->assertArrayNotHasKey('name', $result);
        $this->assertArrayNotHasKey('status', $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function testPresenterCastsAttributes(): void
    {
        $result = $this->presenter->toArray();

        $this->assertIsInt($result['id']);
        $this->assertIsInt($result['status']);
        $this->assertIsBool($result['is_active']);
        $this->assertIsArray($result['settings']);
        $this->assertInstanceOf(\DateTime::class, $result['created_at']);
    }

    public function testPresenterHidesSpecifiedFields(): void
    {
        $result = $this->presenter->toArray();
        $this->assertArrayNotHasKey('password', $result);
    }
} 