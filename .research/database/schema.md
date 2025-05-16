# Database Schema Documentation

## Core Tables

### Users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    role_id INT,
    is_instructor BOOLEAN DEFAULT FALSE,
    social_links JSON,
    biography TEXT,
    payment_keys JSON,
    wishlist JSON,
    status ENUM('active', 'inactive', 'pending'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Courses
```sql
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2),
    discounted_price DECIMAL(10,2),
    discount_flag BOOLEAN DEFAULT FALSE,
    is_free_course BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'pending', 'active', 'inactive'),
    requirements TEXT,
    outcomes TEXT,
    language VARCHAR(50),
    level ENUM('beginner', 'intermediate', 'advanced'),
    instructor_id INT,
    category_id INT,
    sub_category_id INT,
    expiry_period INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (sub_category_id) REFERENCES categories(id)
);
```

### Sections
```sql
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    course_id INT,
    order INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);
```

### Lessons
```sql
CREATE TABLE lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    section_id INT,
    course_id INT,
    video_url VARCHAR(255),
    duration INT,
    order INT,
    lesson_type ENUM('video', 'quiz', 'text', 'assignment'),
    attachment VARCHAR(255),
    summary TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);
```

### Enrollments
```sql
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    status ENUM('active', 'completed', 'cancelled'),
    enrolled_at TIMESTAMP,
    completed_at TIMESTAMP,
    progress INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);
```

### Payments
```sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    amount DECIMAL(10,2),
    status ENUM('pending', 'completed', 'failed', 'refunded'),
    payment_date TIMESTAMP,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);
```

### Payouts
```sql
CREATE TABLE payouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT,
    amount DECIMAL(10,2),
    status ENUM('pending', 'completed', 'failed'),
    payment_date TIMESTAMP,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id)
);
```

### Categories
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    slug VARCHAR(255),
    parent_id INT,
    thumbnail VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);
```

## Supporting Tables

### Quiz Questions
```sql
CREATE TABLE quiz_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer'),
    question TEXT,
    options JSON,
    correct_answer TEXT,
    marks INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES lessons(id)
);
```

### Course Reviews
```sql
CREATE TABLE course_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    user_id INT,
    rating INT,
    review TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Notifications
```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(255),
    message TEXT,
    status ENUM('unread', 'read'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Watch History
```sql
CREATE TABLE watch_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    lesson_id INT,
    progress INT,
    last_watched_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id)
);
```

## Indexes

### Users
- INDEX idx_email (email)
- INDEX idx_role (role_id)
- INDEX idx_status (status)

### Courses
- INDEX idx_instructor (instructor_id)
- INDEX idx_category (category_id)
- INDEX idx_status (status)
- INDEX idx_price (price)

### Enrollments
- INDEX idx_user_course (user_id, course_id)
- INDEX idx_status (status)

### Payments
- INDEX idx_user_course (user_id, course_id)
- INDEX idx_status (status)
- INDEX idx_transaction (transaction_id)

### Lessons
- INDEX idx_course_section (course_id, section_id)
- INDEX idx_order (order)

## Relationships

1. **User Relationships**
   - One-to-Many with Enrollments
   - One-to-Many with Payments
   - One-to-Many with Payouts (as instructor)
   - One-to-Many with Courses (as instructor)
   - One-to-Many with Course Reviews
   - One-to-Many with Notifications
   - One-to-Many with Watch History

2. **Course Relationships**
   - One-to-Many with Sections
   - One-to-Many with Enrollments
   - One-to-Many with Payments
   - One-to-Many with Course Reviews
   - One-to-Many with Watch History

3. **Section Relationships**
   - One-to-Many with Lessons

4. **Category Relationships**
   - Self-referential (parent-child relationship)
   - One-to-Many with Courses

## Data Types and Constraints

1. **Primary Keys**
   - All tables use auto-incrementing INT as primary key
   - Composite keys used where appropriate

2. **Foreign Keys**
   - All foreign key relationships are enforced
   - ON DELETE and ON UPDATE rules specified

3. **Enums**
   - Used for status fields
   - Used for type fields
   - Used for role fields

4. **JSON Fields**
   - Used for flexible data storage
   - Used for configuration data
   - Used for social links

5. **Timestamps**
   - created_at and updated_at on all tables
   - Automatic timestamp management 