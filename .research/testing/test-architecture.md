# Testing Infrastructure

## Overview

This document outlines the testing strategy and infrastructure for the Learning Management System, covering unit tests, integration tests, and end-to-end tests.

## Testing Stack

### Backend Testing
- PHPUnit for unit and integration tests
- Laravel Dusk for browser automation
- Mockery for mocking dependencies
- PHPStan for static analysis
- Infection for mutation testing

### Frontend Testing
- Vitest for unit and integration tests
- Cypress for E2E testing
- Vue Test Utils for component testing
- Jest for JavaScript testing
- ESLint for code quality

## Test Structure

### Backend Tests
```
tests/
├── Unit/
│   ├── Models/
│   ├── Services/
│   ├── Repositories/
│   └── Helpers/
├── Feature/
│   ├── Authentication/
│   ├── Courses/
│   ├── Payments/
│   └── Users/
├── Integration/
│   ├── API/
│   ├── Database/
│   └── External/
└── Browser/
    ├── Authentication/
    ├── Courses/
    └── Admin/
```

### Frontend Tests
```
tests/
├── unit/
│   ├── components/
│   ├── stores/
│   ├── composables/
│   └── utils/
├── integration/
│   ├── pages/
│   ├── flows/
│   └── api/
└── e2e/
    ├── auth/
    ├── courses/
    └── admin/
```

## Test Types and Examples

### Backend Unit Tests

```php
// tests/Unit/Models/CourseTest.php
namespace Tests\Unit\Models;

use App\Models\Course;
use Tests\TestCase;

class CourseTest extends TestCase
{
    public function test_course_can_be_created()
    {
        $course = Course::factory()->create([
            'title' => 'Test Course',
            'price' => 99.99
        ]);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->title);
        $this->assertEquals(99.99, $course->price);
    }

    public function test_course_has_instructor()
    {
        $course = Course::factory()->create();
        $this->assertInstanceOf(User::class, $course->instructor);
    }
}
```

### Backend Feature Tests

```php
// tests/Feature/Courses/CourseEnrollmentTest.php
namespace Tests\Feature\Courses;

use App\Models\User;
use App\Models\Course;
use Tests\TestCase;

class CourseEnrollmentTest extends TestCase
{
    public function test_user_can_enroll_in_course()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/courses/{$course->id}/enroll");

        $response->assertStatus(200);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);
    }
}
```

### Frontend Unit Tests

```typescript
// tests/unit/components/CourseCard.spec.ts
import { mount } from '@vue/test-utils'
import CourseCard from '@/components/CourseCard.vue'
import { Course } from '@/types'

describe('CourseCard', () => {
  it('renders course information correctly', () => {
    const course: Course = {
      id: '1',
      title: 'Test Course',
      description: 'Test Description',
      price: 99.99,
      instructor: {
        id: '1',
        name: 'Test Instructor'
      }
    }

    const wrapper = mount(CourseCard, {
      props: { course }
    })

    expect(wrapper.text()).toContain('Test Course')
    expect(wrapper.text()).toContain('Test Description')
    expect(wrapper.text()).toContain('$99.99')
  })
})
```

### Frontend Store Tests

```typescript
// tests/unit/stores/course.spec.ts
import { setActivePinia, createPinia } from 'pinia'
import { useCourseStore } from '@/stores/course'
import { Course } from '@/types'

describe('Course Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('fetches courses successfully', async () => {
    const store = useCourseStore()
    const mockCourses: Course[] = [
      {
        id: '1',
        title: 'Test Course',
        description: 'Test Description',
        price: 99.99
      }
    ]

    // Mock API call
    vi.spyOn(store, 'fetchCourses').mockResolvedValue(mockCourses)

    await store.fetchCourses()

    expect(store.courses).toEqual(mockCourses)
    expect(store.loading).toBe(false)
  })
})
```

### E2E Tests

```typescript
// tests/e2e/courses/enrollment.cy.ts
describe('Course Enrollment', () => {
  beforeEach(() => {
    cy.login('test@example.com', 'password')
  })

  it('successfully enrolls in a course', () => {
    cy.visit('/courses/1')
    cy.get('[data-test="enroll-button"]').click()
    cy.get('[data-test="payment-form"]').should('be.visible')
    cy.get('[data-test="card-number"]').type('4242424242424242')
    cy.get('[data-test="expiry"]').type('12/25')
    cy.get('[data-test="cvc"]').type('123')
    cy.get('[data-test="submit-payment"]').click()
    cy.url().should('include', '/courses/1/lessons')
  })
})
```

## CI/CD Integration

### GitHub Actions Workflow

```yaml
name: Test Suite

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: secret
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run PHPUnit Tests
        run: php artisan test
        
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse
        
      - name: Run Infection
        run: vendor/bin/infection --min-msi=80 --min-covered-msi=80
```

## Test Coverage Requirements

### Backend
- Minimum 80% code coverage
- All critical paths must be tested
- All API endpoints must have tests
- All database operations must be tested
- All external service integrations must be mocked

### Frontend
- Minimum 80% code coverage
- All components must have unit tests
- All store actions must be tested
- All API integrations must be tested
- All user flows must have E2E tests

## Performance Testing

### Backend Performance Tests
- API response time < 200ms
- Database query time < 50ms
- Concurrent user support > 1000
- Memory usage < 256MB per request

### Frontend Performance Tests
- First contentful paint < 1.5s
- Time to interactive < 3s
- Bundle size < 500KB
- Lighthouse score > 90

## Monitoring and Reporting

### Test Results
- JUnit XML reports
- HTML coverage reports
- Test execution time tracking
- Failure analysis and trends

### Performance Metrics
- Response time tracking
- Memory usage monitoring
- Database query analysis
- Frontend performance metrics

## Next Steps
1. Set up testing infrastructure
2. Create initial test suites
3. Implement CI/CD pipeline
4. Set up monitoring and reporting
5. Establish performance benchmarks 