# Database Structure Documentation

## Overview
The Learning Management System (LMS) uses a MySQL database with a modular structure to support various features including user management, course management, enrollment, payments, and content delivery. This document outlines the core database structure and relationships.

## Core Tables

### 1. Users
- Primary table for user management
- Stores user profiles, credentials, and roles
- Key fields:
  - `id` (Primary Key)
  - `first_name`
  - `last_name`
  - `email`
  - `password`
  - `role` (admin/instructor/student)
  - `wishlist` (JSON encoded)
  - `status`

### 2. Categories
- Manages course categories and subcategories
- Hierarchical structure using parent-child relationship
- Key fields:
  - `id` (Primary Key)
  - `name`
  - `parent` (0 for main categories)
  - `slug`
  - `thumbnail`
  - `status`

### 3. Courses
- Central table for course information
- Key fields:
  - `id` (Primary Key)
  - `title`
  - `short_description`
  - `description`
  - `outcomes`
  - `language`
  - `category_id` (Foreign Key)
  - `sub_category_id` (Foreign Key)
  - `user_id` (Instructor, Foreign Key)
  - `price`
  - `status` (draft/pending/active)
  - `course_type` (general/scorm/h5p)
  - `is_top_course`
  - `is_free_course`

### 4. Sections
- Organizes course content into sections
- Key fields:
  - `id` (Primary Key)
  - `course_id` (Foreign Key)
  - `title`
  - `order`

### 5. Lessons
- Stores individual lesson content
- Key fields:
  - `id` (Primary Key)
  - `course_id` (Foreign Key)
  - `section_id` (Foreign Key)
  - `title`
  - `duration`
  - `lesson_type` (video/quiz/text)
  - `video_url`
  - `attachment`
  - `summary`
  - `order`

### 6. Enrollments
- Tracks course enrollments
- Key fields:
  - `id` (Primary Key)
  - `user_id` (Foreign Key)
  - `course_id` (Foreign Key)
  - `date_added`
  - `status`

### 7. Payments
- Records payment transactions
- Key fields:
  - `id` (Primary Key)
  - `user_id` (Foreign Key)
  - `course_id` (Foreign Key)
  - `amount`
  - `payment_type`
  - `date_added`
  - `status`

### 8. Questions
- Stores quiz questions
- Key fields:
  - `id` (Primary Key)
  - `quiz_id` (Foreign Key)
  - `title`
  - `type` (multiple_choice/single_choice/fill_in_the_blank)
  - `number_of_options`
  - `options` (JSON encoded)
  - `correct_answers` (JSON encoded)
  - `order`

### 9. Watch Histories
- Tracks lesson progress
- Key fields:
  - `id` (Primary Key)
  - `student_id` (Foreign Key)
  - `course_id` (Foreign Key)
  - `lesson_id` (Foreign Key)
  - `watched_time`
  - `quiz_result` (JSON encoded)

## Key Relationships

1. Course Relationships:
   - Course -> Category (Many-to-One)
   - Course -> Instructor (Many-to-One)
   - Course -> Sections (One-to-Many)
   - Course -> Lessons (One-to-Many)
   - Course -> Enrollments (One-to-Many)

2. User Relationships:
   - User -> Courses (One-to-Many, as instructor)
   - User -> Enrollments (One-to-Many, as student)
   - User -> Payments (One-to-Many)
   - User -> Watch Histories (One-to-Many)

3. Section Relationships:
   - Section -> Course (Many-to-One)
   - Section -> Lessons (One-to-Many)

4. Lesson Relationships:
   - Lesson -> Course (Many-to-One)
   - Lesson -> Section (Many-to-One)
   - Lesson -> Watch Histories (One-to-Many)

## Database Features

1. Multi-tenancy Support:
   - Each course can have multiple instructors
   - API key-based access control
   - Separate data access per tenant

2. Content Management:
   - Support for multiple content types (video, quiz, text)
   - Hierarchical content organization (Course -> Section -> Lesson)
   - Progress tracking and quiz results storage

3. Payment Integration:
   - Support for multiple payment gateways
   - Transaction history tracking
   - Course pricing management

4. Performance Optimization:
   - JSON encoding for flexible data storage
   - Indexed key fields for faster queries
   - Efficient relationship management

## Database Maintenance

1. Backup Procedures:
   ```php
   // Backup current database
   function backup_sql($backup_path) {
       // Connect to database
       $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
       
       // Generate SQL script
       $sqlScript = '';
       foreach ($tables as $table) {
           // Create table structure
           $createTableSQL = $createTableStatement['Create Table'];
           $sqlScript .= "$createTableSQL;\n";
           
           // Export data
           $dataQuery = $pdo->query("SELECT * FROM `$table`");
           // Insert statements generation
       }
       
       // Save to backup file
       file_put_contents($backup_path . '/demo.sql', $sqlScript);
   }
   ```

2. Data Migration:
   ```php
   // Import SQL file
   function run_demo_sql($file_path) {
       foreach ($lines as $line) {
           if (substr($line, 0, 2) == '--' || $line == '')
               continue;
           $templine .= $line;
           if (substr(trim($line), -1, 1) == ';') {
               $this->db->query($templine);
               $templine = '';
           }
       }
   }
   ```

## Security Considerations

1. Data Protection:
   - Password hashing
   - Input validation and sanitization
   - Prepared statements for queries

2. Access Control:
   - Role-based access control
   - API authentication using JWT
   - Session management

3. Data Integrity:
   - Foreign key constraints
   - Transaction management
   - Data validation rules

## API Integration

The database structure supports RESTful API integration through:
1. JWT-based authentication
2. Standardized API endpoints for CRUD operations
3. Rate limiting and access control
4. Multi-format response handling (JSON/XML)

## Performance Optimization

1. Query Optimization:
   - Proper indexing on frequently queried fields
   - Efficient JOIN operations
   - Query caching where appropriate

2. Data Storage:
   - Binary storage for files and media
   - JSON encoding for flexible data
   - Efficient data type selection

3. Scalability:
   - Support for database sharding
   - Load balancing capabilities
   - Caching mechanisms 