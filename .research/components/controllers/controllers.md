# Core Controllers Documentation

## Overview
The LMS platform implements a set of core controllers that handle different aspects of the application's functionality. Each controller extends CodeIgniter's `CI_Controller` class and follows the MVC pattern.

## Controller Architecture

### Base Controller Features
- Session management and authentication
- Database connectivity
- Timezone configuration
- Cache control headers
- User role verification
- Model loading

## Core Controllers

### 1. User Controller
The User controller manages instructor and student functionalities.

#### Key Responsibilities:
- Course management for instructors
- Profile management
- Course progress tracking
- Quiz management
- Payment and payout handling
- Blog management
- Academic progress tracking

#### Notable Methods:
- `dashboard()`: Handles user dashboard display
- `courses()`: Manages course listing and filtering
- `course_actions()`: Handles course status changes
- `payout_settings()`: Manages instructor payout preferences
- `quiz_questions()`: Handles quiz creation and management
- `student_academic_progress()`: Tracks student progress
- `resource_files()`: Manages course resources

### 2. Admin Controller
The Admin controller handles administrative functions and system management.

#### Key Responsibilities:
- User management
- Course approval
- System settings
- Payment settings
- Category management
- Instructor management

#### Notable Methods:
- `dashboard()`: Admin dashboard display
- `categories()`: Category CRUD operations
- `instructors()`: Instructor management
- `users()`: User management operations
- `system_settings()`: System configuration

### 3. API Controller
The API controller provides RESTful endpoints for mobile and web clients.

#### Key Responsibilities:
- Authentication and token management
- Course data access
- User data management
- Payment processing
- Progress tracking
- File management

#### Notable Methods:
- `login_get()`: Handles user authentication
- `top_courses_get()`: Retrieves featured courses
- `course_details_by_id_get()`: Gets detailed course information
- `submit_quiz_post()`: Handles quiz submissions
- `save_course_progress_get()`: Tracks learning progress
- `system_settings_get()`: Retrieves system configuration

### 4. Payment Controller
Manages payment processing and financial transactions.

#### Key Responsibilities:
- Payment gateway integration
- Transaction processing
- Invoice generation
- Refund handling
- Payout processing

#### Notable Methods:
- `success_course_payment()`: Handles successful payments
- `success_instructor_payment()`: Processes instructor payouts
- `invoice()`: Generates payment invoices
- `payout_report()`: Generates financial reports

## Authentication Flow

1. User authentication is handled through:
   - Session-based authentication for web interface
   - Token-based authentication for API
   - Role-based access control

2. Token Management:
   - Generated upon successful login
   - Validated on each API request
   - Includes user role and permissions
   - Handles multi-device login scenarios

## Security Features

1. Input Validation:
   - Form validation
   - CSRF protection
   - XSS filtering

2. Access Control:
   - Role-based permissions
   - Method-level authorization
   - Resource ownership verification

## Integration Points

1. Payment Gateways:
   - Stripe integration
   - PayPal integration
   - Custom payment gateway support

2. External Services:
   - Email service integration
   - Storage service integration
   - Video streaming service integration

## Error Handling

1. Error Responses:
   - Standardized error format
   - HTTP status codes
   - Detailed error messages

2. Logging:
   - Activity logging
   - Error logging
   - Security event logging

## Best Practices

1. Code Organization:
   - Consistent method naming
   - Proper error handling
   - Input validation
   - Response formatting

2. Security:
   - Authentication checks
   - Authorization validation
   - Data sanitization
   - CSRF protection

3. Performance:
   - Query optimization
   - Caching implementation
   - Response compression
   - Resource optimization

## Migration Considerations

1. Laravel Migration:
   - Controller restructuring
   - Middleware implementation
   - Route definition
   - Authentication adaptation

2. API Versioning:
   - Version control
   - Backward compatibility
   - Documentation updates
   - Client notification

3. Testing Requirements:
   - Unit tests
   - Integration tests
   - API endpoint tests
   - Authentication tests 