# Assessment System Documentation

## Overview
The Assessment System is a core component of the LMS platform responsible for creating, managing, and evaluating quizzes and assessments. It is integrated with the Course Management System and provides tools for both instructors and students.

## Core Components

### 1. Quiz Management (Crud_model.php)
The assessment functionality is primarily handled within the Crud_model.php, providing:
- Quiz creation and management
- Question management
- Quiz submission handling
- Result tracking and analysis
- Progress monitoring

### 2. Data Structures

#### Quiz Table
```sql
CREATE TABLE quiz (
    id INT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    course_id INT,
    section_id INT,
    lesson_type VARCHAR(50),
    duration INT,
    total_marks INT,
    minimum_marks INT,
    date_added TIMESTAMP,
    last_modified TIMESTAMP,
    status VARCHAR(20)
);
```

#### Question Table
```sql
CREATE TABLE question (
    id INT PRIMARY KEY,
    quiz_id INT,
    title TEXT,
    type VARCHAR(50),
    number_of_options INT,
    options JSON,
    correct_answers JSON,
    marks INT,
    order_index INT,
    date_added TIMESTAMP,
    last_modified TIMESTAMP
);
```

#### Quiz Results Table
```sql
CREATE TABLE quiz_results (
    id INT PRIMARY KEY,
    quiz_id INT,
    user_id INT,
    course_id INT,
    obtained_marks INT,
    total_marks INT,
    quiz_result JSON,
    date_added TIMESTAMP,
    last_modified TIMESTAMP
);
```

## Core Methods

### 1. Quiz Management
```php
// Get quiz questions
public function get_quiz_questions($quiz_id)

// Save quiz result
public function save_quiz_result($course_id, $quiz_id, $obtained_marks)

// View answer sheet
public function view_answer_sheet($quiz_result_id)
```

### 2. Question Management
```php
// Add question
public function add_question($quiz_id)

// Edit question
public function edit_question($question_id)

// Delete question
public function delete_question($question_id)
```

### 3. Result Management
```php
// Submit quiz
public function submit_quiz($from = "")

// Get quiz results
public function get_quiz_results($quiz_id, $user_id)
```

## Question Types

### 1. Multiple Choice
- Single correct answer
- Multiple correct answers
- True/False

### 2. Short Answer
- Text-based responses
- Word limit enforcement
- Auto-grading support

### 3. Essay Questions
- Long-form responses
- Manual grading
- Feedback system

## Integration Points

### 1. Course System Integration
- Quiz embedding in lessons
- Progress tracking
- Course completion requirements
- Grade calculation

### 2. User System Integration
- Student performance tracking
- Instructor grading interface
- Progress reporting
- Achievement tracking

### 3. Analytics Integration
- Performance metrics
- Question analysis
- Time tracking
- Success rates

## Security Features

### 1. Assessment Security
- Time limit enforcement
- Question randomization
- Answer validation
- Anti-cheating measures

### 2. Data Protection
- Result encryption
- Access control
- Audit logging
- Data validation

## Migration Considerations

### 1. Database Changes
- [ ] Optimize quiz tables
- [ ] Add question versioning
- [ ] Implement result archiving
- [ ] Add performance indexes

### 2. Assessment Features
- [ ] Add question banks
- [ ] Implement adaptive testing
- [ ] Add question templates
- [ ] Support multimedia questions

### 3. Architecture Updates
- [ ] Separate assessment module
- [ ] Add assessment API
- [ ] Implement caching
- [ ] Add batch processing

## Testing Strategy

### 1. Unit Tests
- Question creation/editing
- Quiz submission
- Result calculation
- Grade validation

### 2. Integration Tests
- Course integration
- User system integration
- Analytics integration
- Performance testing

### 3. Security Tests
- Access control
- Data validation
- Anti-cheating measures
- Result integrity

## Future Enhancements

### 1. Assessment Features
- [ ] Adaptive testing
- [ ] Question banks
- [ ] Peer assessment
- [ ] Group quizzes
- [ ] Offline assessment

### 2. Analytics Features
- [ ] Detailed performance analysis
- [ ] Question difficulty tracking
- [ ] Learning path optimization
- [ ] Predictive analytics
- [ ] Custom reporting

### 3. Integration Features
- [ ] LTI integration
- [ ] External assessment tools
- [ ] Mobile assessment
- [ ] Offline synchronization
- [ ] API enhancements 