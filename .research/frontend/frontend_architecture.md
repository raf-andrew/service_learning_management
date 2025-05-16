# Frontend Architecture

## Overview
The Learning Management System (LMS) frontend is built using Vue 3 with a component-based architecture. It follows modern web development practices and provides a responsive, accessible, and performant user interface.

## Technology Stack

### Core Technologies
- Vue 3
- TypeScript
- Vite
- Pinia (State Management)
- Vue Router
- Tailwind CSS

### Development Tools
- ESLint
- Prettier
- TypeScript
- Jest
- Cypress

## Project Structure

```
src/
├── assets/
│   ├── images/
│   ├── styles/
│   └── fonts/
├── components/
│   ├── common/
│   ├── layout/
│   ├── course/
│   ├── user/
│   └── admin/
├── composables/
│   ├── useAuth.ts
│   ├── useCourse.ts
│   └── usePayment.ts
├── stores/
│   ├── auth.ts
│   ├── course.ts
│   └── user.ts
├── router/
│   └── index.ts
├── services/
│   ├── api.ts
│   ├── auth.ts
│   └── course.ts
├── types/
│   ├── course.ts
│   ├── user.ts
│   └── api.ts
└── views/
    ├── auth/
    ├── course/
    ├── dashboard/
    └── admin/
```

## Component Architecture

### 1. Common Components
- Button
- Input
- Card
- Modal
- Alert
- Loading
- Pagination

### 2. Layout Components
- Header
- Footer
- Sidebar
- Navigation
- Breadcrumb

### 3. Course Components
- CourseCard
- CourseList
- CourseDetail
- VideoPlayer
- ProgressTracker
- QuizComponent

### 4. User Components
- Profile
- Dashboard
- Enrollment
- Certificate

### 5. Admin Components
- UserManagement
- CourseManagement
- Analytics
- Settings

## State Management

### Pinia Stores

#### Auth Store
```typescript
interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false
  }),
  actions: {
    async login(credentials: LoginCredentials) {
      // Implementation
    },
    async logout() {
      // Implementation
    }
  }
});
```

#### Course Store
```typescript
interface CourseState {
  courses: Course[];
  currentCourse: Course | null;
  loading: boolean;
}

export const useCourseStore = defineStore('course', {
  state: (): CourseState => ({
    courses: [],
    currentCourse: null,
    loading: false
  }),
  actions: {
    async fetchCourses() {
      // Implementation
    },
    async enroll(courseId: number) {
      // Implementation
    }
  }
});
```

## Routing

### Route Configuration
```typescript
const routes = [
  {
    path: '/',
    component: Layout,
    children: [
      {
        path: '',
        name: 'Home',
        component: Home
      },
      {
        path: 'courses',
        name: 'Courses',
        component: CourseList
      },
      {
        path: 'courses/:id',
        name: 'CourseDetail',
        component: CourseDetail
      }
    ]
  },
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        name: 'Login',
        component: Login
      },
      {
        path: 'register',
        name: 'Register',
        component: Register
      }
    ]
  }
];
```

## API Integration

### API Service
```typescript
class ApiService {
  private baseUrl: string;
  private token: string | null;

  constructor() {
    this.baseUrl = import.meta.env.VITE_API_URL;
    this.token = localStorage.getItem('token');
  }

  async get<T>(endpoint: string): Promise<T> {
    // Implementation
  }

  async post<T>(endpoint: string, data: any): Promise<T> {
    // Implementation
  }
}

export const api = new ApiService();
```

## Styling

### Tailwind Configuration
```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#4F46E5',
          dark: '#4338CA'
        }
      }
    }
  }
};
```

### Component Styling
```vue
<template>
  <button class="btn btn-primary">
    <slot></slot>
  </button>
</template>

<style scoped>
.btn {
  @apply px-4 py-2 rounded-md font-medium;
}

.btn-primary {
  @apply bg-primary text-white hover:bg-primary-dark;
}
</style>
```

## Testing

### Unit Tests
```typescript
describe('CourseStore', () => {
  it('should fetch courses', async () => {
    const store = useCourseStore();
    await store.fetchCourses();
    expect(store.courses).toHaveLength(1);
  });
});
```

### Component Tests
```typescript
describe('CourseCard', () => {
  it('should render course title', () => {
    const wrapper = mount(CourseCard, {
      props: {
        course: {
          title: 'Test Course',
          description: 'Test Description'
        }
      }
    });
    expect(wrapper.text()).toContain('Test Course');
  });
});
```

## Performance Optimization

### Code Splitting
```typescript
const CourseDetail = () => import('@/views/CourseDetail.vue');
```

### Lazy Loading
```typescript
const routes = [
  {
    path: '/courses/:id',
    component: () => import('@/views/CourseDetail.vue')
  }
];
```

### Caching Strategy
```typescript
const useCourseStore = defineStore('course', {
  state: () => ({
    courses: [],
    cache: new Map()
  }),
  actions: {
    async fetchCourse(id: number) {
      if (this.cache.has(id)) {
        return this.cache.get(id);
      }
      const course = await api.get(`/courses/${id}`);
      this.cache.set(id, course);
      return course;
    }
  }
});
```

## Accessibility

### ARIA Attributes
```vue
<template>
  <button
    class="btn"
    aria-label="Close modal"
    @click="close"
  >
    <span aria-hidden="true">&times;</span>
  </button>
</template>
```

### Keyboard Navigation
```typescript
onMounted(() => {
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      close();
    }
  });
});
```

## Internationalization

### i18n Configuration
```typescript
const messages = {
  en: {
    course: {
      title: 'Course Title',
      description: 'Course Description'
    }
  },
  es: {
    course: {
      title: 'Título del Curso',
      description: 'Descripción del Curso'
    }
  }
};

const i18n = createI18n({
  locale: 'en',
  messages
});
```

## Deployment

### Build Configuration
```javascript
export default defineConfig({
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: true
  }
});
```

### Environment Variables
```env
VITE_API_URL=https://api.example.com
VITE_APP_NAME=Learning Management System
``` 