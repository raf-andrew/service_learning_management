/**
 * @file form-validator.test.ts
 * @description Form validator class tests for wireframe functionality
 * @tags validation, form-validator, frontend, wireframe, vitest, laravel-validation
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { createMockForm, MockFormValidator } from '../helpers/validationHelpers'

describe('FormValidator Class', () => {
  let form: HTMLFormElement
  let formValidator: MockFormValidator

  beforeEach(() => {
    setupTestEnvironment()
    form = createMockForm()
    document.body.appendChild(form)
    formValidator = new MockFormValidator(form)
  })

  afterEach(() => {
    if (form.parentNode) {
      document.body.removeChild(form)
    }
  })

  describe('Constructor and Setup', () => {
    it('should initialize with form element', () => {
      expect(formValidator.form).toBe(form)
      expect(formValidator.inputs).toBeDefined()
      expect(formValidator.inputs.length).toBe(4) // email, password, confirmPassword, name
    })

    it('should find all form inputs', () => {
      const inputIds = Array.from(formValidator.inputs).map(input => (input as HTMLInputElement).id)
      expect(inputIds).toContain('email')
      expect(inputIds).toContain('password')
      expect(inputIds).toContain('confirmPassword')
      expect(inputIds).toContain('name')
    })
  })

  describe('validateInput', () => {
    describe('Required Field Validation', () => {
      it('should validate required fields', () => {
        const nameInput = form.querySelector('#name') as HTMLInputElement
        nameInput.value = ''
        
        expect(formValidator.validateInput(nameInput)).toBe(false)
        expect(nameInput.classList.contains('is-invalid')).toBe(true)
        
        nameInput.value = 'John Doe'
        expect(formValidator.validateInput(nameInput)).toBe(true)
        expect(nameInput.classList.contains('is-valid')).toBe(true)
      })

      it('should handle whitespace-only values as invalid', () => {
        const nameInput = form.querySelector('#name') as HTMLInputElement
        nameInput.value = '   '
        
        expect(formValidator.validateInput(nameInput)).toBe(false)
        expect(nameInput.classList.contains('is-invalid')).toBe(true)
      })
    })

    describe('Email Field Validation', () => {
      it('should validate email fields', () => {
        const emailInput = form.querySelector('#email') as HTMLInputElement
        
        emailInput.value = 'invalid-email'
        expect(formValidator.validateInput(emailInput)).toBe(false)
        expect(emailInput.classList.contains('is-invalid')).toBe(true)
        
        emailInput.value = 'test@example.com'
        expect(formValidator.validateInput(emailInput)).toBe(true)
        expect(emailInput.classList.contains('is-valid')).toBe(true)
      })

      it('should handle empty email fields', () => {
        const emailInput = form.querySelector('#email') as HTMLInputElement
        emailInput.value = ''
        
        expect(formValidator.validateInput(emailInput)).toBe(false)
        expect(emailInput.classList.contains('is-invalid')).toBe(true)
      })

      it('should validate various email formats', () => {
        const emailInput = form.querySelector('#email') as HTMLInputElement
        
        const validEmails = [
          'user@example.com',
          'user.name@example.com',
          'user+tag@example.com',
          'user@subdomain.example.com'
        ]

        validEmails.forEach(email => {
          emailInput.value = email
          expect(formValidator.validateInput(emailInput)).toBe(true)
        })
      })
    })

    describe('Password Field Validation', () => {
      it('should validate password fields', () => {
        const passwordInput = form.querySelector('#password') as HTMLInputElement
        
        passwordInput.value = '123'
        expect(formValidator.validateInput(passwordInput)).toBe(false)
        expect(passwordInput.classList.contains('is-invalid')).toBe(true)
        
        passwordInput.value = 'password123'
        expect(formValidator.validateInput(passwordInput)).toBe(true)
        expect(passwordInput.classList.contains('is-valid')).toBe(true)
      })

      it('should handle empty password fields', () => {
        const passwordInput = form.querySelector('#password') as HTMLInputElement
        passwordInput.value = ''
        
        expect(formValidator.validateInput(passwordInput)).toBe(false)
        expect(passwordInput.classList.contains('is-invalid')).toBe(true)
      })

      it('should validate passwords with exactly 8 characters', () => {
        const passwordInput = form.querySelector('#password') as HTMLInputElement
        passwordInput.value = '12345678'
        
        expect(formValidator.validateInput(passwordInput)).toBe(true)
        expect(passwordInput.classList.contains('is-valid')).toBe(true)
      })
    })

    describe('Password Confirmation Validation', () => {
      it('should validate password confirmation', () => {
        const passwordInput = form.querySelector('#password') as HTMLInputElement
        const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
        
        passwordInput.value = 'password123'
        confirmInput.value = 'password124'
        expect(formValidator.validateInput(confirmInput)).toBe(false)
        expect(confirmInput.classList.contains('is-invalid')).toBe(true)
        
        confirmInput.value = 'password123'
        expect(formValidator.validateInput(confirmInput)).toBe(true)
        expect(confirmInput.classList.contains('is-valid')).toBe(true)
      })

      it('should handle case-sensitive password matching', () => {
        const passwordInput = form.querySelector('#password') as HTMLInputElement
        const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
        
        passwordInput.value = 'Password123'
        confirmInput.value = 'password123'
        expect(formValidator.validateInput(confirmInput)).toBe(false)
      })

      it('should handle empty password confirmation', () => {
        const passwordInput = form.querySelector('#password') as HTMLInputElement
        const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
        
        passwordInput.value = 'password123'
        confirmInput.value = ''
        expect(formValidator.validateInput(confirmInput)).toBe(false)
      })
    })
  })

  describe('validateForm', () => {
    it('should validate all form fields', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      const emailInput = form.querySelector('#email') as HTMLInputElement
      const passwordInput = form.querySelector('#password') as HTMLInputElement
      const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
      
      // Set all valid values
      nameInput.value = 'John Doe'
      emailInput.value = 'john@example.com'
      passwordInput.value = 'password123'
      confirmInput.value = 'password123'
      
      expect(formValidator.validateForm()).toBe(true)
    })

    it('should return false when any field is invalid', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      const emailInput = form.querySelector('#email') as HTMLInputElement
      const passwordInput = form.querySelector('#password') as HTMLInputElement
      const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
      
      // Set invalid values
      nameInput.value = 'John Doe'
      emailInput.value = 'invalid-email'
      passwordInput.value = 'password123'
      confirmInput.value = 'password123'
      
      expect(formValidator.validateForm()).toBe(false)
    })

    it('should handle multiple invalid fields', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      const emailInput = form.querySelector('#email') as HTMLInputElement
      const passwordInput = form.querySelector('#password') as HTMLInputElement
      const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
      
      // Set multiple invalid values
      nameInput.value = ''
      emailInput.value = 'invalid-email'
      passwordInput.value = '123'
      confirmInput.value = 'password123'
      
      expect(formValidator.validateForm()).toBe(false)
    })
  })

  describe('updateInputState', () => {
    it('should add validation classes and messages for invalid state', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      const formGroup = nameInput.closest('.form-group')
      
      formValidator.updateInputState(nameInput, false, 'This field is required')
      
      expect(nameInput.classList.contains('is-invalid')).toBe(true)
      expect(nameInput.classList.contains('is-valid')).toBe(false)
      
      const validationMessage = formGroup?.querySelector('.form-validation-message')
      expect(validationMessage?.classList.contains('is-invalid')).toBe(true)
      expect(validationMessage?.textContent).toBe('This field is required')
    })

    it('should add validation classes and messages for valid state', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      const formGroup = nameInput.closest('.form-group')
      
      formValidator.updateInputState(nameInput, true, '')
      
      expect(nameInput.classList.contains('is-valid')).toBe(true)
      expect(nameInput.classList.contains('is-invalid')).toBe(false)
      
      const validationMessage = formGroup?.querySelector('.form-validation-message')
      expect(validationMessage?.classList.contains('is-valid')).toBe(true)
      expect(validationMessage?.textContent).toBe('Input is valid')
    })

    it('should remove existing validation classes before adding new ones', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      
      // First set to invalid
      formValidator.updateInputState(nameInput, false, 'Invalid')
      expect(nameInput.classList.contains('is-invalid')).toBe(true)
      expect(nameInput.classList.contains('is-valid')).toBe(false)
      
      // Then set to valid
      formValidator.updateInputState(nameInput, true, '')
      expect(nameInput.classList.contains('is-valid')).toBe(true)
      expect(nameInput.classList.contains('is-invalid')).toBe(false)
    })

    it('should handle missing form group gracefully', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      // Remove the form group
      const formGroup = nameInput.closest('.form-group')
      if (formGroup) {
        formGroup.remove()
      }
      
      // Should not throw error
      expect(() => {
        formValidator.updateInputState(nameInput, true, '')
      }).not.toThrow()
    })
  })

  describe('createValidationMessage', () => {
    it('should create validation message element', () => {
      const formGroup = form.querySelector('.form-group') as Element
      const message = formValidator.createValidationMessage(formGroup)
      
      expect(message.tagName).toBe('DIV')
      expect(message.className).toBe('form-validation-message')
      expect(formGroup.contains(message)).toBe(true)
    })

    it('should append message to form group', () => {
      const formGroup = form.querySelector('.form-group') as Element
      const initialChildCount = formGroup.children.length
      
      formValidator.createValidationMessage(formGroup)
      
      expect(formGroup.children.length).toBe(initialChildCount + 1)
    })
  })

  describe('Integration Tests', () => {
    it('should handle complete form validation flow', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      const emailInput = form.querySelector('#email') as HTMLInputElement
      const passwordInput = form.querySelector('#password') as HTMLInputElement
      const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
      
      // Start with invalid form
      nameInput.value = ''
      emailInput.value = 'invalid'
      passwordInput.value = '123'
      confirmInput.value = '456'
      
      expect(formValidator.validateForm()).toBe(false)
      
      // Fix all fields
      nameInput.value = 'John Doe'
      emailInput.value = 'john@example.com'
      passwordInput.value = 'password123'
      confirmInput.value = 'password123'
      
      expect(formValidator.validateForm()).toBe(true)
    })

    it('should maintain validation state across multiple validations', () => {
      const nameInput = form.querySelector('#name') as HTMLInputElement
      
      // First validation - invalid
      nameInput.value = ''
      formValidator.validateInput(nameInput)
      expect(nameInput.classList.contains('is-invalid')).toBe(true)
      
      // Second validation - valid
      nameInput.value = 'John Doe'
      formValidator.validateInput(nameInput)
      expect(nameInput.classList.contains('is-valid')).toBe(true)
      expect(nameInput.classList.contains('is-invalid')).toBe(false)
    })
  })
}) 