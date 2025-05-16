# Course Management System Documentation

## Overview
The Course Management System is a core component of the LMS platform responsible for course creation, organization, and delivery. It handles course structure, content management, and student enrollment.

## Core Components

### 1. Course Model (Crud_model.php)
The primary model handling course-related operations:
- Course creation and management
- Section and lesson organization
- Course enrollment
- Course ratings and reviews
- Category management
- Course search and filtering

### 2. Course Structure
1. Categories
   - Parent categories
   - Sub-categories
   - Category thumbnails
   - Category organization

2. Courses
   - Course details
   - Course settings
   - Course status
   - Course pricing

3. Sections
   - Section organization
   - Section ordering
   - Section visibility

4. Lessons
   - Lesson types
   - Lesson content
   - Lesson duration
   - Lesson completion

## Data Structures

### 1. Course Table
```sql
CREATE TABLE course (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    short_description TEXT,
    description LONGTEXT,
    outcomes TEXT,
    language VARCHAR(50),
    category_id INT,
    sub_category_id INT,
    section JSON,
    requirements TEXT,
    price DECIMAL(10,2),
    discount_flag TINYINT(1),
    discounted_price DECIMAL(10,2),
    level VARCHAR(50),
    user_id INT,
    thumbnail VARCHAR(255),
    video_url VARCHAR(255),
    date_added INT,
    last_modified INT,
    course_type VARCHAR(50),
    status VARCHAR(50),
    course_overview_provider VARCHAR(50),
    meta_keywords TEXT,
    meta_description TEXT,
    is_top_course TINYINT(1),
    is_admin TINYINT(1),
    FOREIGN KEY (category_id) REFERENCES category(id),
    FOREIGN KEY (sub_category_id) REFERENCES category(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 2. Section Table
```sql
CREATE TABLE section (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    course_id INT,
    order_number INT,
    date_added INT,
    last_modified INT,
    FOREIGN KEY (course_id) REFERENCES course(id)
);
```

### 3. Lesson Table
```sql
CREATE TABLE lesson (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    section_id INT,
    course_id INT,
    lesson_type VARCHAR(50),
    attachment VARCHAR(255),
    summary TEXT,
    video_type VARCHAR(50),
    video_url VARCHAR(255),
    duration VARCHAR(50),
    date_added INT,
    last_modified INT,
    FOREIGN KEY (section_id) REFERENCES section(id),
    FOREIGN KEY (course_id) REFERENCES course(id)
);
```

## Core Methods

### 1. Course Management
```php
// Add new course
public function add_course($param1 = "")

// Update course
public function update_course($course_id, $type = "")

// Delete course
public function delete_course($course_id = "")

// Get course details
public function get_course_by_id($course_id = "")
```

### 2. Section Management
```php
// Add section
public function add_section($course_id)

// Edit section
public function edit_section($section_id)

// Delete section
public function delete_section($course_id, $section_id)

// Get section
public function get_section($type_by, $id)
```

### 3. Lesson Management
```php
// Add lesson
public function add_lesson()

// Edit lesson
public function edit_lesson($lesson_id)

// Delete lesson
public function delete_lesson($lesson_id)

// Get lessons
public function get_lessons($type = "", $id = "")
```

## Integration Points

### 1. User System Integration
- Instructor management
- Student enrollment
- Course access control
- Progress tracking

### 2. Content System Integration
- Video content
- Document attachments
- Quiz integration
- Assignment handling

### 3. Payment System Integration
- Course pricing
- Discount management
- Revenue tracking
- Enrollment processing

## Security Features

### 1. Access Control
- Role-based permissions
- Course visibility
- Content protection
- Enrollment validation

### 2. Data Protection
- Input validation
- File upload security
- Content encryption
- Access logging

## Migration Considerations

### 1. Database Changes
- [ ] Implement UUID for IDs
- [ ] Add audit logging
- [ ] Enhance search capabilities
- [ ] Add version control

### 2. Content Migration
- [ ] Content structure updates
- [ ] Media handling
- [ ] Backup systems
- [ ] Recovery procedures

### 3. Architecture Updates
- [ ] Implement repository pattern
- [ ] Add service layer
- [ ] Create course events
- [ ] Add validation rules

## Testing Strategy

### 1. Unit Tests
- Course creation and validation
- Section management
- Lesson handling
- Content delivery

### 2. Integration Tests
- User enrollment
- Content access
- Payment processing
- Progress tracking

### 3. Security Tests
- Access control
- Content protection
- Data validation
- File handling

## Future Enhancements

### 1. Course Features
- [ ] Course templates
- [ ] Interactive content
- [ ] Gamification
- [ ] Social learning

### 2. Content Features
- [ ] Advanced media support
- [ ] Content versioning
- [ ] Offline access
- [ ] Mobile optimization

### 3. Integration Features
- [ ] API enhancements
- [ ] Webhook support
- [ ] Analytics integration
- [ ] Third-party tools 