/**
 * @file testUtils.ts
 * @description Comprehensive test utilities and helpers for maintaining DRY principles
 * @tags test-utils, helpers, mocks, fixtures
 */

import { vi, beforeEach, afterEach } from 'vitest'

// Global test configuration
export const TEST_CONFIG = {
  API_BASE_URL: 'http://localhost:8000',
  TIMEOUT: 10000,
  RETRY_ATTEMPTS: 3,
  MOCK_DELAY: 100
}

// Common mock data factories
export const createMockUser = (overrides = {}) => ({
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  email_verified_at: '2024-01-01T00:00:00Z',
  password: 'hashed_password',
  remember_token: 'remember_token_123',
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  profile_photo_path: '/storage/photos/profile.jpg',
  two_factor_secret: null,
  two_factor_recovery_codes: null,
  two_factor_confirmed_at: null,
  current_team_id: 1,
  personal_team_id: 1,
  is_admin: false,
  is_active: true,
  last_login_at: '2024-01-01T00:00:00Z',
  login_count: 5,
  preferences: { theme: 'dark', notifications: true },
  api_keys: [],
  health_checks: [],
  health_alerts: [],
  developer_credentials: [],
  codespaces: [],
  sniff_violations: [],
  sniff_results: [],
  environment_variables: [],
  memory_entries: [],
  health_metrics: [],
  health_check_results: [],
  ...overrides
})

export const createMockHealthData = (overrides = {}) => ({
  id: 1,
  system_status: 'healthy',
  cpu_usage: 45.2,
  memory_usage: 67.8,
  disk_usage: 23.1,
  network_status: 'stable',
  response_time: 125,
  error_rate: 0.02,
  uptime: 86400,
  last_check: '2024-01-01T12:00:00Z',
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T12:00:00Z',
  alerts: [],
  metrics: [],
  checks: [],
  ...overrides
})

export const createMockCodespace = (overrides = {}) => ({
  id: 1,
  name: 'test-codespace',
  display_name: 'Test Codespace',
  description: 'A test codespace for development',
  owner: 'test-user',
  repository: 'test-repo',
  branch: 'main',
  machine: 'standardLinux',
  location: 'WestUs2',
  idle_timeout_minutes: 30,
  retention_period_minutes: 1440,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  status: 'Available',
  last_used_at: '2024-01-01T12:00:00Z',
  user: null,
  user_id: null,
  github_id: null,
  environment: 'development',
  size: 'standardLinux',
  ...overrides
})

export const createMockApiKey = (overrides = {}) => {
  const baseApiKey = {
    id: 1,
    name: 'Test API Key',
    key: 'test_api_key_123',
    user_id: 1,
    permissions: ['read', 'write'],
    expires_at: '2025-01-01T00:00:00Z' as string,
    last_used_at: '2024-01-01T12:00:00Z',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    is_active: true,
    ...overrides
  }
  
  // Calculate status properties based on data
  const now = new Date()
  const expiresAt = new Date(baseApiKey.expires_at)
  const isExpired = expiresAt < now
  const isValid = baseApiKey.is_active && !isExpired
  
  return {
    ...baseApiKey,
    is_expired: isExpired,
    is_valid: isValid
  }
}

export const createMockHealthCheck = (overrides = {}) => ({
  id: 1,
  name: 'test-service',
  url: 'http://localhost:8000/health',
  method: 'GET',
  expected_status: 200,
  timeout: 30,
  interval: 60,
  is_active: true,
  last_check_at: '2024-01-01T12:00:00Z',
  last_status: 'healthy',
  last_response_time: 125,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  ...overrides
})

export const createMockHealthAlert = (overrides = {}) => ({
  id: 1,
  service_name: 'test-service',
  alert_type: 'high_cpu_usage',
  severity: 'warning',
  message: 'CPU usage is high',
  details: { cpu_usage: 85 },
  is_resolved: false,
  resolved_at: null as string | null,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  user: null,
  user_id: null,
  resolve: async function() {
    if (this.is_resolved) {
      return false
    }
    this.is_resolved = true
    this.resolved_at = new Date().toISOString()
    return true
  },
  isResolved: function() {
    return this.is_resolved
  },
  isActive: function() {
    return !this.is_resolved
  },
  isCritical: function() {
    return this.severity === 'critical'
  },
  isWarning: function() {
    return this.severity === 'warning'
  },
  ...overrides
})

export const createMockDeveloperCredential = (overrides = {}) => ({
  id: 1,
  user_id: 1,
  type: 'github',
  name: 'GitHub Personal Access Token',
  encrypted_value: 'encrypted_token_value',
  is_active: true,
  last_used_at: '2024-01-01T12:00:00Z',
  expires_at: '2025-01-01T00:00:00Z' as string,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  ...overrides
})

export const createMockEnvironmentVariable = (overrides = {}) => ({
  id: 1,
  name: 'DATABASE_URL',
  value: 'mysql://localhost:3306/test_db',
  environment: 'production',
  is_encrypted: true,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  ...overrides
})

// Mock service responses
export const createMockServiceResponse = (data: any, success = true) => ({
  success,
  data,
  message: success ? 'Success' : 'Error occurred',
  timestamp: new Date().toISOString()
})

// Mock API responses
export const createMockApiResponse = (data: any, status = 200) => ({
  status,
  data,
  headers: {
    'content-type': 'application/json',
    'x-request-id': 'test-request-id'
  }
})

// Mock error responses
export const createMockErrorResponse = (message: string, status = 400) => ({
  status,
  error: {
    message,
    code: status,
    details: []
  }
})

// Test utilities
export const waitFor = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

export const retryOperation = async <T>(
  operation: () => Promise<T>,
  maxAttempts = TEST_CONFIG.RETRY_ATTEMPTS,
  delay = TEST_CONFIG.MOCK_DELAY
): Promise<T> => {
  let lastError: Error
  
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await operation()
    } catch (error) {
      lastError = error as Error
      if (attempt < maxAttempts) {
        await waitFor(delay * attempt)
      }
    }
  }
  
  throw lastError!
}

// Validation helpers
export const validateEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

export const validatePassword = (password: string): boolean => {
  return password.length >= 8 &&
         /[A-Z]/.test(password) &&
         /[a-z]/.test(password) &&
         /[0-9]/.test(password) &&
         /[!@#$%^&*(),.?":{}|<>]/.test(password)
}

export const validateUrl = (url: string): boolean => {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

// Mock setup helpers
export const setupMockFetch = (responses: Record<string, any> = {}) => {
  const mockFetch = vi.fn()
  
  global.fetch = mockFetch
  
  // Default responses
  const defaultResponses = {
    'GET': createMockApiResponse({ message: 'Success' }),
    'POST': createMockApiResponse({ id: 1, created: true }),
    'PUT': createMockApiResponse({ id: 1, updated: true }),
    'DELETE': createMockApiResponse({ deleted: true })
  }
  
  mockFetch.mockImplementation((url: string, options: any = {}) => {
    const method = (options.method || 'GET') as keyof typeof defaultResponses
    const response = responses[url] || responses[method] || defaultResponses[method]
    
    return Promise.resolve({
      ok: response.status < 400,
      status: response.status,
      json: () => Promise.resolve(response.data),
      text: () => Promise.resolve(JSON.stringify(response.data)),
      headers: new Headers(response.headers)
    })
  })
  
  return mockFetch
}

export const setupMockLocalStorage = () => {
  const store: Record<string, string> = {}
  
  const localStorageMock = {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key]
    }),
    clear: vi.fn(() => {
      Object.keys(store).forEach(key => delete store[key])
    }),
    length: Object.keys(store).length,
    key: vi.fn((index: number) => Object.keys(store)[index] || null)
  }
  
  Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
    writable: true
  })
  
  return localStorageMock
}

export const setupMockSessionStorage = () => {
  const store: Record<string, string> = {}
  
  const sessionStorageMock = {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key]
    }),
    clear: vi.fn(() => {
      Object.keys(store).forEach(key => delete store[key])
    }),
    length: Object.keys(store).length,
    key: vi.fn((index: number) => Object.keys(store)[index] || null)
  }
  
  Object.defineProperty(window, 'sessionStorage', {
    value: sessionStorageMock,
    writable: true
  })
  
  return sessionStorageMock
}

// Test lifecycle helpers
export const setupTestEnvironment = () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setupMockLocalStorage()
    setupMockSessionStorage()
    setupMockFetch()
  })
  
  afterEach(() => {
    vi.clearAllMocks()
  })
}

// Assertion helpers
export const expectApiCall = (mockFn: any, expectedUrl: string, expectedMethod = 'GET') => {
  expect(mockFn).toHaveBeenCalledWith(
    expectedUrl,
    expect.objectContaining({
      method: expectedMethod
    })
  )
}

export const expectSuccessfulResponse = (response: any) => {
  expect(response).toBeDefined()
  expect(response.success).toBe(true)
  expect(response.data).toBeDefined()
}

export const expectErrorResponse = (response: any, expectedMessage?: string) => {
  expect(response).toBeDefined()
  expect(response.success).toBe(false)
  if (expectedMessage) {
    expect(response.message).toContain(expectedMessage)
  }
}

// Performance testing helpers
export const measurePerformance = async <T>(
  operation: () => Promise<T>
): Promise<{ result: T; duration: number }> => {
  const start = performance.now()
  const result = await operation()
  const duration = performance.now() - start
  
  return { result, duration }
}

export const expectPerformanceThreshold = (duration: number, threshold: number) => {
  expect(duration).toBeLessThan(threshold)
}

// Database mock helpers
export const createMockDatabase = () => {
  const tables: Record<string, any[]> = {}
  
  return {
    table: (tableName: string) => ({
      insert: vi.fn((data: any) => {
        if (!tables[tableName]) {
          tables[tableName] = []
        }
        const id = tables[tableName].length + 1
        const record = { id, ...data, created_at: new Date().toISOString() }
        tables[tableName].push(record)
        return Promise.resolve(record)
      }),
      where: vi.fn((column: string, value: any) => ({
        first: vi.fn(() => {
          const record = tables[tableName]?.find(r => r[column] === value)
          return Promise.resolve(record || null)
        }),
        get: vi.fn(() => {
          const records = tables[tableName]?.filter(r => r[column] === value) || []
          return Promise.resolve(records)
        }),
        update: vi.fn((data: any) => {
          const index = tables[tableName]?.findIndex(r => r[column] === value)
          if (index !== -1 && index !== undefined) {
            tables[tableName][index] = { ...tables[tableName][index], ...data, updated_at: new Date().toISOString() }
            return Promise.resolve(tables[tableName][index])
          }
          return Promise.resolve(null)
        }),
        delete: vi.fn(() => {
          const index = tables[tableName]?.findIndex(r => r[column] === value)
          if (index !== -1 && index !== undefined) {
            tables[tableName].splice(index, 1)
            return Promise.resolve(true)
          }
          return Promise.resolve(false)
        })
      })),
      all: vi.fn(() => Promise.resolve(tables[tableName] || []))
    }),
    getTables: () => tables
  }
}

// Event testing helpers
export const createMockEventEmitter = () => {
  const listeners: Record<string, Function[]> = {}
  
  return {
    on: vi.fn((event: string, listener: Function) => {
      if (!listeners[event]) {
        listeners[event] = []
      }
      listeners[event].push(listener)
    }),
    emit: vi.fn((event: string, ...args: any[]) => {
      const eventListeners = listeners[event] || []
      eventListeners.forEach(listener => listener(...args))
    }),
    off: vi.fn((event: string, listener: Function) => {
      if (listeners[event]) {
        const index = listeners[event].indexOf(listener)
        if (index !== -1) {
          listeners[event].splice(index, 1)
        }
      }
    }),
    getListeners: () => listeners
  }
}

// Export all utilities
export default {
  TEST_CONFIG,
  createMockUser,
  createMockHealthData,
  createMockCodespace,
  createMockApiKey,
  createMockHealthCheck,
  createMockHealthAlert,
  createMockDeveloperCredential,
  createMockEnvironmentVariable,
  createMockServiceResponse,
  createMockApiResponse,
  createMockErrorResponse,
  waitFor,
  retryOperation,
  validateEmail,
  validatePassword,
  validateUrl,
  setupMockFetch,
  setupMockLocalStorage,
  setupMockSessionStorage,
  setupTestEnvironment,
  expectApiCall,
  expectSuccessfulResponse,
  expectErrorResponse,
  measurePerformance,
  expectPerformanceThreshold,
  createMockDatabase,
  createMockEventEmitter
} 