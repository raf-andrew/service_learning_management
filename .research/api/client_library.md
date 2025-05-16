# LMS Client Library

## Overview
The LMS Client Library provides a clean and type-safe way to interact with the LMS API. It handles authentication, request/response formatting, and provides a strongly-typed interface for all API operations.

## Installation

```bash
composer require lms/client
```

## Basic Usage

```php
use LMS\Client\LMSClient;

$client = new LMSClient([
    'api_key' => 'your-api-key',
    'tenant' => 'your-tenant-id',
    'base_url' => 'https://api.lms.example.com'
]);

// Get courses
$courses = $client->courses()->list();

// Create a course
$course = $client->courses()->create([
    'title' => 'New Course',
    'description' => 'Course description'
]);
```

## Architecture

### Core Components

#### Client
```php
namespace LMS\Client;

class LMSClient
{
    private $config;
    private $httpClient;
    private $services = [];

    public function __construct(array $config)
    {
        $this->config = new Configuration($config);
        $this->httpClient = new HttpClient($this->config);
    }

    public function courses(): CourseService
    {
        return $this->getService(CourseService::class);
    }

    public function users(): UserService
    {
        return $this->getService(UserService::class);
    }

    private function getService(string $class)
    {
        if (!isset($this->services[$class])) {
            $this->services[$class] = new $class($this->httpClient);
        }
        return $this->services[$class];
    }
}
```

#### Configuration
```php
namespace LMS\Client;

class Configuration
{
    private $apiKey;
    private $tenant;
    private $baseUrl;
    private $timeout = 30;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->tenant = $config['tenant'];
        $this->baseUrl = $config['base_url'];
        $this->timeout = $config['timeout'] ?? 30;
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'X-Tenant' => $this->tenant,
            'Content-Type' => 'application/json'
        ];
    }
}
```

#### HTTP Client
```php
namespace LMS\Client;

class HttpClient
{
    private $config;
    private $client;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $config->getBaseUrl(),
            'timeout' => $config->getTimeout(),
            'headers' => $config->getHeaders()
        ]);
    }

    public function request(string $method, string $path, array $options = [])
    {
        try {
            $response = $this->client->request($method, $path, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new LMSException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
```

### Services

#### Base Service
```php
namespace LMS\Client\Services;

abstract class BaseService
{
    protected $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    protected function get(string $path, array $query = [])
    {
        return $this->client->request('GET', $path, ['query' => $query]);
    }

    protected function post(string $path, array $data = [])
    {
        return $this->client->request('POST', $path, ['json' => $data]);
    }

    protected function put(string $path, array $data = [])
    {
        return $this->client->request('PUT', $path, ['json' => $data]);
    }

    protected function delete(string $path)
    {
        return $this->client->request('DELETE', $path);
    }
}
```

#### Course Service
```php
namespace LMS\Client\Services;

class CourseService extends BaseService
{
    public function list(array $params = [])
    {
        return $this->get('/courses', $params);
    }

    public function create(array $data)
    {
        return $this->post('/courses', $data);
    }

    public function get(string $id)
    {
        return $this->get("/courses/{$id}");
    }

    public function update(string $id, array $data)
    {
        return $this->put("/courses/{$id}", $data);
    }

    public function delete(string $id)
    {
        return $this->delete("/courses/{$id}");
    }
}
```

### Models

#### Course Model
```php
namespace LMS\Client\Models;

class Course
{
    public string $id;
    public string $title;
    public string $description;
    public float $price;
    public bool $isPublished;
    public array $sections;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->price = $data['price'];
        $this->isPublished = $data['is_published'];
        $this->sections = array_map(
            fn($section) => new Section($section),
            $data['sections']
        );
        $this->createdAt = $data['created_at'];
        $this->updatedAt = $data['updated_at'];
    }
}
```

## Error Handling

### Custom Exceptions
```php
namespace LMS\Client\Exceptions;

class LMSException extends \Exception
{
    private $errorType;
    private $errorDetails;

    public function __construct(
        string $message,
        int $code = 0,
        \Throwable $previous = null,
        string $errorType = null,
        array $errorDetails = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->errorDetails = $errorDetails;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }
}
```

## Events

### Event System
```php
namespace LMS\Client\Events;

class EventDispatcher
{
    private $listeners = [];

    public function addListener(string $event, callable $listener)
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $data = [])
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener($data);
            }
        }
    }
}
```

## Usage Examples

### Course Management
```php
// List courses with pagination
$courses = $client->courses()->list([
    'page' => 1,
    'per_page' => 20,
    'status' => 'published'
]);

// Create a course
$course = $client->courses()->create([
    'title' => 'New Course',
    'description' => 'Course description',
    'price' => 99.99,
    'sections' => [
        [
            'title' => 'Introduction',
            'order' => 1
        ]
    ]
]);

// Update a course
$client->courses()->update($course['id'], [
    'title' => 'Updated Title'
]);

// Delete a course
$client->courses()->delete($course['id']);
```

### User Management
```php
// Get user profile
$profile = $client->users()->profile();

// Update user profile
$client->users()->update([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// List enrolled courses
$enrollments = $client->users()->enrollments();
```

### Error Handling
```php
try {
    $course = $client->courses()->get('invalid-id');
} catch (LMSException $e) {
    if ($e->getCode() === 404) {
        echo "Course not found";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
```

## Testing

### Unit Tests
```php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LMS\Client\LMSClient;

class CourseServiceTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new LMSClient([
            'api_key' => 'test-key',
            'tenant' => 'test-tenant',
            'base_url' => 'http://api.test'
        ]);
    }

    public function testCourseCreation()
    {
        $course = $this->client->courses()->create([
            'title' => 'Test Course'
        ]);

        $this->assertNotNull($course['id']);
        $this->assertEquals('Test Course', $course['title']);
    }
}
```

### Integration Tests
```php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use LMS\Client\LMSClient;

class CourseIntegrationTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new LMSClient([
            'api_key' => getenv('LMS_API_KEY'),
            'tenant' => getenv('LMS_TENANT'),
            'base_url' => getenv('LMS_API_URL')
        ]);
    }

    public function testFullCourseLifecycle()
    {
        // Create
        $course = $this->client->courses()->create([
            'title' => 'Integration Test Course'
        ]);

        // Verify
        $fetched = $this->client->courses()->get($course['id']);
        $this->assertEquals($course['id'], $fetched['id']);

        // Update
        $updated = $this->client->courses()->update($course['id'], [
            'title' => 'Updated Title'
        ]);
        $this->assertEquals('Updated Title', $updated['title']);

        // Delete
        $this->client->courses()->delete($course['id']);

        // Verify deletion
        $this->expectException(LMSException::class);
        $this->client->courses()->get($course['id']);
    }
}
```

## Next Steps

1. [ ] Implement remaining API endpoints
2. [ ] Add response type hints
3. [ ] Implement caching layer
4. [ ] Add retry mechanism
5. [ ] Implement rate limiting
6. [ ] Add logging system
7. [ ] Create documentation site 