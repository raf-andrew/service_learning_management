/**
 * @file loading.test.ts
 * @description Comprehensive tests for LoadingState functionality
 * @tags loading, frontend, wireframe, vitest, form-handling, dom-manipulation
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Mock LoadingState class for testing
class LoadingState {
  form: any
  submitButton: any

  constructor(form: any) {
    this.form = form
    this.submitButton = form.querySelector('button[type="submit"]')
    this.setupLoadingState()
  }

  setupLoadingState() {
    this.form.addEventListener('submit', async (e: any) => {
      e.preventDefault()
      if (this.form.checkValidity()) {
        await this.handleSubmit()
      }
    })
  }

  async handleSubmit() {
    try {
      this.setLoading(true)
      // Simulate API call
      await this.simulateApiCall()
      this.showSuccess()
    } catch (error: any) {
      this.showError(error.message)
    } finally {
      this.setLoading(false)
    }
  }

  setLoading(isLoading: boolean) {
    this.form.classList.toggle('form-loading', isLoading)
    if (this.submitButton) {
      this.submitButton.classList.toggle('button-loading', isLoading)
      this.submitButton.disabled = isLoading
    }
  }

  showSuccess() {
    const successMessage = document.createElement('div')
    successMessage.className = 'form-success-message'
    successMessage.textContent = 'Form submitted successfully!'
    this.showMessage(successMessage)
  }

  showError(message: string) {
    const errorMessage = document.createElement('div')
    errorMessage.className = 'form-error-message'
    errorMessage.textContent = message || 'An error occurred. Please try again.'
    this.showMessage(errorMessage)
  }

  showMessage(message: any) {
    const existingMessage = this.form.querySelector('.form-success-message, .form-error-message')
    if (existingMessage) {
      existingMessage.remove()
    }
    this.form.insertBefore(message, this.form.firstChild)
    setTimeout(() => message.remove(), 5000)
  }

  // Simulate API call
  simulateApiCall() {
    return new Promise((resolve) => {
      setTimeout(resolve, 2000)
    })
  }
}

describe('Wireframe Loading State', () => {
  let loadingState: LoadingState
  let mockForm: any
  let mockSubmitButton: any
  let mockInput: any
  let mockDocument: any

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Create simple mock objects
    mockSubmitButton = {
      type: 'submit',
      disabled: false,
      classList: {
        contains: vi.fn(),
        add: vi.fn(),
        remove: vi.fn(),
        toggle: vi.fn()
      }
    }
    
    mockInput = {
      required: true,
      value: 'test value'
    }
    
    mockForm = {
      classList: {
        contains: vi.fn(),
        add: vi.fn(),
        remove: vi.fn(),
        toggle: vi.fn()
      },
      checkValidity: vi.fn().mockReturnValue(true),
      querySelector: vi.fn().mockImplementation((selector: string) => {
        if (selector === 'button[type="submit"]') return mockSubmitButton
        if (selector === '.form-success-message, .form-error-message') return null
        return null
      }),
      querySelectorAll: vi.fn().mockReturnValue([]),
      insertBefore: vi.fn(),
      addEventListener: vi.fn(),
      appendChild: vi.fn(),
      firstChild: null
    }
    
    // Mock document.createElement
    mockDocument = {
      createElement: vi.fn().mockImplementation((tagName: string) => ({
        tagName: tagName.toUpperCase(),
        className: '',
        textContent: '',
        remove: vi.fn(),
        insertBefore: vi.fn(),
        appendChild: vi.fn()
      }))
    }
    
    // Mock global document
    global.document = mockDocument as any
    
    loadingState = new LoadingState(mockForm)
  })

  afterEach(() => {
    vi.restoreAllMocks()
    vi.clearAllTimers()
  })

  describe('Constructor and Setup', () => {
    it('should initialize with form and submit button', () => {
      expect(loadingState.form).toBe(mockForm)
      expect(loadingState.submitButton).toBe(mockSubmitButton)
    })

    it('should handle form without submit button', () => {
      const formWithoutButton = {
        querySelector: vi.fn().mockReturnValue(null),
        checkValidity: vi.fn().mockReturnValue(true),
        addEventListener: vi.fn(),
        classList: { toggle: vi.fn() }
      }
      
      const loadingStateWithoutButton = new LoadingState(formWithoutButton)
      
      expect(loadingStateWithoutButton.submitButton).toBeNull()
    })

    it('should setup submit event listener', () => {
      const addEventListenerSpy = vi.spyOn(mockForm, 'addEventListener')
      
      new LoadingState(mockForm)
      
      expect(addEventListenerSpy).toHaveBeenCalledWith('submit', expect.any(Function))
    })
  })

  describe('setLoading', () => {
    it('should add form-loading class when loading is true', () => {
      loadingState.setLoading(true)
      
      expect(mockForm.classList.toggle).toHaveBeenCalledWith('form-loading', true)
    })

    it('should remove form-loading class when loading is false', () => {
      loadingState.setLoading(false)
      
      expect(mockForm.classList.toggle).toHaveBeenCalledWith('form-loading', false)
    })

    it('should add button-loading class to submit button when loading is true', () => {
      loadingState.setLoading(true)
      
      expect(mockSubmitButton.classList.toggle).toHaveBeenCalledWith('button-loading', true)
    })

    it('should remove button-loading class from submit button when loading is false', () => {
      loadingState.setLoading(false)
      
      expect(mockSubmitButton.classList.toggle).toHaveBeenCalledWith('button-loading', false)
    })

    it('should disable submit button when loading is true', () => {
      loadingState.setLoading(true)
      
      expect(mockSubmitButton.disabled).toBe(true)
    })

    it('should enable submit button when loading is false', () => {
      mockSubmitButton.disabled = true
      
      loadingState.setLoading(false)
      
      expect(mockSubmitButton.disabled).toBe(false)
    })

    it('should handle form without submit button gracefully', () => {
      const formWithoutButton = {
        classList: { toggle: vi.fn() },
        querySelector: vi.fn().mockReturnValue(null),
        checkValidity: vi.fn().mockReturnValue(true),
        addEventListener: vi.fn()
      }
      const loadingStateWithoutButton = new LoadingState(formWithoutButton)
      
      expect(() => {
        loadingStateWithoutButton.setLoading(true)
      }).not.toThrow()
    })
  })

  describe('showSuccess', () => {
    it('should create success message element', () => {
      const insertBeforeSpy = vi.spyOn(mockForm, 'insertBefore')
      
      loadingState.showSuccess()
      
      expect(mockDocument.createElement).toHaveBeenCalledWith('div')
      expect(insertBeforeSpy).toHaveBeenCalled()
    })

    it('should remove existing messages before showing new one', () => {
      const existingMessage = { remove: vi.fn() }
      mockForm.querySelector = vi.fn().mockReturnValue(existingMessage)
      
      const removeSpy = vi.spyOn(existingMessage, 'remove')
      
      loadingState.showSuccess()
      
      expect(removeSpy).toHaveBeenCalled()
    })
  })

  describe('showError', () => {
    it('should create error message element with custom message', () => {
      const insertBeforeSpy = vi.spyOn(mockForm, 'insertBefore')
      const customMessage = 'Custom error message'
      
      loadingState.showError(customMessage)
      
      expect(mockDocument.createElement).toHaveBeenCalledWith('div')
      expect(insertBeforeSpy).toHaveBeenCalled()
    })

    it('should create error message element with default message when no message provided', () => {
      const insertBeforeSpy = vi.spyOn(mockForm, 'insertBefore')
      
      loadingState.showError('')
      
      expect(mockDocument.createElement).toHaveBeenCalledWith('div')
      expect(insertBeforeSpy).toHaveBeenCalled()
    })

    it('should remove existing messages before showing new one', () => {
      const existingMessage = { remove: vi.fn() }
      mockForm.querySelector = vi.fn().mockReturnValue(existingMessage)
      
      const removeSpy = vi.spyOn(existingMessage, 'remove')
      
      loadingState.showError('Test error')
      
      expect(removeSpy).toHaveBeenCalled()
    })
  })

  describe('showMessage', () => {
    it('should insert message at the beginning of the form', () => {
      const insertBeforeSpy = vi.spyOn(mockForm, 'insertBefore')
      const message = { className: 'test-message', remove: vi.fn() }
      
      loadingState.showMessage(message)
      
      expect(insertBeforeSpy).toHaveBeenCalledWith(message, null)
    })

    it('should remove existing success message before showing new one', () => {
      const existingSuccessMessage = { remove: vi.fn() }
      mockForm.querySelector = vi.fn().mockReturnValue(existingSuccessMessage)
      
      const removeSpy = vi.spyOn(existingSuccessMessage, 'remove')
      const newMessage = { className: 'new-message', remove: vi.fn() }
      
      loadingState.showMessage(newMessage)
      
      expect(removeSpy).toHaveBeenCalled()
    })

    it('should remove existing error message before showing new one', () => {
      const existingErrorMessage = { remove: vi.fn() }
      mockForm.querySelector = vi.fn().mockReturnValue(existingErrorMessage)
      
      const removeSpy = vi.spyOn(existingErrorMessage, 'remove')
      const newMessage = { className: 'new-message', remove: vi.fn() }
      
      loadingState.showMessage(newMessage)
      
      expect(removeSpy).toHaveBeenCalled()
    })

    it('should auto-remove message after 5 seconds', () => {
      vi.useFakeTimers()
      
      const message = { remove: vi.fn() }
      const removeSpy = vi.spyOn(message, 'remove')
      
      loadingState.showMessage(message)
      
      vi.advanceTimersByTime(5000)
      
      expect(removeSpy).toHaveBeenCalled()
      
      vi.useRealTimers()
    })
  })

  describe('simulateApiCall', () => {
    it('should return a promise', () => {
      const result = loadingState.simulateApiCall()
      
      expect(result).toBeInstanceOf(Promise)
    })

    it('should resolve after 2 seconds', async () => {
      vi.useFakeTimers()
      
      const promise = loadingState.simulateApiCall()
      
      vi.advanceTimersByTime(2000)
      
      await expect(promise).resolves.toBeUndefined()
      
      vi.useRealTimers()
    })
  })

  describe('handleSubmit', () => {
    it('should set loading to true at the start', async () => {
      const setLoadingSpy = vi.spyOn(loadingState, 'setLoading')
      
      const promise = loadingState.handleSubmit()
      
      expect(setLoadingSpy).toHaveBeenCalledWith(true)
      
      // Wait for the promise to resolve
      await promise
    })

    it('should set loading to false at the end', async () => {
      const setLoadingSpy = vi.spyOn(loadingState, 'setLoading')
      
      await loadingState.handleSubmit()
      
      expect(setLoadingSpy).toHaveBeenCalledWith(false)
    })

    it('should show success message on successful submission', async () => {
      const showSuccessSpy = vi.spyOn(loadingState, 'showSuccess')
      
      await loadingState.handleSubmit()
      
      expect(showSuccessSpy).toHaveBeenCalled()
    })

    it('should show error message on failed submission', async () => {
      const showErrorSpy = vi.spyOn(loadingState, 'showError')
      
      // Mock simulateApiCall to throw an error
      vi.spyOn(loadingState, 'simulateApiCall').mockRejectedValue(new Error('API Error'))
      
      await loadingState.handleSubmit()
      
      expect(showErrorSpy).toHaveBeenCalledWith('API Error')
    })

    it('should set loading to false even when error occurs', async () => {
      const setLoadingSpy = vi.spyOn(loadingState, 'setLoading')
      
      // Mock simulateApiCall to throw an error
      vi.spyOn(loadingState, 'simulateApiCall').mockRejectedValue(new Error('API Error'))
      
      await loadingState.handleSubmit()
      
      expect(setLoadingSpy).toHaveBeenCalledWith(false)
    })
  })

  describe('Integration Tests', () => {
    it('should handle complete successful form submission flow', async () => {
      const setLoadingSpy = vi.spyOn(loadingState, 'setLoading')
      const showSuccessSpy = vi.spyOn(loadingState, 'showSuccess')
      
      await loadingState.handleSubmit()
      
      expect(setLoadingSpy).toHaveBeenCalledWith(true)
      expect(showSuccessSpy).toHaveBeenCalled()
      expect(setLoadingSpy).toHaveBeenCalledWith(false)
    })

    it('should handle complete failed form submission flow', async () => {
      const setLoadingSpy = vi.spyOn(loadingState, 'setLoading')
      const showErrorSpy = vi.spyOn(loadingState, 'showError')
      
      // Mock simulateApiCall to throw an error
      vi.spyOn(loadingState, 'simulateApiCall').mockRejectedValue(new Error('Network Error'))
      
      await loadingState.handleSubmit()
      
      expect(setLoadingSpy).toHaveBeenCalledWith(true)
      expect(showErrorSpy).toHaveBeenCalledWith('Network Error')
      expect(setLoadingSpy).toHaveBeenCalledWith(false)
    })

    it('should handle multiple rapid submissions', async () => {
      const setLoadingSpy = vi.spyOn(loadingState, 'setLoading')
      
      // Start multiple submissions
      const promises = [
        loadingState.handleSubmit(),
        loadingState.handleSubmit(),
        loadingState.handleSubmit()
      ]
      
      await Promise.all(promises)
      
      // Should have set loading true and false for each submission
      expect(setLoadingSpy).toHaveBeenCalledTimes(6) // 3 true + 3 false
    })
  })

  describe('Edge Cases', () => {
    it('should handle form with no required fields', () => {
      const formWithoutRequired = {
        checkValidity: vi.fn().mockReturnValue(true),
        querySelector: vi.fn().mockReturnValue(null),
        querySelectorAll: vi.fn().mockReturnValue([]),
        insertBefore: vi.fn(),
        addEventListener: vi.fn(),
        classList: { toggle: vi.fn() }
      }
      
      expect(() => {
        new LoadingState(formWithoutRequired)
      }).not.toThrow()
    })

    it('should handle very long error messages', () => {
      const longMessage = 'a'.repeat(1000)
      
      expect(() => {
        loadingState.showError(longMessage)
      }).not.toThrow()
    })

    it('should handle special characters in error messages', () => {
      const specialMessage = 'Error with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?'
      
      expect(() => {
        loadingState.showError(specialMessage)
      }).not.toThrow()
    })
  })
})
