# Client Library Specification

## Overview
This document specifies the design and implementation of the client library that will enable other applications to interact with the LMS system through a clean, well-documented API.

## Core Features

### 1. Authentication
```typescript
interface AuthClient {
  login(credentials: LoginCredentials): Promise<AuthResponse>;
  logout(): Promise<void>;
  refreshToken(): Promise<AuthResponse>;
  getCurrentUser(): Promise<User>;
}

interface LoginCredentials {
  email: string;
  password: string;
}

interface AuthResponse {
  token: string;
  user: User;
  expiresIn: number;
}
```

### 2. Course Management
```typescript
interface CourseClient {
  list(params?: CourseListParams): Promise<PaginatedResponse<Course>>;
  get(id: string): Promise<Course>;
  create(data: CourseCreateData): Promise<Course>;
  update(id: string, data: CourseUpdateData): Promise<Course>;
  delete(id: string): Promise<void>;
}

interface Course {
  id: string;
  title: string;
  description: string;
  price: number;
  status: CourseStatus;
  createdAt: Date;
  updatedAt: Date;
}

interface CourseListParams {
  page?: number;
  limit?: number;
  search?: string;
  status?: CourseStatus;
}
```

### 3. User Management
```typescript
interface UserClient {
  list(params?: UserListParams): Promise<PaginatedResponse<User>>;
  get(id: string): Promise<User>;
  create(data: UserCreateData): Promise<User>;
  update(id: string, data: UserUpdateData): Promise<User>;
  delete(id: string): Promise<void>;
}

interface User {
  id: string;
  email: string;
  name: string;
  role: UserRole;
  status: UserStatus;
  createdAt: Date;
  updatedAt: Date;
}
```

## Implementation Details

### 1. Base Client
```typescript
class BaseClient {
  protected baseUrl: string;
  protected token: string | null;
  
  constructor(config: ClientConfig) {
    this.baseUrl = config.baseUrl;
    this.token = config.token || null;
  }
  
  protected async request<T>(
    method: HttpMethod,
    path: string,
    data?: any
  ): Promise<T> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json'
    };
    
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    
    const response = await fetch(`${this.baseUrl}${path}`, {
      method,
      headers,
      body: data ? JSON.stringify(data) : undefined
    });
    
    if (!response.ok) {
      throw new ApiError(response.status, await response.text());
    }
    
    return response.json();
  }
}
```

### 2. Error Handling
```typescript
class ApiError extends Error {
  constructor(
    public status: number,
    message: string
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

class ValidationError extends ApiError {
  constructor(
    public errors: Record<string, string[]>
  ) {
    super(422, 'Validation failed');
    this.name = 'ValidationError';
  }
}
```

### 3. Pagination
```typescript
interface PaginatedResponse<T> {
  data: T[];
  meta: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
  };
}
```

## Usage Examples

### 1. Basic Setup
```typescript
const client = new LmsClient({
  baseUrl: 'https://api.lms.example.com',
  token: 'your-api-token'
});

// Auth client
const auth = client.auth();
await auth.login({
  email: 'user@example.com',
  password: 'password'
});

// Course client
const courses = client.courses();
const courseList = await courses.list({
  page: 1,
  limit: 10
});
```

### 2. Error Handling
```typescript
try {
  const course = await courses.get('invalid-id');
} catch (error) {
  if (error instanceof ApiError) {
    console.error(`API Error: ${error.status} - ${error.message}`);
  } else if (error instanceof ValidationError) {
    console.error('Validation errors:', error.errors);
  }
}
```

## Testing

### 1. Unit Tests
```typescript
describe('CourseClient', () => {
  let client: CourseClient;
  let mockFetch: jest.Mock;
  
  beforeEach(() => {
    mockFetch = jest.fn();
    global.fetch = mockFetch;
    client = new CourseClient({
      baseUrl: 'https://api.example.com'
    });
  });
  
  it('lists courses', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        data: [{ id: '1', title: 'Test Course' }],
        meta: { currentPage: 1, lastPage: 1 }
      })
    });
    
    const result = await client.list();
    expect(result.data).toHaveLength(1);
    expect(result.data[0].title).toBe('Test Course');
  });
});
```

### 2. Integration Tests
```typescript
describe('LmsClient Integration', () => {
  let client: LmsClient;
  
  beforeAll(() => {
    client = new LmsClient({
      baseUrl: process.env.API_URL,
      token: process.env.API_TOKEN
    });
  });
  
  it('performs full course lifecycle', async () => {
    // Create course
    const course = await client.courses().create({
      title: 'Test Course',
      description: 'Test Description'
    });
    
    // Update course
    const updated = await client.courses().update(course.id, {
      title: 'Updated Title'
    });
    
    // Delete course
    await client.courses().delete(course.id);
    
    // Verify deletion
    await expect(client.courses().get(course.id))
      .rejects
      .toThrow(ApiError);
  });
});
```

## Documentation

### 1. API Documentation
```typescript
/**
 * LMS Client Library
 * @module lms-client
 */

/**
 * Configuration for the LMS client
 * @interface ClientConfig
 */
interface ClientConfig {
  /** Base URL for API requests */
  baseUrl: string;
  /** Optional API token for authentication */
  token?: string;
}

/**
 * Main client class for interacting with the LMS API
 * @class LmsClient
 */
class LmsClient {
  /**
   * Creates a new LMS client instance
   * @param {ClientConfig} config - Client configuration
   */
  constructor(config: ClientConfig) {
    // Implementation
  }
  
  /**
   * Returns the authentication client
   * @returns {AuthClient}
   */
  auth(): AuthClient {
    // Implementation
  }
}
```

### 2. Usage Guide
```markdown
# LMS Client Library Guide

## Installation
```bash
npm install @lms/client
```

## Basic Usage
```typescript
import { LmsClient } from '@lms/client';

const client = new LmsClient({
  baseUrl: 'https://api.lms.example.com',
  token: 'your-api-token'
});

// List courses
const courses = await client.courses().list();

// Create a course
const newCourse = await client.courses().create({
  title: 'New Course',
  description: 'Course Description'
});
```

## Error Handling
The client library throws specific error types for different scenarios:

- `ApiError`: General API errors
- `ValidationError`: Input validation errors
- `AuthenticationError`: Authentication failures

```typescript
try {
  await client.courses().create(invalidData);
} catch (error) {
  if (error instanceof ValidationError) {
    console.error('Validation errors:', error.errors);
  }
}
```
``` 