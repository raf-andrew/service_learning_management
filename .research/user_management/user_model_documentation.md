# User Management System Documentation

## Overview
The User Management System is a core component of the LMS platform, handling user authentication, authorization, and profile management. It supports multiple user roles including administrators, instructors, and students.

## Core Components

### 1. User Model (`User_model.php`)
Located in `application/models/User_model.php`, this model handles:
- User registration and authentication
- Role-based access control
- Profile management
- Instructor applications
- Social media integration
- Payment gateway configuration

### 2. User Roles
The system supports three primary roles:
1. Administrator (role_id: 1)
2. Student (role_id: 2)
3. Instructor (is_instructor: 1)

### 3. Data Structure

#### User Table Schema
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role_id INT,
    is_instructor BOOLEAN,
    social_links JSON,
    biography TEXT,
    phone VARCHAR(255),
    address TEXT,
    date_added TIMESTAMP,
    wishlist JSON,
    status INT,
    image VARCHAR(255),
    payment_keys JSON
);
```

#### Permissions Table Schema
```sql
CREATE TABLE permissions (
    admin_id INT PRIMARY KEY,
    permissions JSON
);
```

## Core Methods

### 1. User Management
```php
public function add_user($is_instructor = false, $is_admin = false)
public function edit_user($user_id = "")
public function delete_user($user_id = "")
public function get_user($user_id = 0)
public function get_all_user($user_id = 0)
```

### 2. Authentication
```php
public function register_user($data)
public function unlock_screen_by_password($password = "")
public function session_destroy()
public function check_session_data($user_type = "")
```

### 3. Instructor Management
```php
public function get_instructor($id = 0)
public function get_instructor_list()
public function post_instructor_application($user_id = "")
public function update_status_of_application($status, $application_id)
```

### 4. Payment Integration
```php
public function update_instructor_paypal_settings($user_id = '')
public function update_instructor_stripe_settings($user_id = '')
public function update_instructor_razorpay_settings($user_id = '')
```

## Security Features

### 1. Authentication
- Password hashing using SHA1
- Session management
- Role-based access control
- Device login tracking

### 2. Data Validation
- Email duplication check
- Input sanitization
- Role verification
- Permission validation

## Integration Points

### 1. Course System
- Course enrollment tracking
- Wishlist management
- Instructor course management

### 2. Payment System
- Payment gateway configuration
- Instructor payout settings
- Transaction tracking

### 3. Social System
- Social media integration
- Instructor following system
- User interactions

## Migration Considerations

### 1. Database Changes
- [ ] Update password hashing to modern algorithm
- [ ] Normalize social links table
- [ ] Add audit logging
- [ ] Implement soft deletes

### 2. Authentication Updates
- [ ] Implement JWT authentication
- [ ] Add OAuth2 support
- [ ] Implement 2FA
- [ ] Add password reset flow

### 3. Role Management
- [ ] Implement role hierarchy
- [ ] Add permission inheritance
- [ ] Create role templates
- [ ] Add role-based UI elements

## Testing Requirements

### 1. Unit Tests
- User creation/editing
- Authentication flow
- Role management
- Permission handling

### 2. Integration Tests
- Payment gateway integration
- Social media integration
- Course enrollment
- Instructor applications

### 3. Security Tests
- Authentication bypass
- Role escalation
- Data validation
- Session management

## Future Enhancements

### 1. Authentication
- [ ] Implement OAuth2
- [ ] Add social login
- [ ] Implement 2FA
- [ ] Add biometric auth

### 2. User Management
- [ ] Add user groups
- [ ] Implement user import/export
- [ ] Add bulk operations
- [ ] Create user templates

### 3. Security
- [ ] Implement rate limiting
- [ ] Add IP whitelisting
- [ ] Implement audit logging
- [ ] Add security headers 