# User Management System Documentation

## Overview
The User Management System is a core component of the LMS platform that handles user registration, authentication, and role-based access control. It supports multiple user types including administrators, instructors, and students.

## Core Components

### 1. User Model (User_model.php)
The primary model handling user-related operations:
- User registration and authentication
- Role management
- Profile management
- Instructor applications
- Social media integration
- Payment gateway configuration

### 2. User Types
1. Administrator (role_id = 1)
2. Instructor (is_instructor = 1)
3. Student (role_id = 2)

## Data Structures

### 1. User Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255),
    password VARCHAR(255),
    social_links JSON,
    biography TEXT,
    phone VARCHAR(50),
    address TEXT,
    role_id INT,
    is_instructor BOOLEAN,
    date_added TIMESTAMP,
    wishlist JSON,
    status INT,
    image VARCHAR(255),
    payment_keys JSON
);
```

### 2. Permissions Table
```sql
CREATE TABLE permissions (
    admin_id INT PRIMARY KEY,
    permissions JSON
);
```

## Core Methods

### 1. User Management
```php
// Get user details
public function get_user($user_id = 0)

// Add new user
public function add_user($is_instructor = false, $is_admin = false)

// Edit user
public function edit_user($user_id = "")

// Delete user
public function delete_user($user_id = "")
```

### 2. Authentication
```php
// Register user
public function register_user($data)

// Change password
public function change_password($user_id)

// Session management
public function set_login_userdata($user_id = "")
```

### 3. Instructor Management
```php
// Get instructor details
public function get_instructor($id = 0)

// Submit instructor application
public function post_instructor_application($user_id = "")

// Update instructor settings
public function update_instructor_paypal_settings($user_id = '')
```

## Integration Points

### 1. Course System Integration
- Instructor course management
- Student enrollment
- Course access control
- Progress tracking

### 2. Payment System Integration
- Payment gateway configuration
- Instructor payout settings
- Transaction history
- Revenue tracking

### 3. Social System Integration
- Social media links
- User following system
- Social authentication
- Profile sharing

## Security Features

### 1. Authentication Security
- Password hashing
- Session management
- Login tracking
- Device verification

### 2. Data Protection
- Input validation
- XSS prevention
- CSRF protection
- Role-based access control

## Migration Considerations

### 1. Database Changes
- [ ] Optimize user tables
- [ ] Add audit logging
- [ ] Implement soft deletes
- [ ] Add versioning

### 2. Authentication Updates
- [ ] Implement OAuth2
- [ ] Add 2FA support
- [ ] Update password hashing
- [ ] Enhance session security

### 3. Architecture Updates
- [ ] Separate user module
- [ ] Add user API
- [ ] Implement caching
- [ ] Add event system

## Testing Strategy

### 1. Unit Tests
- User creation/editing
- Authentication
- Role management
- Profile updates

### 2. Integration Tests
- Course system integration
- Payment system integration
- Social system integration
- Analytics integration

### 3. Security Tests
- Authentication tests
- Authorization tests
- Data validation tests
- Session management tests

## Future Enhancements

### 1. User Features
- [ ] Advanced profile customization
- [ ] Social media integration
- [ ] Achievement system
- [ ] Badge system

### 2. Authentication Features
- [ ] Multi-factor authentication
- [ ] OAuth integration
- [ ] Single sign-on
- [ ] Passwordless login

### 3. Integration Features
- [ ] API enhancements
- [ ] Webhook support
- [ ] Event system
- [ ] Custom integrations 