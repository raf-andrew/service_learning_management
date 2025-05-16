# Tenant Isolation Strategy

## Overview
This document outlines the strategy for implementing multi-tenancy in the LMS system, ensuring proper isolation between different organizations using the platform.

## Isolation Levels

### 1. Database Isolation

#### Schema-based Isolation
```sql
CREATE SCHEMA tenant_{id};

-- Create tables in tenant schema
CREATE TABLE tenant_{id}.users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE tenant_{id}.courses (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Connection Management
```php
namespace LMS\Database;

class TenantConnection
{
    private $connections = [];

    public function connect(string $tenantId): PDO
    {
        if (!isset($this->connections[$tenantId])) {
            $config = $this->getTenantConfig($tenantId);
            $this->connections[$tenantId] = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password']
            );
        }
        return $this->connections[$tenantId];
    }

    private function getTenantConfig(string $tenantId): array
    {
        return [
            'dsn' => "pgsql:host=localhost;dbname=lms;search_path=tenant_{$tenantId}",
            'username' => 'lms_user',
            'password' => 'secret'
        ];
    }
}
```

### 2. Storage Isolation

#### File Storage Structure
```
/storage
    /tenant_1
        /courses
            /course_1
                /lessons
                    lesson_1.mp4
                    lesson_2.pdf
        /users
            /avatars
                user_1.jpg
    /tenant_2
        /courses
            /course_1
                /lessons
                    lesson_1.mp4
```

#### Storage Service
```php
namespace LMS\Storage;

class TenantStorage
{
    private $filesystem;
    private $tenantId;

    public function __construct(Filesystem $filesystem, string $tenantId)
    {
        $this->filesystem = $filesystem;
        $this->tenantId = $tenantId;
    }

    public function store(string $path, $contents): string
    {
        $tenantPath = "tenant_{$this->tenantId}/{$path}";
        return $this->filesystem->put($tenantPath, $contents);
    }

    public function get(string $path)
    {
        $tenantPath = "tenant_{$this->tenantId}/{$path}";
        return $this->filesystem->get($tenantPath);
    }
}
```

### 3. Cache Isolation

#### Cache Key Strategy
```php
namespace LMS\Cache;

class TenantCache
{
    private $cache;
    private $tenantId;

    public function __construct(CacheInterface $cache, string $tenantId)
    {
        $this->cache = $cache;
        $this->tenantId = $tenantId;
    }

    public function get(string $key)
    {
        return $this->cache->get($this->getTenantKey($key));
    }

    public function set(string $key, $value, $ttl = null)
    {
        return $this->cache->set($this->getTenantKey($key), $value, $ttl);
    }

    private function getTenantKey(string $key): string
    {
        return "tenant_{$this->tenantId}:{$key}";
    }
}
```

### 4. API Isolation

#### Middleware
```php
namespace LMS\Http\Middleware;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant');
        
        if (!$tenantId) {
            throw new TenantNotFoundException();
        }

        if (!$this->isValidTenant($tenantId)) {
            throw new InvalidTenantException();
        }

        TenantContext::set($tenantId);
        
        return $next($request);
    }

    private function isValidTenant(string $tenantId): bool
    {
        return Tenant::where('id', $tenantId)
                    ->where('status', 'active')
                    ->exists();
    }
}
```

## Implementation

### 1. Tenant Context

```php
namespace LMS;

class TenantContext
{
    private static $tenantId;

    public static function set(string $tenantId)
    {
        self::$tenantId = $tenantId;
    }

    public static function get(): string
    {
        if (!self::$tenantId) {
            throw new TenantNotSetException();
        }
        return self::$tenantId;
    }

    public static function clear()
    {
        self::$tenantId = null;
    }
}
```

### 2. Service Provider

```php
namespace LMS\Providers;

class TenantServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TenantManager::class);

        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager(
                $app->make(TenantManager::class)
            );
        });

        $this->app->singleton(StorageManager::class, function ($app) {
            return new StorageManager(
                $app->make(TenantManager::class)
            );
        });
    }

    public function boot()
    {
        $this->app->make(DatabaseManager::class)->initialize();
    }
}
```

### 3. Model Traits

```php
namespace LMS\Traits;

trait TenantAware
{
    protected static function bootTenantAware()
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', TenantContext::get());
        });

        static::creating(function ($model) {
            $model->tenant_id = TenantContext::get();
        });
    }
}
```

## Security Considerations

### 1. Data Access Control
- Enforce tenant isolation at the database level
- Validate tenant access in middleware
- Implement row-level security
- Use prepared statements

### 2. Storage Security
- Isolate tenant storage
- Implement access controls
- Encrypt sensitive data
- Regular security audits

### 3. API Security
- Validate tenant headers
- Rate limiting per tenant
- Authentication per tenant
- Authorization checks

## Monitoring and Debugging

### 1. Logging
```php
namespace LMS\Logging;

class TenantLogger
{
    private $logger;
    private $tenantId;

    public function __construct(LoggerInterface $logger, string $tenantId)
    {
        $this->logger = $logger;
        $this->tenantId = $tenantId;
    }

    public function log($level, $message, array $context = [])
    {
        $context['tenant_id'] = $this->tenantId;
        $this->logger->log($level, $message, $context);
    }
}
```

### 2. Metrics
```php
namespace LMS\Metrics;

class TenantMetrics
{
    private $metrics;
    private $tenantId;

    public function __construct(MetricsInterface $metrics, string $tenantId)
    {
        $this->metrics = $metrics;
        $this->tenantId = $tenantId;
    }

    public function increment(string $metric, int $value = 1)
    {
        $this->metrics->increment(
            "tenant_{$this->tenantId}_{$metric}",
            $value
        );
    }
}
```

## Migration Strategy

### 1. Database Migration
```php
namespace LMS\Database\Migrations;

class CreateTenantSchema
{
    public function up()
    {
        $tenantId = TenantContext::get();
        
        DB::statement("CREATE SCHEMA IF NOT EXISTS tenant_{$tenantId}");
        
        Schema::create('tenant_' . $tenantId . '.users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
    }

    public function down()
    {
        $tenantId = TenantContext::get();
        DB::statement("DROP SCHEMA IF EXISTS tenant_{$tenantId} CASCADE");
    }
}
```

### 2. Data Migration
```php
namespace LMS\Database\Migrations;

class MigrateTenantData
{
    public function migrate(string $tenantId)
    {
        TenantContext::set($tenantId);
        
        DB::transaction(function () {
            $this->migrateUsers();
            $this->migrateCourses();
            $this->migrateContent();
        });
    }

    private function migrateUsers()
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                TenantUser::create($user->toArray());
            }
        });
    }
}
```

## Testing

### 1. Unit Tests
```php
namespace Tests\Unit;

class TenantTest extends TestCase
{
    public function testTenantIsolation()
    {
        TenantContext::set('tenant_1');
        
        $user1 = User::create([
            'name' => 'User 1'
        ]);

        TenantContext::set('tenant_2');
        
        $user2 = User::create([
            'name' => 'User 2'
        ]);

        $this->assertNotEquals(
            $user1->getConnection()->getDatabaseName(),
            $user2->getConnection()->getDatabaseName()
        );
    }
}
```

### 2. Integration Tests
```php
namespace Tests\Integration;

class TenantApiTest extends TestCase
{
    public function testTenantApiIsolation()
    {
        $response = $this->get('/api/users', [
            'X-Tenant' => 'tenant_1'
        ]);

        $response->assertStatus(200);
        
        $response = $this->get('/api/users', [
            'X-Tenant' => 'invalid_tenant'
        ]);

        $response->assertStatus(401);
    }
}
```

## Performance Optimization

### 1. Connection Pooling
```php
namespace LMS\Database;

class ConnectionPool
{
    private $pools = [];

    public function getConnection(string $tenantId): PDO
    {
        if (!isset($this->pools[$tenantId])) {
            $this->pools[$tenantId] = new Pool(function () use ($tenantId) {
                return $this->createConnection($tenantId);
            });
        }
        return $this->pools[$tenantId]->get();
    }
}
```

### 2. Cache Strategy
```php
namespace LMS\Cache;

class TenantCacheStrategy
{
    public function getCacheKey(string $key, string $tenantId): string
    {
        return sprintf(
            '%s:%s:%s',
            config('app.env'),
            $tenantId,
            $key
        );
    }
}
```

## Next Steps

1. Implement tenant provisioning system
2. Create tenant management interface
3. Set up monitoring and alerts
4. Implement backup strategy
5. Create tenant migration tools
6. Document operational procedures
7. Train support team 