/**
 * @file animationHelpers.ts
 * @description Shared animation utilities and mocks for testing
 * @tags animations, helpers, mocks, frontend, wireframe, vitest
 */

import { vi } from 'vitest'

// Mock AnimationHandler class
export class MockAnimationHandler {
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

// Helper function to create mock DOM elements
export const createMockAnimationElements = () => {
  const mockElement = document.createElement('div')
  const mockForm = document.createElement('form')
  const mockInput = document.createElement('input')
  const mockFormGroup = document.createElement('div')
  const mockValidationMessage = document.createElement('div')
  const mockSubmitButton = document.createElement('button')
  
  // Setup DOM structure
  mockFormGroup.className = 'form-group'
  mockValidationMessage.className = 'form-validation-message'
  mockSubmitButton.type = 'submit'
  mockFormGroup.appendChild(mockInput)
  mockFormGroup.appendChild(mockValidationMessage)
  mockForm.appendChild(mockFormGroup)
  mockForm.appendChild(mockSubmitButton)
  
  return {
    mockElement,
    mockForm,
    mockInput,
    mockFormGroup,
    mockValidationMessage,
    mockSubmitButton
  }
}

// Test data for animation tests
export const animationTestData = {
  validAnimations: ['fadeIn', 'fadeOut', 'slideIn', 'slideOut'],
  invalidAnimations: ['invalidAnimation', 'bounce', 'rotate', ''],
  animationConfigs: {
    fadeIn: {
      keyframes: [{ opacity: 0 }, { opacity: 1 }],
      duration: 300,
      easing: 'ease-out'
    },
    fadeOut: {
      keyframes: [{ opacity: 1 }, { opacity: 0 }],
      duration: 300,
      easing: 'ease-in'
    },
    slideIn: {
      keyframes: [
        { transform: 'translateY(20px)', opacity: 0 },
        { transform: 'translateY(0)', opacity: 1 }
      ],
      duration: 400,
      easing: 'ease-out'
    },
    slideOut: {
      keyframes: [
        { transform: 'translateY(0)', opacity: 1 },
        { transform: 'translateY(20px)', opacity: 0 }
      ],
      duration: 400,
      easing: 'ease-in'
    }
  }
} 