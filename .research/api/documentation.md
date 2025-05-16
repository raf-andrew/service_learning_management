# API Documentation

## Authentication

### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "string",
    "password": "string"
}
```

### Register
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "string",
    "email": "string",
    "password": "string",
    "role": "string"
}
```

### Forgot Password
```http
POST /api/auth/forgot-password
Content-Type: application/json

{
    "email": "string"
}
```

## User Management

### Get User Profile
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

### Update User Profile
```http
PUT /api/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "string",
    "email": "string",
    "profile_photo": "string"
}
```

### Change Password
```http
POST /api/users/{id}/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "string",
    "new_password": "string"
}
```

## Course Management

### List Courses
```http
GET /api/courses
Authorization: Bearer {token}
```

### Get Course Details
```http
GET /api/courses/{id}
Authorization: Bearer {token}
```

### Create Course
```http
POST /api/courses
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "description": "string",
    "price": "number",
    "requirements": "string",
    "outcomes": "string"
}
```

### Update Course
```http
PUT /api/courses/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "description": "string",
    "price": "number",
    "requirements": "string",
    "outcomes": "string"
}
```

### Delete Course
```http
DELETE /api/courses/{id}
Authorization: Bearer {token}
```

## Content Management

### List Sections
```http
GET /api/courses/{course_id}/sections
Authorization: Bearer {token}
```

### Create Section
```http
POST /api/courses/{course_id}/sections
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "order": "number"
}
```

### Update Section
```http
PUT /api/sections/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "order": "number"
}
```

### Delete Section
```http
DELETE /api/sections/{id}
Authorization: Bearer {token}
```

### List Lessons
```http
GET /api/sections/{section_id}/lessons
Authorization: Bearer {token}
```

### Create Lesson
```http
POST /api/sections/{section_id}/lessons
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "video_url": "string",
    "duration": "number",
    "order": "number"
}
```

### Update Lesson
```http
PUT /api/lessons/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "video_url": "string",
    "duration": "number",
    "order": "number"
}
```

### Delete Lesson
```http
DELETE /api/lessons/{id}
Authorization: Bearer {token}
```

## Enrollment

### Enroll in Course
```http
POST /api/courses/{course_id}/enroll
Authorization: Bearer {token}
```

### Get Enrollment Status
```http
GET /api/courses/{course_id}/enrollment
Authorization: Bearer {token}
```

### Complete Lesson
```http
POST /api/lessons/{lesson_id}/complete
Authorization: Bearer {token}
```

## Payment

### Process Payment
```http
POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
    "course_id": "number",
    "amount": "number",
    "payment_method": "string"
}
```

### Get Payment History
```http
GET /api/payments
Authorization: Bearer {token}
```

### Get Payment Details
```http
GET /api/payments/{id}
Authorization: Bearer {token}
```

## Instructor

### Get Sales Report
```http
GET /api/instructor/sales
Authorization: Bearer {token}
```

### Get Payout History
```http
GET /api/instructor/payouts
Authorization: Bearer {token}
```

### Request Payout
```http
POST /api/instructor/payouts
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": "number",
    "payment_method": "string"
}
```

## Error Responses

### 400 Bad Request
```json
{
    "error": "string",
    "message": "string",
    "errors": {
        "field": ["string"]
    }
}
```

### 401 Unauthorized
```json
{
    "error": "Unauthorized",
    "message": "string"
}
```

### 403 Forbidden
```json
{
    "error": "Forbidden",
    "message": "string"
}
```

### 404 Not Found
```json
{
    "error": "Not Found",
    "message": "string"
}
```

### 429 Too Many Requests
```json
{
    "error": "Too Many Requests",
    "message": "string",
    "retry_after": "number"
}
```

### 500 Internal Server Error
```json
{
    "error": "Internal Server Error",
    "message": "string"
}
``` 