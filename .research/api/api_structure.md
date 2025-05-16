# API Structure Documentation

## Overview

The LMS platform implements a RESTful API using CodeIgniter's REST_Controller library. The API provides endpoints for course management, user authentication, content delivery, and various LMS functionalities.

## Authentication

### Token-Based Authentication
- Uses JWT (JSON Web Tokens) for API authentication
- Token handling is managed by `TokenHandler` class
- Tokens contain encoded user information and are validated on protected routes

### Authentication Flow
1. User logs in via `/api/login`
2. System validates credentials and generates JWT token
3. Token is required for subsequent authenticated requests
4. Token validation occurs in protected routes

## Core API Components

### Base Structure
- Extends `REST_Controller`
- Implements standard HTTP methods (GET, POST, PUT, DELETE)
- Standardized response format
- Built-in error handling and status codes

### Main Controllers

#### API Controller (`Api.php`)
Primary controller handling API requests with endpoints for:

1. Course Management
   - `top_courses_get()`: Fetch featured courses
   - `course_object_by_id_get()`: Get course details
   - `my_courses_get()`: User's enrolled courses
   - `section_wise_lessons_get()`: Course content structure

2. User Management
   - `login_get()`: User authentication
   - `signup_post()`: User registration
   - `userdata_get()`: User profile data
   - `update_userdata_post()`: Profile updates

3. Learning Progress
   - `save_course_progress_get()`: Track course completion
   - `submit_quiz_post()`: Handle quiz submissions
   - `lesson_details_get()`: Access lesson content

4. Content Access
   - `categories_get()`: Course categories
   - `bundle_courses_get()`: Course bundle access
   - `forum_questions_get()`: Discussion forum access

#### User Controller (`User.php`)
Handles user-specific operations:

1. Course Management
   - `courses()`: Instructor course management
   - `course_actions()`: Course CRUD operations
   - `sections()`: Course section management
   - `lessons()`: Lesson management

2. Instructor Features
   - `become_an_instructor()`: Instructor registration
   - `payout_settings()`: Payment preferences
   - `sales_report()`: Course sales analytics

3. Learning Tools
   - `quizes()`: Quiz management
   - `student_academic_progress()`: Progress tracking
   - `resource_files()`: Course materials

## Response Format

All API responses follow a standardized format:

\`\`\`json
{
    "status": true|false,
    "message": "Response message",
    "data": {
        // Response data
    }
}
\`\`\`

## Error Handling

The API implements comprehensive error handling:

- HTTP status codes for different scenarios
- Detailed error messages
- Validation error responses
- Rate limiting protection

## Security Features

1. Token Validation
2. XSS Protection
3. CORS Support
4. Rate Limiting
5. Input Validation

## API Versioning

Current version is implemented directly in the routes. Future versions should implement:

- URL-based versioning (e.g., /api/v1/)
- Version header support
- Backward compatibility handling

## Integration Guidelines

1. Authentication:
   - Obtain JWT token via login endpoint
   - Include token in Authorization header
   - Handle token refresh and expiration

2. Error Handling:
   - Implement proper error catching
   - Handle different HTTP status codes
   - Validate response format

3. Rate Limiting:
   - Implement proper request throttling
   - Handle rate limit responses
   - Cache frequently accessed data

## Migration Considerations

When migrating to Laravel:

1. Maintain similar endpoint structure
2. Implement Laravel Sanctum for authentication
3. Use Laravel's built-in rate limiting
4. Implement API versioning from start
5. Use Laravel's resource controllers
6. Implement comprehensive API documentation using OpenAPI/Swagger 