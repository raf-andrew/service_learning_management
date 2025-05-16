# API Endpoints Documentation

## Overview
The Learning Management System provides a comprehensive REST API that enables integration with external systems and the Vue 3 frontend. The API follows REST principles and uses JWT for authentication.

## Authentication

### Login
```typescript
POST /api/auth/login
Content-Type: application/json

Request:
{
    "email": string,
    "password": string
}

Response:
{
    "status": boolean,
    "token": string,
    "user_id": number,
    "validity": number,
    "user_data": {
        "id": number,
        "first_name": string,
        "last_name": string,
        "email": string,
        "role": string,
        "is_instructor": boolean
    }
}
```

### Social Login
```typescript
POST /api/auth/social-login
Content-Type: application/json

Request:
{
    "provider": string,
    "access_token": string
}

Response:
{
    "status": boolean,
    "token": string,
    "user_data": object
}
```

## Course Management

### List Courses
```typescript
GET /api/courses
Authorization: Bearer <token>

Query Parameters:
- category_id?: number
- price?: "free" | "paid"
- level?: string
- language?: string
- rating?: number
- search?: string

Response:
{
    "status": boolean,
    "courses": [
        {
            "id": number,
            "title": string,
            "description": string,
            "price": number,
            "instructor": object,
            "thumbnail": string,
            "rating": number,
            "total_enrollments": number
        }
    ]
}
```

### Course Details
```typescript
GET /api/courses/{id}
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "course": {
        "id": number,
        "title": string,
        "description": string,
        "requirements": string[],
        "outcomes": string[],
        "price": number,
        "instructor": object,
        "sections": array,
        "total_lessons": number,
        "total_duration": number,
        "rating": number,
        "total_enrollments": number
    }
}
```

### Create Course
```typescript
POST /api/courses
Authorization: Bearer <token>

Request:
{
    "title": string,
    "description": string,
    "category_id": number,
    "price": number,
    "requirements": string[],
    "outcomes": string[],
    "language": string,
    "level": string
}

Response:
{
    "status": boolean,
    "course_id": number
}
```

## Section Management

### List Sections
```typescript
GET /api/courses/{course_id}/sections
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "sections": [
        {
            "id": number,
            "title": string,
            "order": number,
            "lessons": array
        }
    ]
}
```

### Create Section
```typescript
POST /api/courses/{course_id}/sections
Authorization: Bearer <token>

Request:
{
    "title": string,
    "order": number
}

Response:
{
    "status": boolean,
    "section_id": number
}
```

## Lesson Management

### List Lessons
```typescript
GET /api/sections/{section_id}/lessons
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "lessons": [
        {
            "id": number,
            "title": string,
            "duration": number,
            "video_url": string,
            "summary": string,
            "order": number
        }
    ]
}
```

### Create Lesson
```typescript
POST /api/sections/{section_id}/lessons
Authorization: Bearer <token>

Request:
{
    "title": string,
    "duration": number,
    "video_url": string,
    "summary": string,
    "order": number
}

Response:
{
    "status": boolean,
    "lesson_id": number
}
```

## Enrollment Management

### Enroll in Course
```typescript
POST /api/courses/{course_id}/enroll
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "enrollment_id": number
}
```

### List Enrollments
```typescript
GET /api/users/{user_id}/enrollments
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "enrollments": [
        {
            "id": number,
            "course": object,
            "progress": number,
            "enrolled_at": string,
            "completed_at": string
        }
    ]
}
```

## Payment Management

### Create Payment
```typescript
POST /api/payments
Authorization: Bearer <token>

Request:
{
    "course_id": number,
    "amount": number,
    "payment_method": string
}

Response:
{
    "status": boolean,
    "payment_id": number,
    "transaction_id": string
}
```

### List Payments
```typescript
GET /api/users/{user_id}/payments
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "payments": [
        {
            "id": number,
            "course": object,
            "amount": number,
            "status": string,
            "payment_date": string,
            "payment_method": string
        }
    ]
}
```

## Category Management

### List Categories
```typescript
GET /api/categories
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "categories": [
        {
            "id": number,
            "name": string,
            "slug": string,
            "parent_id": number,
            "thumbnail": string,
            "number_of_courses": number
        }
    ]
}
```

### Category Details
```typescript
GET /api/categories/{id}
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "category": {
        "id": number,
        "name": string,
        "slug": string,
        "parent_id": number,
        "thumbnail": string,
        "courses": array
    }
}
```

## User Management

### User Profile
```typescript
GET /api/users/{id}
Authorization: Bearer <token>

Response:
{
    "status": boolean,
    "user": {
        "id": number,
        "first_name": string,
        "last_name": string,
        "email": string,
        "biography": string,
        "role": string,
        "is_instructor": boolean,
        "social_links": object
    }
}
```

### Update Profile
```typescript
PUT /api/users/{id}
Authorization: Bearer <token>

Request:
{
    "first_name": string,
    "last_name": string,
    "biography": string,
    "social_links": object
}

Response:
{
    "status": boolean,
    "user": object
}
```

## Error Responses

### Authentication Error
```typescript
{
    "status": false,
    "message": string,
    "error_code": number
}
```

### Validation Error
```typescript
{
    "status": false,
    "errors": {
        "field": [
            "error message"
        ]
    }
}
```

### Server Error
```typescript
{
    "status": false,
    "message": "Internal server error",
    "error_code": 500
}
```

## Rate Limiting
- Rate limit: 1000 requests per hour per API key
- Rate limit headers included in response:
  - X-RateLimit-Limit
  - X-RateLimit-Remaining
  - X-RateLimit-Reset

## Pagination
All list endpoints support pagination with the following query parameters:
- page: Page number (default: 1)
- per_page: Items per page (default: 10, max: 100)

Response includes pagination metadata:
```typescript
{
    "status": boolean,
    "data": array,
    "pagination": {
        "total": number,
        "per_page": number,
        "current_page": number,
        "last_page": number
    }
}
``` 