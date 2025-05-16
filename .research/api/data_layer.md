# API Data Layer Documentation

## Overview

The API data layer handles all database operations for the API endpoints, implementing a robust model structure for course management, user interactions, and content delivery.

## Core Model: Api_model

Located in `application/models/Api_model.php`, this model provides comprehensive data access methods for all API operations.

### Course Management

#### 1. Course Retrieval
```php
public function top_courses_get($top_course_id = "")
public function category_wise_course_get($category_id)
public function courses_by_search_string_get($search_string)
public function course_details_by_id_get($user_id = "", $course_id = "")
```

These methods handle:
- Featured courses retrieval
- Category-based course filtering
- Search functionality
- Detailed course information

#### 2. Course Filtering
```php
function filter_course()
```
Supports filtering by:
- Category
- Price (free/paid)
- Level
- Language
- Rating
- Search string

### Category Management

#### 1. Category Operations
```php
function all_categories_get()
public function categories_get($category_id)
public function sub_categories_get($parent_category_id)
```

Features:
- Hierarchical category structure
- Parent-child relationships
- Category metadata
- Course count per category

### User Progress Tracking

#### 1. Course Progress
```php
function save_course_progress_get($user_id = "")
private function course_completion_data($course_id = "", $user_id = "")
```

Tracks:
- Lesson completion
- Course progress
- Learning achievements
- Time spent

#### 2. Learning Analytics
```php
public function get_completed_number_of_lesson($user_id = "", $type = "", $id = "")
```

Provides:
- Completion statistics
- Learning patterns
- Progress metrics

### Content Organization

#### 1. Section Management
```php
public function sections_get($course_id = "", $user_id = "")
public function section_wise_lessons($section_id = "", $user_id = "")
```

Handles:
- Course structure
- Content organization
- Access control

#### 2. Lesson Management
```php
public function lesson_details_get($user_id = "", $lession_id = "")
```

Features:
- Lesson content delivery
- Progress tracking
- Access validation

### User Interaction

#### 1. Wishlist Management
```php
public function my_wishlist_get($user_id = "")
public function toggle_wishlist_items_get($user_id = "")
public function is_added_to_wishlist($user_id = 0, $course_id = "")
```

Supports:
- Wishlist operations
- Course bookmarking
- User preferences

#### 2. Purchase Verification
```php
public function is_purchased($user_id = 0, $course_id = "")
```

Handles:
- Course access rights
- Purchase validation
- Enrollment status

### Forum Integration

#### 1. Discussion Management
```php
public function forum_add_questions_post($user_id = "", $course_id = "")
public function forum_questions_get($user_id, $course_id = "", $page_number = 0, $limit = 20)
public function forum_child_questions_get($parent_question_id = "")
```

Features:
- Question posting
- Reply management
- Pagination
- Search functionality

#### 2. Interaction Tracking
```php
public function forum_question_vote_get($user_id = "", $question_id = "")
public function forum_question_delete_get($user_id = "", $question_id = "")
```

Handles:
- Vote management
- Content moderation
- User interactions

### Bundle Management

#### 1. Bundle Operations
```php
public function bundles_get($limit ="")
public function bundle_courses_get($bundle_id = "", $user_id = "")
public function my_bundles_get($user_id = "")
```

Supports:
- Bundle creation
- Course grouping
- Access management

## Data Transformation

### 1. Course Data Formatting
```php
public function course_data($courses = array())
```

Handles:
- Data normalization
- Price formatting
- Rating calculation
- Image URL generation

### 2. Media Management
```php
public function get_image($type, $identifier)
```

Features:
- Image URL generation
- Asset management
- Type-based handling

## Security Measures

### 1. Access Control
- User authentication validation
- Course access verification
- Role-based permissions

### 2. Data Validation
- Input sanitization
- Output formatting
- Error handling

## Integration Points

### 1. External Systems
- Payment gateway integration
- Social login connections
- Media storage systems

### 2. Internal Services
- Notification system
- Rating system
- Progress tracking

## Migration Considerations

When migrating to Laravel:

### 1. Model Structure
- Convert to Eloquent models
- Implement model relationships
- Use Laravel's query builder

### 2. Data Access
- Implement repository pattern
- Add service layer
- Use Laravel's caching

### 3. Authentication
- Integrate with Laravel Sanctum
- Implement API resources
- Add policy-based authorization

## Testing Strategy

### 1. Unit Tests
- Model method testing
- Data validation
- Error handling

### 2. Integration Tests
- API endpoint testing
- Database operations
- Cache integration

### 3. Performance Tests
- Query optimization
- Cache effectiveness
- Response times 