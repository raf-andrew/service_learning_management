# Course Model Documentation

## Overview
The Course model (Crud_model.php) is the core model handling course management, content organization, and related operations in the LMS platform.

## Database Schema

### Courses Table
```sql
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    short_description TEXT,
    description LONGTEXT,
    outcomes TEXT,
    language VARCHAR(50),
    category_id INT,
    sub_category_id INT,
    section_id INT,
    requirements TEXT,
    price DECIMAL(10,2),
    discount_flag BOOLEAN,
    discounted_price DECIMAL(10,2),
    level VARCHAR(50),
    user_id INT,
    thumbnail VARCHAR(255),
    video_url VARCHAR(255),
    date_added TIMESTAMP,
    last_modified TIMESTAMP,
    course_type VARCHAR(50),
    status VARCHAR(50),
    course_overview_provider VARCHAR(50),
    meta_keywords TEXT,
    meta_description TEXT,
    is_top_course BOOLEAN,
    is_admin BOOLEAN,
    creator VARCHAR(50)
);
```

### Categories Table
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50),
    name VARCHAR(255),
    parent INT,
    slug VARCHAR(255),
    font_awesome_class VARCHAR(255),
    thumbnail VARCHAR(255),
    sub_category_thumbnail VARCHAR(255),
    date_added TIMESTAMP,
    last_modified TIMESTAMP
);
```

### Sections Table
```sql
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    course_id INT,
    order_index INT,
    date_added TIMESTAMP,
    last_modified TIMESTAMP
);
```

### Lessons Table
```sql
CREATE TABLE lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    section_id INT,
    course_id INT,
    lesson_type VARCHAR(50),
    attachment_type VARCHAR(50),
    attachment VARCHAR(255),
    summary TEXT,
    duration VARCHAR(50),
    video_url VARCHAR(255),
    date_added TIMESTAMP,
    last_modified TIMESTAMP,
    order_index INT,
    video_type VARCHAR(50),
    note TEXT,
    is_free BOOLEAN
);
```

## Core Methods

### Course Management
1. `add_course($param1 = "")`
   - Creates new course
   - Handles course details and metadata
   - Manages course sections and lessons
   - Sets up course pricing and discounts

2. `edit_course($course_id, $type = "")`
   - Updates course details
   - Manages course content
   - Updates pricing and discounts
   - Handles course status changes

3. `delete_course($course_id = "")`
   - Removes course and associated content
   - Cleans up related resources

4. `get_course_by_id($course_id = "")`
   - Retrieves course details
   - Includes related content and metadata

### Category Management
1. `add_category()`
   - Creates new course category
   - Handles category hierarchy
   - Manages category thumbnails

2. `edit_category($param1)`
   - Updates category details
   - Manages category relationships
   - Updates category assets

3. `delete_category($category_id)`
   - Removes category and subcategories
   - Cleans up related resources

### Section Management
1. `add_section($course_id)`
   - Creates new course section
   - Manages section ordering
   - Sets up section content

2. `edit_section($section_id)`
   - Updates section details
   - Manages section content
   - Updates section ordering

3. `delete_section($course_id, $section_id)`
   - Removes section and content
   - Updates course structure

### Lesson Management
1. `add_lesson()`
   - Creates new lesson
   - Handles lesson content
   - Manages lesson ordering
   - Sets up lesson resources

2. `edit_lesson($lesson_id)`
   - Updates lesson details
   - Manages lesson content
   - Updates lesson resources

3. `delete_lesson($lesson_id)`
   - Removes lesson and resources
   - Updates section structure

## Relationships
- Course belongs to Category
- Course has many Sections
- Section has many Lessons
- Course has many Enrollments
- Course belongs to User (Instructor)

## Security Considerations
1. Content access control
2. File upload validation
3. User permissions
4. Data validation
5. XSS prevention

## Migration Notes
1. Normalize course metadata
2. Implement soft deletes
3. Add audit logging
4. Improve file handling
5. Enhance content organization
6. Add version control
7. Implement caching

## Testing Requirements
1. Course CRUD operations
2. Category management
3. Section management
4. Lesson management
5. Content access control
6. File handling
7. Data validation
8. Performance testing 