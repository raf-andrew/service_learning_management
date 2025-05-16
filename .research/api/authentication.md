# Authentication System Documentation

## Overview

The LMS platform implements a multi-layered authentication system that handles both web-based and API authentication. The system uses JWT (JSON Web Tokens) for API authentication and session-based authentication for web interfaces.

## Components

### 1. TokenHandler Class
Located in `application/libraries/TokenHandler.php`, manages JWT operations:
- Token generation
- Token validation
- Token refresh
- User data encryption/decryption

### 2. Authentication Controllers

#### Login Controller
Handles web-based authentication:
- User login validation
- Session management
- Password reset
- Remember me functionality
- Social login integration

#### API Controller
Manages API authentication:
- Token-based authentication
- User registration
- Password management
- Profile updates

### 3. User Model
Manages user data and authentication-related operations:
- User creation
- Profile management
- Role management
- Password hashing
- Session data handling

## Authentication Flows

### 1. Web Authentication Flow

1. User submits login credentials
2. System validates credentials against database
3. On success:
   - Creates user session
   - Sets session timeout
   - Stores user role and permissions
   - Redirects to appropriate dashboard
4. On failure:
   - Returns error message
   - Logs failed attempt
   - Implements rate limiting

### 2. API Authentication Flow

1. Client requests authentication token:
   ```http
   POST /api/login
   {
       "email": "user@example.com",
       "password": "password"
   }
   ```

2. System validates credentials and returns JWT:
   ```json
   {
       "status": true,
       "token": "eyJ0eXAiOiJKV1QiLC...",
       "user_id": "123",
       "validity": 3600
   }
   ```

3. Client includes token in subsequent requests:
   ```http
   GET /api/resource
   Authorization: Bearer eyJ0eXAiOiJKV1QiLC...
   ```

4. System validates token for each request:
   - Checks token signature
   - Verifies expiration
   - Validates user permissions

### 3. Social Authentication Flow

1. User initiates social login
2. System redirects to provider
3. Provider returns OAuth token
4. System validates token and creates/updates user
5. Creates session or returns API token

## Security Measures

### 1. Password Security
- Bcrypt hashing
- Password complexity requirements
- Password reset functionality
- Failed attempt limiting

### 2. Session Security
- CSRF protection
- Session timeout
- Session fixation protection
- Secure cookie handling

### 3. API Security
- JWT expiration
- Token refresh mechanism
- Rate limiting
- IP-based blocking
- XSS protection

### 4. Role-Based Access Control
- User roles (Admin, Instructor, Student)
- Permission-based access
- Route protection
- Resource access control

## Migration Considerations

When migrating to Laravel, consider:

### 1. Authentication System
- Use Laravel Sanctum for API authentication
- Implement Laravel's built-in authentication
- Migrate to Laravel's password reset system
- Maintain social authentication providers

### 2. Session Handling
- Use Laravel's session management
- Implement remember me functionality
- Migrate session data structure

### 3. Security Enhancements
- Implement two-factor authentication
- Add OAuth 2.0 support
- Enhance rate limiting
- Add audit logging

### 4. API Authentication
- Implement token abilities
- Add API versioning
- Enhance error responses
- Add refresh token rotation

## Integration Guidelines

### 1. Web Integration
```php
// Example of protected route
public function protected_page() {
    if (!$this->session->userdata('user_login')) {
        redirect('login');
    }
    // Protected content
}
```

### 2. API Integration
```php
// Example of protected API endpoint
public function protected_endpoint_get() {
    $token = $this->input->get_request_header('Authorization');
    if (!$this->tokenHandler->validate_token($token)) {
        $this->response(['status' => false, 'error' => 'Unauthorized'], 401);
    }
    // Protected content
}
```

### 3. Role Verification
```php
// Example of role-based access
public function instructor_only() {
    if ($this->session->userdata('role_id') != 2) {
        $this->response(['status' => false, 'error' => 'Forbidden'], 403);
    }
    // Instructor-only content
}
```

## Testing Considerations

1. Unit Tests:
   - Password hashing
   - Token generation/validation
   - Permission checking
   - Session handling

2. Integration Tests:
   - Login flow
   - Token refresh
   - Social authentication
   - Role-based access

3. Security Tests:
   - Brute force protection
   - Session hijacking prevention
   - CSRF protection
   - XSS prevention 