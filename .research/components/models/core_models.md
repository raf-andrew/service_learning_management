# Core Models Documentation

## Overview
The LMS platform implements several core models that handle different aspects of the application's functionality. Each model extends CodeIgniter's `CI_Model` class and follows the MVC pattern.

## Core Models

### 1. User Model
The User model (`User_model.php`) handles all user-related operations in the system.

#### Key Attributes:
- `id`: Primary key
- `username`: User's display name
- `email`: User's email address
- `password`: Hashed password
- `role_id`: User's role (1 for admin, 2 for regular user)
- `is_instructor`: Boolean flag for instructor status
- `social_links`: JSON field for social media links
- `biography`: User's bio
- `payment_keys`: JSON field for payment gateway configurations
- `wishlist`: JSON field for course wishlist
- `status`: Account status

#### Key Methods:
- `add_user()`: Creates a new user
- `edit_user()`: Updates user details
- `delete_user()`: Removes a user
- `get_user()`: Retrieves user details
- `check_duplication()`: Validates unique email
- `my_courses()`: Gets user's enrolled courses
- `update_account_settings()`: Updates user preferences

### 2. Course Model
The Course model (`Crud_model.php`) manages all course-related operations.

#### Key Attributes:
- `id`: Primary key
- `title`: Course title
- `description`: Course description
- `price`: Course price
- `status`: Course status
- `requirements`: Course prerequisites
- `outcomes`: Learning outcomes
- `instructor_id`: Foreign key to User

#### Key Methods:
- `add_course()`: Creates a new course
- `update_course()`: Updates course details
- `delete_course()`: Removes a course
- `get_course_by_id()`: Retrieves course details
- `change_course_status()`: Updates course status
- `filter_course()`: Advanced course filtering
- `get_courses_by_user_id()`: Gets instructor's courses

### 3. Section Model
The Section model (part of `Crud_model.php`) manages course sections.

#### Key Attributes:
- `id`: Primary key
- `title`: Section title
- `course_id`: Foreign key to Course
- `order`: Section ordering

#### Key Methods:
- `add_section()`: Creates a new section
- `edit_section()`: Updates section details
- `delete_section()`: Removes a section
- `serialize_section()`: Manages section ordering

### 4. Lesson Model
The Lesson model (part of `Crud_model.php`) handles course lessons.

#### Key Attributes:
- `id`: Primary key
- `title`: Lesson title
- `section_id`: Foreign key to Section
- `course_id`: Foreign key to Course
- `video_url`: Lesson video URL
- `duration`: Lesson duration
- `order`: Lesson ordering

#### Key Methods:
- `add_lesson()`: Creates a new lesson
- `edit_lesson()`: Updates lesson details
- `delete_lesson()`: Removes a lesson
- `get_lesson_thumbnail_url()`: Gets lesson media

### 5. Enrollment Model
The Enrollment model (part of `Crud_model.php`) manages course enrollments.

#### Key Attributes:
- `id`: Primary key
- `user_id`: Foreign key to User
- `course_id`: Foreign key to Course
- `status`: Enrollment status
- `enrolled_at`: Enrollment timestamp
- `completed_at`: Completion timestamp

#### Key Methods:
- `enrol_student()`: Processes enrollment
- `enrol_to_free_course()`: Handles free enrollments
- `course_purchase()`: Processes paid enrollments
- `enrol_history()`: Tracks enrollment records

### 6. Payment Model
The Payment model (`Payment_model.php`) handles financial transactions.

#### Key Attributes:
- `id`: Primary key
- `user_id`: Foreign key to User
- `course_id`: Foreign key to Course
- `amount`: Payment amount
- `status`: Payment status
- `payment_date`: Transaction date
- `payment_method`: Payment gateway

#### Key Methods:
- `course_purchase()`: Processes course payments
- `get_payment_details_by_id()`: Retrieves payment details
- `update_payout_status()`: Updates payment status

### 7. Payout Model
The Payout model (part of `Crud_model.php`) manages instructor earnings.

#### Key Attributes:
- `id`: Primary key
- `instructor_id`: Foreign key to User
- `amount`: Payout amount
- `status`: Payout status
- `payment_date`: Payout date
- `payment_method`: Payment method

#### Key Methods:
- `get_payouts()`: Retrieves payout records
- `add_withdrawal_request()`: Processes payout requests
- `get_total_payout_amount()`: Calculates total earnings

## Model Relationships

### User Relationships
- One-to-Many with Enrollment
- One-to-Many with Payment
- One-to-Many with Course (as instructor)
- One-to-Many with Payout (as instructor)

### Course Relationships
- One-to-Many with Enrollment
- One-to-Many with Payment
- One-to-Many with Section
- Many-to-One with User (instructor)

### Section Relationships
- One-to-Many with Lesson
- Many-to-One with Course

### Lesson Relationships
- Many-to-One with Section
- Many-to-One with Course

### Enrollment Relationships
- Many-to-One with User
- Many-to-One with Course

### Payment Relationships
- Many-to-One with User
- Many-to-One with Course

### Payout Relationships
- Many-to-One with User (instructor)

## Migration Considerations

### Laravel Migration
1. Convert models to Laravel Eloquent models
2. Implement proper relationships using Eloquent
3. Add proper type hints and return types
4. Implement proper validation rules
5. Add proper event handling
6. Implement proper scopes
7. Add proper accessors and mutators

### Database Migration
1. Add proper foreign key constraints
2. Add proper indexes
3. Add proper timestamps
4. Add proper soft deletes
5. Add proper enums
6. Add proper JSON columns
7. Add proper spatial columns

### API Migration
1. Implement proper API resources
2. Add proper API documentation
3. Implement proper API versioning
4. Add proper API authentication
5. Add proper API validation
6. Add proper API error handling
7. Add proper API rate limiting 

## User Model

### Attributes
- `id` (PK): Unique identifier
- `username`: User's login name
- `email`: User's email address
- `password`: Hashed password
- `first_name`: User's first name
- `last_name`: User's last name
- `role_id`: User's role identifier
- `is_instructor`: Boolean flag for instructor status
- `social_links`: JSON field for social media links
- `biography`: User's biography text
- `phone`: Contact phone number
- `address`: Physical address
- `image`: Profile image path
- `payment_keys`: JSON field for payment-related keys
- `wishlist`: JSON field for course wishlist
- `status`: User status (active/inactive)
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `get_admin_details()`: Retrieve admin user details
- `get_user($user_id)`: Get user by ID
- `add_user($is_instructor, $is_admin)`: Create new user
- `check_duplication($action, $email, $user_id)`: Check for duplicate users
- `edit_user($user_id)`: Update user details
- `delete_user($user_id)`: Remove user

## Course Model

### Attributes
- `id` (PK): Unique identifier
- `title`: Course title
- `description`: Detailed course description
- `short_description`: Brief course summary
- `requirements`: Course prerequisites
- `outcomes`: Learning outcomes
- `language`: Course language
- `level`: Course difficulty level
- `category_id` (FK): Parent category
- `sub_category_id` (FK): Sub-category
- `instructor_id` (FK): Course creator
- `price`: Course price
- `discount_flag`: Discount availability flag
- `discounted_price`: Discounted price
- `thumbnail`: Course thumbnail image
- `video_url`: Preview video URL
- `status`: Course status
- `course_overview_provider`: Video provider
- `meta_keywords`: SEO keywords
- `meta_description`: SEO description
- `is_top_course`: Featured course flag
- `is_free_course`: Free course flag
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `add_course()`: Create new course
- `update_course($course_id)`: Update course details
- `get_course_by_id($course_id)`: Retrieve course by ID
- `get_courses_by_user_id($user_id)`: Get user's courses
- `delete_course($course_id)`: Remove course

## Section Model

### Attributes
- `id` (PK): Unique identifier
- `title`: Section title
- `course_id` (FK): Parent course
- `order`: Display order
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `add_section($course_id)`: Create new section
- `edit_section($section_id)`: Update section
- `delete_section($course_id, $section_id)`: Remove section
- `get_section($type_by, $id)`: Retrieve section

## Lesson Model

### Attributes
- `id` (PK): Unique identifier
- `title`: Lesson title
- `section_id` (FK): Parent section
- `course_id` (FK): Parent course
- `lesson_type`: Type of lesson
- `duration`: Lesson duration
- `video_url`: Video content URL
- `video_type`: Video format
- `summary`: Lesson summary
- `attachment`: Additional materials
- `attachment_type`: Attachment format
- `order`: Display order
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `add_lesson()`: Create new lesson
- `edit_lesson($lesson_id)`: Update lesson
- `delete_lesson($lesson_id)`: Remove lesson
- `get_lesson_thumbnail_url($lesson_id)`: Get lesson thumbnail

## Enrollment Model

### Attributes
- `id` (PK): Unique identifier
- `user_id` (FK): Enrolled user
- `course_id` (FK): Enrolled course
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `enrol_student($enrol_user_id, $payer_user_id)`: Enroll student
- `enrol_history($course_id)`: Get enrollment history
- `purchase_history($user_id)`: Get user's purchase history

## Payment Model

### Attributes
- `id` (PK): Unique identifier
- `user_id` (FK): Paying user
- `course_id` (FK): Purchased course
- `amount`: Payment amount
- `payment_type`: Payment method
- `transaction_id`: Transaction reference
- `status`: Payment status
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `course_purchase($user_id, $method, $amount)`: Process payment
- `get_payment_details_by_id($payment_id)`: Get payment details
- `update_payout_status($payout_id, $payment_type)`: Update payout status

## Payout Model

### Attributes
- `id` (PK): Unique identifier
- `instructor_id` (FK): Receiving instructor
- `amount`: Payout amount
- `payment_type`: Payment method
- `status`: Payout status
- `date_added`: Creation timestamp
- `last_modified`: Last update timestamp

### Key Methods
- `get_payouts($id, $type)`: Get payout records
- `add_withdrawal_request()`: Create withdrawal request
- `get_total_payout_amount($id)`: Calculate total payouts
- `get_total_revenue($id)`: Calculate total revenue 