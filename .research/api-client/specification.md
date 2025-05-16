# API Client Library Specification

## Overview
The API client library provides a clean, type-safe interface for interacting with the Learning Management System's API. It is designed to be used by both the Vue 3 frontend and external applications that need to integrate with the LMS.

## Core Features
- JWT-based authentication
- Type-safe request/response objects
- Automatic error handling
- Request/response interceptors
- Retry mechanisms
- Rate limiting
- Caching support
- Pagination handling
- File upload/download
- WebSocket support for real-time features

## API Client Structure

### Core Classes

#### LMSClient
```typescript
class LMSClient {
    constructor(config: ClientConfig);
    setAuthToken(token: string): void;
    clearAuthToken(): void;
    setTenant(tenantId: string): void;
    setBaseUrl(url: string): void;
    // ... other core methods
}
```

#### UserService
```typescript
class UserService {
    constructor(client: LMSClient);
    login(credentials: LoginCredentials): Promise<AuthResponse>;
    register(userData: UserRegistration): Promise<User>;
    getProfile(): Promise<UserProfile>;
    updateProfile(profile: UserProfileUpdate): Promise<UserProfile>;
    // ... other user methods
}
```

#### CourseService
```typescript
class CourseService {
    constructor(client: LMSClient);
    getCourses(params: CourseQueryParams): Promise<PaginatedResponse<Course>>;
    getCourse(id: string): Promise<Course>;
    createCourse(course: CourseCreate): Promise<Course>;
    updateCourse(id: string, course: CourseUpdate): Promise<Course>;
    // ... other course methods
}
```

#### PaymentService
```typescript
class PaymentService {
    constructor(client: LMSClient);
    createPayment(payment: PaymentCreate): Promise<Payment>;
    getPayment(id: string): Promise<Payment>;
    refundPayment(id: string): Promise<Payment>;
    // ... other payment methods
}
```

## API Endpoints

### Authentication
- POST `/api/auth/login`
- POST `/api/auth/register`
- POST `/api/auth/refresh`
- POST `/api/auth/logout`
- POST `/api/auth/forgot-password`
- POST `/api/auth/reset-password`

### Users
- GET `/api/users/profile`
- PUT `/api/users/profile`
- GET `/api/users/courses`
- GET `/api/users/enrollments`
- GET `/api/users/payments`

### Courses
- GET `/api/courses`
- POST `/api/courses`
- GET `/api/courses/{id}`
- PUT `/api/courses/{id}`
- DELETE `/api/courses/{id}`
- GET `/api/courses/{id}/sections`
- GET `/api/courses/{id}/lessons`

### Payments
- POST `/api/payments`
- GET `/api/payments/{id}`
- POST `/api/payments/{id}/refund`
- GET `/api/payments/history`

## Type Definitions

### User Types
```typescript
interface UserProfile {
    id: string;
    username: string;
    email: string;
    firstName: string;
    lastName: string;
    role: UserRole;
    isInstructor: boolean;
    socialLinks: SocialLinks;
    biography: string;
    // ... other user fields
}

interface UserRegistration {
    username: string;
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    // ... other registration fields
}
```

### Course Types
```typescript
interface Course {
    id: string;
    title: string;
    description: string;
    price: number;
    instructor: UserProfile;
    sections: Section[];
    // ... other course fields
}

interface Section {
    id: string;
    title: string;
    order: number;
    lessons: Lesson[];
    // ... other section fields
}

interface Lesson {
    id: string;
    title: string;
    content: string;
    duration: number;
    // ... other lesson fields
}
```

### Payment Types
```typescript
interface Payment {
    id: string;
    amount: number;
    currency: string;
    status: PaymentStatus;
    method: PaymentMethod;
    // ... other payment fields
}

interface PaymentCreate {
    courseId: string;
    amount: number;
    currency: string;
    method: PaymentMethod;
    // ... other payment creation fields
}
```

## Error Handling

### Error Classes
```typescript
class APIError extends Error {
    code: string;
    status: number;
    details?: any;
}

class ValidationError extends APIError {
    fields: Record<string, string[]>;
}

class AuthenticationError extends APIError {
    // ... authentication specific fields
}

class AuthorizationError extends APIError {
    // ... authorization specific fields
}
```

## Usage Examples

### Basic Usage
```typescript
const client = new LMSClient({
    baseUrl: 'https://api.lms.example.com',
    tenantId: 'tenant-123'
});

const userService = new UserService(client);

// Login
const auth = await userService.login({
    email: 'user@example.com',
    password: 'password123'
});

// Set auth token
client.setAuthToken(auth.token);

// Get user profile
const profile = await userService.getProfile();
```

### Vue 3 Integration
```typescript
// main.ts
import { createApp } from 'vue';
import { createLMSClient } from '@lms/client';

const app = createApp(App);
const client = createLMSClient({
    baseUrl: import.meta.env.VITE_API_URL,
    tenantId: import.meta.env.VITE_TENANT_ID
});

app.provide('lmsClient', client);
app.mount('#app');

// Component usage
export default defineComponent({
    inject: ['lmsClient'],
    setup() {
        const client = inject('lmsClient');
        const userService = new UserService(client);

        // Use service methods
        const login = async () => {
            try {
                const auth = await userService.login(credentials);
                // Handle success
            } catch (error) {
                // Handle error
            }
        };
    }
});
```

## Next Steps
1. Implement core client class
2. Create service classes
3. Add type definitions
4. Set up testing infrastructure
5. Create documentation
6. Implement Vue 3 integration
7. Add example applications 