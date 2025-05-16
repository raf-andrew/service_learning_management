# LMS Module Integration Patterns

## Overview
This document outlines various integration patterns for incorporating the LMS module into Laravel applications, focusing on data integration, authentication, and frontend integration.

## Integration Patterns

### 1. Direct Integration

#### Service Provider Registration
```php
// config/app.php
'providers' => [
    // ...
    LMS\Providers\LMSServiceProvider::class,
],

'aliases' => [
    // ...
    'LMS' => LMS\Facades\LMS::class,
]
```

#### Configuration
```php
// config/lms.php
return [
    'api_key' => env('LMS_API_KEY'),
    'tenant' => env('LMS_TENANT_ID'),
    'base_url' => env('LMS_API_URL'),
    
    'database' => [
        'connection' => env('LMS_DB_CONNECTION', 'lms'),
        'prefix' => env('LMS_DB_PREFIX', 'lms_'),
    ],
    
    'storage' => [
        'disk' => env('LMS_STORAGE_DISK', 'lms'),
        'path' => env('LMS_STORAGE_PATH', 'lms'),
    ],
];
```

### 2. API Integration

#### API Client Setup
```php
use LMS\Client\LMSClient;

$client = new LMSClient([
    'api_key' => config('lms.api_key'),
    'tenant' => config('lms.tenant'),
    'base_url' => config('lms.base_url')
]);

// Get courses
$courses = $client->courses()->list();

// Create a course
$course = $client->courses()->create([
    'title' => 'New Course',
    'description' => 'Course description'
]);
```

#### API Routes
```php
// routes/api.php
Route::prefix('lms')->group(function () {
    Route::get('courses', [LMSController::class, 'index']);
    Route::post('courses', [LMSController::class, 'store']);
    Route::get('courses/{id}', [LMSController::class, 'show']);
    Route::put('courses/{id}', [LMSController::class, 'update']);
    Route::delete('courses/{id}', [LMSController::class, 'destroy']);
});
```

### 3. Event Integration

#### Event Listeners
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    'LMS\Events\CourseCreated' => [
        'App\Listeners\HandleCourseCreated',
    ],
    'LMS\Events\CourseEnrolled' => [
        'App\Listeners\HandleCourseEnrolled',
    ],
];
```

#### Event Handling
```php
// app/Listeners/HandleCourseCreated.php
class HandleCourseCreated
{
    public function handle(CourseCreated $event)
    {
        // Sync course data
        Course::updateOrCreate(
            ['lms_id' => $event->course->id],
            $event->course->toArray()
        );
    }
}
```

### 4. Database Integration

#### Database Configuration
```php
// config/database.php
'connections' => [
    'lms' => [
        'driver' => 'mysql',
        'host' => env('LMS_DB_HOST', '127.0.0.1'),
        'port' => env('LMS_DB_PORT', '3306'),
        'database' => env('LMS_DB_DATABASE', 'lms'),
        'username' => env('LMS_DB_USERNAME', 'lms'),
        'password' => env('LMS_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => env('LMS_DB_PREFIX', 'lms_'),
    ],
];
```

#### Model Integration
```php
// app/Models/Course.php
class Course extends Model
{
    protected $connection = 'lms';
    
    protected $fillable = [
        'title',
        'description',
        'price',
        'status'
    ];
    
    public function syncWithLMS()
    {
        $lmsCourse = LMS::courses()->get($this->lms_id);
        
        $this->update([
            'title' => $lmsCourse->title,
            'description' => $lmsCourse->description,
            'price' => $lmsCourse->price,
            'status' => $lmsCourse->status
        ]);
    }
}
```

### 5. Frontend Integration

#### Vue Component Integration
```vue
<!-- resources/js/components/LMSCourseList.vue -->
<template>
  <div>
    <h2>LMS Courses</h2>
    <div v-for="course in courses" :key="course.id">
      <course-card :course="course" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useLMS } from '@lms/client'

const { courses, loading, error } = useLMS()

onMounted(async () => {
  await courses.list()
})
</script>
```

#### State Management
```typescript
// resources/js/stores/lms.ts
import { defineStore } from 'pinia'
import { useLMS } from '@lms/client'

export const useLMSStore = defineStore('lms', () => {
  const { courses, users, enrollments } = useLMS()
  
  return {
    courses,
    users,
    enrollments
  }
})
```

### 6. Authentication Integration

#### Authentication Middleware
```php
// app/Http/Middleware/LMSAuth.php
class LMSAuth
{
    public function handle($request, Closure $next)
    {
        if (!LMS::auth()->check()) {
            return redirect()->route('lms.login');
        }
        
        return $next($request);
    }
}
```

#### User Integration
```php
// app/Models/User.php
class User extends Authenticatable
{
    public function lmsUser()
    {
        return $this->hasOne(LMSUser::class);
    }
    
    public function syncWithLMS()
    {
        $lmsUser = LMS::users()->get($this->lmsUser->lms_id);
        
        $this->lmsUser->update([
            'name' => $lmsUser->name,
            'email' => $lmsUser->email
        ]);
    }
}
```

### 7. Cache Integration

#### Cache Configuration
```php
// config/cache.php
'stores' => [
    'lms' => [
        'driver' => 'redis',
        'connection' => 'lms',
        'prefix' => 'lms:',
    ],
],
```

#### Cache Usage
```php
use Illuminate\Support\Facades\Cache;

$courses = Cache::store('lms')->remember('courses', 3600, function () {
    return LMS::courses()->list();
});
```

## Security Considerations

### 1. API Security
- Implement rate limiting
- Validate API keys
- Use HTTPS
- Implement CORS policies

### 2. Data Security
- Encrypt sensitive data
- Implement row-level security
- Use prepared statements
- Regular security audits

### 3. Authentication Security
- Implement OAuth2
- Use JWT tokens
- Implement refresh tokens
- Session management

## Performance Optimization

### 1. Caching Strategy
```php
// app/Services/LMSCache.php
class LMSCache
{
    public function remember(string $key, int $ttl, Closure $callback)
    {
        return Cache::store('lms')
            ->tags(['lms'])
            ->remember($key, $ttl, $callback);
    }
}
```

### 2. Query Optimization
```php
// app/Models/Course.php
class Course extends Model
{
    public function scopePopular($query)
    {
        return $query->with(['enrollments', 'reviews'])
            ->orderBy('enrollments_count', 'desc')
            ->limit(10);
    }
}
```

## Monitoring and Logging

### 1. Logging Configuration
```php
// config/logging.php
'channels' => [
    'lms' => [
        'driver' => 'daily',
        'path' => storage_path('logs/lms.log'),
        'level' => 'debug',
    ],
],
```

### 2. Monitoring Setup
```php
// app/Services/LMSMonitor.php
class LMSMonitor
{
    public function trackRequest(string $endpoint, array $data)
    {
        Log::channel('lms')->info('API Request', [
            'endpoint' => $endpoint,
            'data' => $data
        ]);
        
        Metrics::increment('lms.api.requests');
    }
}
```

## Testing Strategy

### 1. Unit Tests
```php
// tests/Unit/LMS/ClientTest.php
class ClientTest extends TestCase
{
    public function test_course_creation()
    {
        $client = new LMSClient([
            'api_key' => 'test-key',
            'tenant' => 'test-tenant'
        ]);
        
        $course = $client->courses()->create([
            'title' => 'Test Course'
        ]);
        
        $this->assertNotNull($course->id);
    }
}
```

### 2. Integration Tests
```php
// tests/Integration/LMS/IntegrationTest.php
class IntegrationTest extends TestCase
{
    public function test_full_integration()
    {
        $this->actingAs($this->user);
        
        $response = $this->post('/lms/courses', [
            'title' => 'Integration Test Course'
        ]);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('courses', [
            'title' => 'Integration Test Course'
        ]);
    }
}
```

## Next Steps

1. [ ] Implement API client library
2. [ ] Create integration documentation
3. [ ] Set up testing infrastructure
4. [ ] Implement monitoring system
5. [ ] Create deployment guide
6. [ ] Document security best practices
7. [ ] Set up CI/CD pipeline 