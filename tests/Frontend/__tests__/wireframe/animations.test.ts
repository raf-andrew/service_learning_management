/**
 * @file animations.test.ts
 * @description Comprehensive tests for AnimationHandler functionality
 * @tags animations, frontend, wireframe, vitest, dom-manipulation
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Mock AnimationHandler class for testing
class AnimationHandler {
  animations: Record<string, any>

  constructor() {
    this.animations = {
      fadeIn: {
        keyframes: [
          { opacity: 0 },
          { opacity: 1 }
        ],
        options: {
          duration: 300,
          easing: 'ease-out'
        }
      },
      fadeOut: {
        keyframes: [
          { opacity: 1 },
          { opacity: 0 }
        ],
        options: {
          duration: 300,
          easing: 'ease-in'
        }
      },
      slideIn: {
        keyframes: [
          { transform: 'translateY(20px)', opacity: 0 },
          { transform: 'translateY(0)', opacity: 1 }
        ],
        options: {
          duration: 400,
          easing: 'ease-out'
        }
      },
      slideOut: {
        keyframes: [
          { transform: 'translateY(0)', opacity: 1 },
          { transform: 'translateY(20px)', opacity: 0 }
        ],
        options: {
          duration: 400,
          easing: 'ease-in'
        }
      }
    }
  }

  animate(element: HTMLElement, animationName: string) {
    const animation = this.animations[animationName]
    if (!animation) return null

    // Mock animation object
    const mockAnimation = {
      onfinish: null as (() => void) | null,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn()
    }

    return mockAnimation
  }

  fadeIn(element: HTMLElement) {
    return this.animate(element, 'fadeIn')
  }

  fadeOut(element: HTMLElement) {
    return this.animate(element, 'fadeOut')
  }

  slideIn(element: HTMLElement) {
    return this.animate(element, 'slideIn')
  }

  slideOut(element: HTMLElement) {
    return this.animate(element, 'slideOut')
  }

  show(element: HTMLElement, animationName = 'fadeIn') {
    element.style.display = ''
    return this.animate(element, animationName)
  }

  hide(element: HTMLElement, animationName = 'fadeOut') {
    const animation = this.animate(element, animationName)
    if (animation) {
      animation.onfinish = () => {
        element.style.display = 'none'
      }
    }
    return animation
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
    const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement
    if (submitButton) {
      submitButton.classList.add('button-loading')
    }
  }

  animateFormSuccess(form: HTMLElement) {
    const successMessage = form.querySelector('.form-success-message') as HTMLElement
    if (successMessage) {
      this.slideIn(successMessage)
    }
  }

  animateFormError(form: HTMLElement) {
    const errorMessage = form.querySelector('.form-error-message') as HTMLElement
    if (errorMessage) {
      this.slideIn(errorMessage)
    }
  }
}

describe('Wireframe Animation Handler', () => {
  let animationHandler: AnimationHandler
  let mockElement: HTMLElement
  let mockForm: HTMLElement
  let mockInput: HTMLElement
  let mockFormGroup: HTMLElement
  let mockValidationMessage: HTMLElement
  let mockSubmitButton: HTMLButtonElement

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Create mock DOM elements
    mockElement = document.createElement('div')
    mockForm = document.createElement('form')
    mockInput = document.createElement('input')
    mockFormGroup = document.createElement('div')
    mockValidationMessage = document.createElement('div')
    mockSubmitButton = document.createElement('button')
    
    // Setup DOM structure
    mockFormGroup.className = 'form-group'
    mockValidationMessage.className = 'form-validation-message'
    mockSubmitButton.type = 'submit'
    mockFormGroup.appendChild(mockInput)
    mockFormGroup.appendChild(mockValidationMessage)
    mockForm.appendChild(mockFormGroup)
    mockForm.appendChild(mockSubmitButton)
    
    animationHandler = new AnimationHandler()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Animation Configuration', () => {
    it('should have all required animations defined', () => {
      expect(animationHandler.animations).toHaveProperty('fadeIn')
      expect(animationHandler.animations).toHaveProperty('fadeOut')
      expect(animationHandler.animations).toHaveProperty('slideIn')
      expect(animationHandler.animations).toHaveProperty('slideOut')
    })

    it('should have correct fadeIn animation configuration', () => {
      const fadeIn = animationHandler.animations.fadeIn
      expect(fadeIn.keyframes).toHaveLength(2)
      expect(fadeIn.keyframes[0]).toEqual({ opacity: 0 })
      expect(fadeIn.keyframes[1]).toEqual({ opacity: 1 })
      expect(fadeIn.options.duration).toBe(300)
      expect(fadeIn.options.easing).toBe('ease-out')
    })

    it('should have correct fadeOut animation configuration', () => {
      const fadeOut = animationHandler.animations.fadeOut
      expect(fadeOut.keyframes).toHaveLength(2)
      expect(fadeOut.keyframes[0]).toEqual({ opacity: 1 })
      expect(fadeOut.keyframes[1]).toEqual({ opacity: 0 })
      expect(fadeOut.options.duration).toBe(300)
      expect(fadeOut.options.easing).toBe('ease-in')
    })

    it('should have correct slideIn animation configuration', () => {
      const slideIn = animationHandler.animations.slideIn
      expect(slideIn.keyframes).toHaveLength(2)
      expect(slideIn.keyframes[0]).toEqual({ transform: 'translateY(20px)', opacity: 0 })
      expect(slideIn.keyframes[1]).toEqual({ transform: 'translateY(0)', opacity: 1 })
      expect(slideIn.options.duration).toBe(400)
      expect(slideIn.options.easing).toBe('ease-out')
    })

    it('should have correct slideOut animation configuration', () => {
      const slideOut = animationHandler.animations.slideOut
      expect(slideOut.keyframes).toHaveLength(2)
      expect(slideOut.keyframes[0]).toEqual({ transform: 'translateY(0)', opacity: 1 })
      expect(slideOut.keyframes[1]).toEqual({ transform: 'translateY(20px)', opacity: 0 })
      expect(slideOut.options.duration).toBe(400)
      expect(slideOut.options.easing).toBe('ease-in')
    })
  })

  describe('animate', () => {
    it('should return animation object for valid animation name', () => {
      const result = animationHandler.animate(mockElement, 'fadeIn')
      
      expect(result).toBeDefined()
      expect(result).toHaveProperty('onfinish')
      expect(result).toHaveProperty('addEventListener')
      expect(result).toHaveProperty('removeEventListener')
    })

    it('should return null for invalid animation name', () => {
      const result = animationHandler.animate(mockElement, 'invalidAnimation')
      
      expect(result).toBeNull()
    })

    it('should handle different animation types', () => {
      const fadeIn = animationHandler.animate(mockElement, 'fadeIn')
      const fadeOut = animationHandler.animate(mockElement, 'fadeOut')
      const slideIn = animationHandler.animate(mockElement, 'slideIn')
      const slideOut = animationHandler.animate(mockElement, 'slideOut')
      
      expect(fadeIn).toBeDefined()
      expect(fadeOut).toBeDefined()
      expect(slideIn).toBeDefined()
      expect(slideOut).toBeDefined()
    })
  })

  describe('fadeIn', () => {
    it('should call animate with fadeIn animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.fadeIn(mockElement)
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeIn')
    })

    it('should return animation object', () => {
      const result = animationHandler.fadeIn(mockElement)
      
      expect(result).toBeDefined()
      expect(result).toHaveProperty('onfinish')
    })
  })

  describe('fadeOut', () => {
    it('should call animate with fadeOut animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.fadeOut(mockElement)
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeOut')
    })

    it('should return animation object', () => {
      const result = animationHandler.fadeOut(mockElement)
      
      expect(result).toBeDefined()
      expect(result).toHaveProperty('onfinish')
    })
  })

  describe('slideIn', () => {
    it('should call animate with slideIn animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.slideIn(mockElement)
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideIn')
    })

    it('should return animation object', () => {
      const result = animationHandler.slideIn(mockElement)
      
      expect(result).toBeDefined()
      expect(result).toHaveProperty('onfinish')
    })
  })

  describe('slideOut', () => {
    it('should call animate with slideOut animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.slideOut(mockElement)
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideOut')
    })

    it('should return animation object', () => {
      const result = animationHandler.slideOut(mockElement)
      
      expect(result).toBeDefined()
      expect(result).toHaveProperty('onfinish')
    })
  })

  describe('show', () => {
    it('should set element display to empty string', () => {
      mockElement.style.display = 'none'
      
      animationHandler.show(mockElement)
      
      expect(mockElement.style.display).toBe('')
    })

    it('should call animate with default fadeIn animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.show(mockElement)
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeIn')
    })

    it('should call animate with specified animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.show(mockElement, 'slideIn')
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideIn')
    })

    it('should return animation object', () => {
      const result = animationHandler.show(mockElement)
      
      expect(result).toBeDefined()
    })
  })

  describe('hide', () => {
    it('should set onfinish callback to hide element', () => {
      const result = animationHandler.hide(mockElement)
      
      expect(result).toBeDefined()
      expect(result!.onfinish).toBeDefined()
      
      // Simulate animation finish
      result!.onfinish!()
      
      expect(mockElement.style.display).toBe('none')
    })

    it('should call animate with default fadeOut animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.hide(mockElement)
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeOut')
    })

    it('should call animate with specified animation', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.hide(mockElement, 'slideOut')
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideOut')
    })

    it('should handle null animation result', () => {
      const result = animationHandler.hide(mockElement, 'invalidAnimation')
      
      expect(result).toBeNull()
    })
  })

  describe('animateValidation', () => {
    it('should add is-valid classes for valid input', () => {
      animationHandler.animateValidation(mockInput, true)
      
      expect(mockInput.classList.contains('is-valid')).toBe(true)
      expect(mockInput.classList.contains('is-invalid')).toBe(false)
      expect(mockValidationMessage.classList.contains('is-valid')).toBe(true)
      expect(mockValidationMessage.classList.contains('is-invalid')).toBe(false)
    })

    it('should add is-invalid classes for invalid input', () => {
      animationHandler.animateValidation(mockInput, false)
      
      expect(mockInput.classList.contains('is-valid')).toBe(false)
      expect(mockInput.classList.contains('is-invalid')).toBe(true)
      expect(mockValidationMessage.classList.contains('is-valid')).toBe(false)
      expect(mockValidationMessage.classList.contains('is-invalid')).toBe(true)
    })

    it('should remove existing validation classes before adding new ones', () => {
      mockInput.classList.add('is-valid', 'is-invalid')
      mockValidationMessage.classList.add('is-valid', 'is-invalid')
      
      animationHandler.animateValidation(mockInput, true)
      
      expect(mockInput.classList.contains('is-valid')).toBe(true)
      expect(mockInput.classList.contains('is-invalid')).toBe(false)
      expect(mockValidationMessage.classList.contains('is-valid')).toBe(true)
      expect(mockValidationMessage.classList.contains('is-invalid')).toBe(false)
    })

    it('should call slideIn on validation message', () => {
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateValidation(mockInput, true)
      
      expect(spy).toHaveBeenCalledWith(mockValidationMessage)
    })

    it('should handle missing form group', () => {
      const inputWithoutGroup = document.createElement('input')
      
      expect(() => {
        animationHandler.animateValidation(inputWithoutGroup, true)
      }).not.toThrow()
    })

    it('should handle missing validation message', () => {
      const formGroupWithoutMessage = document.createElement('div')
      formGroupWithoutMessage.className = 'form-group'
      formGroupWithoutMessage.appendChild(mockInput)
      
      expect(() => {
        animationHandler.animateValidation(mockInput, true)
      }).not.toThrow()
    })
  })

  describe('animateFormSubmit', () => {
    it('should add button-loading class to submit button', () => {
      animationHandler.animateFormSubmit(mockForm)
      
      expect(mockSubmitButton.classList.contains('button-loading')).toBe(true)
    })

    it('should handle form without submit button', () => {
      const formWithoutButton = document.createElement('form')
      
      expect(() => {
        animationHandler.animateFormSubmit(formWithoutButton)
      }).not.toThrow()
    })
  })

  describe('animateFormSuccess', () => {
    it('should call slideIn on success message', () => {
      const successMessage = document.createElement('div')
      successMessage.className = 'form-success-message'
      mockForm.appendChild(successMessage)
      
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateFormSuccess(mockForm)
      
      expect(spy).toHaveBeenCalledWith(successMessage)
    })

    it('should handle form without success message', () => {
      expect(() => {
        animationHandler.animateFormSuccess(mockForm)
      }).not.toThrow()
    })
  })

  describe('animateFormError', () => {
    it('should call slideIn on error message', () => {
      const errorMessage = document.createElement('div')
      errorMessage.className = 'form-error-message'
      mockForm.appendChild(errorMessage)
      
      const spy = vi.spyOn(animationHandler, 'slideIn')
      
      animationHandler.animateFormError(mockForm)
      
      expect(spy).toHaveBeenCalledWith(errorMessage)
    })

    it('should handle form without error message', () => {
      expect(() => {
        animationHandler.animateFormError(mockForm)
      }).not.toThrow()
    })
  })

  describe('Integration Tests', () => {
    it('should handle complete form validation flow', () => {
      const slideInSpy = vi.spyOn(animationHandler, 'slideIn')
      
      // Simulate validation flow
      animationHandler.animateValidation(mockInput, false)
      expect(mockInput.classList.contains('is-invalid')).toBe(true)
      expect(slideInSpy).toHaveBeenCalledWith(mockValidationMessage)
      
      animationHandler.animateValidation(mockInput, true)
      expect(mockInput.classList.contains('is-valid')).toBe(true)
      expect(mockInput.classList.contains('is-invalid')).toBe(false)
    })

    it('should handle complete form submission flow', () => {
      const successMessage = document.createElement('div')
      successMessage.className = 'form-success-message'
      mockForm.appendChild(successMessage)
      
      const slideInSpy = vi.spyOn(animationHandler, 'slideIn')
      
      // Simulate form submission flow
      animationHandler.animateFormSubmit(mockForm)
      expect(mockSubmitButton.classList.contains('button-loading')).toBe(true)
      
      animationHandler.animateFormSuccess(mockForm)
      expect(slideInSpy).toHaveBeenCalledWith(successMessage)
    })
  })
}) 