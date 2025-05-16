# Vue 3 Frontend Architecture

## Overview
This document outlines the architecture for migrating the LMS frontend to Vue 3, focusing on component structure, state management, and routing.

## Project Structure
```
src/
├── assets/
│   ├── images/
│   ├── styles/
│   └── fonts/
├── components/
│   ├── base/
│   │   ├── Button.vue
│   │   ├── Card.vue
│   │   └── Input.vue
│   ├── course/
│   │   ├── CourseCard.vue
│   │   ├── CourseList.vue
│   │   └── CourseDetail.vue
│   └── layout/
│       ├── Header.vue
│       ├── Footer.vue
│       └── Sidebar.vue
├── composables/
│   ├── useCourse.ts
│   ├── useUser.ts
│   └── useAuth.ts
├── router/
│   ├── index.ts
│   └── routes.ts
├── stores/
│   ├── course.ts
│   ├── user.ts
│   └── index.ts
├── types/
│   ├── course.ts
│   ├── user.ts
│   └── index.ts
└── views/
    ├── Home.vue
    ├── Course.vue
    └── Profile.vue
```

## Component Examples

### Base Button Component
```vue
<template>
  <button
    :class="[
      'btn',
      `btn-${variant}`,
      { 'btn-disabled': disabled }
    ]"
    :disabled="disabled"
    @click="$emit('click')"
  >
    <slot></slot>
  </button>
</template>

<script setup lang="ts">
defineProps<{
  variant: 'primary' | 'secondary' | 'danger'
  disabled?: boolean
}>()

defineEmits<{
  (e: 'click'): void
}>()
</script>
```

### Course Card Component
```vue
<template>
  <div class="course-card">
    <img :src="course.thumbnail" :alt="course.title">
    <div class="course-content">
      <h3>{{ course.title }}</h3>
      <p>{{ course.description }}</p>
      <div class="course-meta">
        <span>{{ course.duration }}</span>
        <span>{{ course.level }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course } from '@/types/course'

defineProps<{
  course: Course
}>()
</script>
```

## State Management

### Course Store
```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Course } from '@/types/course'

export const useCourseStore = defineStore('course', () => {
  const courses = ref<Course[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const featuredCourses = computed(() => 
    courses.value.filter(course => course.isFeatured)
  )

  async function fetchCourses() {
    loading.value = true
    try {
      const response = await fetch('/api/courses')
      courses.value = await response.json()
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  async function enroll(courseId: number) {
    // Implementation
  }

  return {
    courses,
    featuredCourses,
    loading,
    error,
    fetchCourses,
    enroll
  }
})
```

## Composables

### Course Composable
```typescript
import { ref, computed } from 'vue'
import type { Course } from '@/types/course'

export function useCourse(courseId: number) {
  const course = ref<Course | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isEnrolled = computed(() => 
    course.value?.enrollmentStatus === 'enrolled'
  )

  async function loadCourse() {
    loading.value = true
    try {
      const response = await fetch(`/api/courses/${courseId}`)
      course.value = await response.json()
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  return {
    course,
    loading,
    error,
    isEnrolled,
    loadCourse
  }
}
```

## Router Configuration

```typescript
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    component: () => import('@/views/Home.vue')
  },
  {
    path: '/courses/:id',
    component: () => import('@/views/Course.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else {
    next()
  }
})

export default router
```

## API Integration

```typescript
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
```

## Component Communication

### Event Bus
```typescript
import mitt from 'mitt'

type Events = {
  'course:enrolled': { courseId: number }
  'user:logged-in': { userId: number }
}

export const emitter = mitt<Events>()
```

## Styling Architecture

### SCSS Structure
```
styles/
├── _variables.scss
├── _mixins.scss
├── _functions.scss
├── base/
│   ├── _reset.scss
│   ├── _typography.scss
│   └── _utilities.scss
├── components/
│   ├── _buttons.scss
│   ├── _cards.scss
│   └── _forms.scss
└── themes/
    ├── _light.scss
    └── _dark.scss
```

### Theme System
```scss
:root {
  --primary-color: #{$primary-color};
  --secondary-color: #{$secondary-color};
  --text-color: #{$text-color};
  --background-color: #{$background-color};
}

[data-theme="dark"] {
  --primary-color: #{$primary-color-dark};
  --secondary-color: #{$secondary-color-dark};
  --text-color: #{$text-color-dark};
  --background-color: #{$background-color-dark};
}
```

## Testing Strategy

### Component Tests
```typescript
import { mount } from '@vue/test-utils'
import CourseCard from '@/components/course/CourseCard.vue'

describe('CourseCard', () => {
  it('renders course title and description', () => {
    const course = {
      title: 'Test Course',
      description: 'Test Description'
    }
    
    const wrapper = mount(CourseCard, {
      props: { course }
    })
    
    expect(wrapper.text()).toContain('Test Course')
    expect(wrapper.text()).toContain('Test Description')
  })
})
```

## Performance Optimization

### Code Splitting
```typescript
const CourseDetail = defineAsyncComponent(() =>
  import('@/views/CourseDetail.vue')
)
```

### Asset Optimization
```javascript
// vite.config.js
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia']
        }
      }
    }
  }
})
```

## Next Steps

1. [ ] Implement remaining base components
2. [ ] Set up state management for all features
3. [ ] Create API integration layer
4. [ ] Implement authentication flow
5. [ ] Set up testing infrastructure
6. [ ] Configure build and deployment
7. [ ] Create documentation
8. [ ] Set up CI/CD pipeline 