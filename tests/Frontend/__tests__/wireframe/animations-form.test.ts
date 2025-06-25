/**
 * @fileoverview Form Animation Tests
 * @description Tests for form-specific animation methods
 * @tags animations,form,frontend,wireframe,vitest,dom-manipulation
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'

// Mock AnimationHandler class for form animations
class AnimationHandler {
  animations: Record<string, any>

  constructor() {
    this.animations = {
      slideIn: {
        keyframes: [
          { transform: 'translateY(20px)', opacity: 0 },
          { transform: 'translateY(0)', opacity: 1 }
        ],
        options: {
          duration: 400,
          easing: 'ease-out'
        }
      }
    }
  }

  animate(element: HTMLElement, animationName: string) {
    const animation = this.animations[animationName]
    if (!animation) return null

    return {
      onfinish: null as (() => void) | null,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn()
    }
  }

  slideIn(element: HTMLElement) {
    return this.animate(element, 'slideIn')
  }

  animateValidation(input: HTMLElement, isValid: boolean) {
    const formGroup = input.closest('.form-group') as HTMLElement
    if (!formGroup) return

    const validationMessage = formGroup.querySelector('.form-validation-message') as HTMLElement
    if (!validationMessage) return

    // Reset classes
    input.classList.remove('is-valid', 'is-invalid')
    validationMessage.classList.remove('is-valid', 'is-invalid')

    // Add appropriate classes
    if (isValid) {
      input.classList.add('is-valid')
      validationMessage.classList.add('is-valid')
      this.slideIn(validationMessage)
    } else {
      input.classList.add('is-invalid')
      validationMessage.classList.add('is-invalid')
      this.slideIn(validationMessage)
    }
  }

  animateFormSubmit(form: HTMLElement) {
    const submitButtons = form.querySelectorAll('button[type="submit"]') as NodeListOf<HTMLButtonElement>
    submitButtons.forEach(button => {
      button.classList.add('button-loading')
    })
  }

  animateFormSuccess(form: HTMLElement) {
    const successMessages = form.querySelectorAll('.form-success-message') as NodeListOf<HTMLElement>
    successMessages.forEach(message => {
      this.slideIn(message)
    })
  }

  animateFormError(form: HTMLElement) {
    const errorMessages = form.querySelectorAll('.form-error-message') as NodeListOf<HTMLElement>
    errorMessages.forEach(message => {
      this.slideIn(message)
    })
  }
}

describe('Form Animation Functionality', () => {
  let animationHandler: AnimationHandler
  let mockForm: HTMLElement
  let mockInput: HTMLElement
  let mockFormGroup: HTMLElement
  let mockValidationMessage: HTMLElement
  let mockSubmitButton: HTMLButtonElement
  let mockSuccessMessage: HTMLElement
  let mockErrorMessage: HTMLElement

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Create mock DOM elements
    mockForm = document.createElement('form')
    mockInput = document.createElement('input')
    mockFormGroup = document.createElement('div')
    mockValidationMessage = document.createElement('div')
    mockSubmitButton = document.createElement('button')
    mockSuccessMessage = document.createElement('div')
    mockErrorMessage = document.createElement('div')
    
    // Setup DOM structure
    mockFormGroup.className = 'form-group'
    mockValidationMessage.className = 'form-validation-message'
    mockSuccessMessage.className = 'form-success-message'
    mockErrorMessage.className = 'form-error-message'
    mockSubmitButton.type = 'submit'
    
    mockFormGroup.appendChild(mockInput)
    mockFormGroup.appendChild(mockValidationMessage)
    mockForm.appendChild(mockFormGroup)
    mockForm.appendChild(mockSubmitButton)
    mockForm.appendChild(mockSuccessMessage)
    mockForm.appendChild(mockErrorMessage)
    
    animationHandler = new AnimationHandler()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Form Validation Animation', () => {
    it('should handle valid input validation', () => {
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateValidation(mockInput, true)
      
      expect(mockInput.classList.contains('is-valid')).toBe(true)
      expect(mockInput.classList.contains('is-invalid')).toBe(false)
      expect(mockValidationMessage.classList.contains('is-valid')).toBe(true)
      expect(mockValidationMessage.classList.contains('is-invalid')).toBe(false)
      expect(spy).toHaveBeenCalledWith(mockValidationMessage)
    })

    it('should handle invalid input validation', () => {
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateValidation(mockInput, false)
      
      expect(mockInput.classList.contains('is-valid')).toBe(false)
      expect(mockInput.classList.contains('is-invalid')).toBe(true)
      expect(mockValidationMessage.classList.contains('is-valid')).toBe(false)
      expect(mockValidationMessage.classList.contains('is-invalid')).toBe(true)
      expect(spy).toHaveBeenCalledWith(mockValidationMessage)
    })

    it('should reset classes before applying new ones', () => {
      // Set initial classes
      mockInput.classList.add('is-valid', 'is-invalid')
      mockValidationMessage.classList.add('is-valid', 'is-invalid')
      
      animationHandler.animateValidation(mockInput, true)
      
      expect(mockInput.classList.contains('is-valid')).toBe(true)
      expect(mockInput.classList.contains('is-invalid')).toBe(false)
      expect(mockValidationMessage.classList.contains('is-valid')).toBe(true)
      expect(mockValidationMessage.classList.contains('is-invalid')).toBe(false)
    })

    it('should handle input without form group', () => {
      const standaloneInput = document.createElement('input')
      
      expect(() => animationHandler.animateValidation(standaloneInput, true)).not.toThrow()
    })

    it('should handle form group without validation message', () => {
      const formGroupWithoutMessage = document.createElement('div')
      formGroupWithoutMessage.className = 'form-group'
      const inputWithoutMessage = document.createElement('input')
      formGroupWithoutMessage.appendChild(inputWithoutMessage)
      
      expect(() => animationHandler.animateValidation(inputWithoutMessage, true)).not.toThrow()
    })
  })

  describe('Form Submit Animation', () => {
    it('should add loading class to submit button', () => {
      animationHandler.animateFormSubmit(mockForm)
      
      expect(mockSubmitButton.classList.contains('button-loading')).toBe(true)
    })

    it('should handle form without submit button', () => {
      const formWithoutButton = document.createElement('form')
      
      expect(() => animationHandler.animateFormSubmit(formWithoutButton)).not.toThrow()
    })

    it('should handle form with multiple submit buttons', () => {
      const secondButton = document.createElement('button')
      secondButton.type = 'submit'
      mockForm.appendChild(secondButton)
      
      animationHandler.animateFormSubmit(mockForm)
      
      expect(mockSubmitButton.classList.contains('button-loading')).toBe(true)
      expect(secondButton.classList.contains('button-loading')).toBe(true)
    })
  })

  describe('Form Success Animation', () => {
    it('should animate success message', () => {
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateFormSuccess(mockForm)
      
      expect(spy).toHaveBeenCalledWith(mockSuccessMessage)
    })

    it('should handle form without success message', () => {
      const formWithoutSuccess = document.createElement('form')
      
      expect(() => animationHandler.animateFormSuccess(formWithoutSuccess)).not.toThrow()
    })

    it('should handle multiple success messages', () => {
      const secondSuccessMessage = document.createElement('div')
      secondSuccessMessage.className = 'form-success-message'
      mockForm.appendChild(secondSuccessMessage)
      
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateFormSuccess(mockForm)
      
      expect(spy).toHaveBeenCalledWith(mockSuccessMessage)
      expect(spy).toHaveBeenCalledWith(secondSuccessMessage)
      expect(spy).toHaveBeenCalledTimes(2)
    })
  })

  describe('Form Error Animation', () => {
    it('should animate error message', () => {
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateFormError(mockForm)
      
      expect(spy).toHaveBeenCalledWith(mockErrorMessage)
    })

    it('should handle form without error message', () => {
      const formWithoutError = document.createElement('form')
      
      expect(() => animationHandler.animateFormError(formWithoutError)).not.toThrow()
    })

    it('should handle multiple error messages', () => {
      const secondErrorMessage = document.createElement('div')
      secondErrorMessage.className = 'form-error-message'
      mockForm.appendChild(secondErrorMessage)
      
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateFormError(mockForm)
      
      expect(spy).toHaveBeenCalledWith(mockErrorMessage)
      expect(spy).toHaveBeenCalledWith(secondErrorMessage)
      expect(spy).toHaveBeenCalledTimes(2)
    })
  })

  describe('Form Animation Integration', () => {
    it('should handle complete form submission workflow', () => {
      const submitSpy = vi.spyOn(animationHandler, 'animateFormSubmit')
      const successSpy = vi.spyOn(animationHandler, 'animateFormSuccess')
      
      // Simulate form submission workflow
      animationHandler.animateFormSubmit(mockForm)
      animationHandler.animateFormSuccess(mockForm)
      
      expect(submitSpy).toHaveBeenCalledWith(mockForm)
      expect(successSpy).toHaveBeenCalledWith(mockForm)
      expect(mockSubmitButton.classList.contains('button-loading')).toBe(true)
    })

    it('should handle form submission with error', () => {
      const submitSpy = vi.spyOn(animationHandler, 'animateFormSubmit')
      const errorSpy = vi.spyOn(animationHandler, 'animateFormError')
      
      // Simulate form submission with error
      animationHandler.animateFormSubmit(mockForm)
      animationHandler.animateFormError(mockForm)
      
      expect(submitSpy).toHaveBeenCalledWith(mockForm)
      expect(errorSpy).toHaveBeenCalledWith(mockForm)
      expect(mockSubmitButton.classList.contains('button-loading')).toBe(true)
    })
  })
}) 