# Database Schema Analysis

## Core Entities

### User Management
- **User** model serves as the central identity management entity
- Supports social login through `social_login` JSON field
- Role-based access control through `role` field
- Profile management with `profile_photo` support

### Course Management
- **Course** model contains core course information
- Includes pricing, requirements, and learning outcomes
- Links to instructor through `instructor_id`
- Status tracking for course availability

### Content Structure
- Hierarchical content organization:
  - Course → Section → Lesson
- Order management for both sections and lessons
- Video content support through `video_url`
- Duration tracking for lessons

### Enrollment & Progress
- **Enrollment** model tracks student progress
- Status tracking for enrollment state
- Completion tracking with timestamps
- Links user to course

### Payment System
- **Payment** model for course purchases
- **Payout** model for instructor earnings
- Multiple payment method support
- Status tracking for transactions

## Key Relationships
1. User → Course (One-to-Many as instructor)
2. User → Enrollment (One-to-Many)
3. Course → Section (One-to-Many)
4. Section → Lesson (One-to-Many)
5. User → Payment (One-to-Many)
6. User → Payout (One-to-Many as instructor)

## Modularization Considerations
1. User management could be externalized
2. Payment processing could be a separate service
3. Content delivery could be isolated
4. Progress tracking could be modularized

## Multi-tenant Considerations
1. Need to add tenant_id to relevant tables
2. Consider tenant-specific configurations
3. Plan for tenant isolation strategies
4. Design for tenant-specific customizations 