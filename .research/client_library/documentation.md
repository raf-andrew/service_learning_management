# LMS Client Library Documentation

## Overview
This document provides comprehensive documentation for integrating with the Learning Management System (LMS) through its client library. The library provides a simple interface for interacting with the LMS API.

## Installation

### Composer
```bash
composer require your-org/lms-client
```

### NPM
```bash
npm install @your-org/lms-client
```

## Configuration

### PHP Client
```php
use YourOrg\LMS\Client;

$client = new Client([
    'api_key' => 'your-api-key',
    'tenant_id' => 'your-tenant-id',
    'base_url' => 'https://api.your-lms.com',
    'version' => 'v1'
]);
```

### JavaScript Client
```javascript
import { LMSClient } from '@your-org/lms-client';

const client = new LMSClient({
    apiKey: 'your-api-key',
    tenantId: 'your-tenant-id',
    baseUrl: 'https://api.your-lms.com',
    version: 'v1'
});
```

## Authentication

### API Key Authentication
```php
$client->setApiKey('your-api-key');
```

### JWT Authentication
```php
$client->setJwtToken('your-jwt-token');
```

## Core Features

### 1. User Management

#### Create User
```php
$user = $client->users()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret',
    'role' => 'student'
]);
```

#### Get User
```php
$user = $client->users()->find(1);
```

#### Update User
```php
$user = $client->users()->update(1, [
    'name' => 'John Smith'
]);
```

#### Delete User
```php
$client->users()->delete(1);
```

### 2. Course Management

#### List Courses
```php
$courses = $client->courses()->all([
    'page' => 1,
    'per_page' => 10,
    'category' => 'programming'
]);
```

#### Get Course
```php
$course = $client->courses()->find(1);
```

#### Create Course
```php
$course = $client->courses()->create([
    'title' => 'PHP Basics',
    'description' => 'Learn PHP from scratch',
    'price' => 99.99,
    'category_id' => 1
]);
```

#### Update Course
```php
$course = $client->courses()->update(1, [
    'price' => 89.99
]);
```

#### Delete Course
```php
$client->courses()->delete(1);
```

### 3. Enrollment Management

#### Enroll User
```php
$enrollment = $client->enrollments()->create([
    'user_id' => 1,
    'course_id' => 1
]);
```

#### Get Enrollment
```php
$enrollment = $client->enrollments()->find(1);
```

#### List User Enrollments
```php
$enrollments = $client->enrollments()->forUser(1);
```

#### Update Enrollment
```php
$enrollment = $client->enrollments()->update(1, [
    'status' => 'completed'
]);
```

### 4. Payment Processing

#### Create Payment
```php
$payment = $client->payments()->create([
    'user_id' => 1,
    'course_id' => 1,
    'amount' => 99.99,
    'payment_method' => 'stripe'
]);
```

#### Get Payment
```php
$payment = $client->payments()->find(1);
```

#### List User Payments
```php
$payments = $client->payments()->forUser(1);
```

### 5. Progress Tracking

#### Get Course Progress
```php
$progress = $client->progress()->forCourse(1, 1); // course_id, user_id
```

#### Update Progress
```php
$progress = $client->progress()->update(1, 1, [
    'completed_lessons' => [1, 2, 3],
    'current_lesson' => 4
]);
```

## Advanced Features

### 1. Webhooks

#### Register Webhook
```php
$webhook = $client->webhooks()->create([
    'url' => 'https://your-app.com/webhook',
    'events' => ['enrollment.created', 'payment.completed']
]);
```

#### List Webhooks
```php
$webhooks = $client->webhooks()->all();
```

#### Delete Webhook
```php
$client->webhooks()->delete(1);
```

### 2. Batch Operations

#### Batch Create Users
```php
$users = $client->users()->batchCreate([
    [
        'name' => 'User 1',
        'email' => 'user1@example.com'
    ],
    [
        'name' => 'User 2',
        'email' => 'user2@example.com'
    ]
]);
```

#### Batch Enroll Users
```php
$enrollments = $client->enrollments()->batchCreate([
    [
        'user_id' => 1,
        'course_id' => 1
    ],
    [
        'user_id' => 2,
        'course_id' => 1
    ]
]);
```

### 3. Search and Filtering

#### Search Courses
```php
$courses = $client->courses()->search([
    'query' => 'php',
    'category' => 'programming',
    'price_range' => [0, 100],
    'sort' => 'price_asc'
]);
```

#### Filter Users
```php
$users = $client->users()->filter([
    'role' => 'student',
    'status' => 'active',
    'created_after' => '2023-01-01'
]);
```

## Error Handling

### PHP
```php
try {
    $user = $client->users()->find(1);
} catch (\YourOrg\LMS\Exceptions\NotFoundException $e) {
    // Handle not found error
} catch (\YourOrg\LMS\Exceptions\ValidationException $e) {
    // Handle validation error
} catch (\YourOrg\LMS\Exceptions\ApiException $e) {
    // Handle other API errors
}
```

### JavaScript
```javascript
try {
    const user = await client.users.find(1);
} catch (error) {
    if (error instanceof NotFoundError) {
        // Handle not found error
    } else if (error instanceof ValidationError) {
        // Handle validation error
    } else if (error instanceof ApiError) {
        // Handle other API errors
    }
}
```

## Rate Limiting

### Check Rate Limits
```php
$limits = $client->rateLimits()->get();
```

### Reset Rate Limits
```php
$client->rateLimits()->reset();
```

## Best Practices

### 1. Error Handling
- Always implement proper error handling
- Use try-catch blocks
- Log errors appropriately
- Implement retry logic for transient failures

### 2. Rate Limiting
- Monitor rate limits
- Implement exponential backoff
- Cache responses when appropriate
- Batch operations when possible

### 3. Security
- Keep API keys secure
- Use HTTPS
- Validate input data
- Sanitize output data

### 4. Performance
- Cache responses
- Use pagination
- Implement request batching
- Monitor API usage

## Examples

### Complete Integration Example
```php
use YourOrg\LMS\Client;

// Initialize client
$client = new Client([
    'api_key' => 'your-api-key',
    'tenant_id' => 'your-tenant-id',
    'base_url' => 'https://api.your-lms.com'
]);

try {
    // Create user
    $user = $client->users()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret'
    ]);

    // Create course
    $course = $client->courses()->create([
        'title' => 'PHP Basics',
        'description' => 'Learn PHP from scratch',
        'price' => 99.99
    ]);

    // Enroll user
    $enrollment = $client->enrollments()->create([
        'user_id' => $user->id,
        'course_id' => $course->id
    ]);

    // Process payment
    $payment = $client->payments()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'amount' => $course->price,
        'payment_method' => 'stripe'
    ]);

} catch (\Exception $e) {
    // Handle errors
    error_log($e->getMessage());
}
```

## Support

For additional support:
- Documentation: https://docs.your-lms.com
- API Reference: https://api.your-lms.com/docs
- Support Email: support@your-lms.com
- GitHub Issues: https://github.com/your-org/lms-client/issues 