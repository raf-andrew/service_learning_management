# Microservice API Architecture

## Overview

The Learning Management System (LMS) is designed to operate as a microservice that can be integrated into other Laravel platforms. The system uses API keys and tenant identification for multi-tenant support.

## Authentication

### API Key Authentication
```http
POST /api/auth/register-tenant
Content-Type: application/json

{
    "name": "string",
    "email": "string",
    "domain": "string"
}
```

Response:
```json
{
    "tenant_id": "string",
    "api_key": "string",
    "secret_key": "string"
}
```

### JWT Authentication
```http
POST /api/auth/login
Content-Type: application/json
X-Tenant-ID: string
X-API-Key: string

{
    "email": "string",
    "password": "string"
}
```

Response:
```json
{
    "token": "string",
    "user": {
        "id": "integer",
        "name": "string",
        "email": "string",
        "role": "string"
    }
}
```

## Tenant Management

### Get Tenant Details
```http
GET /api/tenant
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
```

### Update Tenant Settings
```http
PUT /api/tenant
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "settings": {
        "branding": {
            "logo": "string",
            "colors": {
                "primary": "string",
                "secondary": "string"
            }
        },
        "payment": {
            "gateways": ["string"],
            "currency": "string"
        },
        "email": {
            "from_name": "string",
            "from_address": "string"
        }
    }
}
```

## Course Management

### List Courses
```http
GET /api/courses
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
```

### Create Course
```http
POST /api/courses
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "description": "text",
    "price": "decimal",
    "category_id": "integer",
    "requirements": ["string"],
    "outcomes": ["string"]
}
```

### Update Course
```http
PUT /api/courses/{id}
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "description": "text",
    "price": "decimal",
    "status": "string"
}
```

## Content Management

### Create Section
```http
POST /api/courses/{course_id}/sections
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "order": "integer"
}
```

### Create Lesson
```http
POST /api/sections/{section_id}/lessons
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "string",
    "video_url": "string",
    "duration": "integer",
    "order": "integer"
}
```

## Enrollment

### Enroll User
```http
POST /api/courses/{course_id}/enroll
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "user_id": "integer",
    "payment_method": "string"
}
```

### Track Progress
```http
POST /api/lessons/{lesson_id}/complete
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
```

## Payment

### Process Payment
```http
POST /api/payments
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "course_id": "integer",
    "amount": "decimal",
    "payment_method": "string",
    "payment_details": {
        "card_number": "string",
        "expiry": "string",
        "cvv": "string"
    }
}
```

### Get Payment History
```http
GET /api/payments
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
```

## Instructor

### Get Sales Report
```http
GET /api/instructor/sales
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
```

### Request Payout
```http
POST /api/instructor/payouts
X-Tenant-ID: string
X-API-Key: string
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": "decimal",
    "payment_method": "string"
}
```

## Error Responses

### 400 Bad Request
```json
{
    "error": "Bad Request",
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

## Rate Limiting

- API requests are limited to 1000 requests per hour per API key
- Authentication endpoints are limited to 100 requests per hour per IP
- Payment endpoints are limited to 100 requests per hour per user

## Webhooks

### Payment Webhook
```http
POST /api/webhooks/payment
X-Tenant-ID: string
X-API-Key: string
X-Webhook-Signature: string

{
    "event": "string",
    "data": {
        "payment_id": "string",
        "status": "string",
        "amount": "decimal"
    }
}
```

### Enrollment Webhook
```http
POST /api/webhooks/enrollment
X-Tenant-ID: string
X-API-Key: string
X-Webhook-Signature: string

{
    "event": "string",
    "data": {
        "user_id": "integer",
        "course_id": "integer",
        "status": "string"
    }
}
```

## Integration Guide

### 1. Register Tenant
1. Register tenant to get API keys
2. Configure tenant settings
3. Set up webhook endpoints

### 2. Configure Authentication
1. Implement JWT authentication
2. Set up API key validation
3. Configure rate limiting

### 3. Implement API Client
1. Create API client class
2. Implement request/response handling
3. Set up error handling
4. Configure retry logic

### 4. Set up Webhooks
1. Configure webhook endpoints
2. Implement signature validation
3. Set up event handling
4. Configure retry mechanism

### 5. Monitor Usage
1. Track API usage
2. Monitor error rates
3. Set up alerts
4. Configure logging 