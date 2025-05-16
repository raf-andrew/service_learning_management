# Entity Relationship Diagram Documentation

## Overview
This document provides a comprehensive Entity Relationship Diagram (ERD) for the Learning Management System (LMS) platform. The ERD illustrates the relationships between all core entities in the system.

## Core Entities

### User Management
- Users
- Roles
- Permissions
- User Settings
- User Preferences

### Course Management
- Courses
- Sections
- Lessons
- Categories
- Enrollments
- Progress Tracking

### Content Management
- Media Files
- Attachments
- Resources
- Quiz Questions
- Quiz Attempts

### Payment System
- Payments
- Transactions
- Refunds
- Payouts
- Coupons

### Blog System
- Blog Posts
- Blog Categories
- Blog Comments
- Blog Settings

## PlantUML Diagram

```plantuml
@startuml LMS Platform ERD

' User Management
class User {
    +id: int
    +name: string
    +email: string
    +password: string
    +role: string
    +status: enum
    +created_at: timestamp
    +updated_at: timestamp
}

class Role {
    +id: int
    +name: string
    +permissions: json
}

class UserPreference {
    +id: int
    +user_id: int
    +key: string
    +value: string
}

' Course Management
class Course {
    +id: int
    +title: string
    +description: text
    +user_id: int
    +category_id: int
    +price: decimal
    +status: enum
    +created_at: timestamp
    +updated_at: timestamp
}

class Section {
    +id: int
    +course_id: int
    +title: string
    +order: int
}

class Lesson {
    +id: int
    +section_id: int
    +title: string
    +type: enum
    +content: text
    +duration: int
    +order: int
}

class Category {
    +id: int
    +name: string
    +parent_id: int
    +slug: string
}

class Enrollment {
    +id: int
    +user_id: int
    +course_id: int
    +price: decimal
    +status: enum
    +enrolled_date: timestamp
}

class Progress {
    +id: int
    +user_id: int
    +course_id: int
    +lesson_id: int
    +progress: int
    +completed: boolean
    +last_accessed: timestamp
}

' Content Management
class Media {
    +id: int
    +type: enum
    +url: string
    +size: int
    +duration: int
}

class Quiz {
    +id: int
    +lesson_id: int
    +title: string
    +duration: int
    +pass_mark: int
}

class Question {
    +id: int
    +quiz_id: int
    +type: enum
    +question: text
    +options: json
    +correct_answer: text
}

class QuizAttempt {
    +id: int
    +user_id: int
    +quiz_id: int
    +score: int
    +status: enum
    +started_at: timestamp
    +completed_at: timestamp
}

' Payment System
class Payment {
    +id: int
    +user_id: int
    +amount: decimal
    +type: enum
    +status: enum
    +gateway: string
    +transaction_id: string
    +created_at: timestamp
}

class Transaction {
    +id: int
    +payment_id: int
    +amount: decimal
    +type: enum
    +status: enum
    +created_at: timestamp
}

class Payout {
    +id: int
    +user_id: int
    +amount: decimal
    +status: enum
    +paid_at: timestamp
}

class Coupon {
    +id: int
    +code: string
    +discount: decimal
    +type: enum
    +valid_from: timestamp
    +valid_to: timestamp
}

' Blog System
class BlogPost {
    +id: int
    +title: string
    +content: text
    +user_id: int
    +category_id: int
    +status: enum
    +created_at: timestamp
}

class BlogCategory {
    +id: int
    +name: string
    +slug: string
}

class BlogComment {
    +id: int
    +post_id: int
    +user_id: int
    +content: text
    +parent_id: int
    +created_at: timestamp
}

' Relationships
User "1" -- "0..*" Course : creates >
User "1" -- "0..*" Enrollment : has >
User "1" -- "0..*" Progress : tracks >
User "1" -- "0..*" Payment : makes >
User "1" -- "0..*" Payout : receives >
User "1" -- "0..*" BlogPost : authors >
User "1" -- "0..*" BlogComment : writes >
User "1" -- "1" Role : has >
User "1" -- "0..*" UserPreference : has >

Course "1" -- "0..*" Section : contains >
Course "1" -- "1" Category : belongs to >
Course "1" -- "0..*" Enrollment : has >
Course "1" -- "0..*" Progress : tracks >

Section "1" -- "0..*" Lesson : contains >
Lesson "1" -- "0..1" Quiz : has >
Lesson "1" -- "0..*" Media : contains >

Quiz "1" -- "0..*" Question : has >
Quiz "1" -- "0..*" QuizAttempt : has >

Payment "1" -- "0..*" Transaction : has >
Payment "0..*" -- "0..1" Coupon : uses >

BlogPost "1" -- "1" BlogCategory : belongs to >
BlogPost "1" -- "0..*" BlogComment : has >
BlogComment "0..*" -- "0..1" BlogComment : replies to >

@enduml
```

## Entity Descriptions

### User Management
- **User**: Core entity representing system users (students, instructors, admins)
- **Role**: Defines user roles and associated permissions
- **UserPreference**: Stores user-specific settings and preferences

### Course Management
- **Course**: Main entity for educational content
- **Section**: Organizes course content into logical groups
- **Lesson**: Individual learning units within sections
- **Category**: Hierarchical organization of courses
- **Enrollment**: Tracks student course enrollments
- **Progress**: Monitors student progress through courses

### Content Management
- **Media**: Handles various types of media content
- **Quiz**: Assessment component of lessons
- **Question**: Individual quiz questions
- **QuizAttempt**: Records of student quiz attempts

### Payment System
- **Payment**: Records of financial transactions
- **Transaction**: Detailed payment transaction records
- **Payout**: Instructor payment records
- **Coupon**: Discount management

### Blog System
- **BlogPost**: Blog content management
- **BlogCategory**: Organization of blog posts
- **BlogComment**: User interactions with blog posts

## Key Relationships

1. **User-Course**
   - Users create courses (instructors)
   - Users enroll in courses (students)
   - Users track progress in courses

2. **Course Structure**
   - Courses contain sections
   - Sections contain lessons
   - Lessons may have quizzes and media

3. **Payment Flow**
   - Users make payments
   - Payments generate transactions
   - Instructors receive payouts

4. **Blog System**
   - Users create blog posts
   - Posts belong to categories
   - Users comment on posts

## Database Considerations

1. **Indexing**
   - Primary keys on all tables
   - Foreign key relationships
   - Composite indexes for common queries

2. **Constraints**
   - Referential integrity
   - Unique constraints
   - Check constraints

3. **Performance**
   - Denormalization where necessary
   - Caching strategies
   - Query optimization

4. **Security**
   - Role-based access control
   - Data encryption
   - Audit logging 