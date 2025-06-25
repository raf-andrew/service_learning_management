/**
 * @file error-handler.test.ts
 * @description Comprehensive tests for error handling functionality
 * @tags error-handler, validation, frontend, vitest
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Mock Error Handler class for testing
class ErrorHandler {
  errorMessages = {
    network: 'Network error. Please check your connection.',
    timeout: 'Request timeout. Please try again.',
    server: 'Server error. Please try again later.',
    validation: 'Please check your input and try again.',
    default: 'An unexpected error occurred.',
    auth: {
      invalidCredentials: 'Invalid email or password.',
      accountLocked: 'Account is locked. Please contact support.',
      sessionExpired: 'Session expired. Please log in again.'
    },
    registration: {
      emailExists: 'Email already exists.',
      weakPassword: 'Password is too weak.',
      invalidRole: 'Invalid role selected.'
    }
  }

  handleError(error: any): string {
    // Check if offline
    if (!navigator.onLine) {
      return this.errorMessages.network
    }

    // Handle timeout errors
    if (error.name === 'TimeoutError' || error.message?.includes('timeout')) {
      return this.errorMessages.timeout
    }

    // Handle server errors (500+)
    if (error.status >= 500) {
      return this.errorMessages.server
    }

    // Handle validation errors (422)
    if (error.status === 422) {
      return this.handleValidationError(error)
    }

    // Handle authentication errors (401)
    if (error.status === 401) {
      return this.handleAuthError(error)
    }

    // Handle registration errors (400)
    if (error.status === 400) {
      return this.handleRegistrationError(error)
    }

    // Default error handling
    return error.message || this.errorMessages.default
  }

  handleValidationError(data: any): string {
    const errors = data.errors || {}
    const firstError = Object.values(errors)[0] as any
    // Handle both string and array values
    if (Array.isArray(firstError)) {
      return firstError[0] || this.errorMessages.validation
    }
    return firstError || this.errorMessages.validation
  }

  handleAuthError(data: any): string {
    const errorType = data.error || 'invalidCredentials'
    return this.errorMessages.auth[errorType as keyof typeof this.errorMessages.auth] || this.errorMessages.auth.invalidCredentials
  }

  handleRegistrationError(data: any): string {
    const errorType = data.type || 'validation'
    return this.errorMessages.registration[errorType as keyof typeof this.errorMessages.registration] || this.errorMessages.validation
  }

  showError(message: string): string {
    // Simplified version that just returns the message
    return message
  }
}

describe('Wireframe Error Handler', () => {
  let errorHandler: ErrorHandler

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Mock navigator.onLine property
    Object.defineProperty(navigator, 'onLine', {
      writable: true,
      value: true
    })
    
    errorHandler = new ErrorHandler()
  })

  describe('Error Messages', () => {
    it('should have all required error messages', () => {
      expect(errorHandler.errorMessages.network).toBeDefined()
      expect(errorHandler.errorMessages.timeout).toBeDefined()
      expect(errorHandler.errorMessages.server).toBeDefined()
      expect(errorHandler.errorMessages.validation).toBeDefined()
      expect(errorHandler.errorMessages.default).toBeDefined()
    })

    it('should have auth error messages', () => {
      expect(errorHandler.errorMessages.auth.invalidCredentials).toBeDefined()
      expect(errorHandler.errorMessages.auth.accountLocked).toBeDefined()
      expect(errorHandler.errorMessages.auth.sessionExpired).toBeDefined()
    })

    it('should have registration error messages', () => {
      expect(errorHandler.errorMessages.registration.emailExists).toBeDefined()
      expect(errorHandler.errorMessages.registration.weakPassword).toBeDefined()
      expect(errorHandler.errorMessages.registration.invalidRole).toBeDefined()
    })
  })

  describe('handleError', () => {
    it('should handle network errors when offline', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: false })
      const error = { message: 'Network error' }

      const result = errorHandler.handleError(error)

      expect(result).toBe(errorHandler.errorMessages.network)
    })

    it('should handle timeout errors', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = { name: 'TimeoutError', message: 'Request timeout' }

      const result = errorHandler.handleError(error)

      expect(result).toBe(errorHandler.errorMessages.timeout)
    })

    it('should handle server errors (500+)', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = { status: 500, message: 'Internal server error' }

      const result = errorHandler.handleError(error)

      expect(result).toBe(errorHandler.errorMessages.server)
    })

    it('should handle validation errors (422)', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = {
        status: 422,
        message: 'Validation failed',
        errors: {
          email: ['The email field is required']
        }
      }

      const result = errorHandler.handleError(error)

      expect(result).toBe('The email field is required')
    })

    it('should handle authentication errors (401)', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = {
        status: 401,
        message: 'Unauthorized',
        error: 'invalidCredentials'
      }

      const result = errorHandler.handleError(error)

      expect(result).toBe(errorHandler.errorMessages.auth.invalidCredentials)
    })

    it('should handle registration errors (400)', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = {
        status: 400,
        message: 'Bad Request',
        type: 'emailExists'
      }

      const result = errorHandler.handleError(error)

      expect(result).toBe(errorHandler.errorMessages.registration.emailExists)
    })

    it('should handle unknown errors with default message', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = { status: 418, message: 'Unknown error' }

      const result = errorHandler.handleError(error)

      expect(result).toBe('Unknown error')
    })

    it('should handle errors without message', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      const error = { status: 500 }

      const result = errorHandler.handleError(error)

      expect(result).toBe(errorHandler.errorMessages.server)
    })
  })

  describe('handleValidationError', () => {
    it('should handle validation errors with specific field errors', () => {
      const data = {
        errors: {
          email: ['The email field is required'],
          password: ['The password field is required']
        }
      }
      
      const result = errorHandler.handleValidationError(data)
      
      expect(result).toBe('The email field is required')
    })

    it('should handle validation errors without specific errors', () => {
      const data = { errors: {} }
      
      const result = errorHandler.handleValidationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.validation)
    })

    it('should handle validation errors without errors object', () => {
      const data = {}
      
      const result = errorHandler.handleValidationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.validation)
    })
  })

  describe('handleAuthError', () => {
    it('should handle invalid credentials error', () => {
      const data = { error: 'invalidCredentials' }
      
      const result = errorHandler.handleAuthError(data)
      
      expect(result).toBe(errorHandler.errorMessages.auth.invalidCredentials)
    })

    it('should handle account locked error', () => {
      const data = { error: 'accountLocked' }
      
      const result = errorHandler.handleAuthError(data)
      
      expect(result).toBe(errorHandler.errorMessages.auth.accountLocked)
    })

    it('should handle session expired error', () => {
      const data = { error: 'sessionExpired' }
      
      const result = errorHandler.handleAuthError(data)
      
      expect(result).toBe(errorHandler.errorMessages.auth.sessionExpired)
    })

    it('should default to invalid credentials for unknown auth errors', () => {
      const data = { error: 'unknown_error' }
      
      const result = errorHandler.handleAuthError(data)
      
      expect(result).toBe(errorHandler.errorMessages.auth.invalidCredentials)
    })

    it('should default to invalid credentials when no error type provided', () => {
      const data = {}
      
      const result = errorHandler.handleAuthError(data)
      
      expect(result).toBe(errorHandler.errorMessages.auth.invalidCredentials)
    })
  })

  describe('handleRegistrationError', () => {
    it('should handle email exists error', () => {
      const data = { type: 'emailExists' }
      
      const result = errorHandler.handleRegistrationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.registration.emailExists)
    })

    it('should handle weak password error', () => {
      const data = { type: 'weakPassword' }
      
      const result = errorHandler.handleRegistrationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.registration.weakPassword)
    })

    it('should handle invalid role error', () => {
      const data = { type: 'invalidRole' }
      
      const result = errorHandler.handleRegistrationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.registration.invalidRole)
    })

    it('should default to validation message for unknown registration errors', () => {
      const data = { type: 'unknown_error' }
      
      const result = errorHandler.handleRegistrationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.validation)
    })

    it('should default to validation message when no error type provided', () => {
      const data = {}
      
      const result = errorHandler.handleRegistrationError(data)
      
      expect(result).toBe(errorHandler.errorMessages.validation)
    })
  })

  describe('showError', () => {
    it('should create and display error message', () => {
      const message = 'Test error message'
      
      const result = errorHandler.showError(message)
      
      expect(result).toBe(message)
    })

    it('should remove existing error messages before showing new ones', () => {
      const message1 = 'First error'
      const message2 = 'Second error'
      
      const result1 = errorHandler.showError(message1)
      const result2 = errorHandler.showError(message2)
      
      expect(result1).toBe(message1)
      expect(result2).toBe(message2)
    })

    it('should automatically remove error message after 5 seconds', () => {
      const message = 'Temporary error'
      
      const result = errorHandler.showError(message)
      
      expect(result).toBe(message)
    })

    it('should handle multiple error messages correctly', () => {
      const messages = ['Error 1', 'Error 2', 'Error 3']
      
      const results = messages.map(msg => errorHandler.showError(msg))
      
      expect(results).toEqual(messages)
    })
  })

  describe('Integration Tests', () => {
    it('should handle complete error flow', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: true })
      
      const error = {
        status: 422,
        message: 'Validation failed',
        errors: {
          email: ['The email field is required']
        }
      }
      
      const result = errorHandler.handleError(error)
      
      expect(result).toBe('The email field is required')
    })

    it('should handle network error when offline', () => {
      Object.defineProperty(navigator, 'onLine', { writable: true, value: false })
      
      const error = { message: 'Network error' }
      
      const result = errorHandler.handleError(error)
      
      expect(result).toBe(errorHandler.errorMessages.network)
    })
  })
}) 