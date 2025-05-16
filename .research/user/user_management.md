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
1. Administrators (role_id = 1)
   - Full system access
   - Permission management
   - User management
   - System configuration

2. Instructors (is_instructor = 1)
   - Course creation and management
   - Student management
   - Revenue tracking
   - Application process

3. Students (role_id = 2)
   - Course enrollment
   - Progress tracking
   - Payment processing
   - Profile management

## Data Structures

### 1. User Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    social_links JSON,
    biography TEXT,
    phone VARCHAR(50),
    address TEXT,
    role_id INT,
    is_instructor TINYINT(1),
    date_added INT,
    wishlist JSON,
    status TINYINT(1),
    image VARCHAR(255),
    payment_keys JSON,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### 2. Permissions Table
```sql
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    permissions JSON,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
```

## Core Methods

### 1. User Management
```php
// Add new user
public function add_user($is_instructor = false, $is_admin = false)

// Edit user
public function edit_user($user_id = "")

// Delete user
public function delete_user($user_id = "")

// Get user details
public function get_user($user_id = 0)
```

### 2. Authentication
```php
// Register user
public function register_user($data)

// Change password
public function change_password($user_id)

// Session management
public function session_destroy()
```

### 3. Instructor Management
```php
// Get instructor details
public function get_instructor($id = 0)

// Update instructor settings
public function update_instructor_paypal_settings($user_id = '')

// Process instructor applications
public function post_instructor_application($user_id = "")
```

## Integration Points

### 1. Course System Integration
- Course enrollment
- Progress tracking
- Instructor course management
- Student course access

### 2. Payment System Integration
- Payment gateway configuration
- Revenue tracking
- Payout processing
- Transaction history

### 3. Content System Integration
- Content access control
- Progress tracking
- Completion status
- Learning analytics

## Security Features

### 1. Authentication
- Password hashing
- Session management
- Device tracking
- Login verification

### 2. Authorization
- Role-based access control
- Permission management
- Content access control
- API access control

## Migration Considerations

### 1. Database Changes
- [ ] Implement UUID for user IDs
- [ ] Add audit logging
- [ ] Enhance security features
- [ ] Add soft delete support

### 2. Authentication Updates
- [ ] Implement OAuth2
- [ ] Add MFA support
- [ ] Enhance session security
- [ ] Add API authentication

### 3. Architecture Updates
- [ ] Implement repository pattern
- [ ] Add service layer
- [ ] Create user events
- [ ] Add validation rules

## Testing Strategy

### 1. Unit Tests
- User creation and validation
- Authentication flows
- Permission checks
- Profile updates

### 2. Integration Tests
- Course enrollment
- Payment processing
- Content access
- API endpoints

### 3. Security Tests
- Authentication bypass
- Permission escalation
- Data validation
- Session management

## Future Enhancements

### 1. User Features
- [ ] Social login integration
- [ ] Profile customization
- [ ] Notification preferences
- [ ] Learning preferences

### 2. Security Features
- [ ] Advanced MFA options
- [ ] Security audit logging
- [ ] IP-based restrictions
- [ ] Device management

### 3. Integration Features
- [ ] SSO support
- [ ] API enhancements
- [ ] Webhook support
- [ ] Analytics integration 