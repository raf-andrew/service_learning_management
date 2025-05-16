# User Model Documentation

## Overview
The User model handles all user-related operations in the LMS platform, including user management, authentication, and instructor-specific functionality.

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    social_links JSON,
    biography TEXT,
    phone VARCHAR(255),
    address TEXT,
    role_id INT,
    is_instructor BOOLEAN DEFAULT FALSE,
    date_added TIMESTAMP,
    wishlist JSON,
    status INT,
    image VARCHAR(255),
    payment_keys JSON,
    verification_code VARCHAR(255),
    last_modified TIMESTAMP,
    title VARCHAR(255),
    skills TEXT
);
```

## Core Methods

### User Management
1. `get_admin_details()`
   - Returns admin users (role_id = 1)
   - Used for admin dashboard and management

2. `get_user($user_id = 0)`
   - Returns regular users (role_id = 2)
   - Can filter by specific user_id

3. `get_all_user($user_id = 0)`
   - Returns all users
   - Can filter by specific user_id

4. `add_user($is_instructor = false, $is_admin = false)`
   - Creates new user
   - Handles email duplication check
   - Sets up social links and payment keys
   - Manages user roles and permissions

5. `edit_user($user_id = "")`
   - Updates user details
   - Handles profile image upload
   - Updates payment gateway settings

6. `delete_user($user_id = "")`
   - Removes user from system

### Authentication
1. `check_duplication($action, $email, $user_id)`
   - Validates email uniqueness
   - Handles both create and update scenarios

2. `unlock_screen_by_password($password)`
   - Verifies password for screen unlock

3. `register_user($data)`
   - Handles user registration
   - Returns new user ID

### Instructor Management
1. `get_instructor($id = 0)`
   - Retrieves instructor details
   - Can filter by specific ID

2. `get_instructor_list()`
   - Returns list of all instructors

3. `post_instructor_application($user_id)`
   - Handles instructor application process
   - Manages application status

### Payment Integration
1. `update_instructor_paypal_settings($user_id)`
   - Manages PayPal payment settings

2. `update_instructor_stripe_settings($user_id)`
   - Manages Stripe payment settings

3. `update_instructor_razorpay_settings($user_id)`
   - Manages Razorpay payment settings

### Session Management
1. `set_login_userdata($user_id)`
   - Sets up user session data
   - Manages login state

2. `session_destroy()`
   - Handles user logout
   - Cleans up session data

## Relationships
- One-to-Many with Courses (instructor)
- One-to-Many with Enrollments (student)
- One-to-Many with Payments
- One-to-Many with Applications (instructor)

## Security Considerations
1. Password hashing using SHA1
2. Email duplication prevention
3. Session management
4. Role-based access control

## Migration Notes
1. Password hashing should be updated to use bcrypt
2. Social links should be normalized into separate table
3. Payment keys should be moved to dedicated payment settings table
4. Add soft delete functionality
5. Implement proper validation rules
6. Add audit logging

## Testing Requirements
1. User CRUD operations
2. Authentication flows
3. Role-based access
4. Payment integration
5. Session management
6. Data validation
7. Security measures 