/**
 * @fileoverview Test utilities for frontend testing
 * @tags testing,frontend,utilities
 */

import { vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import type { ComponentMountingOptions } from '@vue/test-utils'

// Mock Axios
export const mockAxios = {
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
  patch: vi.fn(),
  defaults: {
    headers: {
      common: {}
    }
  }
}

// Mock Inertia
export const mockInertia = {
  visit: vi.fn(),
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  reload: vi.fn(),
  replace: vi.fn(),
  only: vi.fn(),
  except: vi.fn(),
  preserveState: vi.fn(),
  preserveScroll: vi.fn(),
  reset: vi.fn(),
  setQueryString: vi.fn(),
  setHeaders: vi.fn(),
  setErrorBag: vi.fn(),
  setValidationErrors: vi.fn(),
  clearErrors: vi.fn(),
  clearValidationErrors: vi.fn(),
  clearErrorBag: vi.fn(),
  clearQueryString: vi.fn(),
  clearHeaders: vi.fn(),
  clearState: vi.fn(),
  clearScroll: vi.fn(),
  clearValidation: vi.fn(),
  clearAll: vi.fn()
}

// Mock Router
export const mockRouter = {
  push: vi.fn(),
  replace: vi.fn(),
  go: vi.fn(),
  back: vi.fn(),
  forward: vi.fn(),
  currentRoute: {
    value: {
      path: '/',
      name: 'home',
      params: {},
      query: {},
      meta: {}
    }
  }
}

// Mock Store
export const mockStore = {
  state: {},
  getters: {},
  commit: vi.fn(),
  dispatch: vi.fn(),
  subscribe: vi.fn()
}

// Mock Window
export const mockWindow = {
  location: {
    href: 'http://localhost:3000',
    origin: 'http://localhost:3000',
    pathname: '/',
    search: '',
    hash: ''
  },
  history: {
    pushState: vi.fn(),
    replaceState: vi.fn(),
    go: vi.fn(),
    back: vi.fn(),
    forward: vi.fn()
  },
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  dispatchEvent: vi.fn()
}

// Mock Document
export const mockDocument = {
  title: 'Service Learning Management',
  querySelector: vi.fn(),
  querySelectorAll: vi.fn(),
  getElementById: vi.fn(),
  getElementsByClassName: vi.fn(),
  getElementsByTagName: vi.fn(),
  createElement: vi.fn(),
  addEventListener: vi.fn(),
  removeEventListener: vi.fn()
}

// Mock Console
export const mockConsole = {
  log: vi.fn(),
  debug: vi.fn(),
  info: vi.fn(),
  warn: vi.fn(),
  error: vi.fn(),
  group: vi.fn(),
  groupEnd: vi.fn(),
  table: vi.fn()
}

// Test wrapper factory
export function createTestWrapper<T>(
  component: T,
  options: ComponentMountingOptions<T> = {}
): VueWrapper<T> {
  return mount(component, {
    global: {
      mocks: {
        $axios: mockAxios,
        $inertia: mockInertia,
        $router: mockRouter,
        $store: mockStore
      },
      stubs: {
        'router-link': true,
        'router-view': true
      }
    },
    ...options
  })
}

// Mock API response helper
export function mockApiResponse(data: any, status = 200) {
  return {
    data,
    status,
    statusText: status === 200 ? 'OK' : 'Error',
    headers: {},
    config: {}
  }
}

// Mock API error helper
export function mockApiError(message: string, status = 500) {
  return {
    response: {
      data: { message },
      status,
      statusText: 'Error',
      headers: {},
      config: {}
    },
    message,
    status
  }
}

// Wait for next tick
export function waitForNextTick() {
  return new Promise(resolve => setTimeout(resolve, 0))
}

// Wait for specific time
export function waitFor(ms: number) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

// Mock fetch with response
export function mockFetchResponse(data: any, status = 200) {
  const response = {
    ok: status >= 200 && status < 300,
    status,
    statusText: status === 200 ? 'OK' : 'Error',
    json: () => Promise.resolve(data),
    text: () => Promise.resolve(JSON.stringify(data)),
    headers: new Map()
  }
  
  global.fetch = vi.fn().mockResolvedValue(response)
  return response
}

// Mock fetch with error
export function mockFetchError(message: string, status = 500) {
  const error = new Error(message)
  ;(error as any).status = status
  global.fetch = vi.fn().mockRejectedValue(error)
  return error
}

// Reset all mocks
export function resetAllMocks() {
  vi.clearAllMocks()
  mockAxios.get.mockReset()
  mockAxios.post.mockReset()
  mockAxios.put.mockReset()
  mockAxios.delete.mockReset()
  mockAxios.patch.mockReset()
  mockInertia.visit.mockReset()
  mockRouter.push.mockReset()
  mockStore.commit.mockReset()
  mockStore.dispatch.mockReset()
}

// Setup test environment
export function setupTestEnvironment() {
  beforeEach(() => {
    resetAllMocks()
    
    // Mock global objects
    Object.defineProperty(window, 'location', {
      value: mockWindow.location,
      writable: true
    })
    
    Object.defineProperty(window, 'history', {
      value: mockWindow.history,
      writable: true
    })
    
    Object.defineProperty(document, 'title', {
      value: mockDocument.title,
      writable: true
    })
    
    // Mock console
    global.console = mockConsole as any
    
    // Mock fetch
    global.fetch = vi.fn()
  })
  
  afterEach(() => {
    resetAllMocks()
  })
}

// Export all mocks for easy access
export const mocks = {
  axios: mockAxios,
  inertia: mockInertia,
  router: mockRouter,
  store: mockStore,
  window: mockWindow,
  document: mockDocument,
  console: mockConsole
} 