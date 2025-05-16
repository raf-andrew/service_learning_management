# API Design Documentation

## Overview
The Learning Management System (LMS) API is designed to be RESTful, secure, and scalable. It follows industry best practices and provides comprehensive documentation for integration.

## Authentication

### JWT Authentication
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

Response:
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "student"
    }
}
```

### API Key Authentication
```http
GET /api/courses
Authorization: Bearer {api_key}
X-Tenant-ID: {tenant_id}
```

## Rate Limiting
- 60 requests per minute per IP
- 1000 requests per hour per API key
- Custom limits for specific endpoints

## Endpoints

### User Management

#### Register User
```http
POST /api/users
Content-Type: application/json

{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password123",
    "role": "student"
}
```

#### Get User Profile
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

#### Update User Profile
```http
PUT /api/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "John Doe Updated",
    "email": "updated@example.com"
}
```

### Course Management

#### List Courses
```http
GET /api/courses
Authorization: Bearer {token}
```

#### Get Course Details
```http
GET /api/courses/{id}
Authorization: Bearer {token}
```

#### Create Course
```http
POST /api/courses
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Course Title",
    "description": "Course Description",
    "price": 99.99,
    "category_id": 1
}
```

#### Update Course
```http
PUT /api/courses/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Updated Title",
    "description": "Updated Description"
}
```

### Content Management

#### Upload Content
```http
POST /api/courses/{id}/content
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "file": "file_data",
    "type": "video",
    "title": "Content Title"
}
```

#### Get Content
```http
GET /api/courses/{id}/content/{content_id}
Authorization: Bearer {token}
```

### Enrollment Management

#### Enroll in Course
```http
POST /api/courses/{id}/enroll
Authorization: Bearer {token}
```

#### Get Enrollment Status
```http
GET /api/courses/{id}/enrollment
Authorization: Bearer {token}
```

### Payment Processing

#### Initiate Payment
```http
POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
    "course_id": 1,
    "payment_method": "stripe",
    "amount": 99.99
}
```

#### Get Payment Status
```http
GET /api/payments/{id}
Authorization: Bearer {token}
```

## Error Handling

### Error Response Format
```json
{
    "error": {
        "code": "ERROR_CODE",
        "message": "Error message description",
        "details": {
            "field": "Specific error details"
        }
    }
}
```

### Common Error Codes
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 429: Too Many Requests
- 500: Internal Server Error

## Pagination

### Request Format
```http
GET /api/courses?page=1&per_page=10
```

### Response Format
```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 100,
        "total_pages": 10
    },
    "links": {
        "first": "/api/courses?page=1",
        "last": "/api/courses?page=10",
        "prev": null,
        "next": "/api/courses?page=2"
    }
}
```

## Filtering and Sorting

### Filtering
```http
GET /api/courses?filter[category]=programming&filter[price][gt]=50
```

### Sorting
```http
GET /api/courses?sort=-price,created_at
```

## Webhooks

### Available Events
- course.created
- course.updated
- enrollment.created
- payment.completed
- user.created
- user.updated

### Webhook Format
```json
{
    "event": "course.created",
    "data": {
        "id": 1,
        "title": "Course Title",
        "created_at": "2023-01-01T00:00:00Z"
    },
    "timestamp": "2023-01-01T00:00:00Z"
}
```

## SDK Support

### Available SDKs
- PHP
- JavaScript
- Python
- Java

### Example Usage (JavaScript)
```javascript
import { LMSClient } from '@lms/sdk';

const client = new LMSClient({
    apiKey: 'your-api-key',
    tenantId: 'your-tenant-id'
});

// List courses
const courses = await client.courses.list();

// Enroll in course
await client.courses.enroll(courseId);
```

## Versioning

### Version Header
```http
GET /api/courses
Accept: application/vnd.lms.v1+json
```

### Version in URL
```http
GET /api/v1/courses
```

## Security

### SSL/TLS
- All API requests must use HTTPS
- TLS 1.2 or higher required

### Data Encryption
- Sensitive data encrypted at rest
- All communications encrypted in transit

### Access Control
- Role-based access control
- Resource-level permissions
- API key scopes

## Monitoring and Analytics

### Available Metrics
- Request volume
- Response times
- Error rates
- API usage by endpoint
- User activity

### Integration
- Prometheus metrics
- Grafana dashboards
- Webhook notifications
- Email alerts 