/**
 * @file validationHelpers.ts
 * @description Shared validation utilities and mocks for testing
 * @tags validation, helpers, mocks, frontend, wireframe, vitest
 */

import { vi } from 'vitest'

// Mock validation functions
export const mockValidation = {
  email: (value: string) => {
    // More comprehensive email regex that handles edge cases
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
    return emailRegex.test(value) && 
           !value.includes('..') && 
           !value.endsWith('.') &&
           !value.startsWith('.') &&
           !value.includes('@.') &&
           !value.includes('.@')
  },
  password: (value: string) => {
    if (!value || typeof value !== 'string') {
      return false
    }
    return value.length >= 8
  },
  required: (value: string) => {
    if (value == null || typeof value !== 'string') {
      return false
    }
    // Remove only standard whitespace (spaces, tabs, newlines)
    const stripped = value.replace(/[ \t\n\r\f\v]+/g, '')
    return stripped.length > 0
  },
  passwordMatch: (value: string, confirmValue: string) => {
    return value === confirmValue
  }
}

// Mock DOM form creation
export const createMockForm = () => {
  const form = document.createElement('form')
  form.innerHTML = `
    <div class="form-group">
      <input type="email" id="email" required />
    </div>
    <div class="form-group">
      <input type="password" id="password" required />
    </div>
    <div class="form-group">
      <input type="password" id="confirmPassword" required />
    </div>
    <div class="form-group">
      <input type="text" id="name" required />
    </div>
  `
  return form
}

// Mock FormValidator class
export class MockFormValidator {
  form: HTMLFormElement
  inputs: NodeListOf<Element>
  
  constructor(form: HTMLFormElement) {
    this.form = form
    this.inputs = form.querySelectorAll('input, select, textarea')
  }

  validateInput(input: HTMLInputElement) {
    const value = input.value
    let isValid = true
    let errorMessage = ''

    // Required validation
    if (input.hasAttribute('required')) {
      if (!mockValidation.required(value)) {
        isValid = false
        errorMessage = 'This field is required'
      }
    }

    // Email validation
    if (input.type === 'email' && value) {
      if (!mockValidation.email(value)) {
        isValid = false
        errorMessage = 'Please enter a valid email address'
      }
    }

    // Password validation
    if (input.type === 'password' && value) {
      if (!mockValidation.password(value)) {
        isValid = false
        errorMessage = 'Password must be at least 8 characters long'
      }
    }

    // Password match validation
    if (input.id === 'confirmPassword') {
      const password = this.form.querySelector('#password') as HTMLInputElement
      if (password && !mockValidation.passwordMatch(password.value, value)) {
        isValid = false
        errorMessage = 'Passwords do not match'
      }
    }

    this.updateInputState(input, isValid, errorMessage)
    return isValid
  }

  validateForm() {
    let isValid = true
    this.inputs.forEach((input: Element) => {
      if (input instanceof HTMLInputElement && !this.validateInput(input)) {
        isValid = false
      }
    })
    return isValid
  }

  updateInputState(input: HTMLInputElement, isValid: boolean, errorMessage: string) {
    const formGroup = input.closest('.form-group')
    if (!formGroup) return

    let validationMessage = formGroup.querySelector('.form-validation-message') as HTMLElement
    if (!validationMessage) {
      validationMessage = this.createValidationMessage(formGroup)
    }

    input.classList.remove('is-valid', 'is-invalid')
    validationMessage.classList.remove('is-valid', 'is-invalid')

    if (isValid) {
      input.classList.add('is-valid')
      validationMessage.classList.add('is-valid')
      validationMessage.textContent = 'Input is valid'
    } else {
      input.classList.add('is-invalid')
      validationMessage.classList.add('is-invalid')
      validationMessage.textContent = errorMessage
    }
  }

  createValidationMessage(formGroup: Element) {
    const message = document.createElement('div')
    message.className = 'form-validation-message'
    formGroup.appendChild(message)
    return message
  }
}

// Test data for validation tests
export const testData = {
  validEmails: [
    'test@example.com',
    'user.name@domain.co.uk',
    'test+tag@example.org',
    'user123@test-domain.com'
  ],
  invalidEmails: [
    'invalid-email',
    'test@',
    '@example.com',
    'test@example',
    '',
    'test space@example.com',
    'test@example..com',
    'test..test@example.com',
    'test@example.com.'
  ],
  validPasswords: [
    'password123',
    '12345678',
    'abcdefgh',
    'p@ssw0rd',
    'pass word',
    'p@ss w0rd',
    '!@#$%^&*()'
  ],
  invalidPasswords: [
    '1234567',
    'pass',
    ''
  ],
  validRequired: [
    'test',
    '  test  ',
    '123',
    '!@#$',
    '0',
    'false',
    'null'
  ],
  invalidRequired: [
    '',
    '   ',
    '\t\n'
  ]
} 