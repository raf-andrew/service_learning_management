# Testing Strategy

## Overview
This document outlines the testing strategy for the Learning Management System, covering unit tests, integration tests, and end-to-end tests.

## Test Types

### 1. Unit Tests

#### PHPUnit Tests
```php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function test_user_creation()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }
}
```

#### Vue Component Tests
```javascript
import { mount } from '@vue/test-utils'
import CourseCard from '@/components/CourseCard.vue'

describe('CourseCard', () => {
  it('renders course title and description', () => {
    const course = {
      title: 'Test Course',
      description: 'Test Description'
    }
    
    const wrapper = mount(CourseCard, {
      props: { course }
    })
    
    expect(wrapper.text()).toContain('Test Course')
    expect(wrapper.text()).toContain('Test Description')
  })
})
```

### 2. Integration Tests

#### API Tests
```php
namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_creation()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/courses', [
                'title' => 'Test Course',
                'description' => 'Test Description'
            ]);
            
        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Test Course',
                'description' => 'Test Description'
            ]);
    }
}
```

#### Database Tests
```php
namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_enrollment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        $enrollment = $user->enroll($course);
        
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);
    }
}
```

### 3. End-to-End Tests

#### Laravel Dusk Tests
```php
namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CourseEnrollmentTest extends DuskTestCase
{
    public function test_course_enrollment_flow()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'user@example.com')
                ->type('password', 'password')
                ->press('Login')
                ->visit('/courses/1')
                ->press('Enroll Now')
                ->assertSee('Enrollment Successful');
        });
    }
}
```

## Test Organization

### Directory Structure
```
tests/
├── Unit/
│   ├── Models/
│   ├── Services/
│   └── Components/
├── Integration/
│   ├── Api/
│   ├── Database/
│   └── Services/
├── Feature/
│   ├── Authentication/
│   ├── Courses/
│   └── Payments/
└── Browser/
    ├── Authentication/
    ├── Courses/
    └── Payments/
```

## Test Coverage

### Required Coverage
- Models: 90%
- Controllers: 85%
- Services: 90%
- Components: 80%
- API Endpoints: 85%

### Coverage Reports
```bash
# Generate coverage report
php artisan test --coverage-html coverage/

# Check coverage
php artisan test --coverage-text
```

## Test Data

### Factories
```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 0, 1000),
            'instructor_id' => User::factory(),
        ];
    }
}
```

### Seeders
```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            CourseSeeder::class,
            EnrollmentSeeder::class,
        ]);
    }
}
```

## Continuous Integration

### GitHub Actions
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
        coverage: xdebug
        
    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
        
    - name: Copy Environment
      run: cp .env.example .env
        
    - name: Generate Key
      run: php artisan key:generate
        
    - name: Run Tests
      run: php artisan test --coverage-text
```

## Performance Testing

### Load Testing
```php
namespace Tests\Performance;

use Tests\TestCase;

class CoursePerformanceTest extends TestCase
{
    public function test_course_list_performance()
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->get('/api/courses');
        }
        
        $duration = microtime(true) - $start;
        $this->assertLessThan(5, $duration);
    }
}
```

## Security Testing

### Authentication Tests
```php
namespace Tests\Security;

use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_brute_force_protection()
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'user@example.com',
                'password' => 'wrongpassword'
            ]);
        }
        
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'correctpassword'
        ]);
        
        $response->assertStatus(429);
    }
}
```

## Next Steps

1. [ ] Set up test environment
2. [ ] Create test database
3. [ ] Implement factories
4. [ ] Write unit tests
5. [ ] Write integration tests
6. [ ] Write end-to-end tests
7. [ ] Set up CI/CD
8. [ ] Implement coverage reporting
9. [ ] Create test documentation
10. [ ] Train team on testing 